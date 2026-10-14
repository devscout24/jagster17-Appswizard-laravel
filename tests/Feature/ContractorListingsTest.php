<?php

namespace Tests\Feature;

use App\Models\BusinessProfile;
use App\Models\Category;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContractorListingsTest extends TestCase
{
    use RefreshDatabase;

    private User $contractor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SubscriptionPlanSeeder::class);

        $this->contractor = User::factory()->create();
        $this->contractor->assignRole('business');
        BusinessProfile::create(['user_id' => $this->contractor->id, 'business_name' => 'Iron Ridge Contracting', 'member_since' => now()]);
    }

    private function onPlan(string $slug): void
    {
        Subscription::create([
            'business_id'   => $this->contractor->id,
            'plan_id'       => \App\Models\SubscriptionPlan::where('slug', $slug)->value('id'),
            'billing_cycle' => 'monthly',
            'status'        => 'active',
            'renews_at'     => now()->addMonth(),
        ]);
    }

    public function test_free_plan_service_limit_is_enforced(): void
    {
        $this->onPlan('free');
        $category = Category::create(['name' => 'Roofing', 'type' => 'service']);

        foreach (range(1, 3) as $i) {
            $this->actingAs($this->contractor, 'api')->postJson('/api/contractor/services', [
                'category_id' => $category->id, 'name' => "Service {$i}", 'pricing_type' => 'hourly', 'price' => 100,
            ])->assertStatus(201);
        }

        $this->actingAs($this->contractor, 'api')->postJson('/api/contractor/services', [
            'category_id' => $category->id, 'name' => 'One too many', 'pricing_type' => 'custom_quote',
        ])->assertStatus(403);

        $this->actingAs($this->contractor, 'api')->getJson('/api/contractor/services')
            ->assertOk()
            ->assertJsonPath('data.usage.limit', 3)
            ->assertJsonPath('data.usage.can_add_more', false)
            ->assertJsonPath('data.items.0.price_display', '$100/hr');
    }

    public function test_contractor_cannot_edit_another_contractors_service(): void
    {
        $this->onPlan('elite');
        $category = Category::create(['name' => 'Roofing', 'type' => 'service']);

        $id = $this->actingAs($this->contractor, 'api')->postJson('/api/contractor/services', [
            'category_id' => $category->id, 'name' => 'Roof Repair', 'pricing_type' => 'custom_quote',
        ])->json('data.id');

        $other = User::factory()->create();
        $other->assignRole('business');

        $this->actingAs($other, 'api')->putJson("/api/contractor/services/{$id}", ['name' => 'Hijacked'])->assertStatus(404);
    }

    public function test_gallery_upload_and_banner_is_elite_only(): void
    {
        Storage::fake('public');
        $this->onPlan('pro');

        $this->actingAs($this->contractor, 'api')->post('/api/contractor/gallery', [
            'images'   => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
            'category' => 'before_after',
        ], ['Accept' => 'application/json'])->assertStatus(201)->assertJsonCount(2, 'data');

        $this->assertCount(2, $this->contractor->businessProfile->fresh()->gallery_images);

        $this->actingAs($this->contractor, 'api')->post('/api/contractor/banner', [
            'banner_image' => UploadedFile::fake()->image('banner.jpg', 1440, 340),
        ], ['Accept' => 'application/json'])->assertStatus(403);
    }
}
