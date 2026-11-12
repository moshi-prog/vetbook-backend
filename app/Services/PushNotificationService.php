<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    /**
     * Dispatch a native push notification to an Expo push token.
     *
     * @param string|null $pushToken Expo push token (e.g. ExponentPushToken[xxx])
     * @param string $title Notification title
     * @param string $body Notification body / message
     * @param array $data Custom JSON data payload
     * @return array Response status details
     */
    public static function send(?string $pushToken, string $title, string $body, array $data = []): array
    {
        if (empty($pushToken)) {
            return ['success' => false, 'error' => 'No push token provided'];
        }

        // Basic sanity check for Expo push token format
        if (!str_starts_with($pushToken, 'ExponentPushToken') && !str_starts_with($pushToken, 'ExpoPushToken')) {
            Log::info("PushNotificationService: non-standard push token format: {$pushToken}");
        }

        $payload = [
            'to' => $pushToken,
            'sound' => 'default',
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'priority' => 'high',
            'channelId' => 'default',
            '_displayInForeground' => true,
        ];

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Accept-Encoding' => 'gzip, deflate',
                'Content-Type' => 'application/json',
            ])->timeout(10)->post('https://exp.host/--/api/v2/push/send', $payload);

            if ($response->successful()) {
                $resData = $response->json();
                Log::info("PushNotificationService: Push sent successfully to {$pushToken}", ['res' => $resData]);
                return ['success' => true, 'response' => $resData];
            } else {
                Log::warning("PushNotificationService: Failed to send push to {$pushToken}: " . $response->body());
                return ['success' => false, 'error' => $response->body()];
            }
        } catch (\Throwable $e) {
            Log::error("PushNotificationService exception: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

