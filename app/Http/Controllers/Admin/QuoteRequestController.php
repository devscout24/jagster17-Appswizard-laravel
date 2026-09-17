<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\QuoteRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuoteRequestController extends Controller
{
    /**
     * Display listing of quote requests and leads.
     */
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $status = $request->query('status');
        $categoryId = $request->query('category_id');
        $timeline = $request->query('timeline');

        $query = QuoteRequest::with(['business.businessProfile', 'customer', 'category']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                    ->orWhere('project_title', 'like', "%{$search}%")
                    ->orWhere('street_address', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn($cq) => $cq->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                    ->orWhereHas('business.businessProfile', fn($bq) => $bq->where('business_name', 'like', "%{$search}%"));
            });
        }

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($categoryId && $categoryId !== 'all') {
            $query->where('category_id', $categoryId);
        }

        if ($timeline && $timeline !== 'all') {
            $query->where('timeline', $timeline);
        }

        $quotes = $query->latest('requested_at')->paginate(15)->withQueryString();
        $categories = Category::where('type', 'service')->get();

        return view('admin.quotes.index', compact('quotes', 'categories', 'search', 'status', 'categoryId', 'timeline'));
    }

    /**
     * Show quote request details.
     */
    public function show(int $id): View
    {
        $quote = QuoteRequest::with([
            'business.businessProfile',
            'customer.customerProfile',
            'category',
        ])->findOrFail($id);

        return view('admin.quotes.show', compact('quote'));
    }

    /**
     * Show edit quote request form.
     */
    public function edit(int $id): View
    {
        $quote = QuoteRequest::with(['business.businessProfile', 'customer', 'category'])->findOrFail($id);
        $contractors = User::whereHas('roles', fn($q) => $q->where('name', 'business'))
            ->with('businessProfile')
            ->get();
        $categories = Category::where('type', 'service')->get();

        return view('admin.quotes.edit', compact('quote', 'contractors', 'categories'));
    }

    /**
     * Update quote request.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $quote = QuoteRequest::findOrFail($id);

        $validated = $request->validate([
            'business_id' => ['nullable', 'exists:users,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'project_title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'timeline' => ['nullable', 'in:asap,within_1_week,within_1_month,flexible'],
            'quote_amount' => ['nullable', 'numeric', 'min:0'],
            'labor_cost' => ['nullable', 'numeric', 'min:0'],
            'materials_cost' => ['nullable', 'numeric', 'min:0'],
            'tax_cost' => ['nullable', 'numeric', 'min:0'],
            'estimated_duration' => ['nullable', 'string', 'max:100'],
            'contractor_notes' => ['nullable', 'string'],
            'status' => ['required', 'in:new,pending,quoted,accepted,declined,rejected,expired'],
        ]);

        $quote->update($validated);

        return redirect()->route('admin.quotes.show', $quote->id)
            ->with('success', 'Quote request updated successfully.');
    }

    /**
     * Delete quote request.
     */
    public function destroy(int $id): RedirectResponse
    {
        $quote = QuoteRequest::findOrFail($id);
        $quote->delete();

        return redirect()->route('admin.quotes.index')
            ->with('success', 'Quote request removed successfully.');
    }
}
