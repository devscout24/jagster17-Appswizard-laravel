<?php

namespace Tests\Feature\Api;

use App\Models\Banner;
use App\Models\BillingHistory;
use App\Models\BusinessDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContractorToolsTest extends TestCase
{
    use ApiTestHelpers, RefreshDatabase;

    public function test_services_respect_plan_limits_and_can_be_toggled(): void
    {
        $contractor = $this->makeContractor('free');
        $category = $this->category('service');
        $api = $this->actingAs($contractor, 'api');

        for ($i = 1; $i <= 3; $i++) {
            $api->postJson('/api/contractor/services', [
                'name' => "Service {$i}",
                'category_id' => $category->id,
                'pricing_type' => 'custom_quote',
            ])->assertCreated()->assertJsonPath('data.price_display', 'Custom Quote');
        }

        // Free plan allows 3 services
        $api->postJson('/api/contractor/services', [
            'name' => 'One too many',
            'category_id' => $category->id,
            'pricing_type' => 'hourly',
            'price' => 150,
        ])->assertUnprocessable();

        $id = $api->getJson('/api/contractor/services')->assertOk()->json('data.items.0.id');
        $api->postJson("/api/contractor/services/{$id}/toggle")->assertOk()->assertJsonPath('data.is_active', false);
    }

    public function test_products_crud(): void
    {
        $contractor = $this->makeContractor();
        $category = $this->category('product');
        $api = $this->actingAs($contractor, 'api');

        $id = $api->postJson('/api/contractor/products', [
            'name' => 'Cordless Drill Kit',
            'category_id' => $category->id,
            'price' => 129.99,
        ])->assertCreated()->assertJsonPath('data.price_display', '$129.99')->json('data.id');

        $api->putJson("/api/contractor/products/{$id}", ['price' => 119.99])->assertOk()->assertJsonPath('data.price', 119.99);
        $api->postJson("/api/contractor/products/{$id}/toggle")->assertOk()->assertJsonPath('data.is_active', false);
        $api->deleteJson("/api/contractor/products/{$id}")->assertOk();
        $api->getJson('/api/contractor/products')->assertOk()->assertJsonCount(0, 'data.items');
    }

    public function test_gallery_upload_update_and_delete(): void
    {
        Storage::fake('public');
        $contractor = $this->makeContractor();
        $api = $this->actingAs($contractor, 'api');

        $photos = $api->post('/api/contractor/gallery', [
            'photos' => [UploadedFile::fake()->image('kitchen.jpg'), UploadedFile::fake()->image('deck.png')],
            'category' => 'team',
        ], ['Accept' => 'application/json'])->assertCreated()->json('data');

        $this->assertCount(2, $photos);
        $this->assertSame('kitchen', $photos[0]['caption']);

        $api->putJson("/api/contractor/gallery/{$photos[0]['id']}", ['caption' => 'Finished kitchen'])
            ->assertOk()->assertJsonPath('data.caption', 'Finished kitchen');
        $api->deleteJson("/api/contractor/gallery/{$photos[1]['id']}")->assertOk();
        $api->getJson('/api/contractor/gallery')->assertOk()->assertJsonPath('data.usage.used', 1);
    }

    public function test_banner_is_elite_only_and_goes_to_review(): void
    {
        Storage::fake('public');

        $this->actingAs($this->makeContractor('pro'), 'api')
            ->post('/api/contractor/banner', ['image' => UploadedFile::fake()->image('b.jpg', 1440, 340)], ['Accept' => 'application/json'])
            ->assertForbidden();

        $elite = $this->makeContractor('elite');
        $this->actingAs($elite, 'api')
            ->post('/api/contractor/banner', ['image' => UploadedFile::fake()->image('b.jpg', 1440, 340)], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending');

        // Not public until approved
        $this->getJson('/api/banners')->assertOk()->assertJsonCount(0, 'data');
        Banner::first()->update(['status' => 'approved', 'approved_at' => now()]);
        $this->getJson('/api/banners')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_website_domain_is_normalised_and_validated(): void
    {
        $api = $this->actingAs($this->makeContractor(), 'api');

        $api->putJson('/api/contractor/website', ['custom_domain' => 'not a domain'])->assertUnprocessable();
        $api->putJson('/api/contractor/website', ['custom_domain' => 'https://www.IronRidge.co/'])
            ->assertOk()
            ->assertJsonPath('data.custom_domain', 'www.ironridge.co');
    }

    public function test_documents_upload_marks_license_for_review(): void
    {
        Storage::fake('public');
        $contractor = $this->makeContractor();
        $contractor->businessProfile->update(['is_license_verified' => true]);
        $api = $this->actingAs($contractor, 'api');

        $api->post('/api/contractor/documents', [
            'type' => 'license_insurance',
            'file' => UploadedFile::fake()->create('license.pdf', 200, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertCreated()->assertJsonPath('data.status', 'pending');

        $this->assertFalse($contractor->businessProfile->fresh()->is_license_verified);
        $api->getJson('/api/contractor/documents')->assertOk()
            ->assertJsonPath('data.summary.0.status_label', 'Uploaded · Pending review');
        $this->assertSame(1, BusinessDocument::count());
    }

    public function test_profile_update_accepts_new_fields(): void
    {
        $this->actingAs($this->makeContractor(), 'api')->putJson('/api/contractor/profile', [
            'business_category' => 'Blue Collar',
            'insurance_company' => 'State Farm',
            'insurance_policy_number' => 'POL-9876543',
            'offers_emergency_service' => true,
        ])->assertOk();
    }

    public function test_subscription_change_and_receipt(): void
    {
        $contractor = $this->makeContractor('elite');
        $pro = $this->makeContractor('pro')->subscription->plan;
        $api = $this->actingAs($contractor, 'api');

        $api->getJson('/api/contractor/subscription')->assertOk()->assertJsonPath('data.current.plan.slug', 'elite');
        $api->postJson('/api/contractor/subscription/change', ['plan_id' => $pro->id, 'billing_cycle' => 'annual'])->assertOk();
        $api->getJson('/api/contractor/subscription')->assertOk()
            ->assertJsonPath('data.current.plan.slug', 'pro')
            ->assertJsonPath('data.current.billing_cycle', 'annual');
        $this->assertFalse($contractor->businessProfile->fresh()->is_elite);

        $entry = BillingHistory::create([
            'subscription_id' => $contractor->subscription->id,
            'plan_name' => 'Pro',
            'amount' => 290,
            'status' => 'paid',
            'billed_at' => now(),
        ]);
        $api->get("/api/contractor/subscription/receipts/{$entry->id}")
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
    }
}
