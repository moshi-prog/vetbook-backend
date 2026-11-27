<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AppNotification;
use App\Models\ClinicSetting;
use App\Models\Pet;
use App\Models\User;
use App\Services\BranchCapacityService;
use App\Services\PushNotificationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Tymon\JWTAuth\Facades\JWTAuth;

class AppointmentController extends Controller
{
    /**
     * Branches that accept bookings.
     */
    private const BRANCHES = ['Mabini Branch'];

    /**
     * Statuses that occupy a time slot.
     */
    private const ACTIVE_STATUSES = [
        Appointment::STATUS_PENDING,
        Appointment::STATUS_APPROVED,
    ];

    /**
     * Reject appointments scheduled during the 12:00 PM - 1:00 PM clinic lunch break.
     */
    private function rejectIfLunchBreak(string $appointmentTime): ?JsonResponse
    {
        $timeParts = explode(':', $appointmentTime);
        $hour = (int) ($timeParts[0] ?? -1);
        if ($hour === 12) {
            return response()->json([
                'message' => 'The clinic is closed for lunch break between 12:00 PM and 1:00 PM. Please select a morning or afternoon slot.',
            ], 422);
        }

        return null;
    }

    /**
     * Return all appointments belonging to the authenticated user.
     *
     * GET /api/user/appointments
     */
    public function index(Request $request): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        try {
            \App\Services\AppointmentReminderService::dispatchDueReminders($user->id);
        } catch (\Throwable $e) {
            \Log::warning('Due appointment reminder/auto-cancel check failed: ' . $e->getMessage());
        }

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
     * Return already-booked HH:MM slots for a branch, date, and service pool.
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

        $date = Carbon::parse($request->date)->toDateString();
        $serviceParam = $request->input('service', $request->input('reason'));
        $result = BranchCapacityService::computeUnavailableSlots($request->branch, $date, $serviceParam);

        return response()->json([
            'status'           => 'success',
            'booked_slots'     => $result['booked_slots'],
            'cutoff_slots'     => $result['cutoff_slots'],
            'slot_counts'      => $result['slot_counts'],
            'remaining_slots'  => $result['remaining_slots'],
            'capacity'         => $result['capacity'],
            'pool'             => $result['pool'],
            'duration_minutes' => $result['duration_minutes'],
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    /**
     * Book a new appointment for one or multiple authenticated user's pets.
     *
     * POST /api/appointments
     */
    public function store(Request $request): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        $serviceType = Appointment::resolveServiceType($request->input('reason'), $request->input('service_type'));
        $isSurgery = $serviceType === 'surgery';

        $validator = Validator::make($request->all(), [
            'pet_id'              => 'nullable|integer|exists:pets,id|required_without:pet_ids',
            'pet_ids'             => 'nullable|array|min:1|required_without:pet_id',
            'pet_ids.*'           => 'integer|exists:pets,id',
            'branch'              => ['required', 'string', Rule::in(self::BRANCHES)],
            'appointment_date'    => $isSurgery ? 'nullable|date' : 'required|date',
            'appointment_time'    => $isSurgery ? 'nullable|string|max:20' : 'required|string|date_format:H:i',
            'service_type'        => 'nullable|string|max:100',
            'reason'              => 'nullable|string|max:255',
            'notes'               => 'nullable|string|max:500',
            'preferred_time_from' => 'nullable|string|max:30',
            'preferred_time_to'   => 'nullable|string|max:30',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 400);
        }

        $tz = ClinicSetting::get('pref_timezone', 'Asia/Manila') ?: 'Asia/Manila';
        $nowTz = Carbon::now($tz);
        $todayTz = $nowTz->copy()->startOfDay();

        $dateCarbon = null;
        if (!$isSurgery) {
            $dateCarbon = Carbon::parse($request->appointment_date, $tz)->startOfDay();

            if ($dateCarbon->lt($todayTz)) {
                return response()->json([
                    'message' => 'Cannot book an appointment in the past. Please choose a valid date.',
                ], 422);
            }

            if ($dateCarbon->isSameDay($todayTz)) {
                $requestedDateTimeTz = Carbon::parse($dateCarbon->toDateString() . ' ' . $request->appointment_time, $tz);
                if ($requestedDateTimeTz->lte($nowTz)) {
                    return response()->json([
                        'message' => 'This time slot has already passed today. Please choose an upcoming time slot.',
                    ], 422);
                }
            }
        }

        $rawPetIds = $request->filled('pet_ids')
            ? (array) $request->input('pet_ids')
            : [$request->input('pet_id')];
        $petIds = array_values(array_unique(array_map('intval', $rawPetIds)));

        $petsById = Pet::whereIn('id', $petIds)->get()->keyBy('id');
        foreach ($petIds as $pid) {
            $p = $petsById->get($pid);
            if (!$p || (int) $p->user_id !== (int) $user->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }
        $pets = array_map(fn ($pid) => $petsById->get($pid), $petIds);

        $duration = Appointment::durationForService($serviceType);
        $batchId = count($pets) > 1 ? 'BATCH-' . Str::upper(Str::random(10)) : null;

        if ($isSurgery) {
            $baseNotes = trim((string) $request->input('notes', ''));
            $combinedNotes = $baseNotes !== '' ? $baseNotes : null;

            $createdAppointments = DB::transaction(function () use ($pets, $user, $request, $batchId, $combinedNotes) {
                $rows = [];
                foreach ($pets as $pet) {
                    $rows[] = Appointment::create([
                        'user_id'          => $user->id,
                        'pet_id'           => $pet->id,
                        'branch'           => $request->branch,
                        'appointment_date' => null,
                        'appointment_time' => 'ON_CALL',
                        'status'           => Appointment::STATUS_PENDING,
                        'reason'           => 'Surgery',
                        'notes'            => $combinedNotes,
                        'service_type'     => 'surgery',
                        'duration_minutes' => 60,
                        'surgery_status'   => 'awaiting_slot',
                        'proposed_date'    => null,
                        'proposed_time'    => null,
                        'batch_booking_id' => $batchId,
                    ]);
                }
                return $rows;
            });

            $petNames = collect($pets)->pluck('name')->implode(', ');

            try {
                foreach ($createdAppointments as $created) {
                    AppNotification::create([
                        'user_id' => $user->id,
                        'title'   => 'On-Call Surgery Request Submitted',
                        'message' => "Your surgery request for {$created->pet->name} at {$request->branch} has been received. Branch staff will assign an available surgery date for you to confirm.",
                        'type'    => 'surgery_requested',
                        'data'    => ['appointment_id' => $created->id, 'pet_id' => $created->pet_id],
                    ]);
                }

                $staffMembers = User::where('role', 'admin')
                    ->where('branch', $request->branch)
                    ->get();
                foreach ($staffMembers as $staff) {
                    AppNotification::create([
                        'user_id' => $staff->id,
                        'title'   => 'On-Call Surgery Request',
                        'message' => "{$user->name} submitted a Surgery request for {$petNames} ({$request->branch}). Open the Surgery Appointments tab to assign an available date.",
                        'type'    => 'surgery_requested',
                        'data'    => ['appointment_id' => $createdAppointments[0]->id, 'branch' => $request->branch],
                    ]);
                }
            } catch (\Throwable $e) {
                \Log::warning('AppNotification surgery request failed: ' . $e->getMessage());
            }

            $loaded = Appointment::with('pet')->whereIn('id', collect($createdAppointments)->pluck('id'))->get();

            return response()->json([
                'message'      => 'On-call surgery request submitted. Branch staff will assign an available surgery date.',
                'appointment'  => $loaded->first(),
                'appointments' => $loaded,
            ], 201);
        }

        $dateStr = $dateCarbon->toDateString();

        if ($lunchError = $this->rejectIfLunchBreak($request->appointment_time)) {
            return $lunchError;
        }

        $requestedStartMins = BranchCapacityService::timeToMinutes($request->appointment_time);
        if ($requestedStartMins === null) {
            return response()->json(['message' => 'Invalid appointment time.'], 400);
        }

        if ($serviceType === 'grooming') {
            if ($requestedStartMins > BranchCapacityService::GROOMING_CUTOFF_MINUTES) {
                return response()->json([
                    'message' => 'Grooming takes 2 hours. The latest booking start time for grooming is 3:00 PM.',
                ], 422);
            }
            foreach ([0, 30, 60, 90] as $offset) {
                if (BranchCapacityService::isLunchMinute($requestedStartMins + $offset)) {
                    return response()->json([
                        'message' => 'Grooming takes 2 hours and cannot overlap the 12:00 PM - 1:00 PM lunch break. Please choose another time.',
                    ], 422);
                }
            }
        }

        $capacity = Appointment::branchCapacityForService($request->branch, $serviceType);
        $occupancy = BranchCapacityService::analyzeDayOccupancy($request->branch, $dateStr);
        $assignedTimes = [];

        foreach ($pets as $idx => $pet) {
            $candidateMins = $requestedStartMins;

            if ($idx === 0) {
                if (!BranchCapacityService::slotHasCapacity($candidateMins, $serviceType, $capacity, $occupancy)) {
                    return response()->json([
                        'message' => 'This time slot is already booked',
                        'error'   => 'This time slot is already booked',
                    ], 409);
                }
            } else {
                while ($candidateMins <= BranchCapacityService::DAY_END_MINUTES && !BranchCapacityService::slotHasCapacity($candidateMins, $serviceType, $capacity, $occupancy)) {
                    $candidateMins += 30;
                }

                if ($candidateMins > BranchCapacityService::DAY_END_MINUTES || !BranchCapacityService::slotHasCapacity($candidateMins, $serviceType, $capacity, $occupancy)) {
                    return response()->json([
                        'message' => 'Not enough available slots remaining on this date for all selected pets.',
                        'error'   => 'Not enough available slots remaining on this date for all selected pets.',
                    ], 409);
                }
            }

            BranchCapacityService::reserveSlotInOccupancy($candidateMins, $serviceType, $occupancy);
            $assignedTimes[$pet->id] = BranchCapacityService::minutesToTime($candidateMins);
        }

        $createdAppointments = DB::transaction(function () use ($pets, $user, $request, $dateStr, $assignedTimes, $serviceType, $duration, $batchId) {
            $rows = [];
            foreach ($pets as $pet) {
                $rows[] = Appointment::create([
                    'user_id'          => $user->id,
                    'pet_id'           => $pet->id,
                    'branch'           => $request->branch,
                    'appointment_date' => $dateStr,
                    'appointment_time' => $assignedTimes[$pet->id],
                    'status'           => Appointment::STATUS_PENDING,
                    'reason'           => $request->reason,
                    'notes'            => $request->notes,
                    'service_type'     => $serviceType,
                    'duration_minutes' => $duration,
                    'batch_booking_id' => $batchId,
                ]);
            }
            return $rows;
        });

        try {
            $admins = User::where('role', 'admin')
                ->where(function ($q) use ($request) {
                    $q->whereNull('branch')
                      ->orWhere('branch', $request->branch);
                })->get();

            foreach ($createdAppointments as $appointment) {
                $appointment->load('pet');
                $formattedDate = Carbon::parse($appointment->appointment_date)->format('M d, Y');
                $formattedTime = Appointment::format12Hour($appointment->appointment_time);

                AppNotification::create([
                    'user_id' => $user->id,
                    'title'   => 'Booking Submitted',
                    'message' => "Your appointment request for {$appointment->pet->name} on {$formattedDate} at {$formattedTime} has been received and is awaiting clinic confirmation.",
                    'type'    => 'appointment_booked',
                    'data'    => [
                        'appointment_id' => $appointment->id,
                        'pet_id'         => $appointment->pet_id,
                    ],
                ]);

                foreach ($admins as $admin) {
                    AppNotification::create([
                        'user_id' => $admin->id,
                        'title'   => 'New Appointment Booked',
                        'message' => "{$user->name} booked an appointment for {$appointment->pet->name} on {$formattedDate} at {$formattedTime} ({$appointment->branch}).",
                        'type'    => 'appointment_booked',
                        'data'    => [
                            'appointment_id' => $appointment->id,
                            'pet_id'         => $appointment->pet_id,
                            'branch'         => $appointment->branch,
                        ],
                    ]);
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('AppNotification creation failed: ' . $e->getMessage());
        }

        $loaded = Appointment::with('pet')->whereIn('id', collect($createdAppointments)->pluck('id'))->get();

        return response()->json([
            'message'      => 'Appointment booked successfully',
            'appointment'  => $loaded->first(),
            'appointments' => $loaded,
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

        if ($lunchError = $this->rejectIfLunchBreak($request->appointment_time)) {
            return $lunchError;
        }

        // Service-aware conflict check excluding current appointment
        $serviceType = Appointment::resolveServiceType($appointment->reason, $appointment->service_type);
        $capacity = Appointment::branchCapacityForService($appointment->branch, $serviceType);
        $occupancy = BranchCapacityService::analyzeDayOccupancy(
            $appointment->branch,
            Carbon::parse($request->appointment_date)->toDateString(),
            $appointment->id
        );
        $startMins = BranchCapacityService::timeToMinutes($request->appointment_time);

        if ($startMins === null || !BranchCapacityService::slotHasCapacity($startMins, $serviceType, $capacity, $occupancy)) {
            return response()->json([
                'message' => 'That time slot is already booked or unavailable for this branch.',
            ], 422);
        }

        $oldDate = !empty($appointment->appointment_date)
            ? \Carbon\Carbon::parse($appointment->appointment_date)->format('M d, Y')
            : 'N/A';
        $oldTime = Appointment::format12Hour($appointment->appointment_time);

        $appointment->appointment_date = $request->appointment_date;
        $appointment->appointment_time = $request->appointment_time;
        // Keep or reset to pending so clinic is aware of changes
        $appointment->status = Appointment::STATUS_PENDING;

        $newDate = \Carbon\Carbon::parse($appointment->appointment_date)->format('M d, Y');
        $newTime = Appointment::format12Hour($appointment->appointment_time);
        $rescheduleTag = "[Rescheduled by Pet Owner] Previous: {$oldDate} at {$oldTime} -> New: {$newDate} at {$newTime}";
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
                'message' => "You requested to reschedule {$petName}'s visit to {$newDate} at {$newTime} (previously {$oldDate} at {$oldTime}). Please wait for the clinic admin to approve your new schedule.",
                'type'    => 'appointment_reschedule_pending',
                'data'    => [
                    'appointment_id' => $appointment->id,
                    'pet_id'         => $appointment->pet_id,
                    'old_date'       => $oldDate,
                    'old_time'       => $oldTime,
                    'new_date'       => $newDate,
                    'new_time'       => $newTime,
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

            $adminMessage = "{$ownerName} rescheduled their appointment for {$petName} to {$newDate} at {$newTime} (previously {$oldDate} at {$oldTime}).";

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

        try {
            \App\Services\AppointmentReminderService::markReminderNotificationsReadForAppointment((int) $appointment->id);
        } catch (\Throwable $e) {
            \Log::warning('Reminder cleanup on owner cancel failed: ' . $e->getMessage());
        }

        $ownerName = $user->name ?? ($appointment->user?->name ?? 'Pet Owner');
        $petName = $appointment->pet?->name ?? 'your pet';
        $formattedDate = $appointment->appointment_date
            ? \Carbon\Carbon::parse($appointment->appointment_date)->format('M d, Y')
            : 'Awaiting Date';

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

        // Send Owner Cancellation Confirmation SMS Notification
        try {
            $phone = $appointment->user?->phone_number;
            if ($phone) {
                $smsOwnerName = $appointment->user?->name ?: $ownerName;
                \App\Services\SMSService::send(
                    $phone,
                    "Hi {$smsOwnerName}, your appointment for {$petName} on {$formattedDate} has been cancelled as requested. — BK Petaholic",
                    [
                        'user_id' => $appointment->user_id,
                        'appointment_id' => $appointment->id,
                        'type' => 'cancellation',
                    ]
                );
            }
        } catch (\Throwable $e) {
            \Log::warning('SMS cancellation notification failed: ' . $e->getMessage());
        }

        return response()->json(['message' => 'Appointment cancelled successfully']);
    }

    /**
     * Pet owner responds to a staff-proposed On-Call Surgery schedule.
     *
     * POST /api/appointments/{id}/surgery-response
     */
    public function respondToSurgerySlot(Request $request, $id): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        $appointment = Appointment::with(['pet', 'user'])->find($id);
        if (!$appointment) {
            return response()->json(['message' => 'Appointment not found'], 404);
        }
        if ((int) $appointment->user_id !== (int) $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'action'              => ['required', 'string', Rule::in(['accept', 'request_another'])],
            'preferred_date'      => 'nullable|date|after:today',
            'preferred_time_from' => 'nullable|string|max:30',
            'preferred_time_to'   => 'nullable|string|max:30',
            'preferred_notes'     => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $petName = $appointment->pet?->name ?? 'pet';
        $dateSource = $appointment->proposed_date ?: $appointment->appointment_date;
        $dateStr = $dateSource ? Carbon::parse($dateSource)->format('M d, Y') : 'Awaiting Date';

        if ($request->action === 'accept') {
            $appointment->surgery_status = 'confirmed';
            $appointment->status = Appointment::STATUS_APPROVED;
            $appointment->attendance_confirmed_at = $appointment->attendance_confirmed_at ?? now();
            $appointment->save();

            $staffTitle = 'Surgery Date Confirmed by Owner';
            $staffMessage = "{$user->name} confirmed the assigned surgery date for {$petName} on {$dateStr} ({$appointment->branch}).";
            $notifType = 'surgery_confirmed';
        } else {
            $extra = trim((string) $request->input('preferred_notes', ''));
            $summaryText = $extra !== '' ? $extra : 'Requested another available surgery date.';
            $tag = "[Owner Requested Another Surgery Date] {$summaryText}";

            $appointment->surgery_status = 'reschedule_requested';
            $appointment->appointment_date = null;
            $appointment->proposed_date = null;
            $appointment->proposed_time = null;
            $appointment->notes = $appointment->notes ? ($appointment->notes . "\n\n" . $tag) : $tag;
            $appointment->save();

            $staffTitle = 'Owner Requested Another Surgery Date';
            $staffMessage = "{$user->name} requested another surgery date for {$petName} ({$appointment->branch}): {$summaryText}";
            $notifType = 'surgery_reschedule_requested';
        }

        try {
            $staffMembers = User::where('role', 'admin')
                ->where('branch', $appointment->branch)
                ->get();
            foreach ($staffMembers as $staff) {
                AppNotification::create([
                    'user_id' => $staff->id,
                    'title'   => $staffTitle,
                    'message' => $staffMessage,
                    'type'    => $notifType,
                    'data'    => ['appointment_id' => $appointment->id, 'pet_id' => $appointment->pet_id],
                ]);
            }
        } catch (\Throwable $e) {
            \Log::warning('AppNotification surgery response failed: ' . $e->getMessage());
        }

        return response()->json([
            'message'     => $request->action === 'accept'
                ? 'Surgery schedule confirmed!'
                : 'Request for another surgery schedule sent to clinic staff.',
            'appointment' => $appointment->load('pet'),
        ]);
    }

    /**
     * Pet owner confirms they will attend their upcoming appointment (24h/3h reminder CTA).
     *
     * POST /api/appointments/{id}/confirm-attendance
     */
    public function confirmAttendance(Request $request, $id): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        $appointment = Appointment::with(['pet', 'user'])->find($id);
        if (!$appointment) {
            return response()->json(['message' => 'Appointment not found'], 404);
        }
        if ((int) $appointment->user_id !== (int) $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $appointment->attendance_confirmed_at = now();
        $appointment->save();

        try {
            \App\Services\AppointmentReminderService::markReminderNotificationsReadForAppointment((int) $appointment->id);
        } catch (\Throwable $e) {
            \Log::warning('Reminder cleanup on confirmAttendance failed: ' . $e->getMessage());
        }

        $petName = $appointment->pet?->name ?? 'your pet';
        $formattedDate = $appointment->appointment_date
            ? Carbon::parse($appointment->appointment_date)->format('M d, Y')
            : 'Awaiting Date';
        $formattedTime = Appointment::format12Hour($appointment->appointment_time);

        try {
            $staffMembers = User::where('role', 'admin')
                ->where('branch', $appointment->branch)
                ->get();
            foreach ($staffMembers as $staff) {
                AppNotification::create([
                    'user_id' => $staff->id,
                    'title'   => 'Attendance Confirmed by Pet Owner',
                    'message' => "{$user->name} confirmed they will attend {$petName}'s appointment on {$formattedDate} at {$formattedTime}.",
                    'type'    => 'attendance_confirmed',
                    'data'    => ['appointment_id' => $appointment->id, 'pet_id' => $appointment->pet_id],
                ]);
            }
        } catch (\Throwable $e) {
            \Log::warning('AppNotification attendance confirm failed: ' . $e->getMessage());
        }

        return response()->json([
            'message'     => 'Thank you! Your attendance has been confirmed.',
            'appointment' => $appointment->load('pet'),
        ]);
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

        if (in_array($request->status, [Appointment::STATUS_CANCELLED, Appointment::STATUS_COMPLETED], true)) {
            try {
                \App\Services\AppointmentReminderService::markReminderNotificationsReadForAppointment((int) $appointment->id);
            } catch (\Throwable $e) {
                \Log::warning('Reminder cleanup on updateStatus failed: ' . $e->getMessage());
            }
        }

        return response()->json([
            'message'     => 'Status updated',
            'appointment' => $appointment->load('pet'),
        ]);
    }
}
