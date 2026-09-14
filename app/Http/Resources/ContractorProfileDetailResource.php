<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractorProfileDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array matching the Contractor Profile Settings Screen (Node 3363-11).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $profile = $this->businessProfile;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'business_info' => [
                'business_name' => $profile?->business_name ?? $this->name,
                'owner_name' => $profile?->owner_name ?? $this->name,
                'business_type' => $profile?->business_type,
                'phone_number' => $profile?->phone_number ?? $this->phone,
                'tax_id' => $profile?->tax_id,
                'cover_photo' => $profile?->cover_photo,
                'city' => $profile?->city,
                'state' => $profile?->state,
                'zip_code' => $profile?->zip_code,
                'location_display' => array_filter([$profile?->city, $profile?->state]) 
                    ? implode(', ', array_filter([$profile?->city, $profile?->state])) 
                    : 'Not Specified',
            ],
            'professional_details' => [
                'bio' => $profile?->bio,
                'hourly_rate' => $profile?->hourly_rate ? (float) $profile->hourly_rate : null,
                'years_experience' => $profile?->years_experience,
                'license_number' => $profile?->license_number,
                'service_radius' => $profile?->service_radius ?? 25,
                'business_hours' => $profile?->business_hours ?? 'Mon-Sat 7am-7pm',
                'languages' => $profile?->languages ?? ['English'],
                'service_areas' => $profile?->service_areas ?? [],
            ],
            'verifications_and_badges' => [
                'is_elite' => (bool) ($profile?->is_elite ?? false),
                'is_veteran_owned' => (bool) ($profile?->is_veteran_owned ?? false),
                'is_id_verified' => (bool) ($profile?->is_id_verified ?? false),
                'is_license_verified' => (bool) ($profile?->is_license_verified ?? false),
                'is_available_today' => (bool) ($profile?->is_available_today ?? false),
                'id_me_verified_at' => $profile?->id_me_verified_at?->toIso8601String(),
            ],
            'media' => [
                'gallery_images' => $profile?->gallery_images ?? [],
                'video_urls' => $profile?->video_urls ?? [],
            ],
            'social_links' => [
                'website_url' => $profile?->website_url,
                'linkedin_url' => $profile?->linkedin_url,
                'facebook_url' => $profile?->facebook_url,
                'instagram_url' => $profile?->instagram_url,
                'twitter_url' => $profile?->twitter_url,
            ],
            'stats' => [
                'avg_rating' => (float) ($profile?->avg_rating ?? 0.0),
                'review_count' => (int) ($profile?->review_count ?? 0),
                'member_since' => $profile?->member_since?->format('M Y') ?? $this->created_at?->format('M Y'),
            ],
        ];
    }
}
