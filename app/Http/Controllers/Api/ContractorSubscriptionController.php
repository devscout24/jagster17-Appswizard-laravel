<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MembershipPlanResource;
use App\Models\BillingHistory;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use App\Traits\ResolvesContractorPlan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ContractorSubscriptionController extends Controller
{
    use ApiResponseTrait, ResolvesContractorPlan;

    /**
     * Subscription: current plan, plan picker and billing history (Node 3241-1786).
     */
    public function show(): JsonResponse
    {
        $user = auth('api')->user();
        $subscription = $this->currentSubscription($user);
        $currentPlanId = $subscription?->plan_id;

        $plans = SubscriptionPlan::active()->ordered()->get()->map(function (SubscriptionPlan $plan) use ($currentPlanId) {
            return (new MembershipPlanResource($plan))->resolve() + [
                'is_current'         => $plan->id === $currentPlanId,
                'annual_display'     => '$' . number_format($this->annualPrice($plan), 0),
                'annual_savings_pct' => 20,
            ];
        });

        $history = BillingHistory::whereIn('subscription_id', Subscription::where('business_id', $user->id)->pluck('id'))
            ->latest('billed_at')
            ->take(24)
            ->get()
            ->map(fn (BillingHistory $bill) => [
                'id'             => $bill->id,
                'date'           => $bill->billed_at?->format('M j, Y'),
                'plan'           => $bill->plan_name,
                'amount'         => (float) $bill->amount,
                'amount_display' => '$' . number_format((float) $bill->amount, 2),
                'status'         => $bill->status,
                'receipt_url'    => url("/api/contractor/subscription/billing/{$bill->id}/receipt"),
            ]);

        return $this->success([
            'current'         => $subscription ? $this->subscriptionPayload($subscription) : null,
            'plans'           => $plans,
            'billing_history' => $history,
        ], 'Subscription retrieved successfully.');
    }

    /**
     * Choose Your Plan: switch plan and billing cycle. Paid plans are charged immediately
     * (simulated, same as onboarding checkout, until Stripe is connected).
     */
    public function change(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan_id'       => ['required', 'integer', Rule::exists('subscription_plans', 'id')->where('is_active', true)],
            'billing_cycle' => ['sometimes', Rule::in(['monthly', 'annual'])],
            'card_number'   => ['sometimes', 'nullable', 'string', 'regex:/^\d{12,19}$/'],
        ]);

        $user = auth('api')->user();
        $plan = SubscriptionPlan::findOrFail($validated['plan_id']);
        $cycle = $validated['billing_cycle'] ?? 'monthly';
        $current = $this->currentSubscription($user);

        if ($current && $current->plan_id === $plan->id && $current->billing_cycle === $cycle && $current->status === 'active') {
            return $this->error("You are already on the {$plan->name} plan.", 422);
        }

        $isFree = (float) $plan->monthly_price === 0.0;
        $amount = $cycle === 'annual' ? $this->annualPrice($plan) : (float) $plan->monthly_price;
        $last4 = ! empty($validated['card_number'])
            ? substr($validated['card_number'], -4)
            : ($current?->payment_method_last4 ?? '4242');

        $subscription = DB::transaction(function () use ($user, $plan, $cycle, $isFree, $amount, $last4) {
            Subscription::where('business_id', $user->id)
                ->whereIn('status', ['active', 'past_due'])
                ->update(['status' => 'cancelled']);

            $subscription = Subscription::create([
                'business_id'          => $user->id,
                'plan_id'              => $plan->id,
                'billing_cycle'        => $cycle,
                'status'               => 'active',
                'payment_method_last4' => $isFree ? null : $last4,
                'renews_at'            => $cycle === 'annual' ? now()->addYear() : now()->addMonth(),
            ]);

            if (! $isFree) {
                BillingHistory::create([
                    'subscription_id' => $subscription->id,
                    'plan_name'       => $plan->name,
                    'amount'          => $amount,
                    'status'          => 'paid',
                    'billed_at'       => now(),
                ]);
            }

            $user->businessProfile?->update(['is_elite' => $plan->slug === 'elite']);

            return $subscription;
        });

        return $this->success(
            $this->subscriptionPayload($subscription->load('plan')),
            "Your membership is now {$plan->name}."
        );
    }

    /**
     * Cancel the paid plan; the contractor drops back to the free plan.
     */
    public function cancel(): JsonResponse
    {
        $user = auth('api')->user();
        $current = $this->currentSubscription($user);

        if (! $current || (float) $current->plan->monthly_price === 0.0) {
            return $this->error('You do not have a paid membership to cancel.', 422);
        }

        $free = SubscriptionPlan::where('slug', 'free')->first();

        DB::transaction(function () use ($user, $current, $free) {
            $current->update(['status' => 'cancelled']);

            if ($free) {
                Subscription::create([
                    'business_id'   => $user->id,
                    'plan_id'       => $free->id,
                    'billing_cycle' => 'monthly',
                    'status'        => 'active',
                    'renews_at'     => now()->addMonth(),
                ]);
            }

            $user->businessProfile?->update(['is_elite' => false]);
        });

        return $this->success(
            ($sub = $this->currentSubscription($user)) ? $this->subscriptionPayload($sub) : null,
            'Your paid membership has been cancelled.'
        );
    }

    /**
     * Download a billing history receipt as PDF.
     */
    public function receipt(int $id): Response|JsonResponse
    {
        $user = auth('api')->user();
        $bill = BillingHistory::whereIn('subscription_id', Subscription::where('business_id', $user->id)->pluck('id'))
            ->with('subscription')
            ->find($id);

        if (! $bill) {
            return $this->notFound('Receipt not found.');
        }

        return Pdf::loadView('pdf.subscription-receipt', [
            'bill'    => $bill,
            'user'    => $user,
            'profile' => $user->businessProfile,
        ])->download('receipt-' . $bill->billed_at->format('Y-m-d') . '.pdf');
    }

    private function currentSubscription(User $user): ?Subscription
    {
        return Subscription::where('business_id', $user->id)
            ->whereIn('status', ['active', 'past_due'])
            ->with('plan')
            ->latest()
            ->first();
    }

    private function subscriptionPayload(Subscription $subscription): array
    {
        $plan = $subscription->plan;
        $price = $subscription->billing_cycle === 'annual' ? $this->annualPrice($plan) : (float) $plan->monthly_price;
        $period = $subscription->billing_cycle === 'annual' ? 'year' : 'month';

        $summary = '$' . number_format($price, 0) . "/{$period}";
        if ((float) $plan->monthly_price > 0) {
            $summary .= ' · Renews ' . $subscription->renews_at?->format('M j, Y');
            if ($subscription->payment_method_last4) {
                $summary .= ' · Visa ending ' . $subscription->payment_method_last4;
            }
        }

        return [
            'id'                   => $subscription->id,
            'plan'                 => ['id' => $plan->id, 'name' => $plan->name, 'slug' => $plan->slug],
            'status'               => $subscription->status,
            'title'                => "{$plan->slug} Plan — " . ucfirst(str_replace('_', ' ', $subscription->status)),
            'billing_cycle'        => $subscription->billing_cycle,
            'price'                => $price,
            'renews_at'            => $subscription->renews_at?->format('M j, Y'),
            'payment_method_last4' => $subscription->payment_method_last4,
            'summary'              => $summary,
        ];
    }

    /**
     * Annual price: stored annual_price, else 12 months less the advertised 20% saving.
     */
    private function annualPrice(SubscriptionPlan $plan): float
    {
        return $plan->annual_price !== null
            ? (float) $plan->annual_price
            : round((float) $plan->monthly_price * 12 * 0.8, 2);
    }
}
