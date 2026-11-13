<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClinicSetting;
use App\Models\SmsLog;
use App\Models\User;
use App\Services\SMSService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SmsController extends Controller
{
    private const TYPES = ['confirmation', 'rescheduled', 'completed', 'cancellation', 'reminder', 'vaccination'];
    private const STATUSES = ['delivered', 'failed', 'pending'];
    private const RANGES = ['week', 'month', '30days', 'all'];

    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $type = $request->input('type', 'all');
        $status = $request->input('status', 'all');
        $range = $request->input('range', 'month');

        if (!in_array($type, self::TYPES, true)) {
            $type = 'all';
        }
        if (!in_array($status, self::STATUSES, true)) {
            $status = 'all';
        }
        if (!in_array($range, self::RANGES, true)) {
            $range = 'month';
        }

        // Real stats from DB
        $totalSent = SmsLog::count();
        $delivered = SmsLog::where('status', 'delivered')->count();
        $failed = SmsLog::where('status', 'failed')->count();
        $pending = SmsLog::where('status', 'pending')->count();

        $stats = [
            'total' => $totalSent,
            'total_sent' => $totalSent,
            'delivered' => $delivered,
            'failed' => $failed,
            'pending' => $pending,
            'delivery_rate' => $totalSent > 0 ? round(($delivered / $totalSent) * 100, 1) : 0,
        ];

        // Filter + search
        $query = SmsLog::with(['user', 'appointment'])
            ->orderBy('created_at', 'desc');

        if ($type !== 'all') {
            $query->where('type', $type);
        }
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        if ($range !== 'all') {
            $query->where('created_at', '>=', match ($range) {
                'week' => now()->startOfWeek(),
                '30days' => now()->subDays(30),
                default => now()->startOfMonth(),
            });
        }
        if ($search !== '') {
            // Postgres LIKE is case-sensitive; ILIKE gives the expected search behaviour
            $like = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($q) use ($search, $like) {
                $q->where('recipient_phone', $like, "%{$search}%")
                    ->orWhere('message', $like, "%{$search}%")
                    ->orWhereHas('user', fn ($uq) => $uq->where('name', $like, "%{$search}%"));
            });
        }

        $logs = $query->paginate(20)->withQueryString();

        $provider = [
            'connected' => !empty(config('services.iprogsms.api_token')),
            'name' => 'IPROG SMS',
        ];

        $templates = [
            [
                'name' => 'Appointment Confirmation',
                'type' => 'confirmation',
                'body' => ClinicSetting::get(
                    'sms_template_confirmation',
                    'Hi {owner_name}, your appointment for {pet_name} is confirmed for {date} at {time}. — BK Petaholic'
                ),
            ],
            [
                'name' => 'Appointment Rescheduled',
                'type' => 'rescheduled',
                'body' => ClinicSetting::get(
                    'sms_template_rescheduled',
                    'Hi {owner_name}, your appointment for {pet_name} has been rescheduled to {date} at {time}. — BK Petaholic'
                ),
            ],
            [
                'name' => 'Appointment Completed',
                'type' => 'completed',
                'body' => ClinicSetting::get(
                    'sms_template_completed',
                    'Hi {owner_name}, thank you for bringing {pet_name} to BK Petaholic today! We hope {pet_name} is doing well.'
                ),
            ],
            [
                'name' => 'Cancellation Notice',
                'type' => 'cancellation',
                'body' => ClinicSetting::get(
                    'sms_template_cancellation',
                    'Hi {owner_name}, your appointment on {date} has been cancelled. Please rebook if needed.'
                ),
            ],
        ];

        return view('admin.sms.index', [
            'provider' => $provider,
            'stats' => $stats,
            'logs' => $logs,
            'templates' => $templates,
            'filters' => compact('search', 'type', 'status', 'range'),
        ]);
    }

    public function sendTest(Request $request)
    {
        return $this->dispatchSms($request, 'test');
    }

    public function compose(Request $request)
    {
        return $this->dispatchSms($request, 'general');
    }

    /**
     * Validate and send a single SMS through PhilSMS (SMSService logs it to sms_logs).
     * Messages are limited to 160 plain-ASCII characters so they cost exactly 1 credit.
     */
    private function dispatchSms(Request $request, string $type)
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'message' => ['required', 'string'],
        ]);

        $back = fn (string $error) => redirect()->route('admin.sms.index')->with('error', $error)->withInput();

        $phone = SMSService::normalizePhone($data['phone']);
        if (!$phone) {
            return $back('Invalid phone number. Use a Philippine mobile number like 09XXXXXXXXX.');
        }

        $message = trim(str_replace("\r\n", "\n", $data['message']));
        if ($message === '') {
            return $back('Message is required.');
        }
        if (preg_match('/[^\x00-\x7F]/', $message)) {
            return $back('Message must use plain ASCII characters only (no emojis, curly quotes or special dashes) so it costs 1 credit.');
        }
        if (strlen($message) > 160) {
            return $back('Message is ' . strlen($message) . ' characters. The limit is 160 so it costs only 1 credit.');
        }

        // Link the log to a pet owner when the number matches one (stored as 09..., 639... or +639...)
        $userId = User::where('role', 'pet_owner')
            ->whereIn('phone_number', ['0' . substr($phone, 2), $phone, '+' . $phone])
            ->value('id');

        $result = SMSService::send($phone, $message, [
            'user_id' => $userId,
            'type' => $type,
        ]);

        if (!empty($result['success'])) {
            return redirect()->route('admin.sms.index')->with('success', 'SMS sent to ' . $phone . '.');
        }

        // Prefer the error_message stored on the log row by the service
        $stored = SmsLog::where('recipient_phone', $phone)
            ->where('status', 'failed')
            ->orderByDesc('id')
            ->value('error_message');

        return $back('SMS failed: ' . ($stored ?: ($result['error'] ?? 'Unknown error')));
    }
}
