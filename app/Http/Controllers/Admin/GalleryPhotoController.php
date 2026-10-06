<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GalleryPhoto;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Moderate contractor gallery photos (hide inappropriate content).
 */
class GalleryPhotoController extends Controller
{
    public function index(Request $request): View
    {
        $businessId = $request->query('business_id');
        $status = $request->query('status', 'all');

        $photos = GalleryPhoto::with('business.businessProfile')
            ->when($businessId, fn ($q) => $q->where('business_id', $businessId))
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(24)
            ->withQueryString();

        $contractors = User::whereIn('id', GalleryPhoto::distinct()->pluck('business_id'))
            ->with('businessProfile')
            ->get();

        return view('admin.gallery.index', compact('photos', 'contractors', 'businessId', 'status'));
    }

    public function toggle(GalleryPhoto $photo): RedirectResponse
    {
        $photo->update(['status' => $photo->status === 'published' ? 'hidden' : 'published']);

        return back()->with('success', $photo->status === 'published' ? 'Photo is visible again.' : 'Photo hidden from the public profile.');
    }

    public function destroy(GalleryPhoto $photo): RedirectResponse
    {
        if (! str_starts_with($photo->image_path, 'http')) {
            Storage::disk('public')->delete($photo->image_path);
        }
        $photo->delete();

        return back()->with('success', 'Photo deleted.');
    }
}
