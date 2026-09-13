<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractorCardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $profile = $this->businessProfile;
        $location = array_filter([$profile?->city, $profile?->state]);

        // Primary service category name
        $firstService = $this->services->first();
        $categoryName = $firstService?->category?->name ?? 'Professional Contractor';

        // Service specialty tags offered by this business
        $specialties = $this->services->pluck('name')->filter()->unique()->values()->all();
        if (empty($specialties) && $firstService) {
            $specialties = [$firstService->name];
        }

        $hourlyRate = $profile?->hourly_rate ?? $this->services->whereNotNull('price')->avg('price');

        return [
            'id' => $this->id,
            'business_name' => $profile?->business_name ?? $this->name,
            'avatar' => $this->avatar,
            'category_title' => $categoryName,
            'hourly_rate' => $hourlyRate ? (float) $hourlyRate : null,
            'hourly_rate_display' => $hourlyRate ? '$'.number_format($hourlyRate, 0).'/hr' : null,
            'location' => !empty($location) ? implode(', ', $location) : 'Nationwide',
            'rating' => (float) ($profile?->avg_rating ?? 0.0),
            'review_count' => (int) ($profile?->review_count ?? 0),
            'badges' => [
                'is_elite' => (bool) ($profile?->is_elite ?? false),
                'is_id_verified' => (bool) ($profile?->is_id_verified ?? false),
                'is_veteran_owned' => (bool) ($profile?->is_veteran_owned ?? false),
                'is_available_today' => (bool) ($profile?->is_available_today ?? false),
            ],
            'specialties' => $specialties,
            'member_since' => $profile?->member_since?->format('Y') ?? null,
            'actions' => [
                'request_quote_url' => '/quotes/request?contractor_id='.$this->id,
                'profile_url' => '/contractors/'.$this->id,
            ],
        ];
    }
}
