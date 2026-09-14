<?php

namespace Tests\Feature;

use App\Models\BusinessProfile;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ContractorProjectsAndInvoicesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('business', 'api');
        Role::findOrCreate('customer', 'api');
        $this->seed(SubscriptionPlanSeeder::class);
    }

    public function test_can_manage_projects_flow(): void
    {
        $contractor = User::factory()->create(['name' => 'Apex Construction']);
        $contractor->assignRole('business');

        BusinessProfile::create([
            'user_id'       => $contractor->id,
            'business_name' => 'Apex Construction LLC',
            'member_since'  => now(),
        ]);

        $customer = User::factory()->create(['name' => 'Alice Client']);
        $token = auth('api')->login($contractor);
        $authHeader = ['Authorization' => 'Bearer ' . $token];

        // 1. Create Project
        $createRes = $this->withHeaders($authHeader)
            ->postJson('/api/contractor/projects', [
                'title'        => 'Custom Kitchen Remodel',
                'customer_id'  => $customer->id,
                'total_amount' => 15000.00,
                'due_date'     => now()->addMonths(2)->format('Y-m-d'),
                'description'  => 'Cabinet replacement, quartz countertops, and tile backsplash.',
            ]);

        $createRes->assertStatus(201)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.title', 'Custom Kitchen Remodel')
            ->assertJsonPath('data.progress_percent', 0)
            ->assertJsonPath('data.status', 'in_progress');

        $projectId = $createRes->json('data.id');

        // 2. List Projects (Node 3223-1398)
        $listRes = $this->withHeaders($authHeader)
            ->getJson('/api/contractor/projects');

        $listRes->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonStructure([
                'data' => [
                    'counts' => ['all', 'in_progress', 'scheduled', 'pending_materials', 'completed'],
                    'items'  => [
                        '*' => [
                            'id',
                            'title',
                            'total_amount_display',
                            'progress_percent',
                            'status',
                            'status_label',
                            'customer',
                            'invoices_summary',
                        ],
                    ],
                    'pagination',
                ],
            ]);

        $this->assertEquals(1, $listRes->json('data.counts.in_progress'));

        // 3. View Project Details
        $detailRes = $this->withHeaders($authHeader)
            ->getJson('/api/contractor/projects/' . $projectId);

        $detailRes->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.title', 'Custom Kitchen Remodel')
            ->assertJsonPath('data.customer.name', 'Alice Client');

        // 4. Update Project Progress
        $progressRes = $this->withHeaders($authHeader)
            ->putJson('/api/contractor/projects/' . $projectId . '/progress', [
                'progress_percent' => 100,
            ]);

        $progressRes->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.progress_percent', 100)
            ->assertJsonPath('data.status', 'completed');
    }

    public function test_can_create_and_manage_invoices_flow(): void
    {
        $contractor = User::factory()->create(['name' => 'Elite Roofing']);
        $contractor->assignRole('business');

        BusinessProfile::create([
            'user_id'       => $contractor->id,
            'business_name' => 'Elite Roofing Pros',
            'member_since'  => now(),
        ]);

        $customer = User::factory()->create(['name' => 'David Miller', 'email' => 'david@client.test']);

        $project = Project::create([
            'business_id'      => $contractor->id,
            'customer_id'      => $customer->id,
            'title'            => 'Roof Underlayment & Shingles',
            'progress_percent' => 50,
            'status'           => 'in_progress',
        ]);

        $token = auth('api')->login($contractor);
        $authHeader = ['Authorization' => 'Bearer ' . $token];

        // 1. Create & Send Invoice (Node 3354-11)
        $invoiceRes = $this->withHeaders($authHeader)
            ->postJson('/api/contractor/invoices', [
                'project_id'       => $project->id,
                'customer_id'      => $customer->id,
                'amount'           => 4250.00,
                'labor_amount'     => 2600.00,
                'materials_amount' => 1400.00,
                'platform_fee'     => 250.00,
                'due_at'           => now()->addDays(14)->format('Y-m-d'),
                'notes'            => 'Milestone 1 invoice: 50% completion on roof tear-off and underlayment.',
                'send_immediately' => true,
            ]);

        $invoiceRes->assertStatus(201)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.amount_display', '$4,250.00')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.status_label', 'Pending Payment')
            ->assertJsonPath('data.customer.name', 'David Miller');

        $this->assertEquals(4250.0, $invoiceRes->json('data.amount'));
        $invoiceId = $invoiceRes->json('data.id');

        // Check Notification for Customer
        $this->assertDatabaseHas('notifications', [
            'user_id' => $customer->id,
            'type'    => 'invoice_issued',
        ]);

        // 2. List Invoices
        $listRes = $this->withHeaders($authHeader)
            ->getJson('/api/contractor/invoices');

        $listRes->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonStructure([
                'data' => [
                    'counts' => ['all', 'pending', 'paid', 'overdue', 'draft'],
                    'items',
                    'pagination',
                ],
            ]);

        $this->assertEquals(1, $listRes->json('data.counts.pending'));

        // 3. View Single Invoice
        $viewRes = $this->withHeaders($authHeader)
            ->getJson('/api/contractor/invoices/' . $invoiceId);

        $viewRes->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.project.title', 'Roof Underlayment & Shingles')
            ->assertJsonPath('data.amount_display', '$4,250.00');
    }
}
