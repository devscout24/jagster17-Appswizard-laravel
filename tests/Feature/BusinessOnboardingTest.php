<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BusinessOnboardingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('business', 'api');
        $this->seed(SubscriptionPlanSeeder::class);
    }

    public function test_complete_free_onboarding_flow(): void
    {
        // 1. Account Creation (Step 1)
        $step1Res = $this->postJson('/api/business/onboarding/account', [
            'email'          => 'mike@veteranplumbing.test',
            'password'       => 'Secret123!',
            'password_confirmation' => 'Secret123!',
            'business_name'  => 'Mike Veteran Plumbing LLC',
            'owner_name'     => 'Mike Johnson',
            'phone_number'   => '(555) 234-5678',
            'business_type'  => 'LLC',
            'terms_accepted' => true,
        ]);

        $step1Res->assertStatus(201)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.user.email', 'mike@veteranplumbing.test')
            ->assertJsonPath('data.next_step', 'choose_membership');

        $token = $step1Res->json('data.token.access_token');
        $this->assertNotEmpty($token);

        $authHeader = ['Authorization' => 'Bearer ' . $token];

        // 2. Choose Membership (Free Plan)
        $freePlan = SubscriptionPlan::where('slug', 'free')->first();
        $planRes = $this->withHeaders($authHeader)
            ->postJson('/api/business/onboarding/plan', [
                'plan_id'       => $freePlan->id,
                'billing_cycle' => 'monthly',
            ]);

        $planRes->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.plan.is_free', true)
            ->assertJsonPath('data.requires_payment', false)
            ->assertJsonPath('data.next_step', 'id_me');

        // 3. ID.me Verification
        $idMeRes = $this->withHeaders($authHeader)
            ->postJson('/api/business/onboarding/id-me', [
                'is_veteran'      => true,
                'military_branch' => 'United States Marine Corps',
                'status'          => 'verified',
            ]);

        $idMeRes->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.is_id_verified', true)
            ->assertJsonPath('data.is_veteran_owned', true)
            ->assertJsonPath('data.badge', 'Verified Veteran Owned')
            ->assertJsonPath('data.next_step', 'business_info');

        // 4. Business Information
        $infoRes = $this->withHeaders($authHeader)
            ->postJson('/api/business/onboarding/business-info', [
                'city'           => 'San Antonio',
                'state'          => 'TX',
                'zip_code'       => '78201',
                'tax_id'         => 'XX-XXXXXXX',
                'service_radius' => 30,
            ]);

        $infoRes->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.location', 'San Antonio, TX 78201')
            ->assertJsonPath('data.next_step', 'profile');

        // 5. Business Profile
        $profileRes = $this->withHeaders($authHeader)
            ->postJson('/api/business/onboarding/profile', [
                'bio'              => 'USMC veteran owned residential plumbing.',
                'hourly_rate'      => 85.00,
                'years_experience' => 12,
            ]);

        $profileRes->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.next_step', 'services'); // Free flow skips social_links straight to services

        $this->assertEquals(85.0, $profileRes->json('data.hourly_rate'));

        // 6. Service Selection (2 services for Free plan)
        $category = Category::create([
            'name' => 'Plumbing Services',
            'type' => 'service',
        ]);

        $servicesRes = $this->withHeaders($authHeader)
            ->postJson('/api/business/onboarding/services', [
                'services' => [
                    [
                        'category_id'  => $category->id,
                        'name'         => 'Water Heater Replacement',
                        'pricing_type' => 'fixed',
                        'price'        => 1200,
                    ],
                    [
                        'category_id'  => $category->id,
                        'name'         => 'Drain Cleaning',
                        'pricing_type' => 'hourly',
                        'price'        => 95,
                    ],
                ],
            ]);

        $servicesRes->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.services_count', 2)
            ->assertJsonPath('data.next_step', 'review');

        // 7. Review & Summary
        $reviewRes = $this->withHeaders($authHeader)
            ->getJson('/api/business/onboarding/review');

        $reviewRes->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.membership.plan_name', 'Free Verified Membership')
            ->assertJsonPath('data.verification.badge', 'Verified Veteran Owned')
            ->assertJsonPath('data.business_info.city', 'San Antonio');

        // 8. Submit & Complete
        $submitRes = $this->withHeaders($authHeader)
            ->postJson('/api/business/onboarding/submit');

        $submitRes->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.account_status', 'active')
            ->assertJsonPath('data.onboarding_completed', true);
    }

    public function test_complete_pro_and_elite_paid_onboarding_flow(): void
    {
        // 1. Account Creation
        $step1Res = $this->postJson('/api/business/onboarding/account', [
            'email'          => 'sarah@eliteelectrics.test',
            'password'       => 'Password123!',
            'password_confirmation' => 'Password123!',
            'business_name'  => 'Elite Electricians LLC',
            'owner_name'     => 'Sarah Connor',
            'phone_number'   => '(555) 987-6543',
            'business_type'  => 'Corporation',
            'terms_accepted' => true,
        ]);

        $token = $step1Res->json('data.token.access_token');
        $authHeader = ['Authorization' => 'Bearer ' . $token];

        // 2. Select Elite Plan ($49/mo)
        $elitePlan = SubscriptionPlan::where('slug', 'elite')->first();
        $planRes = $this->withHeaders($authHeader)
            ->postJson('/api/business/onboarding/plan', [
                'plan_id'       => $elitePlan->id,
                'billing_cycle' => 'monthly',
            ]);

        $planRes->assertStatus(200)
            ->assertJsonPath('data.requires_payment', true)
            ->assertJsonPath('data.next_step', 'checkout');

        // 3. Checkout Payment
        $checkoutRes = $this->withHeaders($authHeader)
            ->postJson('/api/business/onboarding/checkout', [
                'card_number' => '4242424242424242',
                'exp_month'   => 12,
                'exp_year'    => 2028,
                'cvc'         => '123',
            ]);

        $checkoutRes->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.payment_status', 'succeeded')
            ->assertJsonPath('data.next_step', 'id_me');

        $this->assertDatabaseHas('billing_history', [
            'plan_name' => 'Elite Verified Membership',
            'status'    => 'paid',
        ]);

        // 4. ID.me Verification
        $this->withHeaders($authHeader)
            ->postJson('/api/business/onboarding/id-me', [
                'is_veteran' => false,
                'status'     => 'verified',
            ])->assertStatus(200)
            ->assertJsonPath('data.badge', 'ID Verified')
            ->assertJsonPath('data.next_step', 'business_info');

        // 5. Business Information
        $this->withHeaders($authHeader)
            ->postJson('/api/business/onboarding/business-info', [
                'city'           => 'Austin',
                'state'          => 'TX',
                'zip_code'       => '78701',
                'service_radius' => 50,
            ])->assertStatus(200);

        // 6. Business Profile (Gallery & Videos allowed for Elite)
        $profileRes = $this->withHeaders($authHeader)
            ->postJson('/api/business/onboarding/profile', [
                'bio'            => 'Master electricians with full commercial capabilities.',
                'hourly_rate'    => 120.00,
                'gallery_images' => ['https://img.test/1.jpg', 'https://img.test/2.jpg'],
                'video_urls'     => ['https://youtube.com/watch?v=123'],
            ]);

        $profileRes->assertStatus(200)
            ->assertJsonPath('data.gallery_count', 2)
            ->assertJsonPath('data.video_count', 1)
            ->assertJsonPath('data.next_step', 'social_links');

        // 7. Social Links (Elite / Pro)
        $socialRes = $this->withHeaders($authHeader)
            ->postJson('/api/business/onboarding/social-links', [
                'website_url'  => 'https://eliteelectrics.test',
                'linkedin_url' => 'https://linkedin.com/company/eliteelectrics',
                'facebook_url' => 'https://facebook.com/eliteelectrics',
            ]);

        $socialRes->assertStatus(200)
            ->assertJsonPath('data.social_links.website_url', 'https://eliteelectrics.test')
            ->assertJsonPath('data.next_step', 'services');

        // 8. Service Selection
        $category = Category::create([
            'name' => 'Electrical Services',
            'type' => 'service',
        ]);

        $this->withHeaders($authHeader)
            ->postJson('/api/business/onboarding/services', [
                'services' => [
                    [
                        'category_id'  => $category->id,
                        'name'         => 'Panel Upgrade 200A',
                        'pricing_type' => 'fixed',
                        'price'        => 2500,
                    ],
                ],
            ])->assertStatus(200)
            ->assertJsonPath('data.next_step', 'review');

        // 9. Review
        $reviewRes = $this->withHeaders($authHeader)
            ->getJson('/api/business/onboarding/review');

        $reviewRes->assertStatus(200)
            ->assertJsonPath('data.membership.plan_name', 'Elite Verified Membership')
            ->assertJsonPath('data.membership.is_elite', true);

        // 10. Submit
        $this->withHeaders($authHeader)
            ->postJson('/api/business/onboarding/submit')
            ->assertStatus(200)
            ->assertJsonPath('data.account_status', 'active');
    }

    public function test_free_tier_enforces_maximum_service_limit(): void
    {
        $step1Res = $this->postJson('/api/business/onboarding/account', [
            'email'          => 'dave@handyman.test',
            'password'       => 'Secret123!',
            'password_confirmation' => 'Secret123!',
            'business_name'  => 'Dave Handyman Services',
            'owner_name'     => 'Dave Miller',
            'phone_number'   => '(555) 111-2222',
            'business_type'  => 'Sole Proprietor',
            'terms_accepted' => true,
        ]);

        $token = $step1Res->json('data.token.access_token');
        $authHeader = ['Authorization' => 'Bearer ' . $token];

        $freePlan = SubscriptionPlan::where('slug', 'free')->first();
        $this->withHeaders($authHeader)
            ->postJson('/api/business/onboarding/plan', [
                'plan_id' => $freePlan->id,
            ]);

        $category = Category::create([
            'name' => 'General Maintenance',
            'type' => 'service',
        ]);

        // Attempt to submit 4 services when Free tier only allows 3
        $response = $this->withHeaders($authHeader)
            ->postJson('/api/business/onboarding/services', [
                'services' => [
                    ['category_id' => $category->id, 'name' => 'Job 1', 'pricing_type' => 'fixed', 'price' => 50],
                    ['category_id' => $category->id, 'name' => 'Job 2', 'pricing_type' => 'fixed', 'price' => 60],
                    ['category_id' => $category->id, 'name' => 'Job 3', 'pricing_type' => 'fixed', 'price' => 70],
                    ['category_id' => $category->id, 'name' => 'Job 4', 'pricing_type' => 'fixed', 'price' => 80],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('status', false)
            ->assertJsonPath('message', 'Your Free Verified Membership allows a maximum of 3 services. Please upgrade your plan or select up to 3 services.');
    }
}
