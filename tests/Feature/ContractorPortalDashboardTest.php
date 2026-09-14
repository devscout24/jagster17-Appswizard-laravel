<?php

namespace Tests\Feature;

use App\Models\BusinessProfile;
use App\Models\Category;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\QuoteRequest;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ContractorPortalDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('business', 'api');
        Role::findOrCreate('customer', 'api');
        $this->seed(SubscriptionPlanSeeder::class);
    }

    public function test_can_retrieve_contractor_dashboard(): void
    {
        $contractor = User::factory()->create([
            'name'  => 'Apex Construction',
            'email' => 'apex@contractor.test',
        ]);
        $contractor->assignRole('business');

        $profile = BusinessProfile::create([
            'user_id'            => $contractor->id,
            'business_name'      => 'Apex Construction LLC',
            'owner_name'         => 'Marcus Vance',
            'city'               => 'Austin',
            'state'              => 'TX',
            'is_available_today' => true,
            'is_id_verified'     => true,
            'is_veteran_owned'   => true,
            'is_elite'           => true,
            'member_since'       => now()->subYears(2),
            'avg_rating'         => 4.90,
            'review_count'       => 38,
        ]);

        $proPlan = SubscriptionPlan::where('slug', 'pro')->first();
        Subscription::create([
            'business_id'   => $contractor->id,
            'plan_id'       => $proPlan->id,
            'billing_cycle' => 'monthly',
            'status'        => 'active',
            'renews_at'     => now()->addMonth(),
        ]);

        $customer = User::factory()->create(['name' => 'Alice Homeowner']);
        $category = Category::create(['name' => 'Roofing', 'type' => 'service']);

        // Create sample quote request
        QuoteRequest::create([
            'business_id'   => $contractor->id,
            'customer_id'   => $customer->id,
            'category_id'   => $category->id,
            'project_title' => 'Roof Inspection & Repair',
            'budget_min'    => 1200,
            'budget_max'    => 1800,
            'city'          => 'Austin',
            'state'         => 'TX',
            'status'        => 'pending',
            'requested_at'  => now()->subHours(2),
        ]);

        // Create sample project
        $project = Project::create([
            'business_id'      => $contractor->id,
            'customer_id'      => $customer->id,
            'title'            => 'Full Roof Replacement',
            'progress_percent' => 65,
            'status'           => 'in_progress',
            'due_date'         => now()->addDays(14),
        ]);

        // Create sample paid invoice
        Invoice::create([
            'business_id'    => $contractor->id,
            'customer_id'    => $customer->id,
            'project_id'     => $project->id,
            'invoice_number' => 'INV-2024-001',
            'amount'         => 3450.00,
            'status'         => 'paid',
            'issued_at'      => now()->subDays(5),
            'due_at'         => now()->addDays(10),
            'paid_at'        => now()->subDays(2),
        ]);

        $token = auth('api')->login($contractor);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/contractor/dashboard');

        $response->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'contractor' => [
                        'id',
                        'business_name',
                        'owner_name',
                        'email',
                        'location',
                        'is_available_today',
                        'is_id_verified',
                        'is_veteran_owned',
                        'is_elite',
                        'membership_tier',
                        'membership_badge',
                        'profile_completion_percent',
                    ],
                    'metrics' => [
                        'total_earnings',
                        'total_earnings_display',
                        'monthly_earnings',
                        'active_leads_count',
                        'pending_quotes_count',
                        'active_projects_count',
                        'completed_projects_count',
                        'avg_rating',
                        'review_count',
                        'lead_win_rate_percent',
                        'lead_win_rate_display',
                        'avg_response_time',
                    ],
                    'recent_leads' => [
                        '*' => [
                            'id',
                            'reference_number',
                            'customer_name',
                            'project_title',
                            'category_name',
                            'location',
                            'budget_display',
                            'status',
                            'requested_human',
                        ],
                    ],
                    'ongoing_projects' => [
                        '*' => [
                            'id',
                            'title',
                            'customer_name',
                            'progress_percent',
                            'status',
                            'due_date',
                        ],
                    ],
                    'recent_invoices' => [
                        '*' => [
                            'id',
                            'invoice_number',
                            'customer_name',
                            'amount',
                            'amount_display',
                            'status',
                        ],
                    ],
                    'quick_actions',
                ],
                'code',
            ]);

        $this->assertEquals('Apex Construction LLC', $response->json('data.contractor.business_name'));
        $this->assertTrue($response->json('data.contractor.is_available_today'));
        $this->assertEquals(3450.0, $response->json('data.metrics.total_earnings'));
        $this->assertEquals(1, $response->json('data.metrics.active_leads_count'));
        $this->assertEquals(1, count($response->json('data.recent_leads')));
    }

    public function test_can_toggle_contractor_availability(): void
    {
        $contractor = User::factory()->create();
        $contractor->assignRole('business');

        $profile = BusinessProfile::create([
            'user_id'            => $contractor->id,
            'business_name'      => 'Quick Fix Pros',
            'is_available_today' => false,
            'member_since'       => now(),
        ]);

        $token = auth('api')->login($contractor);

        // Toggle ON
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/contractor/toggle-availability');

        $response->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.is_available_today', true);

        $profile->refresh();
        $this->assertTrue($profile->is_available_today);

        // Toggle OFF explicitly
        $response2 = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/contractor/toggle-availability', [
                'is_available_today' => false,
            ]);

        $response2->assertStatus(200)
            ->assertJsonPath('data.is_available_today', false);

        $profile->refresh();
        $this->assertFalse($profile->is_available_today);
    }

    public function test_can_retrieve_contractor_leads_and_analytics(): void
    {
        $contractor = User::factory()->create();
        $contractor->assignRole('business');

        BusinessProfile::create([
            'user_id'       => $contractor->id,
            'business_name' => 'Solar Pros LLC',
            'member_since'  => now(),
        ]);

        $token = auth('api')->login($contractor);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/contractor/leads');

        $response->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonStructure([
                'data' => [
                    'counts' => ['all', 'new', 'quoted', 'accepted', 'declined'],
                    'items',
                    'pagination',
                ],
            ]);

        $analyticsRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/contractor/analytics');

        $analyticsRes->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonStructure([
                'data' => [
                    'total_revenue',
                    'quotes_sent',
                    'quotes_won',
                    'win_rate',
                    'monthly_breakdown',
                ],
            ]);
    }

    public function test_can_view_lead_details_and_send_quote(): void
    {
        $contractor = User::factory()->create(['name' => 'Iron Ridge Roofing']);
        $contractor->assignRole('business');

        BusinessProfile::create([
            'user_id'       => $contractor->id,
            'business_name' => 'Iron Ridge Roofing LLC',
            'member_since'  => now(),
        ]);

        $customer = User::factory()->create(['name' => 'John Doe', 'email' => 'john@doe.test']);
        $category = Category::create(['name' => 'Roofing', 'type' => 'service']);

        $lead = QuoteRequest::create([
            'business_id'   => $contractor->id,
            'customer_id'   => $customer->id,
            'category_id'   => $category->id,
            'project_title' => 'Asphalt Shingle Replacement',
            'description'   => 'Replace damaged shingles on 2,000 sq ft roof.',
            'budget_min'    => 4000,
            'budget_max'    => 6000,
            'city'          => 'Dallas',
            'state'         => 'TX',
            'status'        => 'pending',
            'requested_at'  => now(),
        ]);

        $token = auth('api')->login($contractor);

        // 1. View Lead Details (Node 3351-5)
        $detailRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/contractor/leads/' . $lead->id);

        $detailRes->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.project_title', 'Asphalt Shingle Replacement')
            ->assertJsonPath('data.customer.name', 'John Doe')
            ->assertJsonPath('data.has_responded', false);

        // 2. Submit / Send Quote (Node 3351-5)
        $sendQuoteRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/contractor/leads/' . $lead->id . '/send-quote', [
                'quote_amount'       => 4850.00,
                'labor_cost'         => 3200.00,
                'materials_cost'     => 1400.00,
                'tax_cost'           => 250.00,
                'estimated_duration' => '3–4 days',
                'contractor_notes'   => 'Includes tear-off, synthetic underlayment, and lifetime architectural shingles.',
            ]);

        $sendQuoteRes->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.status', 'quoted')
            ->assertJsonPath('data.status_label', 'Quote Sent')
            ->assertJsonPath('data.has_responded', true);

        $this->assertEquals(4850.0, $sendQuoteRes->json('data.quote_response.quote_amount'));

        $lead->refresh();
        $this->assertEquals('quoted', $lead->status);
        $this->assertEquals(4850.00, $lead->quote_amount);

        // Assert Notification created for Customer
        $this->assertDatabaseHas('notifications', [
            'user_id' => $customer->id,
            'type'    => 'quote_received',
        ]);
    }

    public function test_can_decline_lead(): void
    {
        $contractor = User::factory()->create();
        $contractor->assignRole('business');

        $customer = User::factory()->create();

        $lead = QuoteRequest::create([
            'business_id'   => $contractor->id,
            'customer_id'   => $customer->id,
            'project_title' => 'Tree Removal',
            'status'        => 'pending',
            'requested_at'  => now(),
        ]);

        $token = auth('api')->login($contractor);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/contractor/leads/' . $lead->id . '/decline');

        $response->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.status', 'declined');

        $lead->refresh();
        $this->assertEquals('declined', $lead->status);
    }
}
