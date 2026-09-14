<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractorLeadResource extends JsonResource
{
    /**
     * Transform the resource into an array for the Contractor Leads & Send Quote views.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $customer = $this->customer;
        $profile = $customer?->customerProfile;

        $budgetDisplay = 'Flexible';
        if ($this->budget_min && $this->budget_max) {
            $budgetDisplay = '$' . number_format((float) $this->budget_min, 0) . ' – $' . number_format((float) $this->budget_max, 0);
        } elseif ($this->budget_min) {
            $budgetDisplay = 'From $' . number_format((float) $this->budget_min, 0);
        } elseif ($this->quote_amount) {
            $budgetDisplay = '$' . number_format((float) $this->quote_amount, 2);
        }

        $locationParts = array_filter([$this->street_address, $this->city, $this->state, $this->zip_code]);
        $locationDisplay = ! empty($locationParts) ? implode(', ', $locationParts) : trim(($this->city ?? '') . ', ' . ($this->state ?? ''));

        $statusLabels = [
            'new'      => 'New Lead',
            'pending'  => 'Awaiting Response',
            'quoted'   => 'Quote Sent',
            'accepted' => 'Quote Accepted',
            'declined' => 'Declined',
            'expired'  => 'Expired',
        ];

        return [
            'id'               => $this->id,
            'reference_number' => $this->reference_number,
            'project_title'    => $this->project_title,
            'category_name'    => $this->category?->name ?? 'General Service',
            'description'      => $this->description,
            'attachments'      => $this->attachments ?? [],
            'budget'           => [
                'min'     => $this->budget_min ? (float) $this->budget_min : null,
                'max'     => $this->budget_max ? (float) $this->budget_max : null,
                'display' => $budgetDisplay,
            ],
            'timeline'         => $this->timeline ?? 'Flexible',
            'location'         => [
                'street_address' => $this->street_address,
                'city'           => $this->city,
                'state'          => $this->state,
                'zip_code'       => $this->zip_code,
                'display'        => $locationDisplay,
            ],
            'customer'         => [
                'id'           => $customer?->id,
                'name'         => $customer?->name ?? 'Homeowner',
                'email'        => $customer?->email,
                'phone'        => $customer?->phone ?? $profile?->phone,
                'avatar'       => $customer?->avatar,
                'member_since' => $customer?->created_at?->format('M Y'),
            ],
            'quote_response'   => $this->quote_amount ? [
                'quote_amount'       => (float) $this->quote_amount,
                'display_amount'     => '$' . number_format((float) $this->quote_amount, 2),
                'labor_cost'         => $this->labor_cost ? (float) $this->labor_cost : null,
                'materials_cost'     => $this->materials_cost ? (float) $this->materials_cost : null,
                'tax_cost'           => $this->tax_cost ? (float) $this->tax_cost : null,
                'estimated_duration' => $this->estimated_duration,
                'contractor_notes'   => $this->contractor_notes,
            ] : null,
            'status'           => $this->status,
            'status_label'     => $statusLabels[$this->status] ?? ucfirst($this->status),
            'has_responded'    => in_array($this->status, ['quoted', 'accepted', 'declined']),
            'requested_at'     => $this->requested_at?->toIso8601String() ?? $this->created_at->toIso8601String(),
            'requested_human'  => $this->requested_at?->diffForHumans() ?? $this->created_at->diffForHumans(),
        ];
    }
}
