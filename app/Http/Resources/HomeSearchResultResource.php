<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HomeSearchResultResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $business = $this->business;
        $profile = $business?->businessProfile;
        $location = array_filter([$profile?->city, $profile?->state]);

        return [
            'id' => $this->id,
            'service_name' => $this->name,
            'description' => $this->description,
            'pricing_type' => $this->pricing_type,
            'price' => $this->price,
            'unit' => $this->unit,
            'category' => [
                'id' => $this->category?->id,
                'name' => $this->category?->name,
            ],
            'business' => [
                'id' => $business?->id,
                'business_name' => $profile?->business_name ?? $business?->name,
                'avatar' => $business?->avatar,
                'location' => !empty($location) ? implode(', ', $location) : null,
                'avg_rating' => (float) ($profile?->avg_rating ?? 0.0),
                'review_count' => (int) ($profile?->review_count ?? 0),
                'is_elite' => (bool) ($profile?->is_elite ?? false),
                'is_veteran_owned' => (bool) ($profile?->is_veteran_owned ?? false),
                'is_id_verified' => (bool) ($profile?->is_id_verified ?? false),
            ],
        ];
    }
}
