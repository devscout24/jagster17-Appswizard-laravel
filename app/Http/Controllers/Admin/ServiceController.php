<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    /**
     * Display listing of contractor services.
     */
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $categoryId = $request->query('category_id');
        $pricingType = $request->query('pricing_type');
        $status = $request->query('status');

        $query = Service::with(['business.businessProfile', 'category']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('business.businessProfile', function ($bq) use ($search) {
                        $bq->where('business_name', 'like', "%{$search}%");
                    });
            });
        }

        if ($categoryId && $categoryId !== 'all') {
            $query->where('category_id', $categoryId);
        }

        if ($pricingType && $pricingType !== 'all') {
            $query->where('pricing_type', $pricingType);
        }

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        $services = $query->latest()->paginate(15)->withQueryString();
        $categories = Category::where('type', 'service')->get();

        return view('admin.services.index', compact('services', 'categories', 'search', 'categoryId', 'pricingType', 'status'));
    }

    /**
     * Show service details.
     */
    public function show(int $id): View
    {
        $service = Service::with(['business.businessProfile', 'category'])->findOrFail($id);

        return view('admin.services.show', compact('service'));
    }

    /**
     * Show edit service form.
     */
    public function edit(int $id): View
    {
        $service = Service::with(['business.businessProfile', 'category'])->findOrFail($id);
        $categories = Category::where('type', 'service')->get();

        return view('admin.services.edit', compact('service', 'categories'));
    }

    /**
     * Update service details.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $service = Service::findOrFail($id);

        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'pricing_type' => ['required', 'in:fixed,hourly,sqft,custom_quote'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $service->update($validated);

        return redirect()->route('admin.services.index')
            ->with('success', 'Service updated successfully.');
    }

    /**
     * Delete service.
     */
    public function destroy(int $id): RedirectResponse
    {
        $service = Service::findOrFail($id);
        $service->delete();

        return redirect()->route('admin.services.index')
            ->with('success', 'Service removed successfully.');
    }
}
