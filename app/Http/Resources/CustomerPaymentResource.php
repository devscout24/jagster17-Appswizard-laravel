<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerPaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array matching the Customer Payments screen.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $contractor = $this->business;
        $profile = $contractor?->businessProfile;

        return [
            'id' => $this->id,
            'transaction_id' => $this->transaction_id,
            'invoice_number' => $this->invoice?->invoice_number ?? 'INV-'.$this->invoice_id,
            'amount' => (float) $this->amount,
            'amount_display' => '$'.number_format((float) $this->amount, 2),
            'payment_method' => $this->payment_method ?? 'Stripe',
            'status' => $this->status,
            'receipt_url' => $this->receipt_url ?? '/receipts/'.$this->transaction_id.'.pdf',
            'contractor' => [
                'id' => $contractor?->id,
                'business_name' => $profile?->business_name ?? $contractor?->name ?? 'ValorHub Contractor',
                'avatar' => $contractor?->avatar,
            ],
            'paid_at' => $this->paid_at?->format('M d, Y, g:i A'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
