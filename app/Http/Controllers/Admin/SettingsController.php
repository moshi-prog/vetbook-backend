<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClinicSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function index()
    {
        $admins = auth()->check() && auth()->user()->branch === null
            ? User::where('role', 'admin')->orderBy('id')->get()
            : collect();
        $adminUsers = $admins;

        $hoursWeekday = ClinicSetting::get('clinic_hours_weekday', '09:00 AM - 06:00 PM');
        $hoursSaturday = ClinicSetting::get('clinic_hours_saturday', '09:00 AM - 05:00 PM');
        $hoursSunday = ClinicSetting::get('clinic_hours_sunday', '10:00 AM - 04:00 PM');

        if (auth()->check() && auth()->user()->branch !== null) {
            $branchKey = 'mabini';
            $hoursWeekday = ClinicSetting::get("branch_{$branchKey}_hours_weekday", $hoursWeekday);
            $hoursSaturday = ClinicSetting::get("branch_{$branchKey}_hours_saturday", $hoursSaturday);
            $hoursSunday = ClinicSetting::get("branch_{$branchKey}_hours_sunday", $hoursSunday);
        }

        $clinic = [
            'name' => ClinicSetting::get('clinic_name', 'BK Petaholic Animal Clinic'),
            'tagline' => ClinicSetting::get('clinic_tagline', 'Compassionate care for your beloved animal companions'),
            'email' => ClinicSetting::get('clinic_email', 'info@bkpetaholic.com'),
            'phone' => ClinicSetting::get('clinic_phone', '+63 917 123 4567'),
            'accreditation' => ClinicSetting::get('clinic_accreditation', 'BAI-RVD-2024-0089'),
            'logo' => ClinicSetting::get('clinic_logo') ?: ClinicSetting::get('logo', null),
            'logo_url' => ClinicSetting::getLogoUrl(),
            'hours_weekday' => $hoursWeekday,
            'hours_saturday' => $hoursSaturday,
            'hours_sunday' => $hoursSunday,
        ];

        $formatBranchHours = function (string $key, string $defaultHours) {
            $savedWeekday = ClinicSetting::get("branch_{$key}_hours_weekday");
            $savedSaturday = ClinicSetting::get("branch_{$key}_hours_saturday");
            $savedSunday = ClinicSetting::get("branch_{$key}_hours_sunday");

            if ($savedWeekday === null && $savedSaturday === null && $savedSunday === null) {
                return $defaultHours;
            }

            $cleanTime = function ($val) {
                $str = trim((string)$val);
                return preg_replace('/^(mon(day)?\s*[\-–]\s*(fri(day)?|sat(urday)?)|sat(urday)?|sun(day)?)\s*:\s*/i', '', $str);
            };

            $hoursParts = [];
            if (!empty($savedWeekday)) {
                $w = $cleanTime($savedWeekday);
                $s = !empty($savedSaturday) ? $cleanTime($savedSaturday) : '';
                if ($s !== '' && strcasecmp($s, 'closed') === 0) {
                    $hoursParts[] = "Mon–Fri: {$w} | Sat: Closed";
                } elseif ($s !== '' && $s !== $w) {
                    $hoursParts[] = "Mon–Fri: {$w} | Sat: {$s}";
                } elseif ($s !== '' && $s === $w) {
                    $hoursParts[] = "Mon–Sat: {$w}";
                } else {
                    $hoursParts[] = "Mon–Fri: {$w}";
                }
            } elseif (!empty($savedSaturday)) {
                $hoursParts[] = "Sat: " . $cleanTime($savedSaturday);
            }

            if (!empty($savedSunday)) {
                $sun = $cleanTime($savedSunday);
                if (strcasecmp($sun, 'closed') === 0) {
                    $hoursParts[] = 'Sun: Closed';
                } else {
                    $hoursParts[] = "Sun: {$sun}";
                }
            } else {
                $hoursParts[] = 'Sun: Closed';
            }

            return implode(' | ', $hoursParts);
        };

        $mabiniAddress = ClinicSetting::get('branch_mabini_address', '142 Mabini St., Poblacion, Mabini, Batangas');

        $branches = [
            [
                'id' => 1,
                'name' => 'Mabini Branch',
                'address' => $mabiniAddress,
                'key' => 'mabini',
                'phone' => ClinicSetting::get('branch_mabini_phone', '0917-123-4567'),
                'hours' => $formatBranchHours('mabini', 'Mon-Sat: 9:00 AM - 6:00 PM | Sun: 10:00 AM - 4:00 PM'),
                'status' => 'active',
            ],
        ];

        $chatbot = [
            'provider' => 'Google Gemini',
            'model' => 'Gemini 1.5 Flash',
            'enabled' => true,
            'system_prompt' => ClinicSetting::get('chatbot_system_prompt', "You are VetBook Assistant for BK Petaholic Animal Clinic. You help pet owners with appointment booking, clinic information, and general pet care questions. You do NOT provide medical diagnoses. For emergencies, direct users to call the clinic immediately."),
            'faqs' => [
                [
                    'question' => 'What are your operating hours?',
                    'answer' => "We're open Mon-Sat 9AM-6PM and Sun 10AM-4PM at Mabini Branch.",
                ],
                [
                    'question' => 'Where is your clinic located?',
                    'answer' => 'We are located at Mabini Branch (142 Mabini St., Poblacion, Mabini, Batangas).',
                ],
                [
                    'question' => 'How do I book an appointment?',
                    'answer' => "You can easily schedule a consultation using the 'Book Visit' option in the VetBook mobile app.",
                ],
                [
                    'question' => 'What services do you offer?',
                    'answer' => 'We offer general checkups, vaccinations, deworming, emergency treatments, surgery, and grooming.',
                ],
            ],
        ];

        $sms = [
            'provider' => 'IPROG SMS',
            'connected' => !empty(config('services.iprogsms.api_token')),
            'templates' => [
                [
                    'id' => 'confirmation',
                    'name' => 'Appointment Confirmation',
                    'content' => ClinicSetting::get(
                        'sms_template_confirmation',
                        'Hi {owner_name}, your appointment for {pet_name} is confirmed for {date} at {time}. — BK Petaholic'
                    ),
                ],
                [
                    'id' => 'rescheduled',
                    'name' => 'Appointment Rescheduled',
                    'content' => ClinicSetting::get(
                        'sms_template_rescheduled',
                        'Hi {owner_name}, your appointment for {pet_name} has been rescheduled to {date} at {time}. — BK Petaholic'
                    ),
                ],
                [
                    'id' => 'completed',
                    'name' => 'Appointment Completed',
                    'content' => ClinicSetting::get(
                        'sms_template_completed',
                        'Hi {owner_name}, thank you for bringing {pet_name} to BK Petaholic today! We hope {pet_name} is doing well.'
                    ),
                ],
                [
                    'id' => 'cancellation',
                    'name' => 'Cancellation Notice',
                    'content' => ClinicSetting::get(
                        'sms_template_cancellation',
                        'Hi {owner_name}, your appointment on {date} has been cancelled. Please rebook if needed.'
                    ),
                ],
            ],
        ];

        $preferences = [
            'timezone' => ClinicSetting::get('pref_timezone', 'Asia/Manila'),
            'date_format' => ClinicSetting::get('pref_date_format', 'F j, Y'),
            'time_format' => ClinicSetting::get('pref_time_format', '12'),
            'language' => ClinicSetting::get('pref_language', 'en'),
            'session_timeout' => ClinicSetting::get('pref_session_timeout', '60'),
            'email_notifications' => true,
            'maintenance_mode' => false,
        ];

        $branchSchedule = [
            'weekday'  => $this->parseScheduleRange($hoursWeekday, '09:00 AM', '06:00 PM'),
            'saturday' => $this->parseScheduleRange($hoursSaturday, '09:00 AM', '05:00 PM'),
            'sunday'   => $this->parseScheduleRange($hoursSunday, '10:00 AM', '04:00 PM'),
        ];

        return view('admin.settings.index', compact(
            'clinic',
            'branches',
            'chatbot',
            'sms',
            'adminUsers',
            'admins',
            'preferences',
            'branchSchedule'
        ));
    }

    private function parseScheduleRange(?string $raw, string $defaultOpen = '09:00 AM', string $defaultClose = '06:00 PM'): array
    {
        $trimmed = trim((string) $raw);
        if ($trimmed === '') {
            return [
                'closed'    => false,
                'open'      => $defaultOpen,
                'close'     => $defaultClose,
                'formatted' => "{$defaultOpen} - {$defaultClose}",
            ];
        }

        if (stripos($trimmed, 'closed') !== false) {
            return [
                'closed'    => true,
                'open'      => $defaultOpen,
                'close'     => $defaultClose,
                'formatted' => 'Closed',
            ];
        }

        $cleaned = preg_replace(
            '/^(?:mon(?:day)?\s*[\-–]\s*(?:fri(?:day)?|sat(?:urday)?)|sat(?:urday)?s?|sun(?:day)?s?)\s*:?\s*/iu',
            '',
            $trimmed
        );

        $sanitize = static fn (string $token): string =>
            preg_replace('/(?<!:)\b0+(?=[1-9]|\d{2})/', '', trim($token));

        $openFormatted = $defaultOpen;
        $closeFormatted = $defaultClose;

        if (preg_match('/(\d{1,2}(?::\d{2})?\s*(?:AM|PM)?)\s*(?:[\-–—]+|\bto\b)\s*(\d{1,2}(?::\d{2})?\s*(?:AM|PM)?)/iu', $sanitize((string) $cleaned), $matches)) {
            try {
                $openFormatted = Carbon::parse($sanitize($matches[1]))->format('h:i A');
                $closeFormatted = Carbon::parse($sanitize($matches[2]))->format('h:i A');
            } catch (\Throwable $e) {
                $openFormatted = $defaultOpen;
                $closeFormatted = $defaultClose;
            }
        }

        return [
            'closed'    => false,
            'open'      => $openFormatted,
            'close'     => $closeFormatted,
            'formatted' => "{$openFormatted} - {$closeFormatted}",
        ];
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        if ($user->branch !== null) {
            if ($request->filled('email') && strcasecmp(trim((string) $request->input('email')), (string) $user->email) !== 0) {
                abort(403, 'Unauthorized. Only Admins can change login email addresses.');
            }

            $request->validate([
                'name' => 'required|string|max:255',
                'phone_number' => 'nullable|string|max:30',
            ]);

            $user->name = $request->name;
            $user->phone_number = $request->phone_number;
            $user->save();

            $this->forgetBranchesCache();

            return back()->with('success', 'Account details updated successfully.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'phone_number' => 'nullable|string|max:30',
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        if ($request->has('phone_number')) {
            $user->phone_number = $request->phone_number;
        }
        $user->save();

        $this->forgetBranchesCache();

        return back()->with('success', 'Account details updated successfully.');
    }

    public function updateAdmin(Request $request, $id)
    {
        if (auth()->user()->branch !== null) {
            abort(403, 'Unauthorized. Only Admins can update admin accounts.');
        }

        $admin = User::where('role', 'admin')->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $admin->id,
            'phone_number' => 'nullable|string|max:30',
            'branch' => ['nullable', 'string', 'max:255', Rule::in(['Mabini Branch', ''])],
        ]);

        $admin->name = $request->name;
        $admin->email = $request->email;
        $admin->phone_number = $request->phone_number;
        $admin->branch = $request->branch ?: null;
        $admin->save();

        $this->forgetBranchesCache();

        return back()->with('success', "Admin account for {$admin->name} updated successfully.");
    }

    private function forgetBranchesCache(): void
    {
        try {
            Cache::store('file')->forget('api_branches_payload');
        } catch (\Throwable $e) {
            // Silently ignore unwritable file cache in restricted environments
        }
    }

    public function changePassword(Request $request)
    {
        if (auth()->user()->branch !== null) {
            abort(403, 'Unauthorized. Only Admins can change admin passwords.');
        }

        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:6|confirmed',
        ]);

        if (!Hash::check($request->current_password, auth()->user()->password)) {
            return back()->withErrors(['current_password' => 'Current password does not match.']);
        }

        $user = auth()->user();
        $user->password = Hash::make($request->new_password);
        $user->save();

        return back()->with('success', 'Your password has been changed successfully.');
    }

    public function resetAdminPassword(Request $request, $id)
    {
        if (auth()->user()->branch !== null) {
            abort(403, 'Unauthorized. Only Admins can reset admin passwords.');
        }

        $request->validate([
            'new_password' => 'required|min:6|confirmed',
        ]);

        $admin = User::where('role', 'admin')->findOrFail($id);
        $admin->password = Hash::make($request->new_password);
        $admin->save();

        return back()->with('success', 'Password for admin has been reset successfully.');
    }

    public function updateClinic(Request $request)
    {
        $ownKey = $this->ownBranchKey();

        if ($ownKey === null) {
            if ($request->filled('branch_mabini_phone')) {
                abort(403, 'Unauthorized. Branch contact numbers can only be changed by branch staff.');
            }
            if ($request->filled('hours_weekday') || $request->filled('hours_saturday') || $request->filled('hours_sunday')) {
                abort(403, 'Unauthorized. Branch operating schedules can only be changed by branch staff.');
            }
        } else {
            if ($request->has('branch_mabini_address') && trim((string) $request->input('branch_mabini_address')) !== '') {
                abort(403, 'Unauthorized.');
            }
        }

        $rules = [
            'clinic_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'branch_mabini_phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{7,30}$/'],
            'branch_mabini_address' => ['nullable', 'string', 'max:255'],
            'tagline' => 'nullable|string|max:255',
            'accreditation' => 'nullable|string|max:255',
            'hours_weekday' => 'nullable|string|max:100',
            'hours_saturday' => 'nullable|string|max:100',
            'hours_sunday' => 'nullable|string|max:100',
            'logo' => 'nullable|image|max:2048',
            'cropped_logo' => 'nullable|string',
        ];

        $request->validate($rules);

        // Only Super Admin can update clinic branding / logo
        if (auth()->user()->branch === null) {
            if ($request->filled('cropped_logo') && str_starts_with($request->cropped_logo, 'data:image')) {
                $dataUrl = $request->cropped_logo;
                if (preg_match('/^data:image\/(\w+);base64,/', $dataUrl, $matches)) {
                    $imageType = strtolower($matches[1]);
                    $base64Data = substr($dataUrl, strpos($dataUrl, ',') + 1);
                    $decodedImage = base64_decode($base64Data);

                    if ($decodedImage !== false) {
                        $extension = in_array($imageType, ['jpeg', 'jpg', 'png', 'webp', 'gif']) ? $imageType : 'png';
                        $filename = 'clinic/logo_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
                        \Illuminate\Support\Facades\Storage::disk('public')->put($filename, $decodedImage);

                        ClinicSetting::set('clinic_logo', $filename);
                        ClinicSetting::set('logo', $filename);
                        ClinicSetting::set('clinic_logo_data', $dataUrl);
                    }
                }
            } elseif ($request->hasFile('logo') && $request->file('logo')->isValid()) {
                $path = $request->file('logo')->store('clinic', 'public');
                ClinicSetting::set('clinic_logo', $path);
                ClinicSetting::set('logo', $path);
                try {
                    $fileContents = file_get_contents($request->file('logo')->getRealPath());
                    $mime = $request->file('logo')->getMimeType() ?: 'image/png';
                    ClinicSetting::set('clinic_logo_data', 'data:' . $mime . ';base64,' . base64_encode($fileContents));
                } catch (\Throwable $e) {
                    // Ignore if cannot read file contents
                }
            }
        }

        ClinicSetting::set('clinic_name', $request->clinic_name);
        ClinicSetting::set('name', $request->clinic_name);
        ClinicSetting::set('clinic_email', $request->email);
        ClinicSetting::set('email', $request->email);

        if ($request->filled('phone')) {
            ClinicSetting::set('clinic_phone', $request->phone);
            ClinicSetting::set('phone', $request->phone);
        }

        // Admin manages branch address; Branch Staff manages branch hotline
        if ($ownKey === null) {
            if ($request->filled('branch_mabini_address')) {
                ClinicSetting::set('branch_mabini_address', trim($request->branch_mabini_address));
            }
        } else {
            if ($request->filled("branch_{$ownKey}_phone")) {
                ClinicSetting::set("branch_{$ownKey}_phone", trim((string) $request->input("branch_{$ownKey}_phone")));
            }
        }

        ClinicSetting::set('clinic_tagline', $request->tagline);
        ClinicSetting::set('tagline', $request->tagline);

        if ($request->has('accreditation') && ($ownKey !== null || $request->filled('accreditation'))) {
            ClinicSetting::set('clinic_accreditation', $request->accreditation);
            ClinicSetting::set('accreditation', $request->accreditation);
        }

        if ($ownKey !== null) {
            if ($request->has('hours_weekday')) {
                ClinicSetting::set("branch_{$ownKey}_hours_weekday", $request->hours_weekday);
            }
            if ($request->has('hours_saturday')) {
                ClinicSetting::set("branch_{$ownKey}_hours_saturday", $request->hours_saturday);
            }
            if ($request->has('hours_sunday')) {
                ClinicSetting::set("branch_{$ownKey}_hours_sunday", $request->hours_sunday);
            }
        }

        $this->forgetBranchesCache();

        return back()->with('success', 'Clinic information updated successfully.');
    }

    public function updateBranch(Request $request, string $branch)
    {
        $branches = [
            'mabini' => 'Mabini Branch',
        ];
        abort_unless(isset($branches[$branch]), 404);

        $own = $this->ownBranchKey();

        if ($own === null) {
            if ($request->has('phone') && trim((string) $request->input('phone')) !== '') {
                abort(403, 'Unauthorized. Branch contact numbers can only be changed by branch staff.');
            }

            $request->validate([
                'address' => ['required', 'string', 'max:255'],
            ]);

            ClinicSetting::set("branch_{$branch}_address", trim((string) $request->address));

            return redirect(route('admin.settings.index') . '#branches')->with('success', 'Branch address updated successfully.');
        }

        if ($own !== $branch) {
            abort(403, 'Unauthorized. You can only edit your assigned branch.');
        }

        if ($request->has('address') && trim((string) $request->input('address')) !== '') {
            abort(403, 'Unauthorized. Only Admins can change branch addresses.');
        }

        $request->validate([
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{7,30}$/'],
        ]);

        ClinicSetting::set("branch_{$branch}_phone", trim($request->phone));

        return redirect(route('admin.settings.index') . '#branches')->with('success', 'Branch contact number updated successfully.');
    }

    private function ownBranchKey(): ?string
    {
        $userBranch = auth()->user()?->branch;
        if ($userBranch === null || trim($userBranch) === '') {
            return null;
        }
        if (stripos($userBranch, 'mabini') !== false) {
            return 'mabini';
        }

        return null;
    }

    public function updateChatbot(Request $request)
    {
        $request->validate([
            'system_prompt' => 'required|string',
        ]);

        ClinicSetting::set('chatbot_system_prompt', $request->system_prompt);

        return back()->with('success', 'Chatbot settings updated successfully.');
    }

    public function updateSms(Request $request)
    {
        $request->validate([
            'templates' => 'required|array',
            'templates.*' => 'nullable|string|max:160',
        ]);

        foreach ($request->input('templates', []) as $key => $content) {
            if ($content !== null) {
                ClinicSetting::set("sms_template_{$key}", trim($content));
            }
        }

        return redirect(route('admin.settings.index') . '#sms')
            ->with('success', 'SMS notification templates updated successfully.');
    }

    public function updatePreferences(Request $request)
    {
        $validated = $request->validate([
            'timezone'        => 'nullable|string|max:100',
            'date_format'     => 'nullable|string|max:50',
            'time_format'     => 'nullable|in:12,24',
            'session_timeout' => 'nullable|integer|min:5|max:1440',
        ]);

        if (!empty($validated['timezone'])) {
            ClinicSetting::set('pref_timezone', $validated['timezone']);
        }
        if (!empty($validated['date_format'])) {
            ClinicSetting::set('pref_date_format', $validated['date_format']);
        }
        if (!empty($validated['time_format'])) {
            ClinicSetting::set('pref_time_format', $validated['time_format']);
        }
        if (!empty($validated['session_timeout'])) {
            ClinicSetting::set('pref_session_timeout', (string) $validated['session_timeout']);
        }

        return back()->with('success', 'System preferences updated successfully.');
    }
}
