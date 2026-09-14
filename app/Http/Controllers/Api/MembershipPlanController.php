<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Membership\SelectMembershipPlanRequest;
use App\Http\Resources\MembershipPlanResource;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class MembershipPlanController extends Controller
{
    use ApiResponseTrait;

    /**
     * Display all available membership plans for the "Choose Your Membership" screen.
     */
    public function index(): JsonResponse
    {
        $plans = SubscriptionPlan::query()
            ->active()
            ->ordered()
            ->get();

        $pageData = [
            'header' => [
                'title'                  => 'Choose Your Membership',
                'subtitle'               => 'Choose a verified membership. ID.me verification is required before continuing.',
                'id_me_required'         => true,
                'id_me_verification_url' => 'https://api.id.me/oauth/checkpoint/valorhub',
            ],
            'plans' => MembershipPlanResource::collection($plans),
        ];

        return $this->success($pageData, 'Membership plans retrieved successfully');
    }

    /**
     * Display details for a specific membership plan.
     */
    public function show(int $id): JsonResponse
    {
        $plan = SubscriptionPlan::query()
            ->active()
            ->find($id);

        if (! $plan) {
            return $this->notFound('Membership plan not found');
        }

        return $this->success(new MembershipPlanResource($plan), 'Membership plan retrieved successfully');
    }

    /**
     * Handle membership plan selection / upgrade for the business contractor.
     */
    public function select(SelectMembershipPlanRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = auth('api')->user();

        if (! $user) {
            return $this->unauthorized('Authentication required to select a membership plan.');
        }

        $plan = SubscriptionPlan::query()
            ->active()
            ->find($validated['plan_id']);

        if (! $plan) {
            return $this->notFound('Selected membership plan is currently unavailable.');
        }

        $billingCycle = $validated['billing_cycle'] ?? 'monthly';

        $subscriptionData = DB::transaction(function () use ($user, $plan, $billingCycle) {
            $isFree = (float) $plan->monthly_price === 0.0;
            $renewsAt = $billingCycle === 'annual' ? now()->addYear() : now()->addMonth();

            // Cancel any previous active subscriptions
            Subscription::where('business_id', $user->id)
                ->where('status', 'active')
                ->update(['status' => 'cancelled']);

            $subscription = Subscription::create([
                'business_id'   => $user->id,
                'plan_id'       => $plan->id,
                'billing_cycle' => $billingCycle,
                'status'        => 'active',
                'renews_at'     => $renewsAt,
            ]);

            // Update business profile tier flag if elite
            if ($user->businessProfile) {
                $user->businessProfile->update([
                    'is_elite' => $plan->slug === 'elite',
                ]);
            }

            return [
                'subscription_id' => $subscription->id,
                'plan'            => new MembershipPlanResource($plan),
                'billing_cycle'   => $billingCycle,
                'status'          => $subscription->status,
                'is_free'         => $isFree,
                'requires_payment'=> ! $isFree,
                'next_step'       => $isFree ? 'id_me_verification' : 'payment_checkout',
            ];
        });

        $message = $subscriptionData['is_free']
            ? 'Free verified membership activated. Proceed to ID.me verification.'
            : 'Plan selected. Proceed to checkout.';

        return $this->success($subscriptionData, $message, 200);
    }
}
