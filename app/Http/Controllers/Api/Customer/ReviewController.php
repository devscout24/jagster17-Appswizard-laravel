<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Review;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Customer dashboard Reviews: completed jobs waiting for a review, and reviews already written.
 */
class ReviewController extends Controller
{
    use ApiResponseTrait;

    public function index(): JsonResponse
    {
        $userId = auth('api')->id();
        $reviewedProjectIds = Review::where('customer_id', $userId)->whereNotNull('project_id')->pluck('project_id');

        $pending = Project::with('business.businessProfile')
            ->where('customer_id', $userId)
            ->where('status', 'completed')
            ->whereNotIn('id', $reviewedProjectIds)
            ->latest('updated_at')
            ->get()
            ->map(fn (Project $project) => [
                'project_id' => $project->id,
                'business_id' => $project->business_id,
                'contractor' => $project->business?->businessProfile?->business_name ?? $project->business?->name,
                'job' => $project->title,
                'completed_at' => $project->updated_at->format('M j, Y'),
            ]);

        $written = Review::with('business.businessProfile', 'project')
            ->where('customer_id', $userId)
            ->latest()
            ->get()
            ->map(fn (Review $review) => $this->present($review));

        return $this->success(compact('pending', 'written'), 'Reviews retrieved successfully.');
    }

    public function store(Request $request): JsonResponse
    {
        $userId = auth('api')->id();
        $validated = $request->validate([
            'project_id' => [
                'required', 'integer',
                Rule::exists('projects', 'id')->where('customer_id', $userId)->where('status', 'completed'),
                Rule::unique('reviews', 'project_id')->where('customer_id', $userId),
            ],
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'project_id.exists' => 'You can only review completed jobs.',
            'project_id.unique' => 'You have already reviewed this job.',
        ]);

        $project = Project::findOrFail($validated['project_id']);
        $review = Review::create([
            'business_id' => $project->business_id,
            'customer_id' => $userId,
            'project_id' => $project->id,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
        ]);

        Notification::create([
            'user_id' => $project->business_id,
            'type' => 'new_review',
            'title' => 'New Review',
            'body' => auth('api')->user()->name . " left you a {$review->rating}-star review.",
            'data' => ['review_id' => $review->id],
        ]);

        return $this->success($this->present($review->load('business.businessProfile', 'project')), 'Thanks for your review!', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $review = Review::where('customer_id', auth('api')->id())->findOrFail($id);
        $review->update($request->validate([
            'rating' => ['sometimes', 'required', 'integer', 'between:1,5'],
            'comment' => ['sometimes', 'required', 'string', 'min:10', 'max:1000'],
        ]));

        return $this->success($this->present($review->load('business.businessProfile', 'project')), 'Review updated.');
    }

    private function present(Review $review): array
    {
        return [
            'id' => $review->id,
            'business_id' => $review->business_id,
            'contractor' => $review->business?->businessProfile?->business_name ?? $review->business?->name,
            'job' => $review->project?->title,
            'rating' => $review->rating,
            'comment' => $review->comment,
            'contractor_reply' => $review->contractor_reply,
            'reviewed_at' => $review->created_at->format('M j, Y'),
        ];
    }
}
