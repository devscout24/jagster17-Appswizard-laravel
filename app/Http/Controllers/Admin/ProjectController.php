<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    /**
     * Display listing of ongoing and completed projects.
     */
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $status = $request->query('status');

        $query = Project::with(['business.businessProfile', 'customer', 'quoteRequest']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn($cq) => $cq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('business.businessProfile', fn($bq) => $bq->where('business_name', 'like', "%{$search}%"));
            });
        }

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        $projects = $query->latest()->paginate(15)->withQueryString();

        return view('admin.projects.index', compact('projects', 'search', 'status'));
    }

    /**
     * Show project details.
     */
    public function show(int $id): View
    {
        $project = Project::with([
            'business.businessProfile',
            'customer.customerProfile',
            'quoteRequest',
            'invoices',
            'reviews',
        ])->findOrFail($id);

        return view('admin.projects.show', compact('project'));
    }

    /**
     * Show edit project form.
     */
    public function edit(int $id): View
    {
        $project = Project::with(['business.businessProfile', 'customer'])->findOrFail($id);

        return view('admin.projects.edit', compact('project'));
    }

    /**
     * Update project progress and details.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $project = Project::findOrFail($id);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'total_amount' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'progress_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'status' => ['required', 'in:in_progress,scheduled,pending_materials,completed,cancelled'],
            'description' => ['nullable', 'string'],
        ]);

        $project->update($validated);

        return redirect()->route('admin.projects.show', $project->id)
            ->with('success', 'Project milestone updated successfully.');
    }

    /**
     * Delete project.
     */
    public function destroy(int $id): RedirectResponse
    {
        $project = Project::findOrFail($id);
        $project->delete();

        return redirect()->route('admin.projects.index')
            ->with('success', 'Project record removed successfully.');
    }
}
