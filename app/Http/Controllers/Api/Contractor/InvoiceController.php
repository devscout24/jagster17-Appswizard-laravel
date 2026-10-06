<?php

namespace App\Http\Controllers\Api\Contractor;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContractorInvoiceResource;
use App\Models\ContractorCustomer;
use App\Models\Invoice;
use App\Models\Notification;
use App\Models\Project;
use App\Models\User;
use App\Services\InvoiceTotals;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Contractor invoice lifecycle: create (with line items and discount), edit, send,
 * mark paid, duplicate and delete. Listing and detail live in ContractorPortalController.
 */
class InvoiceController extends Controller
{
    use ApiResponseTrait;

    public function store(Request $request): JsonResponse
    {
        $business = auth('api')->user();
        $data = $this->validateInvoice($request, $business);

        $invoice = DB::transaction(function () use ($business, $data) {
            $invoice = new Invoice([
                'business_id' => $business->id,
                'invoice_number' => $data['invoice_number'] ?? $this->nextInvoiceNumber(),
                'status' => ($data['status'] ?? 'draft') === 'pending' ? 'pending' : 'draft',
            ]);

            return $this->persist($invoice, $data);
        });

        if ($invoice->status === 'pending') {
            $this->notifyCustomer($invoice, $business);
        }

        return $this->success(
            $this->present($invoice),
            $invoice->status === 'pending' ? 'Invoice sent to client successfully.' : 'Invoice draft saved successfully.',
            201,
        );
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $business = auth('api')->user();
        $invoice = $this->findInvoice($id);

        if ($invoice->status === 'paid') {
            return $this->error('Paid invoices can no longer be edited.', 422);
        }

        $data = $this->validateInvoice($request, $business, $invoice);
        DB::transaction(fn () => $this->persist($invoice, $data));

        return $this->success($this->present($invoice), 'Invoice updated successfully.');
    }

    /**
     * Approve a draft (if needed) and email it to the customer.
     */
    public function send(Request $request, int $id): JsonResponse
    {
        $business = auth('api')->user();
        $invoice = $this->findInvoice($id);
        $validated = $request->validate([
            'to' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:5000'],
        ]);

        if ($invoice->status === 'paid') {
            return $this->error('This invoice has already been paid.', 422);
        }

        $invoice->update(['status' => 'pending', 'sent_at' => now()]);
        $this->notifyCustomer($invoice, $business);

        // Email delivery is queued here once a mail provider is configured
        return $this->success(
            $this->present($invoice),
            "Invoice {$invoice->invoice_number} sent to {$validated['to']}.",
        );
    }

    public function markPaid(Request $request, int $id): JsonResponse
    {
        $invoice = $this->findInvoice($id);
        $validated = $request->validate([
            'paid_on' => ['required', 'date', 'before_or_equal:today'],
            'payment_method' => ['required', Rule::in(['bank_transfer', 'credit_card', 'check', 'cash', 'other'])],
        ]);

        if ($invoice->status === 'draft') {
            return $this->error('Send the invoice before recording a payment.', 422);
        }

        $invoice->update([
            'status' => 'paid',
            'paid_at' => $validated['paid_on'],
            'payment_method' => $validated['payment_method'],
        ]);

        return $this->success($this->present($invoice), 'Invoice marked as paid.');
    }

    public function duplicate(int $id): JsonResponse
    {
        $source = $this->findInvoice($id)->load('items');

        $copy = DB::transaction(function () use ($source) {
            $copy = $source->replicate(['invoice_number', 'status', 'paid_at', 'payment_method', 'sent_at']);
            $copy->fill([
                'invoice_number' => $this->nextInvoiceNumber(),
                'status' => 'draft',
                'issued_at' => now(),
                'due_at' => now()->addDays(14),
            ])->save();

            foreach ($source->items as $item) {
                $copy->items()->create($item->only(['description', 'details', 'quantity', 'unit_price', 'amount', 'sort_order']));
            }

            return $copy;
        });

        return $this->success($this->present($copy), 'Invoice duplicated as a new draft.', 201);
    }

    public function destroy(int $id): JsonResponse
    {
        $invoice = $this->findInvoice($id);

        if ($invoice->status === 'paid') {
            return $this->error('Paid invoices are kept for your records and cannot be deleted.', 422);
        }

        $invoice->delete();

        return $this->success(null, 'Invoice deleted.');
    }

    /**
     * Next invoice number, e.g. INV-2042.
     */
    private function nextInvoiceNumber(): string
    {
        $last = Invoice::where('invoice_number', 'like', 'INV-%')
            ->pluck('invoice_number')
            ->map(fn ($number) => (int) preg_replace('/\D/', '', substr($number, 4)))
            ->filter(fn ($number) => $number < 1_000_000)
            ->max() ?? 2000;

        return 'INV-' . ($last + 1);
    }

    private function validateInvoice(Request $request, User $business, ?Invoice $invoice = null): array
    {
        return $request->validate([
            'customer_id' => ['nullable', 'integer', 'exists:users,id'],
            'contractor_customer_id' => [
                'nullable', 'integer',
                Rule::exists('contractor_customers', 'id')->where('business_id', $business->id),
            ],
            'project_id' => [
                'nullable', 'integer',
                Rule::exists('projects', 'id')->where('business_id', $business->id),
            ],
            'invoice_number' => [
                'nullable', 'string', 'max:50',
                Rule::unique('invoices', 'invoice_number')->ignore($invoice?->id),
            ],
            'po_number' => ['nullable', 'string', 'max:50'],
            'issued_at' => ['nullable', 'date'],
            'due_at' => ['required', 'date', 'after_or_equal:issued_at'],
            'payment_terms' => ['nullable', 'string', 'max:30'],
            // Either line items, or a single amount (older clients)
            'items' => ['required_without:amount', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.details' => ['nullable', 'string', 'max:1000'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'amount' => ['required_without:items', 'numeric', 'min:1'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'footer' => ['nullable', 'string', 'max:1000'],
            'tags' => ['nullable', 'array', 'max:20'],
            'tags.*' => ['string', 'max:40'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['string', 'max:500'],
            'require_terms_acceptance' => ['nullable', 'boolean'],
            'status' => ['nullable', Rule::in(['draft', 'pending'])],
            // Older clients send send_immediately instead of status
            'send_immediately' => ['nullable', 'boolean'],
        ]);
    }

    private function persist(Invoice $invoice, array $data): Invoice
    {
        $projectCustomer = ! empty($data['project_id'])
            ? Project::find($data['project_id'])?->customer_id
            : null;
        $customerId = $data['customer_id'] ?? $projectCustomer ?? $invoice->customer_id;
        $contractorCustomerId = $data['contractor_customer_id'] ?? $invoice->contractor_customer_id;

        abort_if(! $customerId && ! $contractorCustomerId, 422, 'Please choose a customer for this invoice.');

        $items = $data['items'] ?? [[
            'description' => 'Services rendered',
            'quantity' => 1,
            'unit_price' => $data['amount'],
        ]];
        $totals = InvoiceTotals::calculate($items, (float) ($data['discount_percent'] ?? 0));

        if (array_key_exists('send_immediately', $data) && ! $invoice->exists) {
            $invoice->status = $data['send_immediately'] ? 'pending' : 'draft';
        }

        $invoice->fill([
            'customer_id' => $customerId,
            'contractor_customer_id' => $contractorCustomerId,
            'project_id' => $data['project_id'] ?? $invoice->project_id,
            'po_number' => $data['po_number'] ?? null,
            'issued_at' => $data['issued_at'] ?? $invoice->issued_at ?? now(),
            'due_at' => $data['due_at'],
            'payment_terms' => $data['payment_terms'] ?? null,
            'subtotal' => $totals['subtotal'],
            'discount_percent' => (float) ($data['discount_percent'] ?? 0),
            'discount_amount' => $totals['discount_amount'],
            'amount' => $totals['total'],
            'notes' => $data['notes'] ?? null,
            'footer' => $data['footer'] ?? null,
            'tags' => $data['tags'] ?? [],
            'attachments' => $data['attachments'] ?? [],
            'require_terms_acceptance' => (bool) ($data['require_terms_acceptance'] ?? false),
        ]);

        if (! empty($data['invoice_number'])) {
            $invoice->invoice_number = $data['invoice_number'];
        }

        $invoice->save();
        $invoice->items()->delete();
        $invoice->items()->createMany($totals['items']);

        return $invoice;
    }

    private function notifyCustomer(Invoice $invoice, User $business): void
    {
        if (! $invoice->customer_id) {
            return;
        }

        Notification::create([
            'user_id' => $invoice->customer_id,
            'type' => 'invoice_issued',
            'title' => 'New Invoice Issued',
            'body' => ($business->businessProfile?->business_name ?? $business->name)
                . " issued invoice {$invoice->invoice_number} for $" . number_format((float) $invoice->amount, 2) . '.',
            'data' => ['invoice_id' => $invoice->id, 'invoice_number' => $invoice->invoice_number, 'amount' => (float) $invoice->amount],
        ]);
    }

    private function findInvoice(int $id): Invoice
    {
        return Invoice::where('business_id', auth('api')->id())->findOrFail($id);
    }

    private function present(Invoice $invoice): ContractorInvoiceResource
    {
        return new ContractorInvoiceResource($invoice->fresh(['customer', 'contractorCustomer', 'project', 'items']));
    }
}
