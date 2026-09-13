<?php

namespace Tests\Feature;

use App\Models\BusinessProfile;
use App\Models\Category;
use App\Models\CustomerProfile;
use App\Models\Product;
use App\Models\Project;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractorProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_retrieve_contractor_full_profile(): void
    {
        $category = Category::create([
            'name' => 'Blue Collar Services',
            'description' => 'Skilled trades & construction professionals',
            'icon' => 'hammer-wrench',
            'type' => 'service',
        ]);

        $contractor = User::create([
            'name' => 'Marcus Rivera',
            'email' => 'marcus@ironridge.test',
            'password' => 'secret123',
            'phone' => '(555) 234-5678',
            'avatar' => 'https://images.test/marcus.jpg',
            'status' => 'active',
        ]);

        BusinessProfile::create([
            'user_id' => $contractor->id,
            'business_name' => 'Iron Ridge Construction Group',
            'city' => 'Austin',
            'state' => 'TX',
            'zip_code' => '78701',
            'cover_photo' => 'https://images.test/cover.jpg',
            'years_experience' => 15,
            'member_since' => '2011-03-15',
            'is_elite' => true,
            'is_veteran_owned' => true,
            'is_id_verified' => true,
            'is_available_today' => true,
            'is_license_verified' => true,
            'license_number' => 'TX-LIC-98421',
            'business_hours' => 'Mon-Sat 7am-7pm',
            'languages' => ['English', 'Spanish'],
            'service_areas' => ['Downtown', 'North Austin', 'Round Rock', 'Westlake'],
            'gallery_images' => [
                'https://images.test/job1.jpg',
                'https://images.test/job2.jpg',
                'https://images.test/job3.jpg',
            ],
            'video_urls' => [
                [
                    'title' => 'Kitchen Renovation Tour',
                    'thumbnail_url' => 'https://images.test/v1.jpg',
                    'video_url' => 'https://video.test/v1.mp4',
                ],
            ],
            'hourly_rate' => 95.00,
            'avg_rating' => 4.95,
            'review_count' => 127,
            'bio' => 'Iron Ridge is an elite, veteran-owned full-service contracting company in Austin.',
            'website_url' => 'https://ironridge.test',
        ]);

        // Services
        Service::create([
            'business_id' => $contractor->id,
            'category_id' => $category->id,
            'name' => 'Custom Cabinetry & Carpentry',
            'description' => 'Precision woodwork and custom kitchen cabinets.',
            'pricing_type' => 'hourly',
            'price' => 95.00,
            'unit' => '/hr',
            'status' => 'active',
        ]);

        // Products / Packages
        Product::create([
            'business_id' => $contractor->id,
            'category_id' => $category->id,
            'name' => 'Premium Full Kitchen Remodel Package',
            'description' => 'Turnkey design, demolition, and installation.',
            'price' => 12500.00,
            'unit' => '/package',
            'is_elite_tier' => true,
            'features' => ['3D Renderings', 'Permits Included', '5-Year Warranty'],
            'status' => 'active',
        ]);

        // Reviews
        $customer = User::create([
            'name' => 'Sarah Jenkins',
            'email' => 'sarah@test.com',
            'password' => 'secret123',
            'avatar' => 'https://images.test/sarah.jpg',
            'status' => 'active',
        ]);

        CustomerProfile::create([
            'user_id' => $customer->id,
            'city' => 'Austin',
            'state' => 'TX',
        ]);

        $project = Project::create([
            'business_id' => $contractor->id,
            'customer_id' => $customer->id,
            'title' => 'Modern Kitchen Upgrade',
            'status' => 'completed',
        ]);

        Review::create([
            'business_id' => $contractor->id,
            'customer_id' => $customer->id,
            'project_id' => $project->id,
            'rating' => 5,
            'comment' => 'Marcus and his crew did an incredible job. Clean, fast, and on budget.',
        ]);

        $response = $this->getJson('/api/contractors/'.$contractor->id);

        $response->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'business_name',
                    'avatar',
                    'cover_photo',
                    'rating',
                    'review_count',
                    'years_experience',
                    'years_experience_display',
                    'hourly_rate',
                    'hourly_rate_display',
                    'location' => [
                        'city',
                        'state',
                        'zip_code',
                        'display',
                    ],
                    'website_url',
                    'badges' => [
                        'is_elite',
                        'is_id_verified',
                        'is_veteran_owned',
                        'is_available_today',
                    ],
                    'overview' => [
                        'bio',
                        'business_license',
                        'id_me_verified',
                        'business_hours',
                        'languages',
                    ],
                    'gallery_images',
                    'videos',
                    'service_areas',
                    'services' => [
                        '*' => [
                            'id',
                            'name',
                            'description',
                            'pricing_type',
                            'price',
                            'unit',
                            'price_display',
                            'location',
                            'experience_display',
                            'category',
                        ],
                    ],
                    'products' => [
                        '*' => [
                            'id',
                            'name',
                            'description',
                            'price',
                            'unit',
                            'price_display',
                            'is_elite_tier',
                            'features',
                            'category',
                        ],
                    ],
                    'reviews' => [
                        '*' => [
                            'id',
                            'rating',
                            'comment',
                            'author_name',
                            'author_avatar',
                            'project_title',
                            'created_at',
                        ],
                    ],
                    'quick_contact' => [
                        'headline',
                        'location',
                        'phone',
                        'email',
                        'quote_action' => [
                            'label',
                            'url',
                        ],
                    ],
                    'breadcrumbs',
                ],
                'code',
            ]);

        $this->assertEquals('Iron Ridge Construction Group', $response->json('data.business_name'));
        $this->assertEquals(15, $response->json('data.years_experience'));
        $this->assertEquals('15 years experience', $response->json('data.years_experience_display'));
        $this->assertEquals(true, $response->json('data.badges.is_elite'));
        $this->assertEquals(1, count($response->json('data.services')));
        $this->assertEquals(1, count($response->json('data.products')));
        $this->assertEquals(1, count($response->json('data.reviews')));
        $this->assertEquals('Sarah Jenkins', $response->json('data.reviews.0.author_name'));
    }

    public function test_returns_404_for_non_existent_contractor_profile(): void
    {
        $response = $this->getJson('/api/contractors/9999');

        $response->assertStatus(404)
            ->assertJsonPath('status', false)
            ->assertJsonPath('message', 'Contractor not found');
    }
}
