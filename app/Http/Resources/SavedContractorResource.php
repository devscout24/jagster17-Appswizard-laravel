<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SavedContractorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $businessUser = $this->business;
        $profile = $businessUser?->businessProfile;
        $services = $businessUser?->services ?? collect();
        $primaryService = $services->first();
        $primaryCategory = $primaryService?->category?->name ?? 'General Contractor';

        $rate = $profile?->hourly_rate ? (float) $profile->hourly_rate : 100.00;
        $city = $profile?->city ?? 'Phoenix';
        $state = $profile?->state ?? 'AZ';

        return [
            'id' => $this->id,
            'contractor_id' => $businessUser?->id,
            'business_name' => $profile?->business_name ?? $businessUser?->name ?? 'Contractor Pro',
            'avatar' => $businessUser?->avatar ?? 'https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&w=256&q=80',
            'category' => $primaryCategory,
            'hourly_rate' => $rate,
            'rate_display' => '$'.number_format($rate, 0),
            'badges' => [
                'is_elite' => (bool) ($profile?->is_elite ?? true),
                'is_id_verified' => (bool) ($profile?->is_id_verified ?? true),
                'is_veteran_owned' => (bool) ($profile?->is_veteran_owned ?? false),
                'is_available_today' => (bool) ($profile?->is_available_today ?? true),
            ],
            'location' => "{$city}, {$state}",
            'city' => $city,
            'state' => $state,
            'rating' => $profile?->avg_rating ? (float) $profile->avg_rating : 4.8,
            'review_count' => $profile?->review_count ?? 124,
            'services' => $services->pluck('name')->take(3)->values()->all() ?: ['AC Repair', 'Heating', 'Duct Cleaning'],
            'notes' => $this->notes,
            'saved_at' => $this->created_at?->format('M d, Y'),
            'actions' => [
                'request_quote' => [
                    'label' => 'Request Quote',
                    'contractor_id' => $businessUser?->id,
                ],
                'view_profile' => [
                    'label' => 'Profile',
                    'contractor_id' => $businessUser?->id,
                    'endpoint' => '/api/contractors/'.$businessUser?->id,
                ],
                'remove' => [
                    'label' => 'Remove',
                    'endpoint' => '/api/customer/saved-contractors/'.$businessUser?->id,
                    'method' => 'DELETE',
                ],
            ],
        ];
    }
}
