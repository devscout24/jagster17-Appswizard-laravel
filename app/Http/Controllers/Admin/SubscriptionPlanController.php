<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BillingHistory;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SubscriptionPlanController extends Controller
{
    /**
     * Display listing of membership plans.
     */
    public function index(): View
    {
        $plans = SubscriptionPlan::withCount('subscriptions')
            ->orderBy('sort_order')
            ->get();

        return view('admin.plans.index', compact('plans'));
    }

    /**
     * Show create plan form.
     */
    public function create(): View
    {
        return view('admin.plans.create');
    }

    /**
     * Store new plan.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'badge' => ['nullable', 'string', 'max:50'],
            'monthly_price' => ['required', 'numeric', 'min:0'],
            'annual_price' => ['nullable', 'numeric', 'min:0'],
            'service_limit' => ['nullable', 'integer', 'min:1'],
            'gallery_limit' => ['nullable', 'integer', 'min:0'],
            'video_limit' => ['nullable', 'integer', 'min:0'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'features' => ['required', 'string'],
            'is_popular' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $featuresArray = array_filter(array_map('trim', explode("\n", $validated['features'])));
        $slug = Str::slug($validated['name']);

        SubscriptionPlan::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'tagline' => $validated['tagline'],
            'badge' => $validated['badge'],
            'monthly_price' => $validated['monthly_price'],
            'annual_price' => $validated['annual_price'],
            'service_limit' => $validated['service_limit'],
            'gallery_limit' => $validated['gallery_limit'],
            'video_limit' => $validated['video_limit'],
            'sort_order' => $validated['sort_order'],
            'features' => $featuresArray,
            'is_popular' => $request->boolean('is_popular'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.plans.index')
            ->with('success', 'Membership plan created successfully.');
    }

    /**
     * Show edit plan form.
     */
    public function edit(int $id): View
    {
        $plan = SubscriptionPlan::findOrFail($id);

        return view('admin.plans.edit', compact('plan'));
    }

    /**
     * Update plan.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $plan = SubscriptionPlan::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'badge' => ['nullable', 'string', 'max:50'],
            'monthly_price' => ['required', 'numeric', 'min:0'],
            'annual_price' => ['nullable', 'numeric', 'min:0'],
            'service_limit' => ['nullable', 'integer', 'min:1'],
            'gallery_limit' => ['nullable', 'integer', 'min:0'],
            'video_limit' => ['nullable', 'integer', 'min:0'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'features' => ['required', 'string'],
            'is_popular' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $featuresArray = array_filter(array_map('trim', explode("\n", $validated['features'])));

        $plan->update([
            'name' => $validated['name'],
            'tagline' => $validated['tagline'],
            'badge' => $validated['badge'],
            'monthly_price' => $validated['monthly_price'],
            'annual_price' => $validated['annual_price'],
            'service_limit' => $validated['service_limit'],
            'gallery_limit' => $validated['gallery_limit'],
            'video_limit' => $validated['video_limit'],
            'sort_order' => $validated['sort_order'],
            'features' => $featuresArray,
            'is_popular' => $request->boolean('is_popular'),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.plans.index')
            ->with('success', 'Membership plan updated successfully.');
    }

    /**
     * Delete plan.
     */
    public function destroy(int $id): RedirectResponse
    {
        $plan = SubscriptionPlan::withCount('subscriptions')->findOrFail($id);

        if ($plan->subscriptions_count > 0) {
            return back()->with('error', 'Cannot delete a plan with active or past subscriptions attached.');
        }

        $plan->delete();

        return redirect()->route('admin.plans.index')
            ->with('success', 'Membership plan deleted successfully.');
    }

    /**
     * View active contractor subscriptions.
     */
    public function subscriptions(Request $request): View
    {
        $status = $request->query('status');

        $query = Subscription::with(['business.businessProfile', 'plan']);

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        $subscriptions = $query->latest()->paginate(15)->withQueryString();

        return view('admin.plans.subscriptions', compact('subscriptions', 'status'));
    }

    /**
     * View contractor billing transaction history.
     */
    public function billingHistory(Request $request): View
    {
        $query = BillingHistory::with(['subscription.business.businessProfile', 'subscription.plan']);

        $history = $query->latest('billed_at')->paginate(15)->withQueryString();

        return view('admin.plans.billing-history', compact('history'));
    }

    /**
     * Cancel active contractor subscription.
     */
    public function cancelSubscription(int $id): RedirectResponse
    {
        $subscription = Subscription::findOrFail($id);
        $subscription->update(['status' => 'cancelled']);

        return back()->with('success', 'Subscription has been cancelled.');
    }
}
