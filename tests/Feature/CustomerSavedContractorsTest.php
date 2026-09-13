<?php

namespace Tests\Feature;

use App\Models\BusinessProfile;
use App\Models\Category;
use App\Models\CustomerProfile;
use App\Models\SavedContractor;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerSavedContractorsTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;
    private User $contractor;

    protected function setUp(): void
    {
        parent::setUp();

        // Customer
        $this->customer = User::factory()->create([
            'name' => 'Jane Customer',
            'email' => 'jane@example.com',
        ]);
        CustomerProfile::create([
            'user_id' => $this->customer->id,
            'city' => 'Phoenix',
            'state' => 'AZ',
        ]);

        // Category & Service
        $category = Category::create([
            'name' => 'Roofing & Solar',
            'slug' => 'roofing-solar',
            'description' => 'Roofing and solar services',
            'type' => 'service',
        ]);

        // Contractor
        $this->contractor = User::factory()->create([
            'name' => 'RoofGuard Pro Contractor',
            'email' => 'roofguard@example.com',
            'avatar' => 'https://example.com/avatar.jpg',
        ]);
        BusinessProfile::create([
            'user_id' => $this->contractor->id,
            'business_name' => 'RoofGuard Pro',
            'city' => 'Phoenix',
            'state' => 'AZ',
            'hourly_rate' => 100.00,
            'is_elite' => true,
            'is_id_verified' => true,
            'is_veteran_owned' => true,
            'member_since' => '2021-01-01',
            'avg_rating' => 4.9,
            'review_count' => 256,
        ]);
        Service::create([
            'business_id' => $this->contractor->id,
            'category_id' => $category->id,
            'name' => 'Roof Repair & Inspection',
            'pricing_type' => 'hourly',
            'price' => 100.00,
            'status' => 'active',
        ]);
    }

    public function test_customer_can_retrieve_saved_contractors_list(): void
    {
        SavedContractor::create([
            'user_id' => $this->customer->id,
            'business_id' => $this->contractor->id,
            'notes' => 'Preferred roofer',
        ]);

        $response = $this->actingAs($this->customer, 'api')
            ->getJson('/api/customer/saved-contractors');

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'code' => 200,
            ])
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'header' => [
                        'title',
                        'subtitle',
                        'total_saved',
                    ],
                    'items' => [
                        '*' => [
                            'id',
                            'contractor_id',
                            'business_name',
                            'avatar',
                            'category',
                            'hourly_rate',
                            'rate_display',
                            'badges' => [
                                'is_elite',
                                'is_id_verified',
                                'is_veteran_owned',
                            ],
                            'location',
                            'city',
                            'state',
                            'rating',
                            'review_count',
                            'services',
                            'actions' => [
                                'request_quote',
                                'view_profile',
                                'remove',
                            ],
                        ],
                    ],
                    'pagination',
                ],
            ]);

        $this->assertEquals(1, $response->json('data.header.total_saved'));
        $this->assertEquals('RoofGuard Pro', $response->json('data.items.0.business_name'));
        $this->assertEquals('$100', $response->json('data.items.0.rate_display'));
    }

    public function test_customer_can_toggle_bookmark_save_and_unsave(): void
    {
        // 1. Save contractor
        $saveResponse = $this->actingAs($this->customer, 'api')
            ->postJson("/api/customer/saved-contractors/{$this->contractor->id}/toggle", [
                'notes' => 'Great reviews for solar',
            ]);

        $saveResponse->assertStatus(200)
            ->assertJson([
                'status' => true,
                'data' => [
                    'contractor_id' => $this->contractor->id,
                    'is_saved' => true,
                ],
            ]);

        $this->assertDatabaseHas('saved_contractors', [
            'user_id' => $this->customer->id,
            'business_id' => $this->contractor->id,
        ]);

        // 2. Toggle off (unsave)
        $unsaveResponse = $this->actingAs($this->customer, 'api')
            ->postJson("/api/customer/saved-contractors/{$this->contractor->id}/toggle");

        $unsaveResponse->assertStatus(200)
            ->assertJson([
                'status' => true,
                'data' => [
                    'contractor_id' => $this->contractor->id,
                    'is_saved' => false,
                ],
            ]);

        $this->assertDatabaseMissing('saved_contractors', [
            'user_id' => $this->customer->id,
            'business_id' => $this->contractor->id,
        ]);
    }

    public function test_customer_can_remove_saved_contractor(): void
    {
        SavedContractor::create([
            'user_id' => $this->customer->id,
            'business_id' => $this->contractor->id,
        ]);

        $response = $this->actingAs($this->customer, 'api')
            ->deleteJson("/api/customer/saved-contractors/{$this->contractor->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'data' => [
                    'contractor_id' => $this->contractor->id,
                    'is_saved' => false,
                ],
            ]);

        $this->assertDatabaseMissing('saved_contractors', [
            'user_id' => $this->customer->id,
            'business_id' => $this->contractor->id,
        ]);
    }
}
