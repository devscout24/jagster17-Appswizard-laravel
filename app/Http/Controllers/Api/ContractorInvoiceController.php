<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contractor\CreateInvoiceRequest;
use App\Http\Resources\ContractorInvoiceResource;
use App\Models\CustomerPayment;
use App\Models\CustomerProfile;
use App\Models\Invoice;
use App\Models\Notification;
use App\Models\Project;
use App\Models\QuoteRequest;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class ContractorInvoiceController extends Controller
{
    use ApiResponseTrait;

    public const PAYMENT_METHODS = ['bank_transfer', 'cash', 'check', 'card', 'other'];

    /**
     * Edit draft (Node 3422-1711). Only drafts can be edited.
     */
    public function update(CreateInvoiceRequest $request, int $id): JsonResponse
    {
        $invoice = $this->findOwned($id);
        if (! $invoice) {
            return $this->notFound('Invoice not found.');
        }

        if ($invoice->status !== 'draft') {
            return $this->error('Only draft invoices can be edited.', 422);
        }

        $validated = collect($request->validated())->except(['send_immediately'])->all();
        $user = auth('api')->user();

        if (! empty($validated['project_id'])) {
            $project = Project::where('business_id', $user->id)->find($validated['project_id']);
            if (! $project) {
                return $this->notFound('Project not found.');
            }
            $validated['customer_id'] = $project->customer_id;
        }

        if (isset($validated['customer_id']) && ! User::role('customer', 'api')->whereKey($validated['customer_id'])->exists()) {
            return $this->error('The selected client is not a customer account.', 422);
        }

        if (! empty($validated['items'])) {
            $validated['amount'] = $this->itemsTotal($validated['items']);
        }

        // Any edit needs a fresh approval before sending.
        $validated['approved_at'] = null;

        $invoice->update($validated);

        return $this->success($this->resource($invoice), 'Draft updated successfully.');
    }

    /**
     * Approve draft: the invoice is ready to be sent.
     */
    public function approve(int $id): JsonResponse
    {
        $invoice = $this->findOwned($id);
        if (! $invoice) {
            return $this->notFound('Invoice not found.');
        }

        if ($invoice->status !== 'draft') {
            return $this->error('Only draft invoices need approval.', 422);
        }

        $invoice->update(['approved_at' => now()]);

        return $this->success($this->resource($invoice), 'Draft approved. You can now send it to the customer.');
    }

    /**
     * Send invoice to customer (Send Invoice modal: to, subject, message; PDF attached).
     * Sending a draft approves it; sending a pending/overdue invoice re-sends it.
     */
    public function send(Request $request, int $id): JsonResponse
    {
        $invoice = $this->findOwned($id);
        if (! $invoice) {
            return $this->notFound('Invoice not found.');
        }

        if ($invoice->status === 'paid') {
            return $this->error('This invoice has already been paid.', 422);
        }

        $invoice->loadMissing(['customer', 'business.businessProfile', 'project']);
        $defaults = $this->sendDefaults($invoice);

        $validated = $request->validate([
            'to'      => ['sometimes', 'email'],
            'subject' => ['sometimes', 'string', 'max:255'],
            'message' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ]);

        $to = $validated['to'] ?? $defaults['to'];
        $subject = $validated['subject'] ?? $defaults['subject'];
        $body = $validated['message'] ?? $defaults['message'];

        $wasDraft = $invoice->status === 'draft';
        $invoice->update([
            'status'      => $wasDraft ? 'pending' : $invoice->status,
            'approved_at' => $invoice->approved_at ?? now(),
            'sent_at'     => now(),
        ]);

        $pdf = $this->pdf($invoice)->output();
        Mail::raw($body, function (Message $mail) use ($to, $subject, $pdf, $invoice) {
            $mail->to($to)
                ->subject($subject)
                ->attachData($pdf, "{$invoice->invoice_number}.pdf", ['mime' => 'application/pdf']);
        });

        if ($wasDraft) {
            Notification::create([
                'user_id' => $invoice->customer_id,
                'type'    => 'invoice_issued',
                'title'   => 'Invoice ready',
                'body'    => "Your invoice #{$invoice->invoice_number} is ready for payment.",
                'data'    => ['invoice_id' => $invoice->id, 'invoice_number' => $invoice->invoice_number, 'amount' => (float) $invoice->amount],
            ]);
        }

        return $this->success($this->resource($invoice), "Invoice {$invoice->invoice_number} sent to {$to}.");
    }

    /**
     * Pre-filled values for the Send Invoice modal.
     */
    public function sendPreview(int $id): JsonResponse
    {
        $invoice = $this->findOwned($id);
        if (! $invoice) {
            return $this->notFound('Invoice not found.');
        }

        $invoice->loadMissing(['customer', 'business.businessProfile', 'project']);

        return $this->success($this->sendDefaults($invoice) + [
            'attachment' => "{$invoice->invoice_number}.pdf",
        ], 'Send invoice details retrieved successfully.');
    }

    /**
     * Mark as paid (Mark Paid modal: payment date + payment method).
     */
    public function markPaid(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'payment_date'   => ['required', 'date', 'before_or_equal:today'],
            'payment_method' => ['required', Rule::in(self::PAYMENT_METHODS)],
        ]);

        $invoice = $this->findOwned($id);
        if (! $invoice) {
            return $this->notFound('Invoice not found.');
        }

        if ($invoice->status === 'paid') {
            return $this->error('This invoice is already marked as paid.', 422);
        }

        if ($invoice->status === 'draft') {
            return $this->error('Send the invoice before recording a payment.', 422);
        }

        DB::transaction(function () use ($invoice, $validated) {
            $paidAt = \Illuminate\Support\Carbon::parse($validated['payment_date'])->setTimeFrom(now());

            $invoice->update([
                'status'         => 'paid',
                'paid_at'        => $paidAt,
                'payment_method' => $validated['payment_method'],
            ]);

            CustomerPayment::create([
                'user_id'        => $invoice->customer_id,
                'invoice_id'     => $invoice->id,
                'business_id'    => $invoice->business_id,
                'transaction_id' => 'MAN-' . Str::upper(Str::random(12)),
                'amount'         => $invoice->amount,
                'payment_method' => Str::headline($validated['payment_method']),
                'status'         => 'paid',
                'paid_at'        => $paidAt,
            ]);
        });

        return $this->success($this->resource($invoice), "Invoice {$invoice->invoice_number} marked as paid.");
    }

    /**
     * More actions → Duplicate invoice (creates a new draft).
     */
    public function duplicate(int $id): JsonResponse
    {
        $invoice = $this->findOwned($id);
        if (! $invoice) {
            return $this->notFound('Invoice not found.');
        }

        $termDays = max(1, (int) $invoice->issued_at->diffInDays($invoice->due_at));

        $copy = $invoice->replicate(['paid_at', 'payment_method', 'approved_at', 'sent_at']);
        $copy->fill([
            'invoice_number' => Invoice::nextNumber(),
            'status'         => 'draft',
            'issued_at'      => now(),
            'due_at'         => now()->addDays($termDays),
        ])->save();

        return $this->success($this->resource($copy), "Invoice duplicated as draft {$copy->invoice_number}.", 201);
    }

    /**
     * More actions → Delete draft.
     */
    public function destroy(int $id): JsonResponse
    {
        $invoice = $this->findOwned($id);
        if (! $invoice) {
            return $this->notFound('Invoice not found.');
        }

        if ($invoice->status !== 'draft') {
            return $this->error('Only draft invoices can be deleted.', 422);
        }

        $invoice->delete();

        return $this->success(null, 'Draft deleted successfully.');
    }

    /**
     * More actions → Download PDF.
     */
    public function download(int $id): Response|JsonResponse
    {
        $invoice = $this->findOwned($id);
        if (! $invoice) {
            return $this->notFound('Invoice not found.');
        }

        return $this->pdf($invoice)->download("{$invoice->invoice_number}.pdf");
    }

    /**
     * Add a customer modal: recent customers of this contractor, searchable.
     */
    public function customers(Request $request): JsonResponse
    {
        $businessId = auth('api')->id();

        $customerIds = Invoice::where('business_id', $businessId)->pluck('customer_id')
            ->merge(Project::where('business_id', $businessId)->pluck('customer_id'))
            ->merge(QuoteRequest::where('business_id', $businessId)->pluck('customer_id'))
            ->unique();

        $query = User::whereIn('id', $customerIds);

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
        }

        $lastAddresses = Invoice::where('business_id', $businessId)
            ->whereNotNull('bill_to_address')
            ->latest()
            ->get(['customer_id', 'bill_to_address'])
            ->unique('customer_id')
            ->pluck('bill_to_address', 'customer_id');

        $customers = $query->latest('updated_at')->take((int) $request->query('limit', 10))->get()
            ->map(fn (User $customer) => $this->customerPayload($customer, $lastAddresses[$customer->id] ?? null));

        return $this->success($customers, 'Customers retrieved successfully.');
    }

    /**
     * Add a new customer (name, email, phone, billing address).
     * An existing customer account with the same email is reused rather than duplicated.
     */
    public function addCustomer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'email'           => ['required', 'email', 'max:255'],
            'phone'           => ['sometimes', 'nullable', 'string', 'max:50'],
            'billing_address' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $existing = User::where('email', $validated['email'])->first();

        if ($existing && ! $existing->hasRole('customer')) {
            return $this->error('This email belongs to a non-customer account and cannot be invoiced.', 422);
        }

        $customer = $existing ?? DB::transaction(function () use ($validated) {
            Role::findOrCreate('customer', 'api');

            // Invited customers set their own password through "Forgot password".
            $customer = User::create([
                'name'     => $validated['name'],
                'email'    => $validated['email'],
                'phone'    => $validated['phone'] ?? null,
                'password' => Str::random(40),
                'status'   => 'pending',
            ]);
            $customer->assignRole('customer');
            CustomerProfile::create(['user_id' => $customer->id]);

            return $customer;
        });

        return $this->success(
            $this->customerPayload($customer, $validated['billing_address'] ?? null) + ['is_existing' => (bool) $existing],
            $existing ? 'Existing customer selected.' : 'Customer added successfully.',
            $existing ? 200 : 201
        );
    }

    private function sendDefaults(Invoice $invoice): array
    {
        $businessName = $invoice->business->businessProfile?->business_name ?? $invoice->business->name;
        $firstName = Str::before($invoice->customer->name, ' ');
        $work = $invoice->project?->title ? Str::lower($invoice->project->title) : 'recent work';

        return [
            'to'      => $invoice->customer->email,
            'subject' => "Invoice {$invoice->invoice_number} from {$businessName} — $" . number_format((float) $invoice->amount, 2)
                . ' due ' . $invoice->due_at->format('M j, Y'),
            'message' => "Hi {$firstName}, thanks for choosing {$businessName}. Please find your invoice for the {$work} attached — let me know if you have any questions.",
        ];
    }

    private function customerPayload(User $customer, ?string $billingAddress): array
    {
        return [
            'id'              => $customer->id,
            'name'            => $customer->name,
            'email'           => $customer->email,
            'phone'           => $customer->phone,
            'billing_address' => $billingAddress,
        ];
    }

    private function pdf(Invoice $invoice): \Barryvdh\DomPDF\PDF
    {
        $invoice->loadMissing(['customer', 'business.businessProfile', 'project']);
        $profile = $invoice->business->businessProfile;
        $items = $invoice->lineItems();

        return Pdf::loadView('pdf.invoice', [
            'invoice'         => $invoice,
            'business'        => $invoice->business,
            'profile'         => $profile,
            'businessAddress' => $profile?->address
                ?? collect([$profile?->city, trim(($profile?->state ?? '') . ' ' . ($profile?->zip_code ?? ''))])->filter()->join(', '),
            'items'           => $items,
            'subtotal'        => array_sum(array_column($items, 'amount')),
        ]);
    }

    private function itemsTotal(array $items): float
    {
        return round(collect($items)->sum(fn ($item) => $item['quantity'] * $item['price']), 2);
    }

    private function resource(Invoice $invoice): ContractorInvoiceResource
    {
        return new ContractorInvoiceResource($invoice->fresh(['customer', 'project']));
    }

    private function findOwned(int $id): ?Invoice
    {
        return Invoice::where('business_id', auth('api')->id())->find($id);
    }
}
