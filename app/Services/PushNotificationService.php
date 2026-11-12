<?php

namespace App\Services;

use App\Models\User;
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
            Log::info("PushNotificationService: Skipped push because pushToken is empty.");
            return ['success' => false, 'error' => 'No push token provided'];
        }

        // Basic sanity check for Expo push token format
        if (!str_starts_with($pushToken, 'ExponentPushToken') && !str_starts_with($pushToken, 'ExpoPushToken')) {
            Log::info("PushNotificationService: non-standard push token format: {$pushToken}");
        }

        $payload = [
            'to'                   => $pushToken,
            'sound'                => 'default',
            'title'                => $title,
            'body'                 => $body,
            'data'                 => empty($data) ? new \stdClass() : $data,
            'priority'             => 'high',
            'channelId'            => 'default',
            '_displayInForeground' => true,
        ];

        try {
            Log::info("PushNotificationService: Dispatching push to {$pushToken}", ['title' => $title]);

            $response = Http::withHeaders([
                'Accept'          => 'application/json',
                'Accept-Encoding' => 'gzip, deflate',
                'Content-Type'    => 'application/json',
            ])->timeout(10)->post('https://exp.host/--/api/v2/push/send', $payload);

            $statusCode = $response->status();
            $resBody = $response->json();

            Log::info("PushNotificationService: Expo HTTP {$statusCode}", ['response' => $resBody]);

            if ($response->successful()) {
                // Expo wraps ticket statuses in {"data": [{"status": "ok"|"error", ...}]}
                $data = $resBody['data'] ?? [];
                $firstTicket = is_array($data) && isset($data[0]) ? $data[0] : (is_array($data) ? $data : []);

                if (isset($firstTicket['status']) && $firstTicket['status'] === 'error') {
                    $errType = $firstTicket['details']['error'] ?? 'UnknownTicketError';
                    $errMsg = $firstTicket['message'] ?? 'Expo ticket returned error status.';

                    Log::warning("PushNotificationService: Expo push rejected for {$pushToken}: {$errType} - {$errMsg}");

                    // If device is not registered, automatically purge stale push token from database
                    if ($errType === 'DeviceNotRegistered') {
                        User::where('push_token', $pushToken)->update(['push_token' => null]);
                        Log::info("PushNotificationService: Purged invalid push token {$pushToken} from users table.");
                    }

                    return ['success' => false, 'error' => $errMsg, 'details' => $firstTicket];
                }

                return ['success' => true, 'response' => $resBody];
            } else {
                $errContent = $response->body();
                Log::warning("PushNotificationService: HTTP {$statusCode} error from Expo: " . $errContent);

                if (str_contains($errContent, 'DeviceNotRegistered')) {
                    User::where('push_token', $pushToken)->update(['push_token' => null]);
                    Log::info("PushNotificationService: Purged invalid push token {$pushToken} due to DeviceNotRegistered HTTP error.");
                }

                return ['success' => false, 'error' => $errContent];
            }
        } catch (\Throwable $e) {
            Log::error("PushNotificationService exception for {$pushToken}: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

