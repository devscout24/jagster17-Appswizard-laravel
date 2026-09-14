<?php

namespace Tests\Feature;

use App\Models\BusinessProfile;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MembershipPlanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SubscriptionPlanSeeder::class);
    }

    public function test_can_retrieve_all_membership_plans(): void
    {
        $response = $this->getJson('/api/membership-plans');

        $response->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'header' => [
                        'title',
                        'subtitle',
                        'id_me_required',
                        'id_me_verification_url',
                    ],
                    'plans' => [
                        '*' => [
                            'id',
                            'name',
                            'slug',
                            'tagline',
                            'badge',
                            'is_popular',
                            'monthly_price',
                            'annual_price',
                            'display_price',
                            'billing_period',
                            'is_free',
                            'features',
                            'limits' => [
                                'service_limit',
                                'gallery_limit',
                                'video_limit',
                                'is_unlimited',
                            ],
                            'button_text',
                            'button_variant',
                        ],
                    ],
                ],
                'code',
            ]);

        $this->assertCount(3, $response->json('data.plans'));
        $this->assertEquals('Free Verified Membership', $response->json('data.plans.0.name'));
        $this->assertEquals('MOST POPULAR', $response->json('data.plans.0.badge'));
        $this->assertTrue($response->json('data.plans.0.is_popular'));
        $this->assertTrue($response->json('data.plans.0.is_free'));
        $this->assertEquals('$0', $response->json('data.plans.0.display_price'));

        $this->assertEquals('Pro Verified Membership', $response->json('data.plans.1.name'));
        $this->assertEquals('$29', $response->json('data.plans.1.display_price'));
        $this->assertFalse($response->json('data.plans.1.is_free'));

        $this->assertEquals('Elite Verified Membership', $response->json('data.plans.2.name'));
        $this->assertEquals('$49', $response->json('data.plans.2.display_price'));
        $this->assertTrue($response->json('data.plans.2.limits.is_unlimited'));
    }

    public function test_can_retrieve_single_membership_plan(): void
    {
        $plan = SubscriptionPlan::where('slug', 'pro')->first();

        $response = $this->getJson('/api/membership-plans/' . $plan->id);

        $response->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.name', 'Pro Verified Membership')
            ->assertJsonPath('data.slug', 'pro')
            ->assertJsonPath('data.display_price', '$29');

        $this->assertEquals(29.0, $response->json('data.monthly_price'));
    }

    public function test_returns_404_for_non_existent_plan(): void
    {
        $response = $this->getJson('/api/membership-plans/9999');

        $response->assertStatus(404)
            ->assertJsonPath('status', false)
            ->assertJsonPath('message', 'Membership plan not found');
    }

    public function test_unauthenticated_user_cannot_select_plan(): void
    {
        $plan = SubscriptionPlan::where('slug', 'free')->first();

        $response = $this->postJson('/api/membership-plans/select', [
            'plan_id' => $plan->id,
        ]);

        $response->assertStatus(401);
    }

    public function test_validation_error_when_selecting_invalid_plan(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/membership-plans/select', [
                'plan_id' => 99999,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['plan_id']);
    }

    public function test_authenticated_contractor_can_select_free_plan(): void
    {
        Role::findOrCreate('business', 'api');

        $user = User::factory()->create([
            'name' => 'John Smith',
            'email' => 'john@roofing.test',
        ]);
        $user->assignRole('business');

        $profile = BusinessProfile::create([
            'user_id'       => $user->id,
            'business_name' => 'Eagle Roofing LLC',
            'member_since'  => now(),
        ]);

        $token = auth('api')->login($user);
        $plan = SubscriptionPlan::where('slug', 'free')->first();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/membership-plans/select', [
                'plan_id'       => $plan->id,
                'billing_cycle' => 'monthly',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.is_free', true)
            ->assertJsonPath('data.requires_payment', false)
            ->assertJsonPath('data.next_step', 'id_me_verification');

        $this->assertDatabaseHas('subscriptions', [
            'business_id' => $user->id,
            'plan_id'     => $plan->id,
            'status'      => 'active',
        ]);
    }

    public function test_authenticated_contractor_can_select_elite_plan(): void
    {
        Role::findOrCreate('business', 'api');

        $user = User::factory()->create();
        $user->assignRole('business');

        $profile = BusinessProfile::create([
            'user_id'       => $user->id,
            'business_name' => 'Premier Pro LLC',
            'member_since'  => now(),
            'is_elite'      => false,
        ]);

        $token = auth('api')->login($user);
        $plan = SubscriptionPlan::where('slug', 'elite')->first();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/membership-plans/select', [
                'plan_id'       => $plan->id,
                'billing_cycle' => 'annual',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.is_free', false)
            ->assertJsonPath('data.requires_payment', true)
            ->assertJsonPath('data.next_step', 'payment_checkout');

        $this->assertDatabaseHas('subscriptions', [
            'business_id'   => $user->id,
            'plan_id'       => $plan->id,
            'billing_cycle' => 'annual',
            'status'        => 'active',
        ]);

        $profile->refresh();
        $this->assertTrue($profile->is_elite);
    }
}
