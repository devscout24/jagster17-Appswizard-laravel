<?php

namespace App\Http\Resources;

use App\Traits\HandlesUploads;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractorProductResource extends JsonResource
{
    use HandlesUploads;

    /**
     * Product card on the contractor "Products" screen.
     */
    public function toArray(Request $request): array
    {
        $price = (float) $this->price;

        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'description'    => $this->description,
            'image'          => $this->uploadUrl($this->image),
            'price'          => $price,
            'unit'           => $this->unit,
            'price_display'  => '$' . number_format($price, 2) . ($this->unit && $this->unit !== 'each' ? "/{$this->unit}" : ''),
            'stock_quantity' => (int) $this->stock_quantity,
            'in_stock'       => (bool) $this->in_stock,
            'sku'            => $this->sku,
            'warranty'       => $this->warranty,
            'shipping_info'  => $this->shipping_info,
            'external_url'   => $this->external_url,
            'features'       => $this->features ?? [],
            'status'         => $this->status,
            'is_active'      => $this->status === 'active',
            'category'       => $this->category ? [
                'id'   => $this->category->id,
                'name' => $this->category->name,
            ] : null,
            'updated_at'     => $this->updated_at?->toIso8601String(),
        ];
    }
}
