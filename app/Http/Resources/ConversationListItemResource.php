<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationListItemResource extends JsonResource
{
    /**
     * Transform the resource into an array for Conversation sidebar list (Node 3223-2212).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $currentUserId = auth('api')->id();
        $isBusiness = $this->business_id === $currentUserId;
        $otherUser = $isBusiness ? $this->customer : $this->business;
        $profile = $isBusiness ? $otherUser?->customerProfile : $otherUser?->businessProfile;

        $lastMessage = $this->messages()->latest()->first();
        $unreadCount = $this->messages()
            ->where('sender_id', '!=', $currentUserId)
            ->whereNull('read_at')
            ->count();

        $subject = null;
        if ($this->project) {
            $subject = [
                'type'  => 'project',
                'id'    => $this->project->id,
                'title' => $this->project->title,
            ];
        } elseif ($this->quoteRequest) {
            $subject = [
                'type'  => 'quote_request',
                'id'    => $this->quoteRequest->id,
                'title' => $this->quoteRequest->project_title ?? $this->quoteRequest->reference_number,
            ];
        }

        return [
            'id'             => $this->id,
            'other_user'     => [
                'id'       => $otherUser?->id,
                'name'     => $isBusiness ? ($otherUser?->name ?? 'Homeowner') : ($profile?->business_name ?? $otherUser?->name ?? 'Contractor'),
                'avatar'   => $otherUser?->avatar,
                'location' => trim(($profile?->city ?? '') . ', ' . ($profile?->state ?? '')),
                'role'     => $isBusiness ? 'customer' : 'business',
            ],
            'linked_subject' => $subject,
            'last_message'   => $lastMessage ? [
                'body'         => $lastMessage->body,
                'is_sender_me' => $lastMessage->sender_id === $currentUserId,
                'is_read'      => (bool) $lastMessage->read_at,
                'sent_at'      => $lastMessage->created_at->toIso8601String(),
                'sent_human'   => $lastMessage->created_at->diffForHumans(),
            ] : null,
            'unread_count'   => $unreadCount,
            'last_activity'  => $this->last_message_at?->toIso8601String() ?? $this->updated_at->toIso8601String(),
        ];
    }
}
