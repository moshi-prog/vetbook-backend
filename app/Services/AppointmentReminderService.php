<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\Appointment;
use App\Models\ClinicSetting;
use App\Models\User;
use Carbon\Carbon;

class AppointmentReminderService
{
    public const FINALIZED_RETENTION_MINUTES = 5;

    public const REMINDER_TYPES = [
        'appointment_reminder_24h',
        'appointment_reminder_3h',
        'appointment_reminder_2h',
    ];

    /**
     * Determine whether an appointment was booked same-day (created_at matches appointment_date in clinic TZ)
     * and did not previously receive a 1-day advance reminder.
     */
    private static function isSameDayBooking(Appointment $appointment, string $tz): bool
    {
        if ($appointment->appointment_date === null || $appointment->created_at === null) {
            return false;
        }

        if ($appointment->reminder_24h_sent_at !== null) {
            return false;
        }

        return Carbon::parse($appointment->appointment_date)->toDateString()
            === $appointment->created_at->copy()->timezone($tz)->toDateString();
    }

    /**
     * Permanently remove completed and cancelled appointments once the 5-minute retention window expires,
     * keeping Pets, MedicalRecords, MedicalRecordImages, and SmsLogs completely intact.
     */
    public static function purgeExpiredFinalizedAppointments(?int $userId = null): int
    {
        $cutoff = now()->subMinutes(self::FINALIZED_RETENTION_MINUTES);

        $query = Appointment::whereIn('status', [
            Appointment::STATUS_COMPLETED,
            Appointment::STATUS_CANCELLED,
        ])->where('updated_at', '<=', $cutoff);

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        $ids = $query->pluck('id')->all();
        if (empty($ids)) {
            return 0;
        }

        foreach ($ids as $id) {
            self::markReminderNotificationsReadForAppointment((int) $id);
        }

        Appointment::whereIn('id', $ids)->delete();

        return count($ids);
    }

    /**
     * Auto-cancel expired/ignored appointments, clear stale reminder notifications,
     * and dispatch app-only 1-day (advance) and 3-hour (same-day) appointment reminders.
     */
    public static function dispatchDueReminders(?int $userId = null): array
    {
        $tz = ClinicSetting::get('pref_timezone', 'Asia/Manila') ?: 'Asia/Manila';
        $nowTz = Carbon::now($tz);

        // Step 0: Permanently purge completed/cancelled appointments past the 5-minute grace window
        // (Runs BEFORE autoCancelExpiredReminders so newly auto-cancelled rows still stay visible for 5 minutes.)
        $purgedFinalized = self::purgeExpiredFinalizedAppointments($userId);

        // Step A: Auto-cancel expired unconfirmed appointments
        $autoCancelled = self::autoCancelExpiredReminders($userId, $tz);

        // Step B: Clean up stale unread reminder notifications for finalized/confirmed/missing appointments
        self::cleanupStaleReminderNotifications($userId);

        // Step C: Dispatch 1-Day (24-Hour) before reminder for tomorrow's appointments
        $sent24h = 0;
        $tomorrowDateStr = $nowTz->copy()->addDay()->toDateString();

        $tomorrowQuery = Appointment::with(['pet', 'user'])
            ->whereNotNull('appointment_date')
            ->whereDate('appointment_date', $tomorrowDateStr)
            ->whereIn('status', [Appointment::STATUS_PENDING, Appointment::STATUS_APPROVED])
            ->whereNull('reminder_24h_sent_at')
            ->whereNull('attendance_confirmed_at');

        if ($userId !== null) {
            $tomorrowQuery->where('user_id', $userId);
        }

        foreach ($tomorrowQuery->get() as $appointment) {
            if ($appointment->appointment_date === null) {
                continue;
            }
            $petName = $appointment->pet?->name ?? 'your pet';
            $formattedDate = Carbon::parse($appointment->appointment_date)->format('M d, Y');
            $formattedTime = strtoupper(trim((string) $appointment->appointment_time)) === 'ON_CALL'
                ? 'On-Call'
                : Appointment::format12Hour($appointment->appointment_time);

            $title = 'Appointment Tomorrow — Will You Continue?';
            $message = "Reminder: {$petName} has an appointment tomorrow ({$formattedDate} at {$formattedTime}) at {$appointment->branch}. Will your appointment still continue? Ignoring this reminder will automatically cancel and remove your appointment after a 12-hour waiting time.";

            self::notifyOwner($appointment, $title, $message, 'appointment_reminder_24h', [
                'waiting_time_hours' => 12,
                'cancel_deadline_at' => now()->addHours(12)->toIso8601String(),
            ]);

            $appointment->reminder_24h_sent_at = now();
            $appointment->save();
            $sent24h++;
        }

        // Step D: Dispatch 3-Hour Same-Day Reminder (app-only, no SMS) for today's same-day appointments
        $sent3h = 0;
        $todayDateStr = $nowTz->toDateString();

        $todayQuery = Appointment::with(['pet', 'user'])
            ->whereNotNull('appointment_date')
            ->whereDate('appointment_date', $todayDateStr)
            ->whereIn('status', [Appointment::STATUS_PENDING, Appointment::STATUS_APPROVED])
            ->whereNull('reminder_24h_sent_at')
            ->whereNull('reminder_3h_sent_at')
            ->whereNull('attendance_confirmed_at');

        if ($userId !== null) {
            $todayQuery->where('user_id', $userId);
        }

        foreach ($todayQuery->get() as $appointment) {
            if (!self::isSameDayBooking($appointment, $tz)) {
                continue;
            }

            $rawTime = trim((string) ($appointment->appointment_time ?? ''));
            if ($rawTime === '' || strtoupper($rawTime) === 'ON_CALL') {
                continue;
            }

            try {
                $apptDateTime = Carbon::parse($todayDateStr . ' ' . $rawTime, $tz);
            } catch (\Throwable $e) {
                continue;
            }

            if (!$apptDateTime->gt($nowTz) || !$nowTz->gte($apptDateTime->copy()->subHours(3))) {
                continue;
            }

            $petName = $appointment->pet?->name ?? 'your pet';
            $formattedDate = Carbon::parse($appointment->appointment_date)->format('M d, Y');
            $formattedTime = Appointment::format12Hour($appointment->appointment_time);

            $title = 'Same-Day Appointment in 3 Hours — Will You Continue?';
            $message = "Reminder: {$petName} has a same-day appointment today ({$formattedDate} at {$formattedTime}) at {$appointment->branch}. Will your appointment still continue? Ignoring this reminder will automatically cancel and remove your appointment after a 2-hour waiting time.";

            self::notifyOwner($appointment, $title, $message, 'appointment_reminder_3h', [
                'waiting_time_hours' => 2,
                'cancel_deadline_at' => now()->addHours(2)->toIso8601String(),
                'is_same_day'        => true,
            ]);

            $appointment->reminder_3h_sent_at = now();
            $appointment->save();
            $sent3h++;
        }

        return [
            'purged_finalized' => $purgedFinalized,
            'sent_24h'         => $sent24h,
            'sent_3h'          => $sent3h,
            'auto_cancelled'   => $autoCancelled,
        ];
    }

    /**
     * Immediately mark all unread reminder notifications for a given appointment as read
     * so stale center-screen reminder popups never remain stuck.
     */
    public static function markReminderNotificationsReadForAppointment(int $appointmentId): void
    {
        $unread = AppNotification::whereNull('read_at')
            ->whereIn('type', self::REMINDER_TYPES)
            ->get(['id', 'data']);

        $matchingIds = $unread
            ->filter(fn ($notif) => (int) ($notif->data['appointment_id'] ?? 0) === $appointmentId)
            ->pluck('id')
            ->all();

        if (!empty($matchingIds)) {
            AppNotification::whereIn('id', $matchingIds)->update(['read_at' => now()]);
        }
    }

    /**
     * Find any unread reminder notifications pointing to missing, cancelled, completed,
     * or already-confirmed appointments and mark them read.
     */
    public static function cleanupStaleReminderNotifications(?int $userId = null): void
    {
        $query = AppNotification::whereNull('read_at')
            ->whereIn('type', self::REMINDER_TYPES);

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        $unreadReminders = $query->get(['id', 'data']);
        if ($unreadReminders->isEmpty()) {
            return;
        }

        $appointmentIds = $unreadReminders
            ->map(fn ($notif) => (int) ($notif->data['appointment_id'] ?? 0))
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $appointmentsById = !empty($appointmentIds)
            ? Appointment::whereIn('id', $appointmentIds)
                ->get(['id', 'status', 'attendance_confirmed_at'])
                ->keyBy('id')
            : collect();

        $staleIds = $unreadReminders
            ->filter(function ($notif) use ($appointmentsById) {
                $apptId = (int) ($notif->data['appointment_id'] ?? 0);
                if ($apptId <= 0) {
                    return true;
                }

                $appt = $appointmentsById->get($apptId);
                if (!$appt) {
                    return true;
                }

                if (in_array($appt->status, [Appointment::STATUS_CANCELLED, Appointment::STATUS_COMPLETED], true)) {
                    return true;
                }

                return $appt->attendance_confirmed_at !== null;
            })
            ->pluck('id')
            ->all();

        if (!empty($staleIds)) {
            AppNotification::whereIn('id', $staleIds)->update(['read_at' => now()]);
        }
    }

    private static function autoCancelExpiredReminders(?int $userId = null, string $tz = 'Asia/Manila'): int
    {
        $query = Appointment::with(['pet', 'user'])
            ->whereIn('status', [Appointment::STATUS_PENDING, Appointment::STATUS_APPROVED])
            ->whereNull('attendance_confirmed_at');

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        $nowTz = Carbon::now($tz);
        $todayTzStr = $nowTz->toDateString();
        $twelveHoursAgo = now()->subHours(12);
        $twoHoursAgo = now()->subHours(2);
        $cancelled = 0;

        foreach ($query->get() as $appointment) {
            if ($appointment->appointment_date === null) {
                continue;
            }

            $expiredBy12h = $appointment->reminder_24h_sent_at !== null
                && $appointment->reminder_24h_sent_at->lte($twelveHoursAgo);

            $expiredBy2h = $appointment->reminder_3h_sent_at !== null
                && $appointment->reminder_3h_sent_at->lte($twoHoursAgo);

            $expiredByMissedTime = false;
            $rawTime = trim((string) ($appointment->appointment_time ?? ''));
            $isOnCall = strtoupper($rawTime) === 'ON_CALL';

            if (!$isOnCall) {
                $datePart = $appointment->appointment_date
                    ? Carbon::parse($appointment->appointment_date)->toDateString()
                    : null;

                if ($datePart) {
                    if ($datePart < $todayTzStr) {
                        $expiredByMissedTime = true;
                    } elseif ($rawTime !== '') {
                        try {
                            $apptDateTime = Carbon::parse($datePart . ' ' . $rawTime, $tz);
                            if ($apptDateTime->lt($nowTz)) {
                                $expiredByMissedTime = true;
                            }
                        } catch (\Throwable $e) {
                            // Ignore unparseable time strings
                        }
                    }
                }
            }

            if (!$expiredBy12h && !$expiredBy2h && !$expiredByMissedTime) {
                continue;
            }

            $isTwoHourExpiry = $expiredBy2h
                || ($expiredByMissedTime && self::isSameDayBooking($appointment, $tz) && $appointment->reminder_3h_sent_at !== null);

            $petName = $appointment->pet?->name ?? 'your pet';
            $ownerName = $appointment->user?->name ?? 'Pet Owner';
            $formattedDate = $appointment->appointment_date
                ? Carbon::parse($appointment->appointment_date)->format('M d, Y')
                : 'Awaiting Date';
            $formattedTime = $isOnCall
                ? 'On-Call'
                : Appointment::format12Hour($appointment->appointment_time);

            $ownerTitle = 'Appointment Automatically Cancelled';

            if ($isTwoHourExpiry) {
                $tag = '[Auto-Cancelled] Same-day appointment automatically cancelled and removed after the 2-hour waiting time expired without confirmation.';
                $ownerMessage = "Your same-day appointment for {$petName} on {$formattedDate} at {$formattedTime} was automatically cancelled and removed because the 3-hour reminder was ignored past the 2-hour waiting time.";
                $staffMessage = "{$ownerName}'s same-day appointment for {$petName} on {$formattedDate} at {$formattedTime} was auto-cancelled because the 3-hour reminder was ignored beyond the 2-hour waiting time.";
            } else {
                $tag = '[Auto-Cancelled] Appointment automatically cancelled and removed after the 12-hour waiting time expired without confirmation.';
                $ownerMessage = "Your appointment for {$petName} on {$formattedDate} at {$formattedTime} was automatically cancelled and removed because the 1-day reminder was ignored past the 12-hour waiting time.";
                $staffMessage = "{$ownerName}'s appointment for {$petName} on {$formattedDate} at {$formattedTime} was auto-cancelled because the 1-day reminder was ignored beyond the 12-hour waiting time.";
            }

            $appointment->status = Appointment::STATUS_CANCELLED;
            $appointment->notes = $appointment->notes ? ($appointment->notes . "\n\n" . $tag) : $tag;
            $appointment->save();

            self::markReminderNotificationsReadForAppointment((int) $appointment->id);

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
                        $staffMessage,
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
