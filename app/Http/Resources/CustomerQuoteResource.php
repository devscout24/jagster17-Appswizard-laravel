<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerQuoteResource extends JsonResource
{
    /**
     * Transform the resource into an array matching the Customer Portal Quotes & Quote Details screens.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $contractor = $this->business;
        $profile = $contractor?->businessProfile;
        $location = array_filter([$profile?->city, $profile?->state]);

        $totalQuote = (float) ($this->quote_amount ?? $this->budget_max ?? $this->budget_min ?? 420.00);
        $labor = (float) ($this->labor_cost ?? ($totalQuote * 0.65));
        $materials = (float) ($this->materials_cost ?? ($totalQuote * 0.30));
        $tax = (float) ($this->tax_cost ?? ($totalQuote * 0.05));

        return [
            'id' => $this->id,
            'reference_number' => $this->reference_number,
            'project_title' => $this->project_title ?? ($this->category?->name ? $this->category->name.' Repair' : 'Service Request'),
            'service_type' => $this->category?->name ?? 'General Service',
            'description' => $this->description,
            'status' => $this->status,
            'contractor' => [
                'id' => $contractor?->id,
                'business_name' => $profile?->business_name ?? $contractor?->name ?? 'ValorHub Contractor',
                'avatar' => $contractor?->avatar,
                'rating' => (float) ($profile?->avg_rating ?? 4.9),
                'review_count' => (int) ($profile?->review_count ?? 127),
                'location' => !empty($location) ? implode(', ', $location) : 'Phoenix, AZ',
                'is_elite' => (bool) ($profile?->is_elite ?? false),
                'is_id_verified' => (bool) ($profile?->is_id_verified ?? true),
                'is_veteran_owned' => (bool) ($profile?->is_veteran_owned ?? true),
            ],
            'financials' => [
                'labor_cost' => $labor,
                'labor_cost_display' => '$'.number_format($labor, 2),
                'materials_cost' => $materials,
                'materials_cost_display' => '$'.number_format($materials, 2),
                'tax_cost' => $tax,
                'tax_cost_display' => '$'.number_format($tax, 2),
                'total_amount' => $totalQuote,
                'total_amount_display' => '$'.number_format($totalQuote, 2),
            ],
            'estimated_duration' => $this->estimated_duration ?? '2-3 business days',
            'contractor_notes' => $this->contractor_notes ?? 'Price includes full labor, all replacement parts, and a 1-year warranty on workmanship.',
            'attachments' => $this->attachments ?? [],
            'requested_at' => $this->requested_at?->format('M d, Y'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
