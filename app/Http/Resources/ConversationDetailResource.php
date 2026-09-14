<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array for active conversation detail view (Node 3223-2212).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $currentUserId = auth('api')->id();
        $isBusiness = $this->business_id === $currentUserId;
        $otherUser = $isBusiness ? $this->customer : $this->business;
        $profile = $isBusiness ? $otherUser?->customerProfile : $otherUser?->businessProfile;

        $messages = $this->messages()->with('sender')->oldest()->get();

        return [
            'id'             => $this->id,
            'other_user'     => [
                'id'         => $otherUser?->id,
                'name'       => $isBusiness ? ($otherUser?->name ?? 'Homeowner') : ($profile?->business_name ?? $otherUser?->name ?? 'Contractor'),
                'email'      => $otherUser?->email,
                'phone'      => $profile?->phone_number ?? $otherUser?->phone,
                'avatar'     => $otherUser?->avatar,
                'location'   => trim(($profile?->city ?? '') . ', ' . ($profile?->state ?? '')),
                'role'       => $isBusiness ? 'customer' : 'business',
                'avg_rating' => $isBusiness ? null : (float) ($profile?->avg_rating ?? 5.0),
            ],
            'linked_project' => $this->project ? [
                'id'               => $this->project->id,
                'title'            => $this->project->title,
                'status'           => $this->project->status,
                'progress_percent' => $this->project->progress_percent,
            ] : null,
            'linked_quote'   => $this->quoteRequest ? [
                'id'               => $this->quoteRequest->id,
                'reference_number' => $this->quoteRequest->reference_number,
                'project_title'    => $this->quoteRequest->project_title,
                'quote_amount'     => $this->quoteRequest->quote_amount ? (float) $this->quoteRequest->quote_amount : null,
                'status'           => $this->quoteRequest->status,
            ] : null,
            'messages'       => MessageResource::collection($messages),
        ];
    }
}
