<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\Appointment;
use Carbon\Carbon;

class AppointmentReminderService
{
    /**
     * Dispatch idempotent 24-hour (1 day before) and 3-hour (same day) interactive reminders.
     */
    public static function dispatchDueReminders(?int $userId = null): array
    {
        $sent24h = 0;
        $sent3h = 0;

        $tomorrowQuery = Appointment::with(['pet', 'user'])
            ->whereDate('appointment_date', Carbon::tomorrow()->toDateString())
            ->whereIn('status', [Appointment::STATUS_PENDING, Appointment::STATUS_APPROVED])
            ->whereNull('reminder_24h_sent_at');

        if ($userId !== null) {
            $tomorrowQuery->where('user_id', $userId);
        }

        foreach ($tomorrowQuery->get() as $appointment) {
            $petName = $appointment->pet?->name ?? 'your pet';
            $formattedDate = Carbon::parse($appointment->appointment_date)->format('M d, Y');
            $formattedTime = $appointment->appointment_time === 'ON_CALL'
                ? 'On-Call'
                : Appointment::format12Hour($appointment->appointment_time);

            $title = 'Appointment Tomorrow — Will You Continue?';
            $message = "Reminder: {$petName} has an appointment tomorrow ({$formattedDate} at {$formattedTime}) at {$appointment->branch}. Will your appointment still continue? Please tap to confirm or reschedule.";

            self::notifyOwner($appointment, $title, $message, 'appointment_reminder_24h');
            $appointment->reminder_24h_sent_at = now();
            $appointment->save();
            $sent24h++;
        }

        $todayQuery = Appointment::with(['pet', 'user'])
            ->where(function ($q) {
                $q->whereDate('appointment_date', Carbon::today()->toDateString())
                  ->orWhereDate('appointment_date', Carbon::tomorrow()->toDateString());
            })
            ->whereIn('status', [Appointment::STATUS_PENDING, Appointment::STATUS_APPROVED])
            ->whereNull('reminder_3h_sent_at')
            ->whereNotNull('appointment_time')
            ->where('appointment_time', '!=', '')
            ->where('appointment_time', '!=', 'ON_CALL');

        if ($userId !== null) {
            $todayQuery->where('user_id', $userId);
        }

        $windowStart = now()->subMinute();
        $windowEnd = now()->addHours(3)->addMinute();

        foreach ($todayQuery->get() as $appointment) {
            try {
                $datePart = Carbon::parse($appointment->appointment_date)->toDateString();
                $apptDateTime = Carbon::parse("{$datePart} {$appointment->appointment_time}");
            } catch (\Throwable $e) {
                continue;
            }

            if ($apptDateTime->lt($windowStart) || $apptDateTime->gt($windowEnd)) {
                continue;
            }

            $petName = $appointment->pet?->name ?? 'your pet';
            $formattedTime = Appointment::format12Hour($appointment->appointment_time);
            $title = 'Final Reminder (3 Hours Left) — Still Coming?';
            $message = "Second Reminder: {$petName}'s appointment at {$appointment->branch} is in less than 3 hours ({$formattedTime}). Please confirm if your appointment will still continue.";

            self::notifyOwner($appointment, $title, $message, 'appointment_reminder_3h');
            $appointment->reminder_3h_sent_at = now();
            $appointment->save();
            $sent3h++;
        }

        return [
            'sent_24h' => $sent24h,
            'sent_3h' => $sent3h,
        ];
    }

    private static function notifyOwner(Appointment $appointment, string $title, string $message, string $type): void
    {
        AppNotification::create([
            'user_id' => $appointment->user_id,
            'title'   => $title,
            'message' => $message,
            'type'    => $type,
            'data'    => [
                'appointment_id'        => $appointment->id,
                'pet_id'                => $appointment->pet_id,
                'requires_confirmation' => true,
            ],
        ]);

        if (!empty($appointment->user?->push_token)) {
            try {
                PushNotificationService::send(
                    $appointment->user->push_token,
                    $title,
                    $message,
                    ['appointment_id' => $appointment->id, 'type' => $type, 'requires_confirmation' => true]
                );
            } catch (\Throwable $e) {
                \Log::warning("Push notification failed for {$type}: " . $e->getMessage());
            }
        }
    }
}
