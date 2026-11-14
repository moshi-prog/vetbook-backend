<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AppNotification;
use App\Models\Pet;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Tymon\JWTAuth\Facades\JWTAuth;

class AppointmentController extends Controller
{
    /**
     * Branches that accept bookings.
     */
    private const BRANCHES = ['Mabini Branch', 'Batangas City Branch'];

    /**
     * Statuses that occupy a time slot.
     */
    private const ACTIVE_STATUSES = [
        Appointment::STATUS_PENDING,
        Appointment::STATUS_APPROVED,
    ];

    /**
     * Return all appointments belonging to the authenticated user.
     *
     * GET /api/user/appointments
     */
    public function index(Request $request): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        $appointments = Appointment::with('pet')
            ->where('user_id', $user->id)
            ->orderBy('appointment_date', 'desc')
            ->orderBy('appointment_time', 'desc')
            ->get();

        return response()->json([
            'appointments' => $appointments,
        ]);
    }

    /**
     * Return already-booked HH:MM slots for a branch and date.
     *
     * GET /api/appointments/booked-slots
     */
    public function getBookedSlots(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'branch' => 'required|string',
            'date'   => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $date = \Carbon\Carbon::parse($request->date)->toDateString();

        $booked = Appointment::where('branch', $request->branch)
            ->whereDate('appointment_date', $date)
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->pluck('appointment_time')
            ->map(fn ($time) => substr((string) $time, 0, 5))
            ->unique()
            ->values();

        return response()->json([
            'status'       => 'success',
            'booked_slots' => $booked,
        ]);
    }

    /**
     * Book a new appointment for one of the authenticated user's pets.
     *
     * POST /api/appointments
     */
    public function store(Request $request): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        $validator = Validator::make($request->all(), [
            'pet_id'           => 'required|integer|exists:pets,id',
            'branch'           => ['required', 'string', Rule::in(self::BRANCHES)],
            'appointment_date' => 'required|date|after_or_equal:today',
            // Fixed H:i format keeps the conflict check below reliable
            'appointment_time' => 'required|string|date_format:H:i',
            'reason'           => 'nullable|string|max:255',
            'notes'            => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 400);
        }

        $pet = Pet::find($request->pet_id);

        if ((int) $pet->user_id !== (int) $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $conflict = Appointment::where('branch', $request->branch)
            ->whereDate('appointment_date', $request->appointment_date)
            ->where('appointment_time', $request->appointment_time)
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->exists();

        if ($conflict) {
            return response()->json([
                'message' => 'This time slot is already booked',
                'error'   => 'This time slot is already booked',
            ], 409);
        }

        $appointment = Appointment::create([
            'user_id'          => $user->id,
            'pet_id'           => $pet->id,
            'branch'           => $request->branch,
            'appointment_date' => $request->appointment_date,
            'appointment_time' => $request->appointment_time,
            'status'           => Appointment::STATUS_PENDING,
            'reason'           => $request->reason,
            'notes'            => $request->notes,
        ]);

        // Send SMS acknowledgment to pet owner
        if ($appointment->user && !empty($appointment->user->phone_number)) {
            try {
                $appointment->load(['pet', 'user']);
                $smsResult = \App\Services\SMSService::send(
                    $appointment->user->phone_number,
                    "BK Petaholic: Booking for {$appointment->pet->name} on "
                    . \Carbon\Carbon::parse($appointment->appointment_date)->format('M d, Y')
                    . " {$appointment->appointment_time} received. "
                    . "We will text you once confirmed.",
                    [
                        'user_id' => $appointment->user_id,
                        'appointment_id' => $appointment->id,
                        'type' => 'confirmation',
                    ]
                );
                if (!$smsResult['success']) {
                    \Log::warning('Booking SMS failed', [
                        'appointment_id' => $appointment->id,
                        'error' => $smsResult['error'],
                    ]);
                }
            } catch (\Throwable $e) {
                \Log::error('Booking SMS exception: ' . $e->getMessage());
            }
        } else {
            \Log::info('Booking SMS skipped — no phone number', [
                'appointment_id' => $appointment->id,
                'user_id' => $appointment->user_id ?? null,
            ]);
        }

        // Create In-App Notification for Pet Owner
        try {
            $formattedDate = \Carbon\Carbon::parse($appointment->appointment_date)->format('M d, Y');
            AppNotification::create([
                'user_id' => $user->id,
                'title'   => 'Booking Submitted',
                'message' => "Your appointment request for {$pet->name} on {$formattedDate} at {$appointment->appointment_time} has been received and is awaiting clinic confirmation.",
                'type'    => 'appointment_booked',
                'data'    => [
                    'appointment_id' => $appointment->id,
                    'pet_id'         => $pet->id,
                ],
            ]);

            // Notify Admins (Super Admin and relevant Branch Admin)
            $admins = \App\Models\User::where('role', 'admin')
                ->where(function ($q) use ($appointment) {
                    $q->whereNull('branch')
                      ->orWhere('branch', $appointment->branch);
                })->get();

            foreach ($admins as $admin) {
                AppNotification::create([
                    'user_id' => $admin->id,
                    'title'   => 'New Appointment Booked',
                    'message' => "{$user->name} booked an appointment for {$pet->name} on {$formattedDate} at {$appointment->appointment_time} ({$appointment->branch}).",
                    'type'    => 'appointment_booked',
                    'data'    => [
                        'appointment_id' => $appointment->id,
                        'pet_id'         => $pet->id,
                        'branch'         => $appointment->branch,
                    ],
                ]);
            }
        } catch (\Throwable $e) {
            \Log::warning('AppNotification creation failed: ' . $e->getMessage());
        }

        return response()->json([
            'message'     => 'Appointment booked successfully',
            'appointment' => $appointment->load('pet'),
        ], 201);
    }

    /**
     * Return a single appointment (must belong to the authenticated user).
     *
     * GET /api/appointments/{id}
     */
    public function show($id): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        $appointment = Appointment::with(['pet', 'user'])->find($id);

        if (! $appointment) {
            return response()->json(['message' => 'Appointment not found'], 404);
        }

        if ((int) $appointment->user_id !== (int) $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json(['appointment' => $appointment]);
    }

    /**
     * Reschedule an appointment (must belong to the authenticated user).
     *
     * PUT /api/appointments/{id}/reschedule
     */
    public function reschedule(Request $request, $id): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        $appointment = Appointment::find($id);

        if (! $appointment) {
            return response()->json(['message' => 'Appointment not found'], 404);
        }

        if ((int) $appointment->user_id !== (int) $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Only allow rescheduling pending or approved appointments
        if (! in_array($appointment->status, [Appointment::STATUS_PENDING, Appointment::STATUS_APPROVED], true)) {
            return response()->json([
                'message' => 'Only pending or approved appointments can be rescheduled',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required|date_format:H:i',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Optional conflict check for same branch, date, time
        $conflict = Appointment::where('id', '!=', $appointment->id)
            ->where('branch', $appointment->branch)
            ->where('appointment_date', $request->appointment_date)
            ->where('appointment_time', $request->appointment_time)
            ->whereIn('status', [Appointment::STATUS_PENDING, Appointment::STATUS_APPROVED])
            ->exists();

        if ($conflict) {
            return response()->json([
                'message' => 'That time slot is already booked for this branch.',
            ], 422);
        }

        $appointment->appointment_date = $request->appointment_date;
        $appointment->appointment_time = $request->appointment_time;
        // Keep or reset to pending so clinic is aware of changes
        $appointment->status = Appointment::STATUS_PENDING;

        $formattedDate = \Carbon\Carbon::parse($appointment->appointment_date)->format('M d, Y');
        $rescheduleTag = "[Rescheduled by Pet Owner] New schedule: {$formattedDate} at {$appointment->appointment_time}";
        $appointment->notes = $appointment->notes ? ($appointment->notes . "\n\n" . $rescheduleTag) : $rescheduleTag;

        $appointment->save();

        $appointment->load(['pet', 'user']);
        $petName = $appointment->pet?->name ?? 'your pet';
        $ownerName = $user->name ?? ($appointment->user?->name ?? 'Pet Owner');

        // Create In-App Notification for pet owner
        try {
            AppNotification::create([
                'user_id' => $appointment->user_id,
                'title'   => 'Reschedule Request Pending',
                'message' => "You requested to reschedule {$petName}'s visit to {$formattedDate} at {$appointment->appointment_time}. Please wait for the clinic admin to approve your new schedule.",
                'type'    => 'appointment_reschedule_pending',
                'data'    => [
                    'appointment_id' => $appointment->id,
                    'pet_id'         => $appointment->pet_id,
                ],
            ]);
        } catch (\Throwable $e) {
            \Log::warning('AppNotification reschedule creation failed: ' . $e->getMessage());
        }

        // Notify Admins (Branch Admins & Super Admins)
        try {
            $admins = User::where('role', 'admin')
                ->where(function($q) use ($appointment) {
                    $q->whereNull('branch')
                      ->orWhere('branch', '')
                      ->orWhere('branch', $appointment->branch);
                })
                ->get();

            $adminMessage = "{$ownerName} rescheduled their appointment for {$petName} to {$formattedDate} at {$appointment->appointment_time}.";

            foreach ($admins as $admin) {
                AppNotification::create([
                    'user_id' => $admin->id,
                    'title'   => 'Appointment Rescheduled by Owner',
                    'message' => $adminMessage,
                    'type'    => 'appointment_rescheduled_by_owner',
                    'data'    => [
                        'appointment_id' => $appointment->id,
                        'pet_id'         => $appointment->pet_id,
                    ],
                ]);
            }
        } catch (\Throwable $e) {
            \Log::warning('AppNotification admin reschedule notification failed: ' . $e->getMessage());
        }

        return response()->json([
            'message'     => 'Appointment rescheduled successfully.',
            'appointment' => $appointment->load('pet'),
        ]);
    }

    /**
     * Cancel an appointment (must belong to the authenticated user).
     *
     * PUT /api/appointments/{id}/cancel
     */
    public function cancel(Request $request, $id): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        $appointment = Appointment::with(['pet', 'user'])->find($id);

        if (! $appointment) {
            return response()->json(['message' => 'Appointment not found'], 404);
        }

        if ((int) $appointment->user_id !== (int) $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($appointment->status === Appointment::STATUS_COMPLETED) {
            return response()->json([
                'message' => 'Completed appointments cannot be cancelled',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'cancel_reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $cancelReason = $request->filled('cancel_reason') ? trim($request->cancel_reason) : 'No reason provided';
        $tag = "[Cancelled by Pet Owner] Reason: {$cancelReason}";
        $appointment->notes = $appointment->notes ? ($appointment->notes . "\n\n" . $tag) : $tag;

        $appointment->status = Appointment::STATUS_CANCELLED;
        $appointment->save();

        $ownerName = $user->name ?? ($appointment->user?->name ?? 'Pet Owner');
        $petName = $appointment->pet?->name ?? 'your pet';
        $formattedDate = \Carbon\Carbon::parse($appointment->appointment_date)->format('M d, Y');

        // Create In-App Notification for pet owner
        try {
            AppNotification::create([
                'user_id' => $appointment->user_id,
                'title'   => 'Appointment Cancelled',
                'message' => "You have cancelled your appointment for {$petName} on {$formattedDate}.",
                'type'    => 'appointment_cancelled',
                'data'    => [
                    'appointment_id' => $appointment->id,
                    'pet_id'         => $appointment->pet_id,
                ],
            ]);
        } catch (\Throwable $e) {
            \Log::warning('AppNotification cancel creation failed: ' . $e->getMessage());
        }

        // Notify Admins (Branch Admins & Super Admins)
        try {
            $admins = User::where('role', 'admin')
                ->where(function($q) use ($appointment) {
                    $q->whereNull('branch')
                      ->orWhere('branch', '')
                      ->orWhere('branch', $appointment->branch);
                })
                ->get();

            $adminMessage = "{$ownerName} cancelled their appointment for {$petName} on {$formattedDate}. Reason: {$cancelReason}";

            foreach ($admins as $admin) {
                AppNotification::create([
                    'user_id' => $admin->id,
                    'title'   => 'Appointment Cancelled by Owner',
                    'message' => $adminMessage,
                    'type'    => 'appointment_cancelled_by_owner',
                    'data'    => [
                        'appointment_id' => $appointment->id,
                        'pet_id'         => $appointment->pet_id,
                    ],
                ]);
            }
        } catch (\Throwable $e) {
            \Log::warning('AppNotification admin cancel notification failed: ' . $e->getMessage());
        }

        // Send Cancellation SMS Notification
        try {
            app(\App\Services\SMSService::class)->sendAppointmentCancellation($appointment);
        } catch (\Throwable $e) {
            \Log::warning('SMS cancellation notification failed: ' . $e->getMessage());
        }

        return response()->json(['message' => 'Appointment cancelled successfully']);
    }

    /**
     * Update an appointment's status (admin only).
     *
     * PUT /api/appointments/{id}/status
     */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        // Pet owners must not be able to approve/complete their own bookings
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $appointment = Appointment::find($id);

        if (! $appointment) {
            return response()->json(['message' => 'Appointment not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => [
                'required',
                'string',
                Rule::in([
                    Appointment::STATUS_PENDING,
                    Appointment::STATUS_APPROVED,
                    Appointment::STATUS_COMPLETED,
                    Appointment::STATUS_CANCELLED,
                ]),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 400);
        }

        $appointment->status = $request->status;
        $appointment->save();

        return response()->json([
            'message'     => 'Status updated',
            'appointment' => $appointment->load('pet'),
        ]);
    }
}
