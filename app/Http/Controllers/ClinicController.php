<?php

namespace App\Http\Controllers;

use App\Models\ClinicSetting;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class ClinicController extends Controller
{
    /**
     * Get branch information with independent per-branch hotlines from clinic_settings.
     */
    public function getBranches(): JsonResponse
    {
        // Each branch hotline is stored independently; there is intentionally no
        // cross-branch, global hotline, or admin-phone fallback.
        $mabiniPhone = ClinicSetting::get('branch_mabini_phone', '0917-123-4567');
        $batangasPhone = ClinicSetting::get('branch_batangas_phone', '0918-765-4321');

        $mabiniAdmin = User::where('role', 'admin')
            ->where(function ($q) {
                $q->where('branch', 'like', '%Mabini%')->orWhere('branch', 'mabini');
            })
            ->first();
        $batangasAdmin = User::where('role', 'admin')
            ->where(function ($q) {
                $q->where('branch', 'like', '%Batangas%')->orWhere('branch', 'batangas_city');
            })
            ->first();
        // Helper to normalize phone number to standard 11-digit 09XXXXXXXXX
        $normalizePhone = function ($phone) {
            $digits = preg_replace('/[^0-9]/', '', (string)$phone);
            if (strlen($digits) === 12 && str_starts_with($digits, '639')) {
                $digits = '0' . substr($digits, 2);
            } elseif (strlen($digits) === 10 && str_starts_with($digits, '9')) {
                $digits = '0' . $digits;
            }
            return $digits;
        };

        // Helper to format clean tel: URL digits
        $formatDialUrl = function ($phone) use ($normalizePhone) {
            $digits = $normalizePhone($phone);
            if (!empty($digits)) {
                return 'tel:' . $digits;
            }
            $clean = preg_replace('/[^0-9+]/', '', (string)$phone);
            return 'tel:' . $clean;
        };

        // Helper to pretty-format Philippine mobile numbers (09XX-XXX-XXXX) if 11 digits
        $formatDisplay = function ($phone) use ($normalizePhone) {
            $digits = $normalizePhone($phone);
            if (strlen($digits) === 11 && str_starts_with($digits, '09')) {
                return substr($digits, 0, 4) . '-' . substr($digits, 4, 3) . '-' . substr($digits, 7, 4);
            }
            return (string)$phone;
        };

        $parseTimeRange = function (?string $hourString, string $defaultOpen = '09:00', string $defaultClose = '18:00') {
            if (empty($hourString)) {
                return ['open' => $defaultOpen, 'close' => $defaultClose, 'is_closed' => false];
            }

            if (stripos($hourString, 'closed') !== false) {
                return ['open' => null, 'close' => null, 'is_closed' => true];
            }

            // Strip prefix labels like "Mon–Sat:", "Sun:", etc.
            $cleaned = preg_replace('/^(mon(day)?\s*[\-–]\s*(fri(day)?|sat(urday)?)|sat(urday)?|sun(day)?)\s*:\s*/i', '', trim($hourString));

            // Match "9:00 AM – 6:00 PM", "09:00 - 15:00", "9:00AM-3:00PM", etc.
            if (preg_match('/(\d{1,2}(?::\d{2})?\s*(?:AM|PM)?)\s*[\-–—to]+\s*(\d{1,2}(?::\d{2})?\s*(?:AM|PM)?)/i', $cleaned, $matches)) {
                try {
                    $open = \Carbon\Carbon::parse(trim($matches[1]))->format('H:i');
                    $close = \Carbon\Carbon::parse(trim($matches[2]))->format('H:i');
                    return ['open' => $open, 'close' => $close, 'is_closed' => false];
                } catch (\Throwable $e) {}
            }

            return ['open' => $defaultOpen, 'close' => $defaultClose, 'is_closed' => false];
        };

        $getBranchHours = function (string $key, string $defaultWeekday, string $defaultSunday) use ($parseTimeRange) {
            $savedWeekday = ClinicSetting::get("branch_{$key}_hours_weekday");
            $savedSaturday = ClinicSetting::get("branch_{$key}_hours_saturday");
            $savedSunday = ClinicSetting::get("branch_{$key}_hours_sunday");

            if ($savedWeekday === null && $savedSaturday === null && $savedSunday === null) {
                $savedWeekday = ClinicSetting::get("clinic_hours_weekday") ?: ClinicSetting::get("hours_weekday");
                $savedSaturday = ClinicSetting::get("clinic_hours_saturday") ?: ClinicSetting::get("hours_saturday");
                $savedSunday = ClinicSetting::get("clinic_hours_sunday") ?: ClinicSetting::get("hours_sunday");
            }

            $cleanTime = function ($val) {
                $str = trim((string)$val);
                return preg_replace('/^(mon(day)?\s*[\-–]\s*(fri(day)?|sat(urday)?)|sat(urday)?|sun(day)?)\s*:\s*/i', '', $str);
            };

            if (!empty($savedWeekday)) {
                $w = $cleanTime($savedWeekday);
                $s = !empty($savedSaturday) ? $cleanTime($savedSaturday) : '';
                if ($s !== '' && strcasecmp($s, 'closed') === 0) {
                    $hoursWeekday = "Mon–Fri: {$w} | Sat: Closed";
                } elseif ($s !== '' && $s !== $w) {
                    $hoursWeekday = "Mon–Fri: {$w} | Sat: {$s}";
                } elseif ($s !== '' && $s === $w) {
                    $hoursWeekday = "Mon–Sat: {$w}";
                } else {
                    $hoursWeekday = "Mon–Fri: {$w}";
                }
            } elseif (!empty($savedSaturday)) {
                $hoursWeekday = "Sat: " . $cleanTime($savedSaturday);
            } else {
                $hoursWeekday = $defaultWeekday;
            }

            if (!empty($savedSunday)) {
                $sun = $cleanTime($savedSunday);
                if (strcasecmp($sun, 'closed') === 0 || empty($sun)) {
                    $hoursSunday = 'Sun: Closed';
                } else {
                    $hoursSunday = "Sun: {$sun}";
                }
            } else {
                $hoursSunday = $defaultSunday;
            }

            $weekdayTimes = $parseTimeRange($savedWeekday ?: $hoursWeekday, '09:00', '18:00');
            $sundayTimes = $parseTimeRange($savedSunday ?: $hoursSunday, '10:00', '16:00');

            return [
                'hoursWeekday' => $hoursWeekday,
                'hoursSunday' => $hoursSunday,
                'weekday_open' => $weekdayTimes['open'],
                'weekday_close' => $weekdayTimes['close'],
                'sunday_open' => $sundayTimes['is_closed'] ? null : $sundayTimes['open'],
                'sunday_close' => $sundayTimes['is_closed'] ? null : $sundayTimes['close'],
                'sunday_closed' => (bool)$sundayTimes['is_closed'],
            ];
        };

        $mabiniHours = $getBranchHours('mabini', 'Mon–Sat: 9:00 AM – 6:00 PM', 'Sun: 10:00 AM – 4:00 PM');
        $batangasHours = $getBranchHours('batangas', 'Mon–Sat: 9:00 AM – 6:00 PM', 'Sun: 10:00 AM – 4:00 PM');

        $branches = [
            [
                'id' => 'mabini',
                'name' => 'Mabini Branch',
                'address' => '142 Mabini St., Poblacion, Mabini, Batangas',
                'addressSummary' => '142 Mabini St., Poblacion, Mabini',
                'hoursWeekday' => $mabiniHours['hoursWeekday'],
                'hoursSunday' => $mabiniHours['hoursSunday'],
                'weekday_open' => $mabiniHours['weekday_open'],
                'weekday_close' => $mabiniHours['weekday_close'],
                'sunday_open' => $mabiniHours['sunday_open'],
                'sunday_close' => $mabiniHours['sunday_close'],
                'sunday_closed' => $mabiniHours['sunday_closed'],
                'phone' => $formatDisplay($mabiniPhone),
                'dialUrl' => $formatDialUrl($mabiniPhone),
                'admin_name' => $mabiniAdmin?->name,
            ],
            [
                'id' => 'batangas_city',
                'name' => 'Batangas City Branch',
                'address' => 'N.F. Almario Bldg, Kumintang Ilaya, Batangas City',
                'addressSummary' => 'N.F. Almario Bldg, Kumintang Ilaya',
                'hoursWeekday' => $batangasHours['hoursWeekday'],
                'hoursSunday' => $batangasHours['hoursSunday'],
                'weekday_open' => $batangasHours['weekday_open'],
                'weekday_close' => $batangasHours['weekday_close'],
                'sunday_open' => $batangasHours['sunday_open'],
                'sunday_close' => $batangasHours['sunday_close'],
                'sunday_closed' => $batangasHours['sunday_closed'],
                'phone' => $formatDisplay($batangasPhone),
                'dialUrl' => $formatDialUrl($batangasPhone),
                'admin_name' => $batangasAdmin?->name,
            ],
        ];

        $logoUrl = ClinicSetting::getLogoUrl();

        $clinic = [
            'name' => ClinicSetting::get('clinic_name') ?: ClinicSetting::get('name', 'BK Petaholic Animal Clinic'),
            'tagline' => ClinicSetting::get('clinic_tagline') ?: ClinicSetting::get('tagline', 'Compassionate Care for Batangas Pets'),
            'email' => ClinicSetting::get('clinic_email') ?: ClinicSetting::get('email', 'info@bkpetaholic.com'),
            'accreditation' => ClinicSetting::get('clinic_accreditation') ?: ClinicSetting::get('accreditation', 'BAI-RVD-2024-0089'),
            'logo_url' => $logoUrl,
        ];

        return response()->json([
            'status' => 'success',
            'data' => $branches,
            'clinic' => $clinic,
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    public function getClinicInfo(): JsonResponse
    {
        return $this->getBranches();
    }
}
