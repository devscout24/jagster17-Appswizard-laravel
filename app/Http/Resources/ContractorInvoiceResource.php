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
                'name'   => $customer?->name ?? 'Client',
                'email'  => $customer?->email,
                'avatar' => $customer?->avatar,
            ],
            'project'          => $project ? [
                'id'    => $project->id,
                'title' => $project->title,
            ] : null,
            'created_at'       => $this->created_at->toIso8601String(),
        ];
    }
}
