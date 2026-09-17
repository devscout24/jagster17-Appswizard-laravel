<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ContractorController extends Controller
{
    /**
     * Display a listing of contractors.
     */
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $status = $request->query('status');
        $isElite = $request->query('is_elite');
        $isVerified = $request->query('is_id_verified');
        $isVeteran = $request->query('is_veteran_owned');
        $state = $request->query('state');

        $query = User::whereHas('roles', fn($q) => $q->where('name', 'business'))
            ->with(['businessProfile', 'subscription.plan'])
            ->withCount(['services', 'products', 'quoteRequests', 'projects', 'invoices', 'reviews']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('businessProfile', function ($bq) use ($search) {
                        $bq->where('business_name', 'like', "%{$search}%")
                            ->orWhere('license_number', 'like', "%{$search}%")
                            ->orWhere('city', 'like', "%{$search}%");
                    });
            });
        }

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($isElite !== null && $isElite !== '') {
            $query->whereHas('businessProfile', fn($q) => $q->where('is_elite', (bool) $isElite));
        }

        if ($isVerified !== null && $isVerified !== '') {
            $query->whereHas('businessProfile', fn($q) => $q->where('is_id_verified', (bool) $isVerified));
        }

        if ($isVeteran !== null && $isVeteran !== '') {
            $query->whereHas('businessProfile', fn($q) => $q->where('is_veteran_owned', (bool) $isVeteran));
        }

        if ($state && $state !== 'all') {
            $query->whereHas('businessProfile', fn($q) => $q->where('state', $state));
        }

        $contractors = $query->latest()->paginate(12)->withQueryString();
        $states = BusinessProfile::whereNotNull('state')->distinct()->pluck('state');

        return view('admin.contractors.index', compact('contractors', 'states', 'search', 'status', 'isElite', 'isVerified', 'isVeteran', 'state'));
    }

    /**
     * Display contractor details.
     */
    public function show(int $id): View
    {
        $contractor = User::whereHas('roles', fn($q) => $q->where('name', 'business'))
            ->with([
                'businessProfile',
                'subscription.plan',
                'services.category',
                'products.category',
                'quoteRequests.customer',
                'projects.customer',
                'invoices.customer',
                'reviews.customer',
            ])
            ->findOrFail($id);

        $totalEarnings = (float) $contractor->invoices()->where('status', 'paid')->sum('amount');
        $totalPlatformFees = (float) $contractor->invoices()->where('status', 'paid')->sum('platform_fee');

        return view('admin.contractors.show', compact('contractor', 'totalEarnings', 'totalPlatformFees'));
    }

    /**
     * Show edit form for contractor.
     */
    public function edit(int $id): View
    {
        $contractor = User::whereHas('roles', fn($q) => $q->where('name', 'business'))
            ->with('businessProfile')
            ->findOrFail($id);

        return view('admin.contractors.edit', compact('contractor'));
    }

    /**
     * Update contractor information & verification badges.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $contractor = User::whereHas('roles', fn($q) => $q->where('name', 'business'))
            ->with('businessProfile')
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $contractor->id],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'in:active,inactive'],
            'business_name' => ['required', 'string', 'max:255'],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'business_type' => ['nullable', 'string', 'max:100'],
            'tax_id' => ['nullable', 'string', 'max:100'],
            'license_number' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:50'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'years_experience' => ['nullable', 'integer', 'min:0', 'max:100'],
            'service_radius' => ['nullable', 'integer', 'min:1'],
            'business_hours' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string'],
            'is_elite' => ['nullable', 'boolean'],
            'is_veteran_owned' => ['nullable', 'boolean'],
            'is_id_verified' => ['nullable', 'boolean'],
            'is_license_verified' => ['nullable', 'boolean'],
            'is_available_today' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($contractor, $validated, $request) {
            $contractor->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'status' => $validated['status'],
            ]);

            $profileData = [
                'business_name' => $validated['business_name'],
                'owner_name' => $validated['owner_name'],
                'business_type' => $validated['business_type'],
                'phone_number' => $validated['phone'],
                'tax_id' => $validated['tax_id'],
                'license_number' => $validated['license_number'],
                'city' => $validated['city'],
                'state' => $validated['state'],
                'zip_code' => $validated['zip_code'],
                'hourly_rate' => $validated['hourly_rate'],
                'years_experience' => $validated['years_experience'],
                'service_radius' => $validated['service_radius'],
                'business_hours' => $validated['business_hours'],
                'bio' => $validated['bio'],
                'is_elite' => $request->boolean('is_elite'),
                'is_veteran_owned' => $request->boolean('is_veteran_owned'),
                'is_id_verified' => $request->boolean('is_id_verified'),
                'is_license_verified' => $request->boolean('is_license_verified'),
                'is_available_today' => $request->boolean('is_available_today'),
            ];

            if ($contractor->businessProfile) {
                $contractor->businessProfile->update($profileData);
            } else {
                BusinessProfile::create(array_merge(['user_id' => $contractor->id], $profileData));
            }
        });

        return redirect()->route('admin.contractors.show', $contractor->id)
            ->with('success', 'Contractor profile and credentials updated successfully.');
    }

    /**
     * Quick toggle for contractor badges (AJAX / POST).
     */
    public function toggleBadge(Request $request, int $id): RedirectResponse
    {
        $contractor = User::with('businessProfile')->findOrFail($id);
        $badge = $request->input('badge');

        if (!in_array($badge, ['is_elite', 'is_veteran_owned', 'is_id_verified', 'is_license_verified', 'is_available_today'])) {
            return back()->with('error', 'Invalid badge type.');
        }

        if ($contractor->businessProfile) {
            $contractor->businessProfile->update([
                $badge => !$contractor->businessProfile->{$badge},
            ]);
        }

        return back()->with('success', 'Verification badge updated successfully.');
    }

    /**
     * Delete contractor account.
     */
    public function destroy(int $id): RedirectResponse
    {
        $contractor = User::whereHas('roles', fn($q) => $q->where('name', 'business'))->findOrFail($id);
        $contractor->delete();

        return redirect()->route('admin.contractors.index')
            ->with('success', 'Contractor account has been permanently removed.');
    }
}
