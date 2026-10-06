<?php

namespace App\Http\Controllers\Api\Contractor;

use App\Http\Controllers\Controller;
use App\Http\Resources\MembershipPlanResource;
use App\Models\BillingHistory;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class SubscriptionController extends Controller
{
    use ApiResponseTrait;

    public function show(): JsonResponse
    {
        $user = auth('api')->user();
        $subscription = $user->subscription()->with('plan')->first();

        $history = BillingHistory::whereIn('subscription_id', Subscription::where('business_id', $user->id)->pluck('id'))
            ->latest('billed_at')
            ->get()
            ->map(fn (BillingHistory $entry) => [
                'id' => $entry->id,
                'date' => $entry->billed_at?->format('M j, Y'),
                'plan' => $entry->plan_name,
                'amount' => (float) $entry->amount,
                'amount_display' => '$' . number_format((float) $entry->amount, 2),
                'status' => $entry->status,
            ]);

        return $this->success([
            'current' => $subscription ? [
                'plan' => new MembershipPlanResource($subscription->plan),
                'billing_cycle' => $subscription->billing_cycle,
                'status' => $subscription->status,
                'price' => (float) ($subscription->billing_cycle === 'annual'
                    ? ($subscription->plan->annual_price ?? $subscription->plan->monthly_price * 12)
                    : $subscription->plan->monthly_price),
                'renews_at' => $subscription->renews_at?->format('M j, Y'),
                'card_last4' => $subscription->payment_method_last4,
            ] : null,
            'plans' => MembershipPlanResource::collection(SubscriptionPlan::active()->ordered()->get()),
            'billing_history' => $history,
        ], 'Subscription retrieved successfully.');
    }

    /**
     * Switch plan or billing cycle. Payment collection happens via the payment provider later.
     */
    public function change(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'integer', Rule::exists('subscription_plans', 'id')->where('is_active', true)],
            'billing_cycle' => ['required', Rule::in(['monthly', 'annual'])],
        ]);

        $user = auth('api')->user();
        $plan = SubscriptionPlan::findOrFail($validated['plan_id']);
        $current = $user->subscription()->first();

        if ($current && $current->plan_id === $plan->id && $current->billing_cycle === $validated['billing_cycle']) {
            return $this->error('You are already on this plan.', 422);
        }

        DB::transaction(function () use ($user, $plan, $current, $validated) {
            $current?->update(['status' => 'cancelled']);

            Subscription::create([
                'business_id' => $user->id,
                'plan_id' => $plan->id,
                'billing_cycle' => $validated['billing_cycle'],
                'status' => 'active',
                'payment_method_last4' => $current?->payment_method_last4,
                'renews_at' => $validated['billing_cycle'] === 'annual' ? now()->addYear() : now()->addMonth(),
            ]);

            $user->businessProfile?->update(['is_elite' => $plan->slug === 'elite']);
        });

        return $this->success(null, "Your plan has been changed to {$plan->name} ({$validated['billing_cycle']}).");
    }

    /**
     * Plain-text receipt until the payment provider supplies PDF receipts.
     */
    public function receipt(int $id): Response
    {
        $user = auth('api')->user();
        $entry = BillingHistory::whereIn('subscription_id', Subscription::where('business_id', $user->id)->pluck('id'))
            ->findOrFail($id);

        $body = implode("\n", [
            'ValorHub — Payment Receipt',
            '',
            'Business: ' . ($user->businessProfile?->business_name ?? $user->name),
            'Date: ' . $entry->billed_at?->format('M j, Y'),
            "Plan: {$entry->plan_name}",
            'Amount: $' . number_format((float) $entry->amount, 2),
            'Status: ' . ucfirst($entry->status),
        ]);

        return response($body, 200, [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => 'attachment; filename="valorhub-receipt-' . $entry->id . '.txt"',
        ]);
    }
}
