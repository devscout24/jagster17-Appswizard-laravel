<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractorReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array matching the Contractor Portal Reviews Screen (Node 3228-4501 & 3370-11).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $customer = $this->customer;
        $customerProfile = $customer?->customerProfile;
        $location = array_filter([$customerProfile?->city, $customerProfile?->state]);

        return [
            'id' => $this->id,
            'rating' => (int) $this->rating,
            'comment' => $this->comment,
            'is_featured' => (bool) $this->is_featured,
            'contractor_reply' => $this->contractor_reply,
            'replied_at' => $this->replied_at?->toIso8601String(),
            'has_replied' => !empty($this->contractor_reply),
            'customer' => [
                'id' => $customer?->id,
                'name' => $customer?->name ?? 'Verified Customer',
                'avatar' => $customer?->avatar,
                'location' => !empty($location) ? implode(', ', $location) : 'Local Customer',
            ],
            'project' => $this->project ? [
                'id' => $this->project->id,
                'title' => $this->project->title,
                'status' => $this->project->status,
                'completed_date' => $this->project->completed_date?->format('M d, Y'),
            ] : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'date_formatted' => $this->created_at?->format('M d, Y'),
            'relative_time' => $this->created_at?->diffForHumans(),
        ];
    }
}
