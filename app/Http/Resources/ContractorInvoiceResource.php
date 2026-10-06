<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractorInvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array for Invoices & Create Invoice modal (Node 3354-11).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $customer = $this->customer;
        $project = $this->project;

        $statusLabels = [
            'draft'   => 'Draft',
            'pending' => 'Pending Payment',
            'paid'    => 'Paid',
            'overdue' => 'Overdue',
        ];

        return [
            'id'               => $this->id,
            'invoice_number'   => $this->invoice_number,
            'amount'           => (float) $this->amount,
            'amount_display'   => '$' . number_format((float) $this->amount, 2),
            'labor_amount'     => $this->labor_amount ? (float) $this->labor_amount : null,
            'materials_amount' => $this->materials_amount ? (float) $this->materials_amount : null,
            'platform_fee'     => $this->platform_fee ? (float) $this->platform_fee : null,
            'notes'            => $this->notes,
            'issued_at'        => $this->issued_at?->format('M d, Y'),
            'due_at'           => $this->due_at?->format('M d, Y'),
            'paid_at'          => $this->paid_at?->format('M d, Y'),
            'status'           => $this->status,
            'status_label'     => $statusLabels[$this->status] ?? ucfirst($this->status),
            'customer'         => [
                'id'     => $customer?->id,
                'contractor_customer_id' => $this->contractor_customer_id,
                'name'   => $customer?->name ?? $this->contractorCustomer?->name ?? 'Client',
                'email'  => $customer?->email ?? $this->contractorCustomer?->email,
                'phone'  => $customer?->phone ?? $this->contractorCustomer?->phone,
                'billing_address' => $this->contractorCustomer?->billing_address,
                'avatar' => $customer?->avatar,
            ],
            'po_number'        => $this->po_number,
            'subtotal'         => $this->subtotal !== null ? (float) $this->subtotal : (float) $this->amount,
            'discount_percent' => (float) $this->discount_percent,
            'discount_amount'  => (float) $this->discount_amount,
            'payment_terms'    => $this->payment_terms,
            'footer'           => $this->footer,
            'tags'             => $this->tags ?? [],
            'attachments'      => $this->attachments ?? [],
            'require_terms_acceptance' => (bool) $this->require_terms_acceptance,
            'payment_method'   => $this->payment_method,
            'sent_at'          => $this->sent_at?->toIso8601String(),
            'issued_on'        => $this->issued_at?->toDateString(),
            'due_on'           => $this->due_at?->toDateString(),
            'items'            => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id'          => $item->id,
                'description' => $item->description,
                'details'     => $item->details,
                'quantity'    => (float) $item->quantity,
                'unit_price'  => (float) $item->unit_price,
                'amount'      => (float) $item->amount,
            ])),
            'project'          => $project ? [
                'id'    => $project->id,
                'title' => $project->title,
            ] : null,
            'created_at'       => $this->created_at->toIso8601String(),
        ];
    }
}
