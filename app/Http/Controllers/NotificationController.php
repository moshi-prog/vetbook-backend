<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class NotificationController extends Controller
{
    /**
     * Get user notifications list and unread count.
     *
     * GET /api/notifications
     */
    public function index(): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        $notifications = AppNotification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(30)
            ->get();

        $unreadCount = AppNotification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count'  => $unreadCount,
        ]);
    }

    /**
     * Mark a specific notification as read.
     *
     * PUT /api/notifications/{id}/read
     */
    public function markAsRead($id): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        $notification = AppNotification::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (! $notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }

        if (! $notification->read_at) {
            $notification->read_at = now();
            $notification->save();
        }

        $unreadCount = AppNotification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'message'      => 'Notification marked as read',
            'notification' => $notification,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Mark all notifications of the user as read.
     *
     * PUT /api/notifications/read-all
     */
    public function markAllAsRead(): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        AppNotification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'message'      => 'All notifications marked as read',
            'unread_count' => 0,
        ]);
    }
}
