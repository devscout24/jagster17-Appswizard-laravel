<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TestimonialResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $customer = $this->customer;
        $profile = $customer?->customerProfile;
        $location = array_filter([$profile?->city, $profile?->state]);

        return [
            'id' => $this->id,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'author_name' => $customer?->name ?? 'Verified Customer',
            'author_location' => !empty($location) ? implode(', ', $location) : 'Verified Client',
            'project_title' => $this->project?->title ?? 'Home Service',
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
