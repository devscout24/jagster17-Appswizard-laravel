<?php

namespace Tests\Feature;

use App\Models\BusinessProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContractorInvoiceActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_draft_to_paid_invoice_lifecycle(): void
    {
        $contractor = User::factory()->create();
        $contractor->assignRole('business');
        BusinessProfile::create(['user_id' => $contractor->id, 'business_name' => 'Iron Ridge Contracting', 'member_since' => now()]);

        $customerId = $this->actingAs($contractor, 'api')->postJson('/api/contractor/customers', [
            'name' => 'Brian Collins', 'email' => 'brian.collins@email.com',
        ])->assertStatus(201)->json('data.id');

        $invoice = $this->actingAs($contractor, 'api')->postJson('/api/contractor/invoices', [
            'customer_id'      => $customerId,
            'items'            => [['name' => 'Roof Repair', 'quantity' => 1, 'price' => 4100]],
            'due_at'           => now()->addDays(14)->toDateString(),
            'send_immediately' => false,
        ])->assertStatus(201)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.amount', 4100)
            ->json('data');

        $id = $invoice['id'];

        $this->actingAs($contractor, 'api')->postJson("/api/contractor/invoices/{$id}/mark-paid", [
            'payment_date' => now()->toDateString(), 'payment_method' => 'cash',
        ])->assertStatus(422);

        $this->actingAs($contractor, 'api')->putJson("/api/contractor/invoices/{$id}", [
            'items' => [['name' => 'Roof Repair', 'quantity' => 2, 'price' => 100]],
        ])->assertOk()->assertJsonPath('data.amount', 200);

        $this->actingAs($contractor, 'api')->postJson("/api/contractor/invoices/{$id}/approve")->assertOk();

        Mail::fake();
        $this->actingAs($contractor, 'api')->postJson("/api/contractor/invoices/{$id}/send")
            ->assertOk()
            ->assertJsonPath('data.status', 'pending');

        $this->actingAs($contractor, 'api')->deleteJson("/api/contractor/invoices/{$id}")->assertStatus(422);

        $this->actingAs($contractor, 'api')->postJson("/api/contractor/invoices/{$id}/mark-paid", [
            'payment_date' => now()->toDateString(), 'payment_method' => 'bank_transfer',
        ])->assertOk()->assertJsonPath('data.status', 'paid');

        $this->assertDatabaseHas('customer_payments', ['invoice_id' => $id, 'business_id' => $contractor->id]);

        $this->actingAs($contractor, 'api')->get("/api/contractor/invoices/{$id}/pdf")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($contractor, 'api')->postJson("/api/contractor/invoices/{$id}/duplicate")
            ->assertStatus(201)
            ->assertJsonPath('data.status', 'draft');
    }
}
