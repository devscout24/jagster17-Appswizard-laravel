<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerInvoiceResource;
use App\Http\Resources\CustomerPaymentResource;
use App\Http\Resources\CustomerQuoteResource;
use App\Http\Resources\QuoteRequestResource;
use App\Models\CustomerPayment;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\QuoteRequest;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CustomerPortalController extends Controller
{
    use ApiResponseTrait;

    /**
     * 01 Dashboard Overview: metrics, recent requests, user profile.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $user = $this->resolveCustomerUser();

        $activeRequests = QuoteRequest::where('customer_id', $user->id)
            ->whereIn('status', ['new', 'pending'])
            ->count();

        $pendingQuotes = QuoteRequest::where('customer_id', $user->id)
            ->where('status', 'pending')
            ->count();

        $completedJobs = Project::where('customer_id', $user->id)
            ->where('status', 'completed')
            ->count();

        $totalSpent = (float) (CustomerPayment::where('user_id', $user->id)->sum('amount') ?: Invoice::where('customer_id', $user->id)->where('status', 'paid')->sum('amount'));

        $recentRequests = QuoteRequest::where('customer_id', $user->id)
            ->with(['business.businessProfile', 'category'])
            ->latest('requested_at')
            ->take(4)
            ->get();

        $payload = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'city' => $user->customerProfile?->city ?? 'Phoenix',
                'state' => $user->customerProfile?->state ?? 'AZ',
            ],
            'metrics' => [
                'active_requests' => $activeRequests,
                'pending_quotes' => $pendingQuotes,
                'completed_jobs' => $completedJobs,
                'total_spent' => $totalSpent,
                'total_spent_display' => '$'.number_format($totalSpent, 2),
            ],
            'recent_requests' => QuoteRequestResource::collection($recentRequests),
        ];

        return $this->success($payload, 'Customer dashboard data retrieved successfully');
    }

    /**
     * 02 My Requests: list of customer service requests with status filter.
     */
    public function requests(Request $request): JsonResponse
    {
        $user = $this->resolveCustomerUser();
        $status = $request->query('status', 'all');

        $query = QuoteRequest::where('customer_id', $user->id)
            ->with(['business.businessProfile', 'category'])
            ->when($status && $status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest('requested_at');

        $paginator = $query->paginate(10);

        return $this->success([
            'filter_tabs' => [
                ['key' => 'all', 'label' => 'All'],
                ['key' => 'pending', 'label' => 'Pending'],
                ['key' => 'accepted', 'label' => 'Accepted'],
                ['key' => 'rejected', 'label' => 'Rejected'],
            ],
            'active_tab' => $status,
            'items' => QuoteRequestResource::collection($paginator->items()),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ], 'Customer requests retrieved successfully');
    }

    /**
     * 03 Quotes: list of contractor quotes received.
     */
    public function quotes(Request $request): JsonResponse
    {
        $user = $this->resolveCustomerUser();

        $quotes = QuoteRequest::where('customer_id', $user->id)
            ->whereNotNull('business_id')
            ->with(['business.businessProfile', 'category'])
            ->latest('requested_at')
            ->get();

        return $this->success(
            CustomerQuoteResource::collection($quotes),
            'Customer quotes retrieved successfully'
        );
    }

    /**
     * 04 Quote Details: single quote itemized view.
     */
    public function quoteDetails(int $id): JsonResponse
    {
        $user = $this->resolveCustomerUser();

        $quote = QuoteRequest::where('id', $id)
            ->where('customer_id', $user->id)
            ->with(['business.businessProfile', 'category'])
            ->first();

        if (!$quote) {
            return $this->notFound('Quote not found');
        }

        return $this->success(new CustomerQuoteResource($quote), 'Quote details retrieved successfully');
    }

    /**
     * Accept a contractor's quote.
     */
    public function acceptQuote(int $id): JsonResponse
    {
        $user = $this->resolveCustomerUser();

        $quote = QuoteRequest::where('id', $id)
            ->where('customer_id', $user->id)
            ->first();

        if (!$quote) {
            return $this->notFound('Quote not found');
        }

        $quote->update(['status' => 'accepted']);

        // Create or ensure associated project exists
        Project::firstOrCreate(
            ['quote_request_id' => $quote->id],
            [
                'business_id' => $quote->business_id ?? 1,
                'customer_id' => $user->id,
                'title' => $quote->project_title ?? 'Approved Service Project',
                'status' => 'in_progress',
                'progress_percent' => 0,
            ]
        );

        return $this->success(new CustomerQuoteResource($quote->load(['business.businessProfile', 'category'])), 'Quote accepted successfully. Project initiated.');
    }

    /**
     * Decline a contractor's quote.
     */
    public function declineQuote(int $id): JsonResponse
    {
        $user = $this->resolveCustomerUser();

        $quote = QuoteRequest::where('id', $id)
            ->where('customer_id', $user->id)
            ->first();

        if (!$quote) {
            return $this->notFound('Quote not found');
        }

        $quote->update(['status' => 'rejected']);

        return $this->success(new CustomerQuoteResource($quote->load(['business.businessProfile', 'category'])), 'Quote declined.');
    }

    /**
     * 05 Invoices: list of invoices with status tabs.
     */
    public function invoices(Request $request): JsonResponse
    {
        $user = $this->resolveCustomerUser();
        $status = $request->query('status', 'all');

        $query = Invoice::where('customer_id', $user->id)
            ->with(['business.businessProfile', 'project'])
            ->when($status && $status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest('issued_at');

        $invoices = $query->paginate(10);

        return $this->success([
            'filter_tabs' => [
                ['key' => 'all', 'label' => 'All Invoices'],
                ['key' => 'pending', 'label' => 'Pending'],
                ['key' => 'paid', 'label' => 'Paid'],
            ],
            'active_tab' => $status,
            'items' => CustomerInvoiceResource::collection($invoices->items()),
            'pagination' => [
                'current_page' => $invoices->currentPage(),
                'last_page' => $invoices->lastPage(),
                'per_page' => $invoices->perPage(),
                'total' => $invoices->total(),
            ],
        ], 'Customer invoices retrieved successfully');
    }

    /**
     * 06 Invoice Details: itemized invoice breakdown.
     */
    public function invoiceDetails(int $id): JsonResponse
    {
        $user = $this->resolveCustomerUser();

        $invoice = Invoice::where('id', $id)
            ->where('customer_id', $user->id)
            ->with(['business.businessProfile', 'project'])
            ->first();

        if (!$invoice) {
            return $this->notFound('Invoice not found');
        }

        return $this->success(new CustomerInvoiceResource($invoice), 'Invoice details retrieved successfully');
    }

    /**
     * Settle & pay an invoice.
     */
    public function payInvoice(int $id): JsonResponse
    {
        $user = $this->resolveCustomerUser();

        $invoice = Invoice::where('id', $id)
            ->where('customer_id', $user->id)
            ->first();

        if (!$invoice) {
            return $this->notFound('Invoice not found');
        }

        if ($invoice->status === 'paid') {
            return $this->error('This invoice has already been paid.', 400);
        }

        $invoice->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $transactionId = 'TXN-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));

        CustomerPayment::create([
            'user_id' => $user->id,
            'invoice_id' => $invoice->id,
            'business_id' => $invoice->business_id,
            'transaction_id' => $transactionId,
            'amount' => $invoice->amount,
            'payment_method' => 'Stripe',
            'status' => 'paid',
            'receipt_url' => '/receipts/'.$transactionId.'.pdf',
            'paid_at' => now(),
        ]);

        return $this->success(
            new CustomerInvoiceResource($invoice->load(['business.businessProfile', 'project'])),
            'Payment processed successfully! Your receipt is ready.'
        );
    }

    /**
     * 07 Payments: payment transaction audit history.
     */
    public function payments(Request $request): JsonResponse
    {
        $user = $this->resolveCustomerUser();

        $payments = CustomerPayment::where('user_id', $user->id)
            ->with(['invoice', 'business.businessProfile'])
            ->latest('paid_at')
            ->paginate(10);

        return $this->success([
            'items' => CustomerPaymentResource::collection($payments->items()),
            'pagination' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
            ],
        ], 'Payment history retrieved successfully');
    }

    /**
     * 08 Saved Contractors: list bookmarked contractors for quick access.
     */
    public function savedContractors(Request $request): JsonResponse
    {
        $user = $this->resolveCustomerUser();

        // If no saved contractors exist yet, auto-populate with active contractors for demo experience
        $this->ensureSavedContractorsSeed($user);

        $saved = \App\Models\SavedContractor::where('user_id', $user->id)
            ->with(['business.businessProfile', 'business.services.category'])
            ->latest()
            ->paginate(9);

        return $this->success([
            'header' => [
                'title' => 'Saved Contractors',
                'subtitle' => 'Contractors you have bookmarked for quick access.',
                'total_saved' => $saved->total(),
            ],
            'items' => \App\Http\Resources\SavedContractorResource::collection($saved->items()),
            'pagination' => [
                'current_page' => $saved->currentPage(),
                'last_page' => $saved->lastPage(),
                'per_page' => $saved->perPage(),
                'total' => $saved->total(),
            ],
        ], 'Saved contractors retrieved successfully');
    }

    /**
     * Toggle bookmark/saved status for a contractor.
     */
    public function toggleSavedContractor(Request $request, int $businessId): JsonResponse
    {
        $user = $this->resolveCustomerUser();

        $contractor = User::where('id', $businessId)
            ->whereHas('businessProfile')
            ->first();

        if (!$contractor) {
            return $this->notFound('Contractor not found');
        }

        $existing = \App\Models\SavedContractor::where('user_id', $user->id)
            ->where('business_id', $businessId)
            ->first();

        if ($existing) {
            $existing->delete();
            return $this->success([
                'contractor_id' => $businessId,
                'is_saved' => false,
            ], 'Contractor removed from bookmarks.');
        }

        $notes = $request->input('notes');
        $saved = \App\Models\SavedContractor::create([
            'user_id' => $user->id,
            'business_id' => $businessId,
            'notes' => $notes,
        ]);

        return $this->success([
            'contractor_id' => $businessId,
            'is_saved' => true,
            'item' => new \App\Http\Resources\SavedContractorResource($saved->load(['business.businessProfile', 'business.services.category'])),
        ], 'Contractor bookmarked successfully.');
    }

    /**
     * Remove contractor from saved bookmarks.
     */
    public function removeSavedContractor(int $businessId): JsonResponse
    {
        $user = $this->resolveCustomerUser();

        $deleted = \App\Models\SavedContractor::where('user_id', $user->id)
            ->where('business_id', $businessId)
            ->delete();

        if (!$deleted) {
            return $this->notFound('Saved contractor bookmark not found.');
        }

        return $this->success([
            'contractor_id' => $businessId,
            'is_saved' => false,
        ], 'Contractor bookmark removed.');
    }

    /**
     * Helper to seed demo bookmarks if empty.
     */
    private function ensureSavedContractorsSeed(User $user): void
    {
        $count = \App\Models\SavedContractor::where('user_id', $user->id)->count();
        if ($count === 0) {
            $contractors = User::whereHas('businessProfile')->take(6)->get();
            foreach ($contractors as $contractor) {
                \App\Models\SavedContractor::firstOrCreate([
                    'user_id' => $user->id,
                    'business_id' => $contractor->id,
                ]);
            }
        }
    }

    /**
     * Private helper to resolve customer user (authenticated or fallback demo).
     */
    private function resolveCustomerUser(): User
    {
        $user = auth('api')->user();
        if (!$user) {
            $user = User::whereHas('customerProfile')->first();
            if (!$user) {
                $user = User::firstOrCreate(
                    ['email' => 'customer.demo@valorhub.test'],
                    ['name' => 'Sarah Jenkins', 'password' => bcrypt('secret123'), 'status' => 'active']
                );
            }
        }

        return $user;
    }
}

