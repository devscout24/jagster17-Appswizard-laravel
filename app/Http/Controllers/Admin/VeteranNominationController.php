<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VeteranNomination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;


class VeteranNominationController extends Controller
{
    /**
     * Display listing of veteran nominations (Giving Back program).
     */
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $status = $request->query('status');

        $query = VeteranNomination::with('user');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nominee_name', 'like', "%{$search}%")
                    ->orWhere('nominator_name', 'like', "%{$search}%")
                    ->orWhere('nominee_branch', 'like', "%{$search}%")
                    ->orWhere('nominee_city', 'like', "%{$search}%")
                    ->orWhere('project_needed', 'like', "%{$search}%");
            });
        }

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        $nominations = $query->latest()->paginate(15)->withQueryString();

        return view('admin.veteran-nominations.index', compact('nominations', 'search', 'status'));
    }

    /**
     * Show nomination details.
     */
    public function show(int $id): View
    {
        $nomination = VeteranNomination::with('user')->findOrFail($id);

        return view('admin.veteran-nominations.show', compact('nomination'));
    }

    /**
     * Update nomination review status.
     */
    public function updateStatus(Request $request, int $id): RedirectResponse
    {
        $nomination = VeteranNomination::findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'in:pending,reviewing,approved,rejected'],
        ]);

        $nomination->update(['status' => $validated['status']]);

        return back()->with('success', "Nomination status updated to {$validated['status']}.");
    }

    /**
     * Delete nomination.
     */
    public function destroy(int $id): RedirectResponse
    {
        $nomination = VeteranNomination::findOrFail($id);
        $nomination->delete();

        return redirect()->route('admin.veteran-nominations.index')
            ->with('success', 'Nomination record removed successfully.');
    }
}
