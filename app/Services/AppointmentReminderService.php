<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\Appointment;
use App\Models\User;
use Carbon\Carbon;

class AppointmentReminderService
{
    /**
     * Auto-cancel ignored 2-hour reminders past the 1-hour waiting window and dispatch
     * app-only interactive 24-hour (1 day before) and 2-hour (same day) reminders.
     */
    public static function dispatchDueReminders(?int $userId = null): array
    {
        $autoCancelled = self::autoCancelExpiredReminders($userId);
        $sent24h = 0;
        $sent2h = 0;

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
            $message = "Reminder: {$petName} has an appointment tomorrow ({$formattedDate} at {$formattedTime}) at {$appointment->branch}. Will your appointment still continue? Please tap to confirm or cancel.";

            self::notifyOwner($appointment, $title, $message, 'appointment_reminder_24h', [
                'is_urgent_2h'         => false,
                'waiting_time_minutes' => 60,
            ]);
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
        $windowEnd = now()->addHours(2)->addMinute();

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
            $title = 'Urgent: Appointment in 2 Hours — Confirm Now';
            $message = "Reminder: {$petName}'s appointment at {$appointment->branch} is in 2 hours ({$formattedTime}). Will your appointment still continue? Choosing to ignore this reminder will result in automatic appointment cancellation after a 1-hour waiting time.";

            self::notifyOwner($appointment, $title, $message, 'appointment_reminder_3h', [
                'is_urgent_2h'         => true,
                'waiting_time_minutes' => 60,
                'cancel_deadline_at'   => now()->addHour()->toIso8601String(),
            ]);
            $appointment->reminder_3h_sent_at = now();
            $appointment->save();
            $sent2h++;
        }

        return [
            'sent_24h'       => $sent24h,
            'sent_2h'        => $sent2h,
            'sent_3h'        => $sent2h,
            'auto_cancelled' => $autoCancelled,
        ];
    }

    private static function autoCancelExpiredReminders(?int $userId = null): int
    {
        $query = Appointment::with(['pet', 'user'])
            ->whereIn('status', [Appointment::STATUS_PENDING, Appointment::STATUS_APPROVED])
            ->whereNotNull('reminder_3h_sent_at')
            ->whereNull('attendance_confirmed_at')
            ->where('reminder_3h_sent_at', '<=', now()->subHour());

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        $cancelled = 0;

        foreach ($query->get() as $appointment) {
            $tag = '[Auto-Cancelled] Ignored 2-hour reminder beyond the 1-hour waiting time.';
            $appointment->status = Appointment::STATUS_CANCELLED;
            $appointment->notes = $appointment->notes ? ($appointment->notes . "\n\n" . $tag) : $tag;
            $appointment->save();

            $petName = $appointment->pet?->name ?? 'your pet';
            $ownerName = $appointment->user?->name ?? 'Pet Owner';
            $formattedDate = Carbon::parse($appointment->appointment_date)->format('M d, Y');
            $formattedTime = $appointment->appointment_time === 'ON_CALL'
                ? 'On-Call'
                : Appointment::format12Hour($appointment->appointment_time);

            $ownerTitle = 'Appointment Automatically Cancelled';
            $ownerMessage = "Your appointment for {$petName} at {$formattedTime} was automatically cancelled after the 1-hour waiting time because the 2-hour reminder was ignored.";

            self::createNotification($appointment->user_id, $ownerTitle, $ownerMessage, 'appointment_auto_cancelled', [
                'appointment_id' => $appointment->id,
                'pet_id'         => $appointment->pet_id,
            ]);

            if (!empty($appointment->user?->push_token)) {
                try {
                    PushNotificationService::send(
                        $appointment->user->push_token,
                        $ownerTitle,
                        $ownerMessage,
                        ['appointment_id' => $appointment->id, 'type' => 'appointment_auto_cancelled']
                    );
                } catch (\Throwable $e) {
                    \Log::warning('Push failed for auto-cancellation: ' . $e->getMessage());
                }
            }

            try {
                $staffMembers = User::where('role', 'admin')
                    ->where('branch', $appointment->branch)
                    ->get();
                foreach ($staffMembers as $staff) {
                    self::createNotification(
                        $staff->id,
                        'Appointment Auto-Cancelled (Ignored Reminder)',
                        "{$ownerName}'s appointment for {$petName} on {$formattedDate} at {$formattedTime} was auto-cancelled because the 2-hour reminder was ignored beyond the 1-hour waiting time.",
                        'appointment_auto_cancelled',
                        ['appointment_id' => $appointment->id, 'pet_id' => $appointment->pet_id]
                    );
                }
            } catch (\Throwable $e) {
                \Log::warning('Staff notification failed for auto-cancellation: ' . $e->getMessage());
            }

            $cancelled++;
        }

        return $cancelled;
    }

    private static function createNotification(int $userId, string $title, string $message, string $type, array $data = []): void
    {
        AppNotification::create([
            'user_id' => $userId,
            'title'   => $title,
            'message' => $message,
            'type'    => $type,
            'data'    => $data,
        ]);
    }

    private static function notifyOwner(Appointment $appointment, string $title, string $message, string $type, array $extraData = []): void
    {
        $payload = array_merge([
            'appointment_id'        => $appointment->id,
            'pet_id'                => $appointment->pet_id,
            'requires_confirmation' => true,
        ], $extraData);

        self::createNotification($appointment->user_id, $title, $message, $type, $payload);

        if (!empty($appointment->user?->push_token)) {
            try {
                PushNotificationService::send(
                    $appointment->user->push_token,
                    $title,
                    $message,
                    array_merge($payload, ['type' => $type])
                );
            } catch (\Throwable $e) {
                \Log::warning("Push notification failed for {$type}: " . $e->getMessage());
            }
        }
    }
}
