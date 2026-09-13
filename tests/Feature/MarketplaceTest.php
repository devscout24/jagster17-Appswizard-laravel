<?php

namespace Tests\Feature;

use App\Models\BusinessProfile;
use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_retrieve_marketplace_landing_screen(): void
    {
        $category = Category::create([
            'name' => 'Plumbing Supplies',
            'description' => 'Pipes, fittings, and tools',
            'icon' => 'wrench',
            'type' => 'product',
        ]);

        $seller = User::create([
            'name' => 'Marcus Rivera',
            'email' => 'marcus@ironridge.test',
            'password' => 'secret123',
            'status' => 'active',
        ]);

        BusinessProfile::create([
            'user_id' => $seller->id,
            'business_name' => 'Iron Ridge Supply Co.',
            'city' => 'Austin',
            'state' => 'TX',
            'member_since' => '2021-01-01',
            'is_elite' => true,
            'is_veteran_owned' => true,
            'is_id_verified' => true,
            'avg_rating' => 4.9,
            'review_count' => 120,
        ]);

        Product::create([
            'business_id' => $seller->id,
            'category_id' => $category->id,
            'name' => 'Copper Pipe Repair Kit',
            'description' => 'Complete copper pipe emergency repair setup.',
            'image' => 'https://images.test/pipe-kit.jpg',
            'price' => 45.00,
            'unit' => '/kit',
            'is_elite_tier' => true,
            'is_trending' => true,
            'in_stock' => true,
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/marketplace');

        $response->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'hero' => [
                        'headline',
                        'highlight',
                        'description',
                        'search_placeholder',
                    ],
                    'categories' => [
                        '*' => [
                            'id',
                            'name',
                            'description',
                            'icon',
                        ],
                    ],
                    'trending_products' => [
                        '*' => [
                            'id',
                            'name',
                            'image',
                            'price',
                            'price_display',
                            'is_elite_tier',
                            'is_trending',
                            'in_stock',
                            'category',
                            'seller',
                            'detail_url',
                        ],
                    ],
                    'seller_cta',
                    'breadcrumbs',
                ],
                'code',
            ]);

        $this->assertEquals('Copper Pipe Repair Kit', $response->json('data.trending_products.0.name'));
        $this->assertEquals('$45.00', $response->json('data.trending_products.0.price_display'));
    }

    public function test_can_browse_and_filter_products_catalog(): void
    {
        $cat1 = Category::create(['name' => 'Electrical Supplies', 'type' => 'product']);
        $cat2 = Category::create(['name' => 'General Hardware', 'type' => 'product']);

        $seller = User::create([
            'name' => 'Pro Supplier',
            'email' => 'supplier@test.com',
            'password' => 'secret123',
            'status' => 'active',
        ]);

        BusinessProfile::create([
            'user_id' => $seller->id,
            'business_name' => 'Pro Tech Store',
            'city' => 'Austin',
            'state' => 'TX',
            'member_since' => '2022-01-01',
            'is_elite' => true,
            'is_veteran_owned' => false,
            'is_id_verified' => true,
        ]);

        Product::create([
            'business_id' => $seller->id,
            'category_id' => $cat1->id,
            'name' => '12-Gauge Wire Spool 500ft',
            'price' => 120.00,
            'in_stock' => true,
            'status' => 'active',
        ]);

        Product::create([
            'business_id' => $seller->id,
            'category_id' => $cat2->id,
            'name' => 'Basic Screwdriver Set',
            'price' => 25.00,
            'in_stock' => false,
            'status' => 'active',
        ]);

        // 1. Fetch all products
        $response = $this->getJson('/api/marketplace/products');
        $response->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 2);

        // 2. Filter by price >= 50 and in_stock_only
        $filteredResponse = $this->getJson('/api/marketplace/products?min_price=50&in_stock_only=1');
        $filteredResponse->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.name', '12-Gauge Wire Spool 500ft');
    }

    public function test_can_view_product_details(): void
    {
        $category = Category::create(['name' => 'HVAC Equipment', 'type' => 'product']);

        $seller = User::create([
            'name' => 'Marcus Rivera',
            'email' => 'marcus@ironridge.test',
            'password' => 'secret123',
            'status' => 'active',
        ]);

        BusinessProfile::create([
            'user_id' => $seller->id,
            'business_name' => 'Iron Ridge Co.',
            'city' => 'Austin',
            'state' => 'TX',
            'member_since' => '2021-01-01',
            'is_elite' => true,
            'is_veteran_owned' => true,
            'is_id_verified' => true,
            'website_url' => 'https://ironridge.test',
            'avg_rating' => 4.95,
            'review_count' => 127,
        ]);

        $product = Product::create([
            'business_id' => $seller->id,
            'category_id' => $category->id,
            'name' => 'Smart Circuit Breaker Panel 200A',
            'description' => 'Advanced smart energy monitoring panel.',
            'image' => 'https://images.test/panel-main.jpg',
            'gallery_images' => [
                'https://images.test/panel-1.jpg',
                'https://images.test/panel-2.jpg',
            ],
            'price' => 850.00,
            'is_elite_tier' => true,
            'in_stock' => true,
            'stock_quantity' => 5,
            'sku' => 'SCB-200A-PRO',
            'warranty' => '3-year manufacturer warranty',
            'shipping_info' => 'Free 2-day delivery',
            'external_url' => 'https://ironridge.test/checkout/scb-200a',
            'rating' => 4.9,
            'review_count' => 42,
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/marketplace/products/'.$product->id);

        $response->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'name',
                    'description',
                    'price',
                    'price_display',
                    'is_elite_tier',
                    'rating',
                    'review_count',
                    'rating_display',
                    'image',
                    'gallery_images',
                    'in_stock',
                    'stock_quantity',
                    'sku',
                    'warranty',
                    'shipping_info',
                    'external_url',
                    'redirection_notice',
                    'specifications',
                    'seller' => [
                        'id',
                        'business_name',
                        'avatar',
                        'rating',
                        'review_count',
                        'is_elite',
                        'is_id_verified',
                        'is_veteran_owned',
                        'location',
                        'website_url',
                        'profile_url',
                    ],
                    'category',
                    'related_products',
                    'reviews',
                    'breadcrumbs',
                ],
                'code',
            ]);

        $this->assertEquals('Smart Circuit Breaker Panel 200A', $response->json('data.name'));
        $this->assertEquals('$850.00', $response->json('data.price_display'));
        $this->assertEquals('SCB-200A-PRO', $response->json('data.sku'));
        $this->assertEquals('Iron Ridge Co.', $response->json('data.seller.business_name'));
        $this->assertEquals(true, $response->json('data.seller.is_elite'));
    }

    public function test_returns_404_for_non_existent_product(): void
    {
        $response = $this->getJson('/api/marketplace/products/9999');

        $response->assertStatus(404)
            ->assertJsonPath('status', false)
            ->assertJsonPath('message', 'Product not found');
    }
}
