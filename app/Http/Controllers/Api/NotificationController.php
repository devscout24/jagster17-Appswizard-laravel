<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponseTrait;

    /**
     * Resolve the authenticated user (routes are guarded by auth:api).
     */
    protected function resolveUser(): User
    {
        return auth('api')->user();
    }

    /**
     * List user notifications with unread count and filters.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $this->resolveUser();

        $unreadCount = Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();

        $query = Notification::where('user_id', $user->id);

        if ($request->query('filter') === 'unread') {
            $query->whereNull('read_at');
        } elseif ($request->query('filter') === 'read') {
            $query->whereNotNull('read_at');
        }

        $notifications = $query->latest()->paginate($request->query('per_page', 15));

        return $this->success([
            'unread_count' => $unreadCount,
            'items'        => NotificationResource::collection($notifications->items()),
            'pagination'   => [
                'current_page' => $notifications->currentPage(),
                'last_page'    => $notifications->lastPage(),
                'per_page'     => $notifications->perPage(),
                'total'        => $notifications->total(),
            ],
        ], 'Notifications retrieved successfully.');
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(int $id): JsonResponse
    {
        $user = $this->resolveUser();

        $notification = Notification::where('user_id', $user->id)->find($id);

        if (! $notification) {
            return $this->notFound('Notification not found.');
        }

        if (is_null($notification->read_at)) {
            $notification->update(['read_at' => now()]);
        }

        return $this->success(
            new NotificationResource($notification),
            'Notification marked as read.'
        );
    }

    /**
     * Mark all user notifications as read.
     */
    public function markAllAsRead(): JsonResponse
    {
        $user = $this->resolveUser();

        Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return $this->success(null, 'All notifications marked as read.');
    }

    /**
     * Clear all notifications for the current user.
     */
    public function clearAll(): JsonResponse
    {
        Notification::where('user_id', $this->resolveUser()->id)->delete();

        return $this->success(null, 'All notifications cleared.');
    }

    /**
     * Delete a notification.
     */
    public function destroy(int $id): JsonResponse
    {
        $user = $this->resolveUser();

        $notification = Notification::where('user_id', $user->id)->find($id);

        if (! $notification) {
            return $this->notFound('Notification not found.');
        }

        $notification->delete();

        return $this->success(null, 'Notification deleted successfully.');
    }
}
