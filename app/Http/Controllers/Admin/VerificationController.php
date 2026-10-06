<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessDocument;
use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Review contractor license & insurance documents.
 */
class VerificationController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', 'pending');
        $type = $request->query('type', 'license_insurance');
        $search = $request->query('search');

        $documents = BusinessDocument::with('business.businessProfile', 'reviewer')
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($type !== 'all', fn ($q) => $q->where('type', $type))
            ->when($search, fn ($q) => $q->whereHas('business', fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhereHas('businessProfile', fn ($q) => $q->where('business_name', 'like', "%{$search}%"))))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $counts = BusinessDocument::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.verifications.index', compact('documents', 'status', 'type', 'search', 'counts'));
    }

    public function update(Request $request, BusinessDocument $document): RedirectResponse
    {
        $validated = $request->validate([
            'decision' => ['required', 'in:verified,rejected'],
            'admin_notes' => ['nullable', 'required_if:decision,rejected', 'string', 'max:1000'],
        ], ['admin_notes.required_if' => 'Tell the contractor why the document was rejected.']);

        $document->update([
            'status' => $validated['decision'],
            'admin_notes' => $validated['admin_notes'] ?? null,
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
        ]);

        if ($document->type === 'license_insurance') {
            $document->business->businessProfile?->update(['is_license_verified' => $validated['decision'] === 'verified']);
        }

        Notification::create([
            'user_id' => $document->business_id,
            'type' => 'document_review',
            'title' => $validated['decision'] === 'verified' ? 'Document verified' : 'Document needs attention',
            'body' => $validated['decision'] === 'verified'
                ? 'Your ' . BusinessDocument::TYPES[$document->type] . ' document has been verified.'
                : 'Your ' . BusinessDocument::TYPES[$document->type] . ' document was rejected: ' . $validated['admin_notes'],
            'data' => ['document_id' => $document->id],
        ]);

        return back()->with('success', $validated['decision'] === 'verified' ? 'Document verified.' : 'Document rejected and the contractor was notified.');
    }
}
