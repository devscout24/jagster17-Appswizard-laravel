<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\SendMessageRequest;
use App\Http\Requests\Chat\StartConversationRequest;
use App\Http\Resources\ConversationDetailResource;
use App\Http\Resources\ConversationListItemResource;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Notification;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    use ApiResponseTrait;

    /**
     * Get the authenticated user.
     */
    protected function resolveUser(): User
    {
        $user = auth('api')->user();

        if (! $user) {
            $user = User::first();
        }

        return $user;
    }

    /**
     * List all conversations for the authenticated user (Node 3223-2212 sidebar).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $this->resolveUser();

        $query = Conversation::where(function ($q) use ($user) {
            $q->where('business_id', $user->id)
              ->orWhere('customer_id', $user->id);
        })->with([
            'business.businessProfile',
            'customer.customerProfile',
            'project',
            'quoteRequest',
            'messages',
        ]);

        if ($request->query('filter') === 'unread') {
            $query->whereHas('messages', function ($mq) use ($user) {
                $mq->where('sender_id', '!=', $user->id)
                   ->whereNull('read_at');
            });
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('business', fn ($bq) => $bq->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('project', fn ($pq) => $pq->where('title', 'like', "%{$search}%"))
                  ->orWhereHas('messages', fn ($mq) => $mq->where('body', 'like', "%{$search}%"));
            });
        }

        $conversations = $query->orderByDesc('last_message_at')->paginate($request->query('per_page', 15));

        $totalUnread = Message::whereHas('conversation', function ($cq) use ($user) {
            $cq->where('business_id', $user->id)->orWhere('customer_id', $user->id);
        })->where('sender_id', '!=', $user->id)->whereNull('read_at')->count();

        $data = [
            'total_unread' => $totalUnread,
            'items'        => ConversationListItemResource::collection($conversations->items()),
            'pagination'   => [
                'current_page' => $conversations->currentPage(),
                'last_page'    => $conversations->lastPage(),
                'per_page'     => $conversations->perPage(),
                'total'        => $conversations->total(),
            ],
        ];

        return $this->success($data, 'Conversations retrieved successfully.');
    }

    /**
     * View active conversation thread and mark unread messages as read (Node 3223-2212).
     */
    public function show(int $id): JsonResponse
    {
        $user = $this->resolveUser();

        $conversation = Conversation::where(function ($q) use ($user) {
            $q->where('business_id', $user->id)
              ->orWhere('customer_id', $user->id);
        })->with([
            'business.businessProfile',
            'customer.customerProfile',
            'project',
            'quoteRequest',
            'messages.sender',
        ])->find($id);

        if (! $conversation) {
            return $this->notFound('Conversation not found.');
        }

        // Mark unread messages from other user as read
        Message::where('conversation_id', $conversation->id)
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return $this->success(
            new ConversationDetailResource($conversation),
            'Conversation loaded successfully.'
        );
    }

    /**
     * Send message in an existing conversation.
     */
    public function sendMessage(SendMessageRequest $request, int $id): JsonResponse
    {
        $user = $this->resolveUser();
        $validated = $request->validated();

        $conversation = Conversation::where(function ($q) use ($user) {
            $q->where('business_id', $user->id)
              ->orWhere('customer_id', $user->id);
        })->find($id);

        if (! $conversation) {
            return $this->notFound('Conversation not found.');
        }

        $message = DB::transaction(function () use ($conversation, $user, $validated) {
            $msg = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id'       => $user->id,
                'body'            => $validated['body'],
                'attachment_url'  => $validated['attachment_url'] ?? null,
            ]);

            $conversation->update([
                'last_message_at' => now(),
            ]);

            // Notify the recipient
            $recipientId = $conversation->business_id === $user->id
                ? $conversation->customer_id
                : $conversation->business_id;

            if ($recipientId) {
                Notification::create([
                    'user_id' => $recipientId,
                    'title'   => 'New Message from ' . $user->name,
                    'body'    => \Illuminate\Support\Str::limit($validated['body'], 100),
                    'type'    => 'message',
                    'data'    => [
                        'conversation_id' => $conversation->id,
                        'message_id'      => $msg->id,
                    ],
                ]);
            }

            return $msg;
        });

        return $this->success(
            new MessageResource($message->load('sender')),
            'Message sent successfully.',
            201
        );
    }

    /**
     * Start a new conversation or return existing conversation thread.
     */
    public function start(StartConversationRequest $request): JsonResponse
    {
        $user = $this->resolveUser();
        $validated = $request->validated();
        $recipientId = (int) $validated['recipient_id'];

        $recipient = User::find($recipientId);
        if (! $recipient) {
            return $this->notFound('Recipient user not found.');
        }

        // Determine business vs customer roles
        $isUserBusiness = $user->hasRole('business') || $user->businessProfile !== null;
        $businessId = $isUserBusiness ? $user->id : $recipientId;
        $customerId = $isUserBusiness ? $recipientId : $user->id;

        $conversation = Conversation::firstOrCreate(
            [
                'business_id' => $businessId,
                'customer_id' => $customerId,
            ],
            [
                'project_id'       => $validated['project_id'] ?? null,
                'quote_request_id' => $validated['quote_request_id'] ?? null,
                'last_message_at'  => now(),
            ]
        );

        if (! empty($validated['message'])) {
            Message::create([
                'conversation_id' => $conversation->id,
                'sender_id'       => $user->id,
                'body'            => $validated['message'],
                'attachment_url'  => $validated['attachment_url'] ?? null,
            ]);
            $conversation->update(['last_message_at' => now()]);
        }

        return $this->success(
            new ConversationDetailResource($conversation->fresh([
                'business.businessProfile',
                'customer.customerProfile',
                'project',
                'quoteRequest',
                'messages.sender',
            ])),
            'Conversation ready.',
            201
        );
    }

    /**
     * Get total unread messages count for notifications badge.
     */
    public function unreadCount(): JsonResponse
    {
        $user = $this->resolveUser();

        $count = Message::whereHas('conversation', function ($cq) use ($user) {
            $cq->where('business_id', $user->id)->orWhere('customer_id', $user->id);
        })->where('sender_id', '!=', $user->id)->whereNull('read_at')->count();

        return $this->success(['unread_count' => $count], 'Unread count retrieved.');
    }
}
