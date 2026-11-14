<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class PushNotificationService
{
    /**
     * Dispatch a push notification to an Expo push token or Web Push PWA subscription.
     *
     * @param string|null $pushToken Expo push token (ExponentPushToken[...]) or serialized Web Push JSON ({"endpoint":...})
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

        $trimmed = trim($pushToken);

        // 0. Native standalone device fallback (notifications handled locally via client polling)
        if (str_starts_with($trimmed, 'LocalDeviceToken[')) {
            Log::info("PushNotificationService: Local device fallback active ({$trimmed}); notifications handled on device.");
            return ['success' => true, 'response' => 'Local device notification fallback is active.'];
        }

        // 1. Detect Web Push subscription (JSON object containing 'endpoint')
        if (str_starts_with($trimmed, '{') && str_contains($trimmed, 'endpoint')) {
            return static::sendWebPush($pushToken, $title, $body, $data);
        }

        // 2. Default to Expo Push API for native mobile devices
        return static::sendExpoPush($pushToken, $title, $body, $data);
    }

    /**
     * Dispatch a Web Push notification (VAPID) to a browser PWA subscription.
     */
    protected static function sendWebPush(string $subscriptionJson, string $title, string $body, array $data = []): array
    {
        try {
            $subData = json_decode($subscriptionJson, true);
            if (!is_array($subData) || empty($subData['endpoint'])) {
                Log::warning("PushNotificationService: Invalid Web Push subscription JSON payload.");
                return ['success' => false, 'error' => 'Invalid Web Push subscription payload'];
            }

            $subscription = Subscription::create($subData);

            $auth = [
                'VAPID' => [
                    'subject'    => config('services.vapid.subject', 'mailto:vetbookbkpetaholic@gmail.com'),
                    'publicKey'  => config('services.vapid.public_key'),
                    'privateKey' => config('services.vapid.private_key'),
                ],
            ];

            $webPush = new WebPush($auth);

            $payload = json_encode([
                'title' => $title,
                'body'  => $body,
                'data'  => $data,
            ]);

            Log::info("PushNotificationService: Dispatching Web Push to " . substr($subscription->getEndpoint(), 0, 45) . '...');

            $report = $webPush->sendOneNotification($subscription, $payload);

            if ($report->isSuccess()) {
                Log::info("PushNotificationService: Web Push delivered successfully.");
                return ['success' => true, 'response' => 'Web Push notification delivered'];
            }

            $reason = $report->getReason();
            Log::warning("PushNotificationService: Web Push failed: {$reason}");

            // If subscription has expired, unsubscribed, or endpoint is 410/404, purge it from users table
            if ($report->isSubscriptionExpired()) {
                User::where('push_token', $subscriptionJson)->update(['push_token' => null]);
                Log::info("PushNotificationService: Purged expired Web Push subscription from users table.");
            }

            return ['success' => false, 'error' => $reason];
        } catch (\Throwable $e) {
            Log::error("PushNotificationService: WebPush exception: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Dispatch a native push notification via Expo Push API.
     */
    protected static function sendExpoPush(string $pushToken, string $title, string $body, array $data = []): array
    {
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
            Log::info("PushNotificationService: Dispatching Expo push to {$pushToken}", ['title' => $title]);

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
                $ticketData = $resBody['data'] ?? [];
                $firstTicket = is_array($ticketData) && isset($ticketData[0]) ? $ticketData[0] : (is_array($ticketData) ? $ticketData : []);

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
