<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractorServiceResource extends JsonResource
{
    /**
     * Service card on the contractor "My Services" screen.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'description'   => $this->description,
            'pricing_type'  => $this->pricing_type,
            'price'         => $this->price !== null ? (float) $this->price : null,
            'unit'          => $this->unit,
            'price_display' => $this->priceDisplay(),
            'status'        => $this->status,
            'is_active'     => $this->status === 'active',
            'category'      => $this->category ? [
                'id'   => $this->category->id,
                'name' => $this->category->name,
            ] : null,
            'updated_at'    => $this->updated_at?->toIso8601String(),
        ];
    }

    private function priceDisplay(): string
    {
        if ($this->pricing_type === 'custom_quote' || $this->price === null) {
            return 'Custom Quote';
        }

        $amount = '$' . number_format((float) $this->price, fmod((float) $this->price, 1) ? 2 : 0);

        return match ($this->pricing_type) {
            'hourly' => "{$amount}/hr",
            'sqft'   => "{$amount}/sqft",
            default  => $this->unit ? "{$amount} {$this->unit}" : "Starting {$amount}",
        };
    }
}
