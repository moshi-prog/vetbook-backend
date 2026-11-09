<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminNotificationController extends Controller
{
    /**
     * Fetch recent unread alerts for the authenticated admin (past 15 minutes).
     */
    public function unreadAlerts(): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['alerts' => []], 401);
        }

        $alerts = AppNotification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->where('created_at', '>=', now()->subMinutes(15))
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function (AppNotification $n) {
                $appointmentId = null;
                if (is_array($n->data) && isset($n->data['appointment_id'])) {
                    $appointmentId = $n->data['appointment_id'];
                }

                return [
                    'id' => $n->id,
                    'title' => $n->title,
                    'message' => $n->message,
                    'type' => $n->type,
                    'appointment_id' => $appointmentId,
                    'created_at' => $n->created_at->toIso8601String(),
                    'time_ago' => $n->created_at->diffForHumans(),
                ];
            });

        return response()->json([
            'status' => 'success',
            'alerts' => $alerts,
        ]);
    }

    /**
     * Mark an alert as read / dismissed by ID.
     */
    public function markRead(int $id): JsonResponse
    {
        $user = auth()->user();

        $notification = AppNotification::where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if ($notification) {
            $notification->update(['read_at' => now()]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Notification marked as read.',
        ]);
    }
}

