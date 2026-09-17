<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerPayment;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    /**
     * Display listing of invoices.
     */
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $status = $request->query('status');

        $query = Invoice::with(['business.businessProfile', 'customer', 'project']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn($cq) => $cq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('business.businessProfile', fn($bq) => $bq->where('business_name', 'like', "%{$search}%"));
            });
        }

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        $invoices = $query->latest('issued_at')->paginate(15)->withQueryString();

        $totalPaid = (float) Invoice::where('status', 'paid')->sum('amount');
        $totalPending = (float) Invoice::where('status', 'pending')->sum('amount');
        $totalOverdue = (float) Invoice::where('status', 'overdue')->sum('amount');
        $totalPlatformFees = (float) Invoice::where('status', 'paid')->sum('platform_fee');

        return view('admin.invoices.index', compact('invoices', 'search', 'status', 'totalPaid', 'totalPending', 'totalOverdue', 'totalPlatformFees'));
    }

    /**
     * Show invoice details & printable layout.
     */
    public function show(int $id): View
    {
        $invoice = Invoice::with([
            'business.businessProfile',
            'customer.customerProfile',
            'project',
        ])->findOrFail($id);

        $customerPayment = CustomerPayment::where('invoice_id', $invoice->id)->first();

        return view('admin.invoices.show', compact('invoice', 'customerPayment'));
    }

    /**
     * Show edit invoice form.
     */
    public function edit(int $id): View
    {
        $invoice = Invoice::with(['business.businessProfile', 'customer', 'project'])->findOrFail($id);

        return view('admin.invoices.edit', compact('invoice'));
    }

    /**
     * Update invoice details & status.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $invoice = Invoice::findOrFail($id);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'labor_amount' => ['nullable', 'numeric', 'min:0'],
            'materials_amount' => ['nullable', 'numeric', 'min:0'],
            'platform_fee' => ['nullable', 'numeric', 'min:0'],
            'issued_at' => ['required', 'date'],
            'due_at' => ['required', 'date'],
            'paid_at' => ['nullable', 'date'],
            'status' => ['required', 'in:draft,pending,paid,overdue'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($validated['status'] === 'paid' && empty($validated['paid_at'])) {
            $validated['paid_at'] = now();
        }

        $invoice->update($validated);

        return redirect()->route('admin.invoices.show', $invoice->id)
            ->with('success', 'Invoice updated successfully.');
    }

    /**
     * Customer Payments transaction log.
     */
    public function payments(Request $request): View
    {
        $search = $request->query('search');
        $status = $request->query('status');

        $query = CustomerPayment::with(['user', 'business.businessProfile', 'invoice']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%")
                    ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('business.businessProfile', fn($bq) => $bq->where('business_name', 'like', "%{$search}%"));
            });
        }

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        $payments = $query->latest('paid_at')->paginate(15)->withQueryString();

        return view('admin.invoices.payments', compact('payments', 'search', 'status'));
    }

    /**
     * Delete invoice.
     */
    public function destroy(int $id): RedirectResponse
    {
        $invoice = Invoice::findOrFail($id);
        $invoice->delete();

        return redirect()->route('admin.invoices.index')
            ->with('success', 'Invoice removed successfully.');
    }
}
