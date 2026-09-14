<?php

namespace Tests\Feature;

use App\Models\BusinessProfile;
use App\Models\CustomerProfile;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ContractorReviewsAndProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('business', 'api');
        Role::findOrCreate('customer', 'api');
        $this->seed(SubscriptionPlanSeeder::class);
    }

    public function test_can_retrieve_reviews_with_breakdown_and_filter(): void
    {
        $contractor = User::factory()->create(['name' => 'Summit Remodeling']);
        $contractor->assignRole('business');

        BusinessProfile::create([
            'user_id'       => $contractor->id,
            'business_name' => 'Summit Remodeling LLC',
            'member_since'  => now()->subYears(3),
            'avg_rating'    => 4.8,
            'review_count'  => 3,
        ]);

        $customer1 = User::factory()->create(['name' => 'Sarah Connor']);
        $customer2 = User::factory()->create(['name' => 'John Wick']);
        CustomerProfile::create(['user_id' => $customer1->id, 'city' => 'Austin', 'state' => 'TX']);
        CustomerProfile::create(['user_id' => $customer2->id, 'city' => 'Dallas', 'state' => 'TX']);

        $project = Project::create([
            'business_id' => $contractor->id,
            'customer_id' => $customer1->id,
            'title'       => 'Kitchen Renovation',
            'status'      => 'completed',
        ]);

        // Review 1 (5 star with reply)
        Review::create([
            'business_id'      => $contractor->id,
            'customer_id'      => $customer1->id,
            'project_id'       => $project->id,
            'rating'           => 5,
            'comment'          => 'Incredible work on our kitchen! On time and under budget.',
            'contractor_reply' => 'Thank you Sarah, it was a pleasure working with you!',
            'replied_at'       => now(),
            'is_featured'      => true,
        ]);

        // Review 2 (4 star unanswered)
        Review::create([
            'business_id' => $contractor->id,
            'customer_id' => $customer2->id,
            'rating'      => 4,
            'comment'     => 'Great job overall, minor delay on tile delivery.',
        ]);

        $token = auth('api')->login($contractor);
        $authHeader = ['Authorization' => 'Bearer ' . $token];

        // 1. Get All Reviews (Node 3228-4501)
        $response = $this->withHeaders($authHeader)
            ->getJson('/api/contractor/reviews');

        $response->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.rating_summary.total_reviews', 2)
            ->assertJsonPath('data.rating_summary.average_rating', 4.5)
            ->assertJsonPath('data.rating_summary.unanswered', 1)
            ->assertJsonPath('data.rating_summary.with_replies', 1)
            ->assertJsonPath('data.rating_summary.featured_count', 1);

        $this->assertCount(2, $response->json('data.items'));

        // 2. Filter by 5-star
        $fiveStarRes = $this->withHeaders($authHeader)
            ->getJson('/api/contractor/reviews?rating=5');

        $fiveStarRes->assertStatus(200);
        $this->assertCount(1, $fiveStarRes->json('data.items'));
        $this->assertEquals(5, $fiveStarRes->json('data.items.0.rating'));

        // 3. Filter by unanswered
        $unansweredRes = $this->withHeaders($authHeader)
            ->getJson('/api/contractor/reviews?filter=unanswered');

        $unansweredRes->assertStatus(200);
        $this->assertCount(1, $unansweredRes->json('data.items'));
        $this->assertEquals(4, $unansweredRes->json('data.items.0.rating'));
        $this->assertFalse($unansweredRes->json('data.items.0.has_replied'));

        // 4. Search filter
        $searchRes = $this->withHeaders($authHeader)
            ->getJson('/api/contractor/reviews?search=kitchen');

        $searchRes->assertStatus(200);
        $this->assertCount(1, $searchRes->json('data.items'));
        $this->assertEquals('Sarah Connor', $searchRes->json('data.items.0.customer.name'));
    }

    public function test_can_reply_to_review_and_notify_customer(): void
    {
        $contractor = User::factory()->create(['name' => 'Summit Remodeling']);
        $contractor->assignRole('business');

        BusinessProfile::create([
            'user_id'       => $contractor->id,
            'business_name' => 'Summit Remodeling LLC',
        ]);

        $customer = User::factory()->create(['name' => 'Emily Blunt']);
        $token = auth('api')->login($contractor);
        $authHeader = ['Authorization' => 'Bearer ' . $token];

        $review = Review::create([
            'business_id' => $contractor->id,
            'customer_id' => $customer->id,
            'rating'      => 5,
            'comment'     => 'Super fast and clean work. Highly recommended!',
        ]);

        // Submit Reply (Node 3370-11)
        $replyRes = $this->withHeaders($authHeader)
            ->postJson("/api/contractor/reviews/{$review->id}/reply", [
                'contractor_reply' => 'Thanks Emily! We loved helping transform your space.',
            ]);

        $replyRes->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.contractor_reply', 'Thanks Emily! We loved helping transform your space.')
            ->assertJsonPath('data.has_replied', true);

        $this->assertNotNull($replyRes->json('data.replied_at'));

        // Verify customer received notification
        $this->assertDatabaseHas('notifications', [
            'user_id' => $customer->id,
            'type'    => 'review_reply',
        ]);
    }

    public function test_can_toggle_feature_review(): void
    {
        $contractor = User::factory()->create(['name' => 'Summit Remodeling']);
        $contractor->assignRole('business');

        $token = auth('api')->login($contractor);
        $authHeader = ['Authorization' => 'Bearer ' . $token];

        $review = Review::create([
            'business_id' => $contractor->id,
            'customer_id' => User::factory()->create()->id,
            'rating'      => 5,
            'comment'     => 'Top notch craftsmanship!',
            'is_featured' => false,
        ]);

        $toggleRes = $this->withHeaders($authHeader)
            ->postJson("/api/contractor/reviews/{$review->id}/toggle-feature");

        $toggleRes->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.is_featured', true);
    }

    public function test_can_get_and_update_contractor_profile_settings(): void
    {
        $contractor = User::factory()->create([
            'name'  => 'Marcus Vance',
            'email' => 'marcus@summit.test',
            'phone' => '512-555-0199',
        ]);
        $contractor->assignRole('business');

        BusinessProfile::create([
            'user_id'          => $contractor->id,
            'business_name'    => 'Summit Builders',
            'owner_name'       => 'Marcus Vance',
            'business_type'    => 'General Contractor',
            'phone_number'     => '512-555-0199',
            'city'             => 'Austin',
            'state'            => 'TX',
            'zip_code'         => '78701',
            'bio'              => 'Over 15 years building custom homes and commercial renovations.',
            'hourly_rate'      => 85.00,
            'years_experience' => 15,
            'is_elite'         => true,
            'is_veteran_owned' => true,
            'is_id_verified'   => true,
            'service_radius'   => 40,
        ]);

        $token = auth('api')->login($contractor);
        $authHeader = ['Authorization' => 'Bearer ' . $token];

        // 1. Get Profile Details (Node 3363-11)
        $profileRes = $this->withHeaders($authHeader)
            ->getJson('/api/contractor/profile');

        $profileRes->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.business_info.business_name', 'Summit Builders')
            ->assertJsonPath('data.business_info.city', 'Austin')
            ->assertJsonPath('data.professional_details.years_experience', 15)
            ->assertJsonPath('data.verifications_and_badges.is_elite', true)
            ->assertJsonPath('data.verifications_and_badges.is_veteran_owned', true);

        // 2. Update Profile Settings (Node 3363-11)
        $updateRes = $this->withHeaders($authHeader)
            ->putJson('/api/contractor/profile', [
                'business_name'   => 'Summit Builders & Remodeling',
                'bio'             => 'Updated premier home remodeling and commercial construction experts.',
                'hourly_rate'     => 95.00,
                'license_number'  => 'TX-GC-98234',
                'service_radius'  => 50,
                'website_url'     => 'https://summitbuilders.test',
                'languages'       => ['English', 'Spanish'],
                'service_areas'   => ['Austin', 'Round Rock', 'Cedar Park', 'Westlake'],
            ]);

        $updateRes->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.business_info.business_name', 'Summit Builders & Remodeling')
            ->assertJsonPath('data.professional_details.hourly_rate', 95)
            ->assertJsonPath('data.professional_details.license_number', 'TX-GC-98234')
            ->assertJsonPath('data.professional_details.service_radius', 50)
            ->assertJsonPath('data.social_links.website_url', 'https://summitbuilders.test');

        $this->assertDatabaseHas('business_profiles', [
            'user_id'        => $contractor->id,
            'business_name'  => 'Summit Builders & Remodeling',
            'license_number' => 'TX-GC-98234',
        ]);
    }
}
