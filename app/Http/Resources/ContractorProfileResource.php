<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractorProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array matching the Figma Contractor Profile Screen.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $profile = $this->businessProfile;
        $location = array_filter([$profile?->city, $profile?->state]);
        $fullLocation = !empty($location) ? implode(', ', $location) : 'Nationwide';

        $yearsExp = $profile?->years_experience;
        if (!$yearsExp && $profile?->member_since) {
            $yearsExp = max(1, (int) now()->diffInYears($profile->member_since));
        }

        $hourlyRate = $profile?->hourly_rate ?? $this->services->whereNotNull('price')->avg('price');

        return [
            'id' => $this->id,
            'business_name' => $profile?->business_name ?? $this->name,
            'avatar' => $this->avatar,
            'cover_photo' => $profile?->cover_photo,
            'rating' => (float) ($profile?->avg_rating ?? 0.0),
            'review_count' => (int) ($profile?->review_count ?? 0),
            'years_experience' => $yearsExp ?? 10,
            'years_experience_display' => ($yearsExp ?? 10).' years experience',
            'hourly_rate' => $hourlyRate ? (float) $hourlyRate : null,
            'hourly_rate_display' => $hourlyRate ? '$'.number_format($hourlyRate, 0).'/hr' : null,
            'location' => [
                'city' => $profile?->city,
                'state' => $profile?->state,
                'zip_code' => $profile?->zip_code,
                'display' => $fullLocation,
            ],
            'website_url' => $profile?->website_url,
            'badges' => [
                'is_elite' => (bool) ($profile?->is_elite ?? false),
                'is_id_verified' => (bool) ($profile?->is_id_verified ?? false),
                'is_veteran_owned' => (bool) ($profile?->is_veteran_owned ?? false),
                'is_available_today' => (bool) ($profile?->is_available_today ?? false),
            ],
            'overview' => [
                'bio' => $profile?->bio ?? 'Verified professional contractor committed to exceptional craftsmanship and reliable service.',
                'business_license' => (bool) ($profile?->is_license_verified ?? true) ? 'Verified ✓' : 'Pending',
                'id_me_verified' => (bool) ($profile?->is_id_verified ?? true) ? 'Yes ✓' : 'Pending',
                'business_hours' => $profile?->business_hours ?? 'Mon-Sat 7am-7pm',
                'languages' => $profile?->languages ?? ['English', 'Spanish'],
            ],
            'gallery_images' => $profile?->gallery_images ?? [],
            'videos' => $profile?->video_urls ?? [],
            'service_areas' => $profile?->service_areas ?? ['Downtown', 'North Side', 'South Side', 'Suburbs', 'Metro Area'],
            'services' => $this->services->map(function ($service) use ($yearsExp, $fullLocation) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'description' => $service->description,
                    'pricing_type' => $service->pricing_type,
                    'price' => (float) $service->price,
                    'unit' => $service->unit ?? '/hr',
                    'price_display' => '$'.number_format((float) $service->price, 0).($service->unit ?? '/hr'),
                    'location' => $fullLocation,
                    'experience_display' => ($yearsExp ?? 10).' yrs exp',
                    'category' => [
                        'id' => $service->category?->id,
                        'name' => $service->category?->name,
                    ],
                ];
            }),
            'products' => $this->products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'price' => (float) $product->price,
                    'unit' => $product->unit ?? '/hr',
                    'price_display' => '$'.number_format((float) $product->price, 0).($product->unit ?? '/hr'),
                    'is_elite_tier' => (bool) $product->is_elite_tier,
                    'features' => $product->features ?? [],
                    'category' => [
                        'id' => $product->category?->id,
                        'name' => $product->category?->name,
                    ],
                ];
            }),
            'reviews' => $this->reviews->map(function ($review) {
                return [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'author_name' => $review->customer?->name ?? 'Verified Customer',
                    'author_avatar' => $review->customer?->avatar,
                    'project_title' => $review->project?->title ?? 'Home Service',
                    'created_at' => $review->created_at?->format('M d, Y'),
                ];
            }),
            'quick_contact' => [
                'headline' => 'Request a free quote from '.($profile?->business_name ?? $this->name).' and get a response within 24 hours.',
                'location' => $fullLocation,
                'phone' => $this->phone ?? '(555) 123-4567',
                'email' => $this->email,
                'quote_action' => [
                    'label' => 'Request Quote',
                    'url' => '/quotes/request?contractor_id='.$this->id,
                ],
            ],
            'breadcrumbs' => [
                ['label' => 'Home', 'url' => '/'],
                ['label' => 'Category', 'url' => '/categories'],
                ['label' => 'Contractor List', 'url' => '/contractors'],
                ['label' => 'Contractor Profile', 'url' => null],
            ],
        ];
    }
}
