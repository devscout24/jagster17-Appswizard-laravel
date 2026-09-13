<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuoteRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array matching the Figma Quote Request & Confirmation screen.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $budgetLabels = [
            'under_1000' => 'Under $1,000',
            '1000_5000' => '$1,000–$5,000',
            '5000_15000' => '$5,000–$15,000',
            '15000_plus' => '$15,000+',
        ];

        $timelineLabels = [
            'asap' => 'ASAP',
            'within_1_week' => 'Within 1 week',
            'within_1_month' => 'Within 1 month',
            'flexible' => 'Flexible',
        ];

        $locationParts = array_filter([$this->street_address, $this->city, $this->state, $this->zip_code]);
        $locationDisplay = !empty($locationParts) ? implode(', ', $locationParts) : 'Location provided';

        $recipient = $this->business;
        $profile = $recipient?->businessProfile;

        return [
            'id' => $this->id,
            'reference_number' => $this->reference_number,
            'project_title' => $this->project_title ?? ($this->category?->name ? $this->category->name.' Project' : 'General Service Project'),
            'service_type' => $this->category?->name ?? 'General Service',
            'description' => $this->description,
            'attachments' => $this->attachments ?? [],
            'budget' => [
                'range_key' => $this->budget_range,
                'display' => $budgetLabels[$this->budget_range] ?? ($this->budget_min || $this->budget_max ? '$'.number_format($this->budget_min).'–$'.number_format($this->budget_max) : 'Not specified'),
                'min' => $this->budget_min ? (float) $this->budget_min : null,
                'max' => $this->budget_max ? (float) $this->budget_max : null,
            ],
            'timeline' => [
                'key' => $this->timeline,
                'display' => $timelineLabels[$this->timeline] ?? $this->timeline,
            ],
            'location' => [
                'street_address' => $this->street_address,
                'city' => $this->city,
                'state' => $this->state,
                'zip_code' => $this->zip_code,
                'display' => $locationDisplay,
            ],
            'recipient' => $recipient ? [
                'id' => $recipient->id,
                'business_name' => $profile?->business_name ?? $recipient->name,
                'avatar' => $recipient->avatar,
                'is_elite' => (bool) ($profile?->is_elite ?? false),
                'is_veteran_owned' => (bool) ($profile?->is_veteran_owned ?? false),
                'is_id_verified' => (bool) ($profile?->is_id_verified ?? false),
            ] : null,
            'status' => $this->status,
            'confirmation' => [
                'title' => 'Request submitted!',
                'message' => $recipient
                    ? 'Your service request has been sent to '.($profile?->business_name ?? $recipient->name).'. They will respond with a detailed quote within 24 hours.'
                    : 'Your service request has been sent to nearby contractors. You will start receiving quotes shortly — usually within 24 hours.',
                'reference_badge' => 'Request #'.$this->reference_number,
            ],
            'requested_at' => $this->requested_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
