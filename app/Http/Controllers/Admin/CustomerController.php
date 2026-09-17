<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CustomerController extends Controller
{
    /**
     * Display listing of customer users.
     */
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $status = $request->query('status');

        $query = User::whereHas('roles', fn($q) => $q->where('name', 'customer'))
            ->with('customerProfile')
            ->withCount(['customerQuoteRequests', 'customerProjects', 'customerInvoices', 'customerReviews']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('customerProfile', function ($cq) use ($search) {
                        $cq->where('city', 'like', "%{$search}%")
                            ->orWhere('state', 'like', "%{$search}%");
                    });
            });
        }

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        $customers = $query->latest()->paginate(12)->withQueryString();

        return view('admin.customers.index', compact('customers', 'search', 'status'));
    }

    /**
     * Show customer details.
     */
    public function show(int $id): View
    {
        $customer = User::whereHas('roles', fn($q) => $q->where('name', 'customer'))
            ->with([
                'customerProfile',
                'customerQuoteRequests.business.businessProfile',
                'customerQuoteRequests.category',
                'customerProjects.business.businessProfile',
                'customerInvoices.business.businessProfile',
                'customerReviews.business.businessProfile',
                'customerPayments',
            ])
            ->findOrFail($id);

        $totalSpent = (float) $customer->customerInvoices()->where('status', 'paid')->sum('amount');

        return view('admin.customers.show', compact('customer', 'totalSpent'));
    }

    /**
     * Show customer edit form.
     */
    public function edit(int $id): View
    {
        $customer = User::whereHas('roles', fn($q) => $q->where('name', 'customer'))
            ->with('customerProfile')
            ->findOrFail($id);

        return view('admin.customers.edit', compact('customer'));
    }

    /**
     * Update customer profile.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $customer = User::whereHas('roles', fn($q) => $q->where('name', 'customer'))
            ->with('customerProfile')
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $customer->id],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'in:active,inactive'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:50'],
        ]);

        DB::transaction(function () use ($customer, $validated) {
            $customer->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'status' => $validated['status'],
            ]);

            if ($customer->customerProfile) {
                $customer->customerProfile->update([
                    'city' => $validated['city'],
                    'state' => $validated['state'],
                ]);
            } else {
                CustomerProfile::create([
                    'user_id' => $customer->id,
                    'city' => $validated['city'],
                    'state' => $validated['state'],
                ]);
            }
        });

        return redirect()->route('admin.customers.show', $customer->id)
            ->with('success', 'Customer profile updated successfully.');
    }

    /**
     * Delete customer account.
     */
    public function destroy(int $id): RedirectResponse
    {
        $customer = User::whereHas('roles', fn($q) => $q->where('name', 'customer'))->findOrFail($id);
        $customer->delete();

        return redirect()->route('admin.customers.index')
            ->with('success', 'Customer account has been permanently removed.');
    }
}
