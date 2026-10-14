<?php

namespace App\Traits;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;

trait ResolvesContractorPlan
{
    /**
     * The contractor's currently active plan, falling back to the free plan.
     */
    protected function activePlan(User $user): ?SubscriptionPlan
    {
        $subscription = Subscription::where('business_id', $user->id)
            ->where('status', 'active')
            ->with('plan')
            ->latest()
            ->first();

        return $subscription?->plan ?? SubscriptionPlan::where('slug', 'free')->first();
    }

    /**
     * Listing limit for a resource on the contractor's plan. Null means unlimited.
     * Services, gallery and videos come from the plan record (managed in admin);
     * products come from config/valorhub.php as plans have no product column.
     */
    protected function planLimit(User $user, string $resource): ?int
    {
        $plan = $this->activePlan($user);

        return match ($resource) {
            'services' => $plan?->service_limit,
            'gallery'  => $plan?->gallery_limit,
            'videos'   => $plan?->video_limit,
            'products' => config('valorhub.product_limits.' . ($plan?->slug ?? 'free'), 0),
            default    => 0,
        };
    }

    /**
     * Whether the contractor's plan unlocks a premium feature (banner, custom_domain, ...).
     */
    protected function planAllows(User $user, string $feature): bool
    {
        $slug = $this->activePlan($user)?->slug ?? 'free';

        return in_array($slug, config("valorhub.feature_plans.{$feature}", []), true);
    }

    /**
     * Usage summary used by the "Elite Plan: Unlimited services. Currently using 6." banners.
     */
    protected function planUsage(User $user, string $resource, int $used): array
    {
        $plan = $this->activePlan($user);
        $limit = $this->planLimit($user, $resource);

        return [
            'plan'          => $plan?->name,
            'plan_slug'     => $plan?->slug,
            'limit'         => $limit,
            'is_unlimited'  => $limit === null,
            'used'          => $used,
            'remaining'     => $limit === null ? null : max(0, $limit - $used),
            'can_add_more'  => $limit === null || $used < $limit,
        ];
    }
}
