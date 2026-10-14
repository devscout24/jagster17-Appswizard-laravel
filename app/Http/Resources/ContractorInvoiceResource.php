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
            'items'            => $this->lineItems(),
            'subtotal'         => round(array_sum(array_column($this->lineItems(), 'amount')), 2),
            'amount_due'       => $this->status === 'paid' ? 0.0 : (float) $this->amount,
            'bill_to_address'  => $this->bill_to_address,
            'payment_method'   => $this->payment_method,
            'is_approved'      => $this->approved_at !== null,
            'approved_at'      => $this->approved_at?->toIso8601String(),
            'sent_at'          => $this->sent_at?->toIso8601String(),
            'is_editable'      => $this->status === 'draft',
            'timeline'         => [
                [
                    'step'      => 'create',
                    'label'     => 'Create invoice',
                    'done'      => true,
                    'detail'    => 'Created ' . $this->created_at->format('M j, Y \a\t g:i A'),
                ],
                [
                    'step'      => 'send',
                    'label'     => 'Send invoice',
                    'done'      => $this->sent_at !== null,
                    'detail'    => $this->sent_at
                        ? 'Sent ' . $this->sent_at->format('M j, Y \a\t g:i A')
                        : ($this->approved_at ? 'Approved — ready to send' : 'Not sent yet — approve the draft first'),
                ],
                [
                    'step'      => 'payment',
                    'label'     => 'Manage payments',
                    'done'      => $this->status === 'paid',
                    'detail'    => $this->status === 'paid'
                        ? 'Paid ' . $this->paid_at?->format('M j, Y') . ($this->payment_method ? ' via ' . str_replace('_', ' ', $this->payment_method) : '')
                        : 'Amount due: $' . number_format((float) $this->amount, 2),
                ],
            ],
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
                'phone'  => $customer?->phone,
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
