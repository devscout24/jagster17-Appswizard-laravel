<?php

namespace Tests\Feature;

use App\Models\BusinessProfile;
use App\Models\Category;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractorListTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_retrieve_contractor_list_with_filters(): void
    {
        $category = Category::create([
            'name' => 'Blue Collar Services',
            'description' => 'Skilled trades & construction professionals',
            'icon' => 'hammer-wrench',
            'type' => 'service',
        ]);

        // Contractor 1: Veteran, Elite, Phoenix
        $c1 = User::create([
            'name' => 'Marcus Rivera',
            'email' => 'marcus@ironridge.test',
            'password' => 'secret123',
            'status' => 'active',
        ]);
        BusinessProfile::create([
            'user_id' => $c1->id,
            'business_name' => 'Iron Ridge Co.',
            'city' => 'Phoenix',
            'state' => 'AZ',
            'member_since' => '2021-01-01',
            'is_elite' => true,
            'is_veteran_owned' => true,
            'is_id_verified' => true,
            'is_available_today' => true,
            'hourly_rate' => 89.00,
            'avg_rating' => 4.9,
            'review_count' => 120,
        ]);
        Service::create([
            'business_id' => $c1->id,
            'category_id' => $category->id,
            'name' => 'AC Repair',
            'pricing_type' => 'hourly',
            'price' => 89.00,
            'status' => 'active',
        ]);
        Service::create([
            'business_id' => $c1->id,
            'category_id' => $category->id,
            'name' => 'Duct Cleaning',
            'pricing_type' => 'fixed',
            'price' => 200.00,
            'status' => 'active',
        ]);

        // Contractor 2: Non-veteran, Dallas
        $c2 = User::create([
            'name' => 'John Doe',
            'email' => 'john@test.com',
            'password' => 'secret123',
            'status' => 'active',
        ]);
        BusinessProfile::create([
            'user_id' => $c2->id,
            'business_name' => 'Apex Tech Services',
            'city' => 'Dallas',
            'state' => 'TX',
            'member_since' => '2023-01-01',
            'is_elite' => false,
            'is_veteran_owned' => false,
            'is_id_verified' => true,
            'is_available_today' => false,
            'hourly_rate' => 150.00,
            'avg_rating' => 4.2,
            'review_count' => 15,
        ]);
        Service::create([
            'business_id' => $c2->id,
            'category_id' => $category->id,
            'name' => 'Electrical Inspection',
            'pricing_type' => 'fixed',
            'price' => 150.00,
            'status' => 'active',
        ]);

        // 1. Fetch unfiltered contractor list
        $response = $this->getJson('/api/contractors?category_id='.$category->id);

        $response->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'hero' => [
                        'breadcrumbs',
                        'active_category' => [
                            'id',
                            'name',
                            'subtitle',
                        ],
                        'metrics' => [
                            'services_count_display',
                            'professionals_count_display',
                            'avg_rating_display',
                        ],
                    ],
                    'popular_tags',
                    'filter_options' => [
                        'categories',
                        'credentials',
                        'ratings',
                    ],
                    'applied_filters',
                    'items' => [
                        '*' => [
                            'id',
                            'business_name',
                            'avatar',
                            'category_title',
                            'hourly_rate',
                            'hourly_rate_display',
                            'location',
                            'rating',
                            'review_count',
                            'badges' => [
                                'is_elite',
                                'is_id_verified',
                                'is_veteran_owned',
                                'is_available_today',
                            ],
                            'specialties',
                            'member_since',
                            'actions' => [
                                'request_quote_url',
                                'profile_url',
                            ],
                        ],
                    ],
                    'pagination' => [
                        'current_page',
                        'last_page',
                        'per_page',
                        'total',
                    ],
                    'cta_banner',
                ],
                'code',
            ]);

        $this->assertEquals(2, $response->json('data.pagination.total'));
        $this->assertEquals('Iron Ridge Co.', $response->json('data.items.0.business_name'));
        $this->assertEquals('$89/hr', $response->json('data.items.0.hourly_rate_display'));
        $this->assertEquals(['AC Repair', 'Duct Cleaning'], $response->json('data.items.0.specialties'));

        // 2. Filter by veteran owned & location Phoenix
        $filteredResponse = $this->getJson('/api/contractors?is_veteran_owned=1&zip_code=Phoenix');
        $filteredResponse->assertStatus(200);
        $this->assertEquals(1, $filteredResponse->json('data.pagination.total'));
        $this->assertEquals('Iron Ridge Co.', $filteredResponse->json('data.items.0.business_name'));

        // 3. Filter by available today
        $availResponse = $this->getJson('/api/contractors?is_available_today=1');
        $availResponse->assertStatus(200);
        $this->assertEquals(1, $availResponse->json('data.pagination.total'));
    }

    public function test_can_view_single_contractor(): void
    {
        $contractor = User::create([
            'name' => 'Marcus Rivera',
            'email' => 'marcus@ironridge.test',
            'password' => 'secret123',
            'status' => 'active',
        ]);

        BusinessProfile::create([
            'user_id' => $contractor->id,
            'business_name' => 'Iron Ridge Co.',
            'city' => 'Phoenix',
            'state' => 'AZ',
            'member_since' => '2021-01-01',
            'hourly_rate' => 95.00,
            'avg_rating' => 4.9,
            'review_count' => 50,
        ]);

        $response = $this->getJson('/api/contractors/'.$contractor->id);

        $response->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.business_name', 'Iron Ridge Co.')
            ->assertJsonPath('data.hourly_rate_display', '$95/hr');
    }
}
