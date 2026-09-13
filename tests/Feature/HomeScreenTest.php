<?php

namespace Tests\Feature;

use App\Models\BusinessProfile;
use App\Models\Category;
use App\Models\CustomerProfile;
use App\Models\Project;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeScreenTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_retrieve_home_screen_data(): void
    {
        // Setup contractor & profile
        $contractor = User::create([
            'name' => 'Marcus Rivera',
            'email' => 'marcus@ironridge.test',
            'password' => 'secret123',
            'phone' => '+15551234567',
            'avatar' => 'avatars/marcus.jpg',
            'status' => 'active',
        ]);

        BusinessProfile::create([
            'user_id' => $contractor->id,
            'business_name' => 'Precision HVAC Solutions',
            'city' => 'Phoenix',
            'state' => 'AZ',
            'member_since' => '2021-04-10',
            'is_elite' => true,
            'is_veteran_owned' => true,
            'is_id_verified' => true,
            'avg_rating' => 4.95,
            'review_count' => 64,
            'bio' => 'Top-rated HVAC specialist in Phoenix with 15+ years experience.',
            'website_url' => 'https://precisionhvac.test',
        ]);

        // Setup customer & review
        $customer = User::create([
            'name' => 'Sarah Jenkins',
            'email' => 'sarah@test.com',
            'password' => 'secret123',
            'status' => 'active',
        ]);

        CustomerProfile::create([
            'user_id' => $customer->id,
            'city' => 'Phoenix',
            'state' => 'AZ',
        ]);

        $category = Category::create([
            'name' => 'Blue Collar Services',
            'description' => 'Skilled trades & construction professionals',
            'icon' => 'hammer-wrench',
            'type' => 'service',
        ]);

        Service::create([
            'business_id' => $contractor->id,
            'category_id' => $category->id,
            'name' => 'AC Installation & Repair',
            'description' => 'Fast residential & commercial AC repair',
            'pricing_type' => 'fixed',
            'price' => 350.00,
            'unit' => '/unit',
            'status' => 'active',
        ]);

        $project = Project::create([
            'business_id' => $contractor->id,
            'customer_id' => $customer->id,
            'title' => 'Full HVAC Replacement',
            'status' => 'completed',
        ]);

        Review::create([
            'business_id' => $contractor->id,
            'customer_id' => $customer->id,
            'project_id' => $project->id,
            'rating' => 5,
            'comment' => 'Exceptional service and quick turnaround!',
        ]);

        $response = $this->getJson('/api/home');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'hero_spotlight' => [
                        'id',
                        'business_name',
                        'specialty',
                        'location',
                        'avg_rating',
                        'review_count',
                        'is_elite',
                        'badge_label',
                    ],
                    'metrics' => [
                        'verified_pros_count',
                        'verified_pros_display',
                        'jobs_completed_count',
                        'jobs_completed_display',
                        'cities_served_count',
                        'cities_served_display',
                        'customer_satisfaction_rate',
                    ],
                    'categories' => [
                        '*' => [
                            'id',
                            'name',
                            'description',
                            'icon',
                            'type',
                            'services_count',
                        ],
                    ],
                    'testimonials' => [
                        '*' => [
                            'id',
                            'rating',
                            'comment',
                            'author_name',
                            'author_location',
                            'project_title',
                        ],
                    ],
                    'value_propositions' => [
                        '*' => [
                            'key',
                            'title',
                            'description',
                        ],
                    ],
                    'how_it_works' => [
                        '*' => [
                            'step',
                            'title',
                            'description',
                        ],
                    ],
                ],
                'code',
            ]);

        $this->assertTrue($response->json('status'));
        $this->assertEquals('Precision HVAC Solutions', $response->json('data.hero_spotlight.business_name'));
        $this->assertEquals('Blue Collar Services', $response->json('data.categories.0.name'));
        $this->assertEquals(1, $response->json('data.categories.0.services_count'));
    }

    public function test_can_search_from_home_screen(): void
    {
        $contractor = User::create([
            'name' => 'Precision HVAC',
            'email' => 'hvac@test.com',
            'password' => 'secret123',
            'status' => 'active',
        ]);

        BusinessProfile::create([
            'user_id' => $contractor->id,
            'business_name' => 'Precision HVAC Solutions',
            'city' => 'Phoenix',
            'state' => 'AZ',
            'member_since' => '2021-01-01',
            'avg_rating' => 4.9,
            'review_count' => 10,
        ]);

        $category = Category::create([
            'name' => 'HVAC Services',
            'type' => 'service',
        ]);

        Service::create([
            'business_id' => $contractor->id,
            'category_id' => $category->id,
            'name' => 'Emergency Heating Repair',
            'description' => '24/7 heating troubleshooting',
            'pricing_type' => 'hourly',
            'price' => 85.00,
            'unit' => '/hr',
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/home/search?service=Heating&zip_code=Phoenix');

        $response->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.items.0.service_name', 'Emergency Heating Repair')
            ->assertJsonPath('data.items.0.business.business_name', 'Precision HVAC Solutions');
    }
}
