<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MembershipPlanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $monthlyPrice = (float) $this->monthly_price;
        $isFree = $monthlyPrice === 0.0;

        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'slug'           => $this->slug,
            'tagline'        => $this->tagline,
            'badge'          => $this->badge,
            'is_popular'     => (bool) $this->is_popular,
            'monthly_price'  => $monthlyPrice,
            'annual_price'   => $this->annual_price !== null ? (float) $this->annual_price : null,
            'display_price'  => '$' . ($isFree ? '0' : number_format($monthlyPrice, 0)),
            'billing_period' => '/ month',
            'is_free'        => $isFree,
            'features'       => $this->features ?? [],
            'limits'         => [
                'service_limit' => $this->service_limit,
                'gallery_limit' => $this->gallery_limit,
                'video_limit'   => $this->video_limit,
                'is_unlimited'  => $this->service_limit === null,
            ],
            'button_text'    => 'Continue',
            'button_variant' => $this->is_popular ? 'primary' : 'outline',
        ];
    }
}
