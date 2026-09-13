<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductCardResource extends JsonResource
{
    /**
     * Transform the resource into an array matching product cards in Figma.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $seller = $this->business;
        $profile = $seller?->businessProfile;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'image' => $this->image ?? 'https://images.test/product-placeholder.jpg',
            'price' => (float) $this->price,
            'price_display' => '$'.number_format((float) $this->price, 2),
            'unit' => $this->unit,
            'is_elite_tier' => (bool) $this->is_elite_tier,
            'is_trending' => (bool) $this->is_trending,
            'in_stock' => (bool) $this->in_stock,
            'category' => [
                'id' => $this->category?->id,
                'name' => $this->category?->name ?? 'Hardware Supplies',
            ],
            'seller' => [
                'id' => $seller?->id,
                'business_name' => $profile?->business_name ?? $seller?->name ?? 'ValorHub Verified Seller',
                'avatar' => $seller?->avatar,
                'is_elite' => (bool) ($profile?->is_elite ?? false),
                'is_id_verified' => (bool) ($profile?->is_id_verified ?? false),
                'is_veteran_owned' => (bool) ($profile?->is_veteran_owned ?? false),
            ],
            'detail_url' => '/marketplace/products/'.$this->id,
        ];
    }
}
