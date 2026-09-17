<?php

namespace Tests\Feature;

use App\Models\BusinessProfile;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Faq;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Project;
use App\Models\QuoteRequest;
use App\Models\Review;
use App\Models\Service;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\VeteranNomination;
use Database\Seeders\AdminSeeder;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SubscriptionPlanSeeder::class);
        $this->seed(AdminSeeder::class);
    }

    public function test_guest_is_redirected_to_admin_login()
    {
        $response = $this->get('/admin/dashboard');
        $response->assertRedirect('/admin/login');
    }

    public function test_admin_can_view_login_page()
    {
        $response = $this->get('/admin/login');
        $response->assertStatus(200);
        $response->assertSee('Admin Portal Sign In');
    }

    public function test_admin_can_authenticate_and_access_dashboard()
    {
        $response = $this->post('/admin/login', [
            'email' => 'admin@valorhub.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticated('web');

        $dashboardResponse = $this->get('/admin/dashboard');
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee('Platform Executive Dashboard');
    }

    public function test_non_admin_cannot_access_dashboard()
    {
        $customer = User::where('email', 'michael.chang@test.com')->first();
        $this->assertNotNull($customer);

        $response = $this->actingAs($customer, 'web')->get('/admin/dashboard');
        $response->assertRedirect('/admin/login');
        $response->assertSessionHas('error');
    }

    public function test_admin_can_view_contractors_and_details()
    {
        $admin = User::where('email', 'admin@valorhub.com')->first();

        $response = $this->actingAs($admin, 'web')->get('/admin/contractors');
        $response->assertStatus(200);
        $response->assertSee('Contractors & Service Providers');

        $contractor = User::where('email', 'contact@apexpatriot.test')->first();
        $detailResponse = $this->actingAs($admin, 'web')->get("/admin/contractors/{$contractor->id}");
        $detailResponse->assertStatus(200);
        $detailResponse->assertSee('Apex Patriot Roofing LLC');
    }

    public function test_admin_can_toggle_contractor_badges()
    {
        $admin = User::where('email', 'admin@valorhub.com')->first();
        $contractor = User::where('email', 'info@bluestarplumbing.test')->first();
        $initialElite = $contractor->businessProfile->is_elite;

        $response = $this->actingAs($admin, 'web')->post("/admin/contractors/{$contractor->id}/toggle-badge", [
            'badge' => 'is_elite',
        ]);

        $response->assertRedirect();
        $this->assertEquals(!$initialElite, $contractor->fresh()->businessProfile->is_elite);
    }

    public function test_admin_can_view_and_manage_categories()
    {
        $admin = User::where('email', 'admin@valorhub.com')->first();

        $response = $this->actingAs($admin, 'web')->get('/admin/categories');
        $response->assertStatus(200);

        $storeResponse = $this->actingAs($admin, 'web')->post('/admin/categories', [
            'name' => 'Fencing & Gates',
            'type' => 'service',
            'icon' => 'ti ti-fence',
            'description' => 'Wood, vinyl, and chain link fencing services.',
        ]);

        $storeResponse->assertRedirect('/admin/categories');
        $this->assertDatabaseHas('categories', ['name' => 'Fencing & Gates']);
    }

    public function test_admin_can_view_quotes_projects_and_invoices()
    {
        $admin = User::where('email', 'admin@valorhub.com')->first();

        $quotesResponse = $this->actingAs($admin, 'web')->get('/admin/quotes');
        $quotesResponse->assertStatus(200);

        $projectsResponse = $this->actingAs($admin, 'web')->get('/admin/projects');
        $projectsResponse->assertStatus(200);

        $invoicesResponse = $this->actingAs($admin, 'web')->get('/admin/invoices');
        $invoicesResponse->assertStatus(200);

        $paymentsResponse = $this->actingAs($admin, 'web')->get('/admin/payments');
        $paymentsResponse->assertStatus(200);
    }

    public function test_admin_can_view_and_update_veteran_nominations()
    {
        $admin = User::where('email', 'admin@valorhub.com')->first();
        $nomination = VeteranNomination::first();
        $this->assertNotNull($nomination);

        $response = $this->actingAs($admin, 'web')->get('/admin/veteran-nominations');
        $response->assertStatus(200);

        $updateResponse = $this->actingAs($admin, 'web')->put("/admin/veteran-nominations/{$nomination->id}/status", [
            'status' => 'approved',
        ]);

        $updateResponse->assertRedirect();
        $this->assertEquals('approved', $nomination->fresh()->status);
    }

    public function test_admin_can_view_and_manage_faqs()
    {
        $admin = User::where('email', 'admin@valorhub.com')->first();

        $response = $this->actingAs($admin, 'web')->get('/admin/faqs');
        $response->assertStatus(200);

        $storeResponse = $this->actingAs($admin, 'web')->post('/admin/faqs', [
            'category' => 'general',
            'question' => 'What is the platform fee?',
            'answer' => 'Platform fees vary based on membership tier.',
            'sort_order' => 10,
            'is_published' => 1,
        ]);

        $storeResponse->assertRedirect('/admin/faqs');
        $this->assertDatabaseHas('faqs', ['question' => 'What is the platform fee?']);
    }
}
