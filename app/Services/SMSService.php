<?php

namespace App\Services;

use App\Models\ClinicSetting;
use App\Models\SmsLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SMSService
{
    public static function send(?string $phoneNumber, string $message, array $context = []): array
    {
        if (empty($phoneNumber)) {
            return ['success' => false, 'message_id' => null, 'error' => 'No phone number provided'];
        }

        $apiToken = config('services.philsms.api_token');
        $senderId = config('services.philsms.sender_id');
        $baseUrl = rtrim(config('services.philsms.base_url'), '/');
        $timeout = config('services.philsms.timeout', 15);

        if (empty($apiToken)) {
            Log::warning('PhilSMS: API token not configured');
            return ['success' => false, 'message_id' => null, 'error' => 'SMS provider not configured'];
        }

        $phoneNumber = self::normalizePhone($phoneNumber);
        if (!$phoneNumber) {
            return ['success' => false, 'message_id' => null, 'error' => 'Invalid phone number'];
        }

        // Create the log entry first (status: pending)
        $log = SmsLog::create([
            'user_id' => $context['user_id'] ?? null,
            'appointment_id' => $context['appointment_id'] ?? null,
            'recipient_phone' => $phoneNumber,
            'type' => $context['type'] ?? 'general',
            'message' => $message,
            'status' => 'pending',
        ]);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiToken,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->timeout($timeout)
            ->post($baseUrl . '/sms/send', [
                'recipient' => $phoneNumber,
                'sender_id' => $senderId,
                'message' => $message,
            ]);

            $body = $response->json() ?? [];

            // Some providers answer HTTP 200 with {"status":"error"}; treat that as failed.
            $bodyStatus = is_array($body) ? ($body['status'] ?? null) : null;
            $bodyRejected = $bodyStatus !== null && strtolower((string) $bodyStatus) !== 'success';

            if ($response->successful() && !$bodyRejected) {
                $log->update([
                    'status' => 'delivered',
                    'provider_message_id' => $body['data']['uid'] ?? $body['message_id'] ?? $body['id'] ?? null,
                ]);
                Log::info('PhilSMS: SMS sent', [
                    'recipient' => $phoneNumber,
                    'message_id' => $body['data']['uid'] ?? $body['message_id'] ?? $body['id'] ?? null,
                ]);
                return [
                    'success' => true,
                    'message_id' => $body['data']['uid'] ?? $body['message_id'] ?? $body['id'] ?? null,
                    'error' => null,
                ];
            }

            $log->update([
                'status' => 'failed',
                'error_message' => $body['message'] ?? 'Unknown error',
            ]);
            Log::error('PhilSMS: SMS failed', [
                'status' => $response->status(),
                'body' => $body,
                'recipient' => $phoneNumber,
            ]);
            return [
                'success' => false,
                'message_id' => null,
                'error' => $body['message'] ?? 'SMS provider returned error',
            ];
        } catch (\Throwable $e) {
            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            Log::error('PhilSMS: Exception', ['message' => $e->getMessage()]);
            return [
                'success' => false,
                'message_id' => null,
                'error' => 'SMS sending failed: ' . $e->getMessage(),
            ];
        }
    }

    public static function normalizePhone(string $phone): ?string
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        if (preg_match('/^\+63(9\d{9})$/', $phone, $m)) {
            return '63' . $m[1];
        }

        if (preg_match('/^0(9\d{9})$/', $phone, $m)) {
            return '63' . $m[1];
        }

        if (preg_match('/^63(9\d{9})$/', $phone, $m)) {
            return '63' . $m[1];
        }

        return null;
    }

    public static function formatTemplate(string $templateKey, string $defaultTemplate, $appointment): string
    {
        $template = ClinicSetting::get($templateKey, $defaultTemplate);

        $date = !empty($appointment->appointment_date)
            ? \Carbon\Carbon::parse($appointment->appointment_date)->format('M d, Y')
            : '';
        $time = (string) ($appointment->appointment_time ?? '');
        $ownerName = $appointment->user?->name ?? 'Client';
        $petName = $appointment->pet?->name ?? 'your pet';
        $branch = (string) ($appointment->branch ?? 'BK Petaholic');

        $replacements = [
            '{owner_name}' => $ownerName,
            '{pet_name}'   => $petName,
            '{date}'       => $date,
            '{time}'       => $time,
            '{branch}'     => $branch,
        ];

        return strtr($template, $replacements);
    }

    public static function sendAppointmentConfirmation($appointment): array
    {
        $user = $appointment->user;
        $pet = $appointment->pet;
        if (!$user || empty($user->phone_number)) {
            return ['success' => false, 'message_id' => null, 'error' => 'Missing user or pet'];
        }

        $defaultTemplate = 'Hi {owner_name}, your appointment for {pet_name} is confirmed for {date} at {time}. — BK Petaholic';
        $message = self::formatTemplate('sms_template_confirmation', $defaultTemplate, $appointment);

        return self::send($user->phone_number, $message, [
            'user_id' => $user->id,
            'appointment_id' => $appointment->id,
            'type' => 'confirmation',
        ]);
    }

    public static function sendAppointmentRescheduled($appointment): array
    {
        $user = $appointment->user;
        $phone = $user?->phone_number;

        if (!$phone) {
            return ['success' => false, 'message_id' => null, 'error' => 'No phone number provided'];
        }

        $defaultTemplate = 'Hi {owner_name}, your appointment for {pet_name} has been rescheduled to {date} at {time}. — BK Petaholic';
        $message = self::formatTemplate('sms_template_rescheduled', $defaultTemplate, $appointment);

        return self::send($phone, $message, [
            'user_id' => $appointment->user_id,
            'appointment_id' => $appointment->id,
            'type' => 'rescheduled',
        ]);
    }

    public static function sendAppointmentCompleted($appointment): array
    {
        $user = $appointment->user;
        $phone = $user?->phone_number;

        if (!$phone) {
            return ['success' => false, 'message_id' => null, 'error' => 'No phone number provided'];
        }

        $defaultTemplate = 'Hi {owner_name}, thank you for bringing {pet_name} to BK Petaholic today! We hope {pet_name} is doing well.';
        $message = self::formatTemplate('sms_template_completed', $defaultTemplate, $appointment);

        return self::send($phone, $message, [
            'user_id' => $appointment->user_id,
            'appointment_id' => $appointment->id,
            'type' => 'completed',
        ]);
    }

    public static function sendAppointmentReminder($appointment): array
    {
        $user = $appointment->user;
        $pet = $appointment->pet;
        if (!$user || !$pet) {
            return ['success' => false, 'message_id' => null, 'error' => 'Missing user or pet'];
        }

        $message = "BK Petaholic: Reminder - {$pet->name} has an appointment tomorrow at "
            . "{$appointment->appointment_time}, {$appointment->branch}. See you!";

        return self::send($user->phone_number, $message, [
            'user_id' => $user->id,
            'appointment_id' => $appointment->id,
            'type' => 'reminder',
        ]);
    }

    public static function sendVaccinationReminder($user, $pet, $dueDate): array
    {
        $message = "Hi {$user->name}, {$pet->name}'s vaccine is due on "
            . \Carbon\Carbon::parse($dueDate)->format('M d, Y')
            . ". Please book a visit. - BK Petaholic";

        return self::send($user->phone_number, $message, [
            'user_id' => $user->id,
            'type' => 'vaccination',
        ]);
    }

    public static function sendAppointmentCancellation($appointment): array
    {
        $user = $appointment->user;
        $phone = $user?->phone_number;

        if (!$phone) {
            return ['success' => false, 'message_id' => null, 'error' => 'No phone number provided'];
        }

        $defaultTemplate = 'Hi {owner_name}, your appointment on {date} has been cancelled. Please rebook if needed.';
        $message = self::formatTemplate('sms_template_cancellation', $defaultTemplate, $appointment);

        return self::send($phone, $message, [
            'user_id' => $appointment->user_id,
            'appointment_id' => $appointment->id,
            'type' => 'cancellation',
        ]);
    }
}
