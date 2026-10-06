<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Approve Elite contractors' homepage rotating banners.
 */
class BannerController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', 'pending');

        $banners = Banner::with('business.businessProfile', 'reviewer')
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $counts = Banner::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.banners.index', compact('banners', 'status', 'counts'));
    }

    public function update(Request $request, Banner $banner): RedirectResponse
    {
        $validated = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'rejection_reason' => ['nullable', 'required_if:decision,rejected', 'string', 'max:255'],
        ], ['rejection_reason.required_if' => 'Give a reason so the contractor can fix the banner.']);

        $banner->update([
            'status' => $validated['decision'],
            'rejection_reason' => $validated['decision'] === 'rejected' ? $validated['rejection_reason'] : null,
            'approved_at' => $validated['decision'] === 'approved' ? now() : null,
            'reviewed_by' => auth()->id(),
        ]);

        Notification::create([
            'user_id' => $banner->business_id,
            'type' => 'banner_review',
            'title' => $validated['decision'] === 'approved' ? 'Your banner is live' : 'Banner not approved',
            'body' => $validated['decision'] === 'approved'
                ? 'Your homepage banner was approved and is now in the rotation.'
                : 'Your homepage banner was not approved: ' . $validated['rejection_reason'],
            'data' => ['banner_id' => $banner->id],
        ]);

        return back()->with('success', $validated['decision'] === 'approved' ? 'Banner approved and now live on the homepage.' : 'Banner rejected and the contractor was notified.');
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        if (! str_starts_with($banner->image_path, 'http')) {
            Storage::disk('public')->delete($banner->image_path);
        }
        $banner->delete();

        return back()->with('success', 'Banner deleted.');
    }
}
