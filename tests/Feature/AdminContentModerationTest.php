<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\BusinessDocument;
use App\Models\GalleryPhoto;
use App\Models\Notification;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Database\Seeders\AdminSeeder;
use Database\Seeders\PlatformContentSeeder;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminContentModerationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->seed([SubscriptionPlanSeeder::class, AdminSeeder::class, PlatformContentSeeder::class]);
        $this->admin = User::where('email', 'admin@valorhub.com')->first();
    }

    public function test_new_admin_pages_load(): void
    {
        foreach (['verifications', 'banners', 'gallery', 'pages', 'pages/create', 'settings', 'dashboard'] as $path) {
            $this->actingAs($this->admin, 'web')->get("/admin/{$path}")->assertOk();
        }
    }

    public function test_verifying_a_license_marks_the_contractor_verified_and_notifies_them(): void
    {
        $document = BusinessDocument::where('status', 'pending')->first();

        $this->actingAs($this->admin, 'web')
            ->put("/admin/verifications/{$document->id}", ['decision' => 'verified'])
            ->assertRedirect();

        $this->assertSame('verified', $document->fresh()->status);
        $this->assertTrue($document->business->businessProfile->fresh()->is_license_verified);
        $this->assertTrue(Notification::where('user_id', $document->business_id)->where('type', 'document_review')->exists());
    }

    public function test_rejections_need_a_reason(): void
    {
        $document = BusinessDocument::where('status', 'pending')->first();
        $banner = Banner::where('status', 'pending')->first();

        $this->actingAs($this->admin, 'web')
            ->put("/admin/verifications/{$document->id}", ['decision' => 'rejected'])
            ->assertSessionHasErrors('admin_notes');
        $this->actingAs($this->admin, 'web')
            ->put("/admin/banners/{$banner->id}", ['decision' => 'rejected'])
            ->assertSessionHasErrors('rejection_reason');

        $this->assertSame('pending', $banner->fresh()->status);
    }

    public function test_approving_a_banner_puts_it_on_the_homepage(): void
    {
        $banner = Banner::where('status', 'pending')->first();
        $before = count($this->getJson('/api/banners')->json('data'));

        $this->actingAs($this->admin, 'web')
            ->put("/admin/banners/{$banner->id}", ['decision' => 'approved'])
            ->assertRedirect();

        $this->assertCount($before + 1, $this->getJson('/api/banners')->json('data'));
    }

    public function test_hiding_a_gallery_photo(): void
    {
        $photo = GalleryPhoto::first();

        $this->actingAs($this->admin, 'web')->post("/admin/gallery/{$photo->id}/toggle")->assertRedirect();

        $this->assertSame('hidden', $photo->fresh()->status);
    }

    public function test_pages_crud_and_public_api(): void
    {
        $this->actingAs($this->admin, 'web')->post('/admin/pages', [
            'title' => 'Cookie Policy',
            'content' => '<p>We use cookies.</p>',
            'is_published' => '1',
        ])->assertRedirect('/admin/pages');

        $this->getJson('/api/pages/cookie-policy')->assertOk()->assertJsonPath('data.title', 'Cookie Policy');

        $page = Page::where('slug', 'cookie-policy')->first();
        $this->actingAs($this->admin, 'web')->put("/admin/pages/{$page->id}", [
            'title' => 'Cookie Policy',
            'slug' => 'cookie-policy',
            'content' => '<p>Updated.</p>',
        ])->assertRedirect();

        // Unchecked "published" hides it from the website
        $this->getJson('/api/pages/cookie-policy')->assertNotFound();
    }

    public function test_site_settings_validate_and_save(): void
    {
        $this->actingAs($this->admin, 'web')
            ->put('/admin/settings', ['facebook_url' => 'not-a-url'])
            ->assertSessionHasErrors('facebook_url');

        $this->actingAs($this->admin, 'web')
            ->put('/admin/settings', ['support_email' => 'help@valorhub.com', 'facebook_url' => 'https://facebook.com/valorhub'])
            ->assertRedirect();

        $this->assertSame('help@valorhub.com', SiteSetting::get('support_email'));
        $this->getJson('/api/site-settings')->assertJsonPath('data.facebook_url', 'https://facebook.com/valorhub');
    }

    public function test_customers_cannot_reach_moderation_pages(): void
    {
        $customer = User::whereHas('customerProfile')->first();

        $this->actingAs($customer, 'web')->get('/admin/verifications')->assertRedirect('/admin/login');
    }
}
