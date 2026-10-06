<?php

namespace Tests\Feature\Api;

use App\Models\BusinessProfile;
use App\Models\Category;
use App\Models\CustomerProfile;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Spatie\Permission\Models\Role;

trait ApiTestHelpers
{
    protected function makeCustomer(array $attributes = []): User
    {
        $user = User::factory()->create(array_merge(['status' => 'active'], $attributes));
        $user->assignRole(Role::findOrCreate('customer', 'api'));
        CustomerProfile::create(['user_id' => $user->id, 'city' => 'Austin', 'state' => 'TX']);

        return $user;
    }

    protected function makeContractor(string $plan = 'elite'): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole(Role::findOrCreate('business', 'api'));
        BusinessProfile::create([
            'user_id' => $user->id,
            'business_name' => 'Iron Ridge Contracting',
            'member_since' => now(),
            'onboarding_completed' => true,
            'is_elite' => $plan === 'elite',
        ]);

        $limits = ['free' => [3, 0, 0], 'pro' => [10, 20, 0], 'elite' => [null, null, 5]][$plan];
        $planModel = SubscriptionPlan::firstOrCreate(['slug' => $plan], [
            'name' => ucfirst($plan) . ' Verified Membership',
            'monthly_price' => ['free' => 0, 'pro' => 29, 'elite' => 49][$plan],
            'annual_price' => ['free' => 0, 'pro' => 290, 'elite' => 490][$plan],
            'features' => [],
            'service_limit' => $limits[0],
            'gallery_limit' => $limits[1],
            'video_limit' => $limits[2],
            'is_active' => true,
        ]);
        Subscription::create([
            'business_id' => $user->id,
            'plan_id' => $planModel->id,
            'billing_cycle' => 'monthly',
            'status' => 'active',
            'renews_at' => now()->addMonth(),
        ]);

        return $user;
    }

    protected function category(string $type = 'service'): Category
    {
        return Category::create(['name' => ucfirst($type) . ' Category', 'type' => $type]);
    }
}
