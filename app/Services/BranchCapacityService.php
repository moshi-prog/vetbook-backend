<?php

namespace App\Services;

use App\Models\Appointment;
use Carbon\Carbon;

class BranchCapacityService
{
    public const GROOMING_CUTOFF_MINUTES = 900; // 15:00 (3:00 PM)
    public const DAY_START_MINUTES = 450;       // 07:30
    public const DAY_END_MINUTES = 1050;        // 17:30
    public const LUNCH_START_MINUTES = 720;     // 12:00
    public const LUNCH_END_MINUTES = 780;       // 13:00

    private const ACTIVE_STATUSES = [
        Appointment::STATUS_PENDING,
        Appointment::STATUS_APPROVED,
    ];

    /**
     * Resolve independent staff pool name for a service type.
     */
    public static function poolForService(string $serviceType): string
    {
        return $serviceType === 'grooming' ? 'groomer' : 'vet';
    }

    /**
     * Convert HH:MM / HH:MM:SS / 12-hour time strings into minutes from midnight.
     */
    public static function timeToMinutes(?string $time): ?int
    {
        if (empty($time)) {
            return null;
        }

        $trimmed = trim($time);
        if (strtoupper($trimmed) === 'ON_CALL') {
            return null;
        }

        try {
            if (stripos($trimmed, 'am') !== false || stripos($trimmed, 'pm') !== false) {
                $parsed = Carbon::parse($trimmed);
                return ($parsed->hour * 60) + $parsed->minute;
            }
            if (preg_match('/^(\d{1,2}):(\d{2})/', $trimmed, $m)) {
                return ((int) $m[1] * 60) + (int) $m[2];
            }
            $parsed = Carbon::parse($trimmed);
            return ($parsed->hour * 60) + $parsed->minute;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function minutesToTime(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    public static function isLunchMinute(int $minutes): bool
    {
        return $minutes >= self::LUNCH_START_MINUTES && $minutes < self::LUNCH_END_MINUTES;
    }

    /**
     * Build independent Groomer and Veterinarian pool occupancy maps for a branch & date.
     * Includes assigned date-only ON_CALL surgeries in the Veterinarian pool.
     */
    public static function analyzeDayOccupancy(string $branch, string $date, ?int $excludeAppointmentId = null): array
    {
        $query = Appointment::where('branch', $branch)
            ->whereDate('appointment_date', $date)
            ->whereIn('status', self::ACTIVE_STATUSES);

        if ($excludeAppointmentId !== null) {
            $query->where('id', '!=', $excludeAppointmentId);
        }

        $appointments = $query->get(['id', 'appointment_time', 'reason', 'service_type', 'duration_minutes']);

        $groomerCounts = [];
        $vetCounts = [];
        $onCallSurgeriesCount = 0;
        $rawEntries = [];

        foreach ($appointments as $appt) {
            $type = Appointment::resolveServiceType($appt->reason, $appt->service_type);
            $isGrooming = ($type === 'grooming') || ((int) ($appt->duration_minutes ?? 0) >= 120 && $type !== 'surgery');
            $startMins = self::timeToMinutes($appt->appointment_time);

            if ($isGrooming) {
                if ($startMins !== null) {
                    foreach ([0, 30, 60, 90] as $offset) {
                        $m = $startMins + $offset;
                        $groomerCounts[$m] = ($groomerCounts[$m] ?? 0) + 1;
                    }
                }
            } elseif ($type === 'surgery') {
                if ($startMins !== null) {
                    $vetCounts[$startMins] = ($vetCounts[$startMins] ?? 0) + 1;
                    if ((int) ($appt->duration_minutes ?? 0) >= 60) {
                        $m2 = $startMins + 30;
                        $vetCounts[$m2] = ($vetCounts[$m2] ?? 0) + 1;
                    }
                } else {
                    $onCallSurgeriesCount++;
                }
            } else {
                if ($startMins !== null) {
                    $vetCounts[$startMins] = ($vetCounts[$startMins] ?? 0) + 1;
                }
            }

            $rawEntries[] = [
                'start'       => $startMins,
                'is_grooming' => $isGrooming,
                'type'        => $type,
            ];
        }

        return [
            'groomer'            => $groomerCounts,
            'vet'                => $vetCounts,
            'on_call_surgeries'  => $onCallSurgeriesCount,
            'entries'            => $rawEntries,
        ];
    }

    /**
     * Check whether a candidate start minute can fit into its service's independent staff pool.
     */
    public static function slotHasCapacity(int $startMins, string $serviceType, int $capacity, array $occupancy): bool
    {
        if (self::isLunchMinute($startMins) || $startMins > self::DAY_END_MINUTES) {
            return false;
        }

        if ($serviceType === 'grooming') {
            if ($startMins > self::GROOMING_CUTOFF_MINUTES) {
                return false;
            }
            foreach ([0, 30, 60, 90] as $offset) {
                $m = $startMins + $offset;
                if (self::isLunchMinute($m) || ($occupancy['groomer'][$m] ?? 0) >= $capacity) {
                    return false;
                }
            }
            return true;
        }

        $used = ($occupancy['vet'][$startMins] ?? 0) + ($occupancy['on_call_surgeries'] ?? 0);
        return $used < $capacity;
    }

    /**
     * Reserve a single appointment slot inside the in-memory occupancy array (used by multi-pet bookings).
     */
    public static function reserveSlotInOccupancy(int $startMins, string $serviceType, array &$occupancy): void
    {
        if ($serviceType === 'grooming') {
            foreach ([0, 30, 60, 90] as $offset) {
                $m = $startMins + $offset;
                $occupancy['groomer'][$m] = ($occupancy['groomer'][$m] ?? 0) + 1;
            }
        } else {
            $occupancy['vet'][$startMins] = ($occupancy['vet'][$startMins] ?? 0) + 1;
        }
    }

    /**
     * Compute unavailable/booked slots, cut-off slots, occupancy counts, and remaining counts for a branch, date & service.
     */
    public static function computeUnavailableSlots(
        string $branch,
        string $date,
        ?string $requestedService = null,
        ?int $excludeAppointmentId = null
    ): array {
        $occupancy = self::analyzeDayOccupancy($branch, $date, $excludeAppointmentId);

        if ($requestedService === null || trim($requestedService) === '') {
            $booked = [];
            foreach ($occupancy['entries'] as $entry) {
                if ($entry['start'] === null) {
                    continue;
                }
                $cap = Appointment::branchCapacityForService($branch, $entry['is_grooming'] ? 'grooming' : $entry['type']);
                if ($entry['is_grooming']) {
                    foreach ([0, 30, 60, 90] as $offset) {
                        $m = $entry['start'] + $offset;
                        if (($occupancy['groomer'][$m] ?? 0) >= $cap) {
                            $booked[] = self::minutesToTime($m);
                        }
                    }
                } else {
                    $used = ($occupancy['vet'][$entry['start']] ?? 0) + ($occupancy['on_call_surgeries'] ?? 0);
                    if ($used >= $cap) {
                        $booked[] = self::minutesToTime($entry['start']);
                    }
                }
            }

            $booked = array_values(array_unique($booked));
            sort($booked);

            return [
                'booked_slots'     => $booked,
                'cutoff_slots'     => [],
                'slot_counts'      => [],
                'remaining_slots'  => [],
                'capacity'         => 1,
                'pool'             => null,
                'duration_minutes' => 30,
            ];
        }

        $reqType = Appointment::resolveServiceType($requestedService, $requestedService);
        $capacity = Appointment::branchCapacityForService($branch, $reqType);
        $duration = Appointment::durationForService($reqType);
        $pool = self::poolForService($reqType);

        if ($reqType === 'surgery') {
            return [
                'booked_slots'     => [],
                'cutoff_slots'     => [],
                'slot_counts'      => [],
                'remaining_slots'  => [],
                'capacity'         => $capacity,
                'pool'             => $pool,
                'duration_minutes' => $duration,
            ];
        }

        $bookedSlots = [];
        $cutoffSlots = [];
        $slotCounts = [];
        $remainingSlots = [];

        for ($t = self::DAY_START_MINUTES; $t <= self::DAY_END_MINUTES; $t += 30) {
            if (self::isLunchMinute($t)) {
                continue;
            }

            $hhmm = self::minutesToTime($t);

            if ($reqType === 'grooming') {
                $used = max(
                    $occupancy['groomer'][$t] ?? 0,
                    $occupancy['groomer'][$t + 30] ?? 0,
                    $occupancy['groomer'][$t + 60] ?? 0,
                    $occupancy['groomer'][$t + 90] ?? 0
                );
                $slotCounts[$hhmm] = $used;

                if ($t > self::GROOMING_CUTOFF_MINUTES) {
                    $cutoffSlots[] = $hhmm;
                    $bookedSlots[] = $hhmm;
                    $remainingSlots[$hhmm] = 0;
                } elseif (!self::slotHasCapacity($t, $reqType, $capacity, $occupancy)) {
                    $bookedSlots[] = $hhmm;
                    $remainingSlots[$hhmm] = 0;
                } else {
                    $remainingSlots[$hhmm] = max(0, $capacity - $used);
                }
            } else {
                $used = ($occupancy['vet'][$t] ?? 0) + ($occupancy['on_call_surgeries'] ?? 0);
                $slotCounts[$hhmm] = $used;

                if (!self::slotHasCapacity($t, $reqType, $capacity, $occupancy)) {
                    $bookedSlots[] = $hhmm;
                    $remainingSlots[$hhmm] = 0;
                } else {
                    $remainingSlots[$hhmm] = max(0, $capacity - $used);
                }
            }
        }

        return [
            'booked_slots'     => array_values(array_unique($bookedSlots)),
            'cutoff_slots'     => array_values(array_unique($cutoffSlots)),
            'slot_counts'      => $slotCounts,
            'remaining_slots'  => $remainingSlots,
            'capacity'         => $capacity,
            'pool'             => $pool,
            'duration_minutes' => $duration,
        ];
    }
}
