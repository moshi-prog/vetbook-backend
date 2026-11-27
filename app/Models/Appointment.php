<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $fillable = [
        'user_id',
        'pet_id',
        'branch',
        'appointment_date',
        'appointment_time',
        'status',
        'reason',
        'notes',
        'veterinarian',
        'service_type',
        'duration_minutes',
        'surgery_status',
        'proposed_date',
        'proposed_time',
        'reminder_24h_sent_at',
        'reminder_3h_sent_at',
        'attendance_confirmed_at',
        'batch_booking_id',
    ];

    protected function casts(): array
    {
        return [
            'appointment_date' => 'date',
            'proposed_date' => 'date',
            'reminder_24h_sent_at' => 'datetime',
            'reminder_3h_sent_at' => 'datetime',
            'attendance_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Resolve canonical service type key from reason or explicit service_type.
     */
    public static function resolveServiceType(?string $reason, ?string $serviceType = null): string
    {
        $source = strtolower(trim(($serviceType ?? '') . ' ' . ($reason ?? '')));

        if (str_contains($source, 'groom')) {
            return 'grooming';
        }
        if (str_contains($source, 'surg')) {
            return 'surgery';
        }
        if (str_contains($source, 'vaccin') || str_contains($source, 'deworm')) {
            return 'vaccination';
        }

        return 'checkup';
    }

    /**
     * Average slot duration in minutes for a service type.
     */
    public static function durationForService(string $serviceType): int
    {
        return match ($serviceType) {
            'grooming' => 120,
            'surgery' => 60,
            default => 30,
        };
    }

    /**
     * Branch concurrent staff capacity for the service's staff pool (Mabini Branch: 1 Groomer, 1 Vet).
     */
    public static function branchCapacityForService(string $branch, string $serviceType): int
    {
        return 1;
    }

    /**
     * Maximum daily surgeries permitted per clinic branch (Mabini Branch: 1/day).
     */
    public static function dailySurgeryLimitForBranch(string $branch): int
    {
        return 1;
    }

    // Status Constants
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pet()
    {
        return $this->belongsTo(Pet::class);
    }

    /**
     * Format a stored time string into 12-hour AM/PM format (e.g. '2:30 PM').
     */
    public static function format12Hour(?string $time): string
    {
        if (empty($time)) {
            return '';
        }

        if (strtoupper(trim($time)) === 'ON_CALL') {
            return 'On-Call';
        }

        try {
            return Carbon::parse($time)->format('g:i A');
        } catch (\Throwable $e) {
            return (string) $time;
        }
    }

    /**
     * Format appointment time based on the clinic's preferred display time format ('12' vs '24').
     */
    public function getFormattedTimeAttribute(): string
    {
        if (empty($this->appointment_time)) {
            return '—';
        }

        if (strtoupper(trim($this->appointment_time)) === 'ON_CALL') {
            return 'On-Call';
        }

        $format = ClinicSetting::get('pref_time_format', '12');

        try {
            $carbon = Carbon::parse($this->appointment_time);
            return $format === '24' ? $carbon->format('H:i') : $carbon->format('g:i A');
        } catch (\Throwable $e) {
            return (string) $this->appointment_time;
        }
    }
}
