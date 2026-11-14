<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $branch = $user->branch;

        $sort = $request->input('sort', 'newest_booked');

        $query = Appointment::with(['pet', 'user']);

        if ($sort === 'appointment_date') {
            $query->orderBy('appointment_date', 'desc')->orderBy('appointment_time', 'desc');
        } elseif ($sort === 'oldest') {
            $query->orderBy('created_at', 'asc')->orderBy('id', 'asc');
        } else {
            // Default: Newest Booked First so the latest action is ALWAYS at the very top of Row 1
            $query->orderBy('created_at', 'desc')->orderBy('id', 'desc');
        }

        // Branch filter: if branch admin, enforce their branch. If Super Admin ($branch is null), allow filtering by ?branch=...
        if ($branch) {
            $query->where('branch', $branch);
        } elseif ($request->filled('branch') && $request->branch !== 'all') {
            $query->where('branch', $request->branch);
        }

        // Status filter (from query string)
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Date filter: support 'date' (today, tomorrow, YYYY-MM-DD) or 'date_from' / 'date_to'
        $isPgsql = DB::connection()->getDriverName() === 'pgsql';

        if ($request->filled('date')) {
            $dateVal = match ($request->date) {
                'today' => now()->toDateString(),
                'tomorrow' => now()->addDay()->toDateString(),
                default => $request->date,
            };
            if ($isPgsql) {
                $query->whereRaw("appointment_date::date = ?", [$dateVal]);
            } else {
                $query->whereDate('appointment_date', $dateVal);
            }
        } else {
            if ($request->filled('date_from')) {
                if ($isPgsql) {
                    $query->whereRaw("appointment_date::date >= ?", [$request->date_from]);
                } else {
                    $query->whereDate('appointment_date', '>=', $request->date_from);
                }
            }
            if ($request->filled('date_to')) {
                if ($isPgsql) {
                    $query->whereRaw("appointment_date::date <= ?", [$request->date_to]);
                } else {
                    $query->whereDate('appointment_date', '<=', $request->date_to);
                }
            }
        }

        // Search filter (pet name, owner name, phone number, branch, reason)
        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $like = $isPgsql ? 'ilike' : 'like';
            $query->where(function($q) use ($search, $like) {
                $q->where('branch', $like, "%{$search}%")
                  ->orWhere('reason', $like, "%{$search}%")
                  ->orWhereHas('pet', function($pq) use ($search, $like) {
                      $pq->where('name', $like, "%{$search}%");
                  })
                  ->orWhereHas('user', function($uq) use ($search, $like) {
                      $uq->where('name', $like, "%{$search}%")
                         ->orWhere('email', $like, "%{$search}%")
                         ->orWhere('phone_number', $like, "%{$search}%");
                  });
            });
        }

        $appointments = $query->paginate(15)->withQueryString();

        // Stats for the current branch scope (consolidated to 1 single DB query)
        $statsQuery = Appointment::query();
        if ($branch) {
            $statsQuery->where('branch', $branch);
        } elseif ($request->filled('branch') && $request->branch !== 'all') {
            $statsQuery->where('branch', $request->branch);
        }
        $statusCounts = (clone $statsQuery)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $stats = [
            'all' => (int) $statusCounts->sum(),
            'pending' => (int) $statusCounts->get('pending', 0),
            'approved' => (int) $statusCounts->get('approved', 0),
            'completed' => (int) $statusCounts->get('completed', 0),
            'cancelled' => (int) $statusCounts->get('cancelled', 0),
        ];

        return view('admin.appointments.index', compact('appointments', 'stats', 'branch', 'sort'));
    }

    /**
     * Get the ID and timestamp of the latest booked appointment in the current admin's branch scope.
     * Used for real-time table polling on /admin/appointments.
     */
    public function latestId(Request $request): JsonResponse
    {
        $user = auth()->user();
        $branch = $user ? $user->branch : null;

        $query = Appointment::query();
        if ($branch) {
            $query->where('branch', $branch);
        } elseif ($request->filled('branch') && $request->branch !== 'all') {
            $query->where('branch', $request->branch);
        }

        $latest = $query->orderByDesc('id')->first(['id', 'created_at']);

        return response()->json([
            'status'     => 'success',
            'latest_id'  => $latest ? (int) $latest->id : 0,
            'created_at' => $latest && $latest->created_at ? $latest->created_at->toIso8601String() : null,
        ]);
    }

    // Show single appointment detail
    public function show($id)
    {
        $user = auth()->user();
        $appointment = Appointment::with(['pet', 'user'])->findOrFail($id);

        // Branch scope check
        if ($user->branch && $appointment->branch !== $user->branch) {
            abort(403, 'Access denied. This appointment belongs to another branch.');
        }

        $veterinarians = \App\Models\Veterinarian::active()
            ->forBranch($appointment->branch)
            ->orderBy('name')
            ->get();

        return view('admin.appointments.show', compact('appointment', 'veterinarians'));
    }

    // Assign veterinarian to an appointment
    public function assignVeterinarian(Request $request, $id)
    {
        $user = auth()->user();
        $appointment = Appointment::findOrFail($id);

        if ($user->branch && $appointment->branch !== $user->branch) {
            abort(403);
        }

        $validated = $request->validate([
            'veterinarian' => 'nullable|string|max:255',
        ]);

        $appointment->veterinarian = $validated['veterinarian'] ?: null;
        $appointment->save();

        return back()->with('success', 'Veterinarian assigned successfully.');
    }

    // Approve a pending appointment
    public function approve($id)
    {
        $user = auth()->user();
        $appointment = Appointment::with(['pet', 'user'])->findOrFail($id);

        if ($user->branch && $appointment->branch !== $user->branch) {
            abort(403);
        }

        if ($appointment->status !== 'pending') {
            return back()->with('error', 'Only pending appointments can be approved.');
        }

        $appointment->status = 'approved';
        $appointment->save();

        // Create In-App Notification
        try {
            $appointment->load(['user', 'pet']);
            $petName = $appointment->pet?->name ?? 'your pet';
            $formattedDate = \Carbon\Carbon::parse($appointment->appointment_date)->format('M d, Y');
            $formattedTime = Appointment::format12Hour($appointment->appointment_time);
            AppNotification::create([
                'user_id' => $appointment->user_id,
                'title'   => 'Appointment Confirmed',
                'message' => "Great news! Your appointment for {$petName} on {$formattedDate} at {$formattedTime} at {$appointment->branch} has been confirmed by the clinic.",
                'type'    => 'appointment_approved',
                'data'    => [
                    'appointment_id' => $appointment->id,
                    'pet_id'         => $appointment->pet_id,
                ],
            ]);

            // Real-Time Native Push Notification (Lock Screen & Background)
            if ($appointment->user?->push_token) {
                \App\Services\PushNotificationService::send(
                    $appointment->user->push_token,
                    'Appointment Approved! 🐾',
                    "Great news! Your appointment for {$petName} on {$formattedDate} at {$formattedTime} has been confirmed.",
                    ['appointment_id' => $appointment->id, 'type' => 'appointment_approved']
                );
            }
        } catch (\Throwable $e) {
            \Log::warning('AppNotification approve creation failed: ' . $e->getMessage());
        }

        // Send SMS confirmation to pet owner
        try {
            $smsResult = \App\Services\SMSService::sendAppointmentConfirmation($appointment);
        } catch (\Throwable $e) {
            \Log::error('SMS approval notification exception: ' . $e->getMessage(), [
                'appointment_id' => $appointment->id,
            ]);
            $smsResult = ['success' => false, 'message_id' => null, 'error' => $e->getMessage()];
        }

        if ($smsResult['success']) {
            return back()->with('success', 'Appointment approved and SMS confirmation sent.');
        } else {
            \Log::warning('SMS approval notification failed', [
                'appointment_id' => $appointment->id,
                'error' => $smsResult['error'],
            ]);
            return back()->with('success', 'Appointment approved. (SMS notification failed — check logs.)');
        }
    }

    // Mark as completed
    public function complete($id)
    {
        $user = auth()->user();
        $appointment = Appointment::findOrFail($id);

        if ($user->branch && $appointment->branch !== $user->branch) {
            abort(403);
        }

        if (!in_array($appointment->status, ['approved', 'pending'])) {
            return back()->with('error', 'Only approved or pending appointments can be completed.');
        }

        $appointment->status = 'completed';
        $appointment->save();

        // Create In-App Notification for pet owner
        try {
            $appointment->load(['user', 'pet']);
            $petName = $appointment->pet?->name ?? 'your pet';
            AppNotification::create([
                'user_id' => $appointment->user_id,
                'title'   => 'Visit Completed',
                'message' => "Thank you for bringing {$petName} to BK Petaholic today! Consultations and prescription records have been updated.",
                'type'    => 'appointment_completed',
                'data'    => [
                    'appointment_id' => $appointment->id,
                    'pet_id'         => $appointment->pet_id,
                ],
            ]);

            // Real-Time Native Push Notification (Lock Screen & Background)
            if ($appointment->user?->push_token) {
                \App\Services\PushNotificationService::send(
                    $appointment->user->push_token,
                    'Appointment Completed 🐾',
                    "Thank you for bringing {$petName} to BK Petaholic today! Consultations and prescription records have been updated.",
                    ['appointment_id' => $appointment->id, 'type' => 'appointment_completed']
                );
            }
        } catch (\Throwable $e) {
            \Log::warning('AppNotification admin complete creation failed: ' . $e->getMessage());
        }

        // Send SMS completed notification
        try {
            $appointment->load(['user', 'pet']);
            \App\Services\SMSService::sendAppointmentCompleted($appointment);
        } catch (\Throwable $e) {
            \Log::warning('SMS admin complete notification failed: ' . $e->getMessage());
        }

        return back()->with('success', 'Appointment marked as completed.');
    }

    // Cancel an appointment
    public function cancel(Request $request, $id)
    {
        $user = auth()->user();
        $appointment = Appointment::findOrFail($id);

        if ($user->branch && $appointment->branch !== $user->branch) {
            abort(403);
        }

        if (in_array($appointment->status, ['completed', 'cancelled'])) {
            return back()->with('error', 'This appointment cannot be cancelled.');
        }

        $request->validate([
            'cancel_reason' => 'nullable|string|max:500',
        ]);

        $appointment->status = 'cancelled';
        if ($request->filled('cancel_reason')) {
            $appointment->notes = ($appointment->notes ? $appointment->notes . "\n\n" : '')
                . "[Cancelled] " . $request->cancel_reason;
        }
        $appointment->save();

        // Create In-App Notification for pet owner
        try {
            $appointment->load(['user', 'pet']);
            $petName = $appointment->pet?->name ?? 'your pet';
            $formattedDate = \Carbon\Carbon::parse($appointment->appointment_date)->format('M d, Y');
            $reasonNote = $request->filled('cancel_reason') ? " Reason: {$request->cancel_reason}." : '';
            AppNotification::create([
                'user_id' => $appointment->user_id,
                'title'   => 'Appointment Cancelled by Clinic',
                'message' => "Your appointment for {$petName} on {$formattedDate} has been cancelled by the clinic.{$reasonNote}",
                'type'    => 'appointment_cancelled',
                'data'    => [
                    'appointment_id' => $appointment->id,
                    'pet_id'         => $appointment->pet_id,
                ],
            ]);

            // Real-Time Native Push Notification (Lock Screen & Background)
            if ($appointment->user?->push_token) {
                \App\Services\PushNotificationService::send(
                    $appointment->user->push_token,
                    'Appointment Cancelled',
                    "Your appointment for {$petName} on {$formattedDate} has been cancelled by the clinic.{$reasonNote}",
                    ['appointment_id' => $appointment->id, 'type' => 'appointment_cancelled']
                );
            }
        } catch (\Throwable $e) {
            \Log::warning('AppNotification admin cancel creation failed: ' . $e->getMessage());
        }

        // Send Cancellation SMS Notification
        try {
            app(\App\Services\SMSService::class)->sendAppointmentCancellation($appointment);
        } catch (\Throwable $e) {
            \Log::warning('SMS admin cancellation notification failed: ' . $e->getMessage());
        }

        return redirect()->route('admin.appointments.index')
            ->with('success', 'Appointment cancelled successfully.');
    }

    // Reschedule appointment (change date/time)
    public function reschedule(Request $request, $id)
    {
        $user = auth()->user();
        $appointment = Appointment::findOrFail($id);

        if ($user->branch && $appointment->branch !== $user->branch) {
            abort(403);
        }

        if (in_array($appointment->status, ['completed', 'cancelled'], true)) {
            return back()->with('error', 'Completed or cancelled appointments cannot be rescheduled.');
        }

        $validated = $request->validate([
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required|date_format:H:i',
        ]);

        // Block lunch break hour (12:00 PM - 12:59 PM)
        if ((int) explode(':', $validated['appointment_time'])[0] === 12) {
            return back()->withInput()->with('error', 'The clinic is closed for lunch break between 12:00 PM and 1:00 PM. Please choose another time slot.');
        }

        // Conflict check
        $conflict = Appointment::where('id', '!=', $appointment->id)
            ->where('branch', $appointment->branch)
            ->whereDate('appointment_date', $validated['appointment_date'])
            ->where('appointment_time', $validated['appointment_time'])
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($conflict) {
            return back()->with('error', 'That time slot is already booked for this branch.');
        }

        $oldDate = !empty($appointment->appointment_date)
            ? \Carbon\Carbon::parse($appointment->appointment_date)->format('M d, Y')
            : 'N/A';
        $oldTime = Appointment::format12Hour($appointment->appointment_time);

        $appointment->appointment_date = $validated['appointment_date'];
        $appointment->appointment_time = $validated['appointment_time'];

        $newDate = \Carbon\Carbon::parse($appointment->appointment_date)->format('M d, Y');
        $newTime = Appointment::format12Hour($appointment->appointment_time);

        $rescheduleTag = "[Rescheduled by Clinic] Previous: {$oldDate} at {$oldTime} -> New: {$newDate} at {$newTime}";
        $appointment->notes = $appointment->notes ? ($appointment->notes . "\n\n" . $rescheduleTag) : $rescheduleTag;
        $appointment->save();

        // Create In-App Notification
        try {
            $appointment->load(['user', 'pet']);
            $petName = $appointment->pet?->name ?? 'your pet';
            AppNotification::create([
                'user_id' => $appointment->user_id,
                'title'   => 'Appointment Rescheduled by Clinic',
                'message' => "BK Petaholic Clinic has updated {$petName}'s appointment to {$newDate} at {$newTime} (previously {$oldDate} at {$oldTime}).",
                'type'    => 'appointment_rescheduled',
                'data'    => [
                    'appointment_id' => $appointment->id,
                    'pet_id'         => $appointment->pet_id,
                    'old_date'       => $oldDate,
                    'old_time'       => $oldTime,
                    'new_date'       => $newDate,
                    'new_time'       => $newTime,
                ],
            ]);

            // Real-Time Native Push Notification (Lock Screen & Background)
            if ($appointment->user?->push_token) {
                \App\Services\PushNotificationService::send(
                    $appointment->user->push_token,
                    'Appointment Rescheduled by Clinic',
                    "BK Petaholic Clinic has updated {$petName}'s appointment to {$newDate} at {$newTime} (previously {$oldDate} at {$oldTime}).",
                    ['appointment_id' => $appointment->id, 'type' => 'appointment_rescheduled']
                );
            }
        } catch (\Throwable $e) {
            \Log::warning('AppNotification admin reschedule creation failed: ' . $e->getMessage());
        }

        // Send Rescheduled SMS Notification
        try {
            $appointment->load(['user', 'pet']);
            \App\Services\SMSService::sendAppointmentRescheduled($appointment);
        } catch (\Throwable $e) {
            \Log::warning('SMS admin reschedule notification failed: ' . $e->getMessage());
        }

        return back()->with('success', 'Appointment rescheduled successfully.');
    }

    /**
     * Delete an appointment cleanly.
     */
    public function destroy($id)
    {
        $user = auth()->user();
        $appointment = Appointment::findOrFail($id);

        if ($user->branch && $appointment->branch !== $user->branch) {
            abort(403, 'Unauthorized branch access.');
        }

        $appointment->delete();

        return redirect()->route('admin.appointments.index')
            ->with('success', 'Appointment deleted successfully.');
    }

    /**
     * Bulk delete multiple appointments.
     */
    public function bulkDestroy(Request $request)
    {
        $ids = $request->input('selected_ids', $request->input('ids', []));
        if (is_string($ids)) {
            $ids = array_filter(explode(',', $ids));
        }

        if (empty($ids) || !is_array($ids)) {
            return back()->with('error', 'No appointments selected for deletion.');
        }

        $user = auth()->user();
        $query = Appointment::whereIn('id', $ids);
        if ($user->branch) {
            $query->where('branch', $user->branch);
        }

        $count = $query->count();
        $query->delete();

        return redirect()->route('admin.appointments.index')
            ->with('success', "{$count} appointment(s) permanently deleted.");
    }

    /**
     * Export appointments to Branded PDF / Print view or CSV.
     */
    public function export(Request $request)
    {
        $user = auth()->user();
        $branch = $user->branch;
        $isPgsql = DB::connection()->getDriverName() === 'pgsql';

        $query = Appointment::with(['pet', 'user'])
            ->orderBy('appointment_date', 'desc')
            ->orderBy('appointment_time', 'desc');

        // Always enforce branch scoping for branch admins, even when specific IDs are supplied
        if ($branch) {
            $query->where('branch', $branch);
        }

        if ($request->filled('ids')) {
            $ids = is_array($request->ids) ? $request->ids : explode(',', (string) $request->ids);
            $query->whereIn('id', array_filter($ids));
        } else {
            // Apply branch filter
            if (!$branch && $request->filled('branch') && $request->branch !== 'all') {
                $query->where('branch', $request->branch);
            }

            // Status filter
            if ($request->filled('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // Date filter
            if ($request->filled('date')) {
                $dateVal = match ($request->date) {
                    'today' => now()->toDateString(),
                    'tomorrow' => now()->addDay()->toDateString(),
                    default => $request->date,
                };
                if ($isPgsql) {
                    $query->whereRaw("appointment_date::date = ?", [$dateVal]);
                } else {
                    $query->whereDate('appointment_date', $dateVal);
                }
            } else {
                if ($request->filled('date_from')) {
                    if ($isPgsql) {
                        $query->whereRaw("appointment_date::date >= ?", [$request->date_from]);
                    } else {
                        $query->whereDate('appointment_date', '>=', $request->date_from);
                    }
                }
                if ($request->filled('date_to')) {
                    if ($isPgsql) {
                        $query->whereRaw("appointment_date::date <= ?", [$request->date_to]);
                    } else {
                        $query->whereDate('appointment_date', '<=', $request->date_to);
                    }
                }
            }

            // Search filter
            if ($request->filled('search')) {
                $search = trim((string) $request->search);
                $like = $isPgsql ? 'ilike' : 'like';
                $query->where(function($q) use ($search, $like) {
                    $q->where('branch', $like, "%{$search}%")
                      ->orWhere('reason', $like, "%{$search}%")
                      ->orWhereHas('pet', fn($pq) => $pq->where('name', $like, "%{$search}%"))
                      ->orWhereHas('user', fn($uq) => $uq->where('name', $like, "%{$search}%")
                          ->orWhere('email', $like, "%{$search}%")
                          ->orWhere('phone_number', $like, "%{$search}%"));
                });
            }
        }

        $records = $query->get();

        if ($request->input('format') === 'csv') {
            $filename = 'appointments-report-' . now()->format('Y-m-d-His') . '.csv';
            $headers = ['ID', 'Date', 'Time', 'Pet Name', 'Species', 'Breed', 'Pet Owner', 'Owner Phone', 'Owner Email', 'Branch', 'Reason', 'Status', 'Veterinarian', 'Notes'];
            $rows = $records->map(function ($appt) {
                return [
                    $appt->id,
                    $appt->appointment_date ? \Carbon\Carbon::parse($appt->appointment_date)->format('Y-m-d') : '',
                    $appt->appointment_time ?? '',
                    $appt->pet->name ?? 'Unknown',
                    $appt->pet->species ?? '',
                    $appt->pet->breed ?? '',
                    $appt->user->name ?? 'Unknown',
                    $appt->user->phone_number ?? '',
                    $appt->user->email ?? '',
                    $appt->branch ?? '',
                    $appt->reason ?? '',
                    ucfirst($appt->status ?? 'pending'),
                    $appt->veterinarian ?? '',
                    $appt->notes ?? '',
                ];
            });

            return \App\Services\ReportService::streamCsv($filename, $headers, $rows);
        }

        $clinic = \App\Services\ReportService::getClinicMetadata($branch ?: ($request->input('branch') !== 'all' ? $request->input('branch') : null));
        $title = 'Appointments Report';
        $subtitle = 'Official Log of Scheduled and Completed Patient Visits';
        $reportType = 'appointments';

        return view('admin.reports.printable', compact('records', 'clinic', 'title', 'subtitle', 'reportType'));
    }
}
