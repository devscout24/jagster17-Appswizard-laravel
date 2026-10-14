<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreReviewRequest;
use App\Models\BusinessProfile;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Review;
use App\Traits\ApiResponseTrait;
use App\Traits\HandlesUploads;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CustomerReviewController extends Controller
{
    use ApiResponseTrait, HandlesUploads;

    /**
     * 09 Reviews: completed jobs awaiting feedback, plus past reviews (Node 2872-1242).
     */
    public function index(): JsonResponse
    {
        $customerId = auth('api')->id();

        $awaiting = Project::where('customer_id', $customerId)
            ->where('status', 'completed')
            ->whereDoesntHave('reviews', fn ($q) => $q->where('customer_id', $customerId))
            ->with('business.businessProfile')
            ->latest('updated_at')
            ->get()
            ->map(fn (Project $project) => [
                'project_id'    => $project->id,
                'project_title' => $project->title,
                'business'      => $this->businessPayload($project->business),
                'completed_at'  => $project->updated_at?->format('M j, Y'),
                'subtitle'      => "{$project->title} · Completed " . $project->updated_at?->format('M j, Y'),
            ]);

        $past = Review::where('customer_id', $customerId)
            ->with(['business.businessProfile', 'project'])
            ->latest()
            ->get()
            ->map(fn (Review $review) => $this->reviewPayload($review));

        return $this->success([
            'awaiting_feedback' => $awaiting,
            'past_reviews'      => $past,
        ], 'Reviews retrieved successfully.');
    }

    /**
     * Leave feedback for a completed job (one review per project).
     */
    public function store(StoreReviewRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $customerId = auth('api')->id();

        $project = Project::where('customer_id', $customerId)->find($validated['project_id']);
        if (! $project) {
            return $this->notFound('Project not found.');
        }

        if ($project->status !== 'completed') {
            return $this->error('You can review a job once it has been completed.', 422);
        }

        if (Review::where('customer_id', $customerId)->where('project_id', $project->id)->exists()) {
            return $this->error('You have already reviewed this job.', 422);
        }

        $review = DB::transaction(function () use ($project, $customerId, $validated) {
            $review = Review::create([
                'business_id' => $project->business_id,
                'customer_id' => $customerId,
                'project_id'  => $project->id,
                'rating'      => $validated['rating'],
                'comment'     => $validated['comment'] ?? null,
            ]);

            $stats = Review::where('business_id', $project->business_id)
                ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as review_count')
                ->first();

            BusinessProfile::where('user_id', $project->business_id)->update([
                'avg_rating'   => round((float) $stats->avg_rating, 2),
                'review_count' => (int) $stats->review_count,
            ]);

            Notification::create([
                'user_id' => $project->business_id,
                'type'    => 'review',
                'title'   => 'New review',
                'body'    => auth('api')->user()->name . " left a {$validated['rating']}-star review for {$project->title}.",
                'data'    => ['review_id' => $review->id, 'project_id' => $project->id, 'rating' => (int) $validated['rating']],
            ]);

            return $review;
        });

        return $this->success(
            $this->reviewPayload($review->load(['business.businessProfile', 'project'])),
            'Thank you! Your review has been posted.',
            201
        );
    }

    private function reviewPayload(Review $review): array
    {
        return [
            'id'               => $review->id,
            'rating'           => (int) $review->rating,
            'comment'          => $review->comment,
            'date'             => $review->created_at?->format('M d, Y'),
            'business'         => $this->businessPayload($review->business),
            'project'          => $review->project ? ['id' => $review->project->id, 'title' => $review->project->title] : null,
            'contractor_reply' => $review->contractor_reply,
            'replied_at'       => $review->replied_at?->format('M d, Y'),
        ];
    }

    private function businessPayload($business): ?array
    {
        if (! $business) {
            return null;
        }

        return [
            'id'     => $business->id,
            'name'   => $business->businessProfile?->business_name ?? $business->name,
            'avatar' => $this->uploadUrl($business->businessProfile?->logo ?? $business->avatar),
        ];
    }
}
