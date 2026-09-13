<?php

namespace Tests\Feature;

use App\Models\BusinessProfile;
use App\Models\Category;
use App\Models\CustomerProfile;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\QuoteRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_retrieve_customer_dashboard_overview(): void
    {
        $customer = User::create([
            'name' => 'Sarah Jenkins',
            'email' => 'sarah@test.com',
            'password' => 'secret123',
            'status' => 'active',
        ]);
        CustomerProfile::create(['user_id' => $customer->id, 'city' => 'Austin', 'state' => 'TX']);

        $contractor = User::create([
            'name' => 'Marcus Rivera',
            'email' => 'marcus@ironridge.test',
            'password' => 'secret123',
            'status' => 'active',
        ]);
        BusinessProfile::create(['user_id' => $contractor->id, 'business_name' => 'Iron Ridge Co.', 'member_since' => '2021-01-01']);

        QuoteRequest::create([
            'customer_id' => $customer->id,
            'business_id' => $contractor->id,
            'project_title' => 'HVAC Duct Maintenance',
            'description' => 'Fix master bedroom airflow',
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        Project::create([
            'customer_id' => $customer->id,
            'business_id' => $contractor->id,
            'title' => 'Completed Patio Tile',
            'status' => 'completed',
        ]);

        Invoice::create([
            'customer_id' => $customer->id,
            'business_id' => $contractor->id,
            'invoice_number' => 'INV-1001',
            'amount' => 1250.00,
            'issued_at' => '2026-09-01',
            'due_at' => '2026-09-15',
            'paid_at' => now(),
            'status' => 'paid',
        ]);

        $token = auth('api')->login($customer);

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->getJson('/api/customer/dashboard');

        $response->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'city',
                        'state',
                    ],
                    'metrics' => [
                        'active_requests',
                        'pending_quotes',
                        'completed_jobs',
                        'total_spent',
                        'total_spent_display',
                    ],
                    'recent_requests',
                ],
                'code',
            ]);

        $this->assertEquals(1, $response->json('data.metrics.active_requests'));
        $this->assertEquals(1, $response->json('data.metrics.completed_jobs'));
        $this->assertEquals(1250.00, $response->json('data.metrics.total_spent'));
    }

    public function test_can_view_quotes_and_accept(): void
    {
        $customer = User::create([
            'name' => 'Sarah Jenkins',
            'email' => 'sarah@test.com',
            'password' => 'secret123',
            'status' => 'active',
        ]);

        $contractor = User::create([
            'name' => 'Marcus Rivera',
            'email' => 'marcus@ironridge.test',
            'password' => 'secret123',
            'status' => 'active',
        ]);
        BusinessProfile::create(['user_id' => $contractor->id, 'business_name' => 'Iron Ridge Co.', 'member_since' => '2021-01-01']);

        $quote = QuoteRequest::create([
            'customer_id' => $customer->id,
            'business_id' => $contractor->id,
            'project_title' => 'Water Heater Replacement',
            'description' => 'Install tankless water heater',
            'quote_amount' => 1800.00,
            'labor_cost' => 1200.00,
            'materials_cost' => 500.00,
            'tax_cost' => 100.00,
            'estimated_duration' => '1 day',
            'contractor_notes' => 'Includes haul away of old unit.',
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        $token = auth('api')->login($customer);

        // 1. Get quotes list
        $quotesRes = $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->getJson('/api/customer/quotes');
        $quotesRes->assertStatus(200)
            ->assertJsonPath('data.0.project_title', 'Water Heater Replacement');

        // 2. Get quote details
        $detailRes = $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->getJson('/api/customer/quotes/'.$quote->id);
        $detailRes->assertStatus(200)
            ->assertJsonPath('data.financials.total_amount_display', '$1,800.00')
            ->assertJsonPath('data.contractor.business_name', 'Iron Ridge Co.');

        // 3. Accept quote
        $acceptRes = $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->postJson('/api/customer/quotes/'.$quote->id.'/accept');
        $acceptRes->assertStatus(200)
            ->assertJsonPath('data.status', 'accepted');

        $this->assertDatabaseHas('quote_requests', [
            'id' => $quote->id,
            'status' => 'accepted',
        ]);

        $this->assertDatabaseHas('projects', [
            'quote_request_id' => $quote->id,
            'customer_id' => $customer->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_can_view_and_pay_invoice(): void
    {
        $customer = User::create([
            'name' => 'Sarah Jenkins',
            'email' => 'sarah@test.com',
            'password' => 'secret123',
            'status' => 'active',
        ]);

        $contractor = User::create([
            'name' => 'Marcus Rivera',
            'email' => 'marcus@ironridge.test',
            'password' => 'secret123',
            'status' => 'active',
        ]);
        BusinessProfile::create(['user_id' => $contractor->id, 'business_name' => 'Iron Ridge Co.', 'member_since' => '2021-01-01']);

        $invoice = Invoice::create([
            'customer_id' => $customer->id,
            'business_id' => $contractor->id,
            'invoice_number' => 'INV-2041',
            'amount' => 450.00,
            'labor_amount' => 315.00,
            'materials_amount' => 90.00,
            'platform_fee' => 45.00,
            'issued_at' => '2026-09-10',
            'due_at' => '2026-09-25',
            'status' => 'pending',
        ]);

        $token = auth('api')->login($customer);

        // 1. Get invoices list
        $invoicesRes = $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->getJson('/api/customer/invoices');
        $invoicesRes->assertStatus(200)
            ->assertJsonPath('data.items.0.invoice_number', 'INV-2041');

        // 2. View invoice details
        $detailRes = $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->getJson('/api/customer/invoices/'.$invoice->id);
        $detailRes->assertStatus(200)
            ->assertJsonPath('data.breakdown.total_due_display', '$450.00');

        // 3. Pay invoice
        $payRes = $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->postJson('/api/customer/invoices/'.$invoice->id.'/pay');
        $payRes->assertStatus(200)
            ->assertJsonPath('data.status', 'paid');

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'paid',
        ]);

        $this->assertDatabaseHas('customer_payments', [
            'invoice_id' => $invoice->id,
            'user_id' => $customer->id,
            'amount' => 450.00,
            'status' => 'paid',
        ]);

        // 4. Check payments history
        $paymentsRes = $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->getJson('/api/customer/payments');
        $paymentsRes->assertStatus(200)
            ->assertJsonPath('data.items.0.amount_display', '$450.00')
            ->assertJsonPath('data.items.0.invoice_number', 'INV-2041');
    }
}
