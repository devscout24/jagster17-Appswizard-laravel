<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractorProjectResource extends JsonResource
{
    /**
     * Transform the resource into an array for Projects Management (Node 3223-1398).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $customer = $this->customer;
        $profile = $customer?->customerProfile;

        $invoices = $this->invoices ?? collect();
        $totalInvoiced = (float) $invoices->sum('amount');
        $totalPaid = (float) $invoices->where('status', 'paid')->sum('amount');
        $pendingAmount = (float) $invoices->whereIn('status', ['pending', 'overdue', 'draft'])->sum('amount');

        $statusLabels = [
            'in_progress'       => 'In Progress',
            'scheduled'         => 'Scheduled',
            'pending_materials' => 'Pending Materials',
            'completed'         => 'Completed',
            'cancelled'         => 'Cancelled',
        ];

        $totalAmount = $this->total_amount !== null
            ? (float) $this->total_amount
            : ($this->quoteRequest?->quote_amount ? (float) $this->quoteRequest->quote_amount : $totalInvoiced);

        return [
            'id'                   => $this->id,
            'title'                => $this->title,
            'description'          => $this->description,
            'total_amount'         => $totalAmount,
            'total_amount_display' => '$' . number_format($totalAmount, 2),
            'progress_percent'     => (int) ($this->progress_percent ?? 0),
            'status'               => $this->status,
            'status_label'         => $statusLabels[$this->status] ?? ucfirst(str_replace('_', ' ', $this->status)),
            'start_date'           => $this->start_date?->format('M d, Y'),
            'due_date'             => $this->due_date?->format('M d, Y'),
            'due_date_human'       => $this->due_date?->diffForHumans(),
            'customer'             => [
                'id'       => $customer?->id,
                'name'     => $customer?->name ?? 'Client',
                'email'    => $customer?->email,
                'phone'    => $customer?->phone ?? $profile?->phone,
                'avatar'   => $customer?->avatar,
                'location' => trim(($profile?->city ?? '') . ', ' . ($profile?->state ?? '')),
            ],
            'quote_reference'      => $this->quoteRequest?->reference_number,
            'invoices_summary'     => [
                'total_invoiced'         => $totalInvoiced,
                'total_invoiced_display' => '$' . number_format($totalInvoiced, 2),
                'total_paid'             => $totalPaid,
                'total_paid_display'     => '$' . number_format($totalPaid, 2),
                'pending_amount'         => $pendingAmount,
                'pending_amount_display' => '$' . number_format($pendingAmount, 2),
                'invoices_count'         => $invoices->count(),
            ],
            'created_at'           => $this->created_at->toIso8601String(),
        ];
    }
}
