<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_retrieve_category_directory(): void
    {
        $cat1 = Category::create([
            'name' => 'Blue Collar Services',
            'description' => 'Skilled trades & construction professionals',
            'icon' => 'hammer-wrench',
            'type' => 'service',
        ]);

        $cat2 = Category::create([
            'name' => 'White Collar Services',
            'description' => 'Professional & business services',
            'icon' => 'briefcase-chart',
            'type' => 'service',
        ]);

        $contractor = User::create([
            'name' => 'Marcus Rivera',
            'email' => 'marcus@ironridge.test',
            'password' => 'secret123',
            'status' => 'active',
        ]);

        Service::create([
            'business_id' => $contractor->id,
            'category_id' => $cat1->id,
            'name' => 'Roof Replacement',
            'pricing_type' => 'fixed',
            'price' => 8000,
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/categories');

        $response->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'hero' => [
                        'headline',
                        'subtitle',
                    ],
                    'popular_tags',
                    'metrics' => [
                        'verified_pros_display',
                        'jobs_completed_display',
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
                            'services_count_label',
                        ],
                    ],
                    'cta_banner' => [
                        'headline',
                        'subtitle',
                        'primary_cta',
                        'secondary_cta',
                    ],
                ],
                'code',
            ]);

        $this->assertEquals(2, count($response->json('data.categories')));
        $this->assertEquals('Blue Collar Services', $response->json('data.categories.0.name'));
        $this->assertEquals(1, $response->json('data.categories.0.services_count'));
        $this->assertEquals('1+ services available', $response->json('data.categories.0.services_count_label'));
    }

    public function test_can_retrieve_single_category(): void
    {
        $cat = Category::create([
            'name' => 'Gold Collar Services',
            'description' => 'Healthcare & advanced professionals',
            'icon' => 'stethoscope-star',
            'type' => 'service',
        ]);

        $response = $this->getJson('/api/categories/'.$cat->id);

        $response->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.name', 'Gold Collar Services')
            ->assertJsonPath('data.description', 'Healthcare & advanced professionals');
    }

    public function test_returns_404_for_non_existent_category(): void
    {
        $response = $this->getJson('/api/categories/9999');

        $response->assertStatus(404)
            ->assertJsonPath('status', false)
            ->assertJsonPath('message', 'Category not found');
    }
}
