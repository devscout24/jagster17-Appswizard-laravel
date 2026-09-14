<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    /**
     * Transform the resource into an array for chat messages.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $currentUserId = auth('api')->id();
        $sender = $this->sender;

        return [
            'id'              => $this->id,
            'conversation_id' => $this->conversation_id,
            'sender'          => [
                'id'     => $sender?->id,
                'name'   => $sender?->name ?? 'User',
                'avatar' => $sender?->avatar,
            ],
            'is_sender_me'    => $this->sender_id === $currentUserId,
            'body'            => $this->body,
            'attachment_url'  => $this->attachment_url,
            'is_read'         => (bool) $this->read_at,
            'read_at'         => $this->read_at?->toIso8601String(),
            'sent_at'         => $this->created_at->toIso8601String(),
            'sent_time'       => $this->created_at->format('g:i A'),
            'sent_date'       => $this->created_at->format('M d, Y'),
            'sent_human'      => $this->created_at->diffForHumans(),
        ];
    }
}
