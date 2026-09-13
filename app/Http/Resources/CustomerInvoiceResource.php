<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerInvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array matching the Customer Portal Invoices & Invoice Details screens.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $contractor = $this->business;
        $profile = $contractor?->businessProfile;

        $totalAmount = (float) $this->amount;
        $labor = (float) ($this->labor_amount ?? ($totalAmount * 0.70));
        $materials = (float) ($this->materials_amount ?? ($totalAmount * 0.20));
        $platformFee = (float) ($this->platform_fee ?? ($totalAmount * 0.10));

        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'amount' => $totalAmount,
            'amount_display' => '$'.number_format($totalAmount, 2),
            'status' => $this->status,
            'issued_at' => $this->issued_at?->format('M d, Y'),
            'due_at' => $this->due_at?->format('M d, Y'),
            'paid_at' => $this->paid_at?->format('M d, Y, g:i A'),
            'notes' => $this->notes ?? 'Thank you for your business! Please pay within 15 days of issue.',
            'breakdown' => [
                'labor_amount' => $labor,
                'labor_amount_display' => '$'.number_format($labor, 2),
                'materials_amount' => $materials,
                'materials_amount_display' => '$'.number_format($materials, 2),
                'platform_fee' => $platformFee,
                'platform_fee_display' => '$'.number_format($platformFee, 2),
                'total_due' => $totalAmount,
                'total_due_display' => '$'.number_format($totalAmount, 2),
            ],
            'contractor' => [
                'id' => $contractor?->id,
                'business_name' => $profile?->business_name ?? $contractor?->name ?? 'ValorHub Contractor',
                'avatar' => $contractor?->avatar,
                'is_elite' => (bool) ($profile?->is_elite ?? false),
                'is_veteran_owned' => (bool) ($profile?->is_veteran_owned ?? false),
            ],
            'project' => $this->project ? [
                'id' => $this->project->id,
                'title' => $this->project->title,
                'status' => $this->project->status,
            ] : null,
        ];
    }
}
