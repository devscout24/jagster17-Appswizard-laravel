<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessOnboardingReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $profile = $this->businessProfile;
        $subscription = $this->subscription;
        $plan = $subscription?->plan;
        $services = $this->services()->with('category')->get();
        $isFree = $plan ? (float) $plan->monthly_price === 0.0 : true;

        return [
            'account' => [
                'user_id'    => $this->id,
                'owner_name' => $this->name,
                'email'      => $this->email,
                'phone'      => $this->phone,
                'status'     => $this->status,
            ],
            'membership' => [
                'plan_name'     => $plan?->name ?? 'Free Verified Membership',
                'slug'          => $plan?->slug ?? 'free',
                'monthly_price' => $plan ? (float) $plan->monthly_price : 0.0,
                'display_price' => '$' . ($plan ? number_format((float) $plan->monthly_price, 0) : '0') . '/mo',
                'is_free'       => $isFree,
                'is_elite'      => (bool) $profile?->is_elite,
                'billing_cycle' => $subscription?->billing_cycle ?? 'monthly',
                'status'        => $subscription?->status ?? 'active',
            ],
            'verification' => [
                'is_id_verified'   => (bool) $profile?->is_id_verified,
                'is_veteran_owned' => (bool) $profile?->is_veteran_owned,
                'badge'            => $profile?->is_veteran_owned ? 'Verified Veteran Owned' : 'ID Verified',
            ],
            'business_info' => [
                'business_name'  => $profile?->business_name,
                'business_type'  => $profile?->business_type,
                'phone_number'   => $profile?->phone_number,
                'tax_id'         => $profile?->tax_id,
                'location'       => trim(($profile?->city ?? '') . ', ' . ($profile?->state ?? '') . ' ' . ($profile?->zip_code ?? '')),
                'city'           => $profile?->city,
                'state'          => $profile?->state,
                'zip_code'       => $profile?->zip_code,
                'service_radius' => $profile?->service_radius ? $profile->service_radius . ' miles' : null,
                'business_hours' => $profile?->business_hours,
                'languages'      => $profile?->languages ?? [],
                'service_areas'  => $profile?->service_areas ?? [],
            ],
            'profile_details' => [
                'bio'              => $profile?->bio,
                'hourly_rate'      => $profile?->hourly_rate !== null ? (float) $profile->hourly_rate : null,
                'years_experience' => $profile?->years_experience,
                'cover_photo'      => $profile?->cover_photo,
                'gallery_count'    => count($profile?->gallery_images ?? []),
                'gallery_images'   => $profile?->gallery_images ?? [],
                'video_urls'       => $profile?->video_urls ?? [],
            ],
            'social_links' => [
                'website_url'   => $profile?->website_url,
                'linkedin_url'  => $profile?->linkedin_url,
                'facebook_url'  => $profile?->facebook_url,
                'instagram_url' => $profile?->instagram_url,
                'twitter_url'   => $profile?->twitter_url,
            ],
            'services' => $services->map(function ($s) {
                return [
                    'id'            => $s->id,
                    'name'          => $s->name,
                    'category'      => $s->category?->name,
                    'price'         => (float) $s->price,
                    'pricing_type'  => $s->pricing_type,
                    'display_price' => '$' . number_format((float) $s->price, 2),
                ];
            }),
            'terms_agreed' => (bool) $profile?->terms_accepted_at,
            'can_submit'   => true,
        ];
    }
}
