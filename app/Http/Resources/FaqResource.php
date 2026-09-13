<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FaqResource extends JsonResource
{
    /**
     * Transform the resource into an array matching the FAQ accordions in Figma.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $categoryLabels = [
            'customers' => 'For Customers',
            'contractors' => 'For Contractors',
            'payments' => 'Payments & Billing',
            'general' => 'General',
        ];

        return [
            'id' => $this->id,
            'category' => $this->category,
            'category_label' => $categoryLabels[$this->category] ?? 'General',
            'question' => $this->question,
            'answer' => $this->answer,
            'sort_order' => (int) $this->sort_order,
        ];
    }
}
