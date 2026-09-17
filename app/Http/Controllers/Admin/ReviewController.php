<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessProfile;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    /**
     * Display listing of client reviews.
     */
    public function index(Request $request): View
    {
        $rating = $request->query('rating');
        $featured = $request->query('is_featured');
        $search = $request->query('search');

        $query = Review::with(['business.businessProfile', 'customer', 'project']);

        if ($rating && $rating !== 'all') {
            $query->where('rating', $rating);
        }

        if ($featured !== null && $featured !== '') {
            $query->where('is_featured', (bool) $featured);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('comment', 'like', "%{$search}%")
                    ->orWhere('contractor_reply', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn($cq) => $cq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('business.businessProfile', fn($bq) => $bq->where('business_name', 'like', "%{$search}%"));
            });
        }

        $reviews = $query->latest()->paginate(15)->withQueryString();

        return view('admin.reviews.index', compact('reviews', 'rating', 'featured', 'search'));
    }

    /**
     * Show review details.
     */
    public function show(int $id): View
    {
        $review = Review::with(['business.businessProfile', 'customer.customerProfile', 'project'])->findOrFail($id);

        return view('admin.reviews.show', compact('review'));
    }

    /**
     * Toggle featured status for testimonials.
     */
    public function toggleFeatured(int $id): RedirectResponse
    {
        $review = Review::findOrFail($id);
        $review->update(['is_featured' => !$review->is_featured]);

        $statusMsg = $review->is_featured ? 'marked as featured testimonial' : 'removed from featured';

        return back()->with('success', "Review has been {$statusMsg}.");
    }

    /**
     * Delete inappropriate review and recalculate contractor's rating.
     */
    public function destroy(int $id): RedirectResponse
    {
        $review = Review::findOrFail($id);
        $businessId = $review->business_id;
        $review->delete();

        // Recalculate average rating for business
        $profile = BusinessProfile::where('user_id', $businessId)->first();
        if ($profile) {
            $newAvg = Review::where('business_id', $businessId)->avg('rating') ?? 0;
            $newCount = Review::where('business_id', $businessId)->count();
            $profile->update([
                'avg_rating' => round($newAvg, 2),
                'review_count' => $newCount,
            ]);
        }

        return redirect()->route('admin.reviews.index')
            ->with('success', 'Review deleted and contractor rating recalculated.');
    }
}
