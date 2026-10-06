<?php

namespace Tests\Feature\Api;

use App\Models\Invoice;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractorInvoiceLifecycleTest extends TestCase
{
    use ApiTestHelpers, RefreshDatabase;

    private function draftPayload(int $contractorCustomerId): array
    {
        return [
            'contractor_customer_id' => $contractorCustomerId,
            'po_number' => 'PO-77',
            'issued_at' => now()->toDateString(),
            'due_at' => now()->addDays(15)->toDateString(),
            'payment_terms' => 'Net 15',
            'items' => [['description' => 'HVAC tune-up & filter replacement', 'quantity' => 1, 'unit_price' => 225]],
            'discount_percent' => 10,
            'notes' => 'Thanks!',
            'tags' => ['hvac'],
            'status' => 'draft',
        ];
    }

    public function test_full_invoice_lifecycle(): void
    {
        $contractor = $this->makeContractor();
        $api = $this->actingAs($contractor, 'api');

        $customerId = $api->postJson('/api/contractor/customers', [
            'name' => 'Brian Collins',
            'email' => 'brian@example.com',
            'billing_address' => '218 Maple Grove Ln, Kyle, TX',
        ])->assertCreated()->json('data.id');

        // Matches the design example: 10% off $225 = $202.50
        $invoice = $api->postJson('/api/contractor/invoices', $this->draftPayload($customerId))
            ->assertCreated()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.subtotal', 225)
            ->assertJsonPath('data.discount_amount', 22.5)
            ->assertJsonPath('data.amount', 202.5)
            ->assertJsonPath('data.customer.name', 'Brian Collins')
            ->json('data');

        $id = $invoice['id'];
        $this->assertMatchesRegularExpression('/^INV-\d+$/', $invoice['invoice_number']);

        // Can't record a payment before it's sent
        $api->postJson("/api/contractor/invoices/{$id}/mark-paid", ['paid_on' => now()->toDateString(), 'payment_method' => 'check'])
            ->assertUnprocessable();

        $api->postJson("/api/contractor/invoices/{$id}/send", [
            'to' => 'brian@example.com',
            'subject' => 'Your invoice',
        ])->assertOk()->assertJsonPath('data.status', 'pending');

        $api->postJson("/api/contractor/invoices/{$id}/mark-paid", ['paid_on' => now()->toDateString(), 'payment_method' => 'bank_transfer'])
            ->assertOk()
            ->assertJsonPath('data.status', 'paid')
            ->assertJsonPath('data.payment_method', 'bank_transfer');

        // Paid invoices are locked
        $api->putJson("/api/contractor/invoices/{$id}", $this->draftPayload($customerId))->assertUnprocessable();
        $api->deleteJson("/api/contractor/invoices/{$id}")->assertUnprocessable();

        $copy = $api->postJson("/api/contractor/invoices/{$id}/duplicate")
            ->assertCreated()
            ->assertJsonPath('data.status', 'draft')
            ->json('data');
        $this->assertNotSame($invoice['invoice_number'], $copy['invoice_number']);
        $this->assertCount(1, $copy['items']);

        $api->deleteJson("/api/contractor/invoices/{$copy['id']}")->assertOk();
        $this->assertNull(Invoice::find($copy['id']));
    }

    public function test_sending_to_a_platform_customer_notifies_them(): void
    {
        $contractor = $this->makeContractor();
        $customer = $this->makeCustomer();

        $this->actingAs($contractor, 'api')->postJson('/api/contractor/invoices', [
            'customer_id' => $customer->id,
            'due_at' => now()->addWeek()->toDateString(),
            'items' => [['description' => 'Roof repair', 'quantity' => 1, 'unit_price' => 4100]],
            'status' => 'pending',
        ])->assertCreated();

        $this->assertTrue(Notification::where('user_id', $customer->id)->where('type', 'invoice_issued')->exists());
    }

    public function test_contractors_cannot_touch_each_others_invoices(): void
    {
        $owner = $this->makeContractor();
        $intruder = $this->makeContractor();
        $customer = $this->makeCustomer();

        $id = $this->actingAs($owner, 'api')->postJson('/api/contractor/invoices', [
            'customer_id' => $customer->id,
            'due_at' => now()->addWeek()->toDateString(),
            'amount' => 500,
        ])->assertCreated()->json('data.id');

        $this->actingAs($intruder, 'api')->postJson("/api/contractor/invoices/{$id}/duplicate")->assertNotFound();
        $this->actingAs($intruder, 'api')->deleteJson("/api/contractor/invoices/{$id}")->assertNotFound();
    }

    public function test_invoice_requires_a_customer_and_items(): void
    {
        $contractor = $this->makeContractor();

        $this->actingAs($contractor, 'api')->postJson('/api/contractor/invoices', [
            'due_at' => now()->addWeek()->toDateString(),
        ])->assertUnprocessable()->assertJsonValidationErrors(['items']);
    }
}
