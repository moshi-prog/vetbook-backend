<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AccountApprovedMail;
use App\Models\Appointment;
use App\Models\AppNotification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class PetOwnerController extends Controller
{
    private const FILTERS = ['all', 'pending', 'new', 'active', 'pets'];
    private const AUTH_FILTERS = ['pending', 'rejected', 'approved', 'all'];
    private const SORTS = ['newest', 'name', 'pets', 'appointments'];
    private const DIRECTORY_COLUMNS = [
        'users.id',
        'users.name',
        'users.email',
        'users.phone_number',
        'users.avatar_url',
        'users.role',
        'users.branch',
        'users.email_verified_at',
        'users.approval_status',
        'users.approved_at',
        'users.approved_by',
        'users.verification_id_type',
        'users.created_at',
        'users.updated_at',
    ];

    private function ensureStaffBranch(): string
    {
        $user = auth()->user();
        if (!$user || $user->branch === null) {
            abort(403, 'Unauthorized. Only Clinic Staff can access Account Authorizations.');
        }

        return $user->branch;
    }

    private function visiblePetOwnersQuery()
    {
        return User::where('role', 'pet_owner')
            ->where(function ($q) {
                $q->whereNotNull('email_verified_at')
                  ->orWhere('approval_status', '!=', 'pending')
                  ->orWhereNull('approval_status');
            });
    }

    public function index(Request $request)
    {
        $branch = auth()->user()->branch;

        $search = trim((string) $request->input('search', ''));
        $filter = $request->input('filter', 'all');
        $sort = $request->input('sort', 'newest');
        if (!in_array($filter, self::FILTERS, true)) {
            $filter = 'all';
        }
        if (!in_array($sort, self::SORTS, true)) {
            $sort = 'newest';
        }

        $today = Carbon::today();
        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();

        // Branch-scoped appointment constraint (no-op for super admin)
        $apptScope = function ($q) use ($branch) {
            if ($branch) {
                $q->where('branch', $branch);
            }
        };
        $activeThisMonth = function ($q) use ($branch, $monthStart, $monthEnd) {
            if ($branch) {
                $q->where('branch', $branch);
            }
            $q->whereBetween('appointment_date', [$monthStart->toDateString(), $monthEnd->toDateString()]);
        };

        // Base: visible pet owners (excludes unverified pending registrations until OTP verification)
        $base = $this->visiblePetOwnersQuery();

        // Stats (scoped to visible owners, consolidated to 1 single DB query)
        $mS = $monthStart->copy()->startOfDay()->toDateTimeString();
        $mE = $monthEnd->copy()->endOfDay()->toDateTimeString();
        $mSDate = $monthStart->toDateString();
        $mEDate = $monthEnd->toDateString();

        $branchSql = $branch ? "AND a.branch = ?" : "";
        $branchBindings = $branch ? [$branch] : [];
        $bindings = array_merge([$mS, $mE], $branchBindings, [$mSDate, $mEDate]);

        $statsRow = \Illuminate\Support\Facades\DB::selectOne("
            SELECT 
                COUNT(*) as total,
                COUNT(CASE WHEN approval_status = 'pending' AND email_verified_at IS NOT NULL THEN 1 END) as pending_approval,
                COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) as new_this_month,
                COUNT(CASE WHEN EXISTS (
                    SELECT 1 FROM appointments a 
                    WHERE a.user_id = users.id 
                    {$branchSql}
                    AND a.appointment_date BETWEEN ? AND ?
                ) THEN 1 END) as active_this_month,
                COUNT(CASE WHEN (SELECT COUNT(*) FROM pets p WHERE p.user_id = users.id) >= 2 THEN 1 END) as multi_pets
            FROM users
            WHERE role = 'pet_owner'
              AND (email_verified_at IS NOT NULL OR approval_status IS NULL OR approval_status != 'pending')
        ", $bindings);

        $stats = [
            'total' => (int) ($statsRow->total ?? 0),
            'pending_approval' => (int) ($statsRow->pending_approval ?? 0),
            'new_this_month' => (int) ($statsRow->new_this_month ?? 0),
            'active_this_month' => (int) ($statsRow->active_this_month ?? 0),
            'multi_pets' => (int) ($statsRow->multi_pets ?? 0),
        ];

        $query = (clone $base)
            ->select(self::DIRECTORY_COLUMNS)
            ->withCount([
                'pets',
                'appointments' => $apptScope,
            ])
            ->withMax(['appointments as last_booking' => $apptScope], 'appointment_date');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        switch ($filter) {
            case 'pending':
                $query->where('approval_status', 'pending')->whereNotNull('email_verified_at');
                break;
            case 'new':
                $query->whereBetween('created_at', [$monthStart->copy()->startOfDay(), $monthEnd->copy()->endOfDay()]);
                break;
            case 'active':
                $query->whereHas('appointments', $activeThisMonth);
                break;
            case 'pets':
                $query->has('pets');
                break;
        }

        switch ($sort) {
            case 'name':
                $query->orderBy('name');
                break;
            case 'pets':
                $query->orderByDesc('pets_count')->orderBy('name');
                break;
            case 'appointments':
                $query->orderByDesc('appointments_count')->orderBy('name');
                break;
            default:
                $query->orderByDesc('created_at')->orderByDesc('id');
        }

        $owners = $query->paginate(15)->withQueryString();

        // Branches each owner on this page has visited (single query, no N+1)
        $branchesByOwner = Appointment::whereIn('user_id', $owners->pluck('id'))
            ->select('user_id', 'branch')
            ->distinct()
            ->get()
            ->groupBy('user_id')
            ->map(fn ($rows) => $rows->pluck('branch')->filter()->unique()->values());

        $filters = ['search' => $search, 'filter' => $filter, 'sort' => $sort];
        $hasFilters = $search !== '' || $filter !== 'all' || $sort !== 'newest';

        return view('admin.pet-owners.index', compact(
            'owners', 'stats', 'branchesByOwner', 'filters', 'hasFilters', 'branch'
        ));
    }

    /**
     * Dedicated Staff portal page for reviewing verified pet owner IDs / photos.
     */
    public function authorizations(Request $request)
    {
        $branch = $this->ensureStaffBranch();

        $search = trim((string) $request->input('search', ''));
        $filter = (string) $request->input('filter', 'pending');
        if (!in_array($filter, self::AUTH_FILTERS, true)) {
            $filter = 'pending';
        }

        $todayStart = Carbon::today()->startOfDay()->toDateTimeString();

        $statsRow = DB::selectOne("
            SELECT
                COUNT(CASE WHEN approval_status = 'pending' THEN 1 END) as pending,
                COUNT(CASE WHEN approval_status = 'pending' AND verification_id_image IS NOT NULL AND verification_id_image != '' THEN 1 END) as with_id,
                COUNT(CASE WHEN approval_status = 'approved' AND approved_at >= ? THEN 1 END) as approved_today,
                COUNT(CASE WHEN approval_status = 'rejected' THEN 1 END) as rejected
            FROM users
            WHERE role = 'pet_owner'
              AND email_verified_at IS NOT NULL
        ", [$todayStart]);

        $stats = [
            'pending'        => (int) ($statsRow->pending ?? 0),
            'with_id'        => (int) ($statsRow->with_id ?? 0),
            'approved_today' => (int) ($statsRow->approved_today ?? 0),
            'rejected'       => (int) ($statsRow->rejected ?? 0),
        ];

        $query = User::where('role', 'pet_owner')
            ->whereNotNull('email_verified_at')
            ->select(array_merge(self::DIRECTORY_COLUMNS, ['users.verification_id_image']));

        if ($filter !== 'all') {
            $query->where('approval_status', $filter);
        }

        if ($search !== '') {
            $likeOp = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($q) use ($search, $likeOp) {
                $q->where('name', $likeOp, "%{$search}%")
                  ->orWhere('email', $likeOp, "%{$search}%")
                  ->orWhere('phone_number', $likeOp, "%{$search}%");
            });
        }

        $applicants = $query
            ->orderByRaw("CASE WHEN approval_status = 'pending' THEN 0 WHEN approval_status = 'rejected' THEN 1 ELSE 2 END")
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        return view('admin.pet-owners.authorizations', compact(
            'applicants', 'stats', 'filter', 'search', 'branch'
        ));
    }

    public function show($id)
    {
        $admin = auth()->user();
        $branch = $admin->branch;

        $owner = User::where('role', 'pet_owner')->findOrFail($id);

        $pets = $owner->pets()->withCount('medicalRecords')->orderBy('name')->get();

        $appointmentsQuery = $owner->appointments();
        if ($branch) {
            $appointmentsQuery->where('branch', $branch);
        }

        $stats = [
            'pets' => $pets->count(),
            'appointments' => (clone $appointmentsQuery)->count(),
            'completed' => (clone $appointmentsQuery)->where('status', 'completed')->count(),
            'last_visit' => (clone $appointmentsQuery)->where('status', 'completed')->max('appointment_date'),
        ];

        $appointments = (clone $appointmentsQuery)
            ->with('pet')
            ->orderByDesc('appointment_date')
            ->orderByDesc('appointment_time')
            ->limit(10)
            ->get();

        $branchesVisited = $owner->appointments()
            ->whereNotNull('branch')
            ->distinct()
            ->pluck('branch');

        return view('admin.pet-owners.show', compact(
            'owner', 'pets', 'stats', 'appointments', 'branchesVisited', 'branch'
        ));
    }

    /**
     * Delete a pet owner account and all associated pets, appointments, and records.
     */
    public function approve($id)
    {
        $owner = User::where('role', 'pet_owner')->findOrFail($id);
        $owner->approval_status = 'approved';
        $owner->approved_at = now();
        $owner->approved_by = auth()->id();
        if ($owner->email_verified_at === null) {
            $owner->email_verified_at = now();
        }
        $owner->save();

        try {
            AppNotification::create([
                'user_id' => $owner->id,
                'title'   => 'Account Approved!',
                'message' => 'Your VetBook pet owner account has been verified and approved by clinic staff.',
                'type'    => 'account_approved',
                'data'    => ['user_id' => $owner->id],
            ]);
        } catch (\Throwable $e) {
            \Log::warning('Failed creating account approved notification: ' . $e->getMessage());
        }

        if (config('mail.default') !== 'log' && !empty($owner->email)) {
            try {
                Mail::to($owner->email)->send(new AccountApprovedMail($owner->name));
            } catch (\Throwable $e) {
                \Log::error('Failed sending account approved email: ' . $e->getMessage());
            }
        }

        return back()->with('success', "Pet owner account for {$owner->name} has been approved and an approval email has been sent.");
    }

    public function reject($id)
    {
        $owner = User::where('role', 'pet_owner')->findOrFail($id);
        $owner->approval_status = 'rejected';
        $owner->save();

        return back()->with('success', "Pet owner account for {$owner->name} has been rejected.");
    }

    public function destroy($id)
    {
        $owner = User::where('role', 'pet_owner')->findOrFail($id);

        // Clean up linked pets, their appointments, and medical records
        foreach ($owner->pets as $pet) {
            $pet->appointments()->delete();
            $pet->medicalRecords()->delete();
            $pet->delete();
        }

        // Clean up any direct appointments belonging to user
        $owner->appointments()->delete();

        // Clean up notifications for this user
        \App\Models\AppNotification::where('user_id', $owner->id)->delete();

        $owner->delete();

        return redirect()->route('admin.pet-owners.index')
            ->with('success', 'Pet owner and all associated records deleted successfully.');
    }

    /**
     * Bulk delete multiple pet owner accounts and cascade all associated data.
     */
    public function bulkDestroy(Request $request)
    {
        $ids = $request->input('selected_ids', $request->input('ids', []));
        if (is_string($ids)) {
            $ids = array_filter(explode(',', $ids));
        }

        if (empty($ids) || !is_array($ids)) {
            return back()->with('error', 'No pet owners selected for deletion.');
        }

        $owners = User::where('role', 'pet_owner')->whereIn('id', $ids)->get();
        $count = $owners->count();

        foreach ($owners as $owner) {
            foreach ($owner->pets as $pet) {
                $pet->appointments()->delete();
                $pet->medicalRecords()->delete();
                $pet->delete();
            }
            $owner->appointments()->delete();
            \App\Models\AppNotification::where('user_id', $owner->id)->delete();
            $owner->delete();
        }

        return redirect()->route('admin.pet-owners.index')
            ->with('success', "{$count} pet owner account(s) and their associated records permanently deleted.");
    }

    /**
     * Export pet owners to Branded PDF / Print view or CSV.
     */
    public function export(Request $request)
    {
        $branch = auth()->user()->branch;
        $search = trim((string) $request->input('search', ''));
        $filter = $request->input('filter', 'all');
        $sort = $request->input('sort', 'newest');

        $query = $this->visiblePetOwnersQuery()
            ->select(self::DIRECTORY_COLUMNS)
            ->withCount(['pets', 'appointments']);

        if ($request->filled('ids')) {
            $ids = is_array($request->ids) ? $request->ids : explode(',', (string) $request->ids);
            $query->whereIn('id', array_filter($ids));
        } else {
            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone_number', 'like', "%{$search}%");
                });
            }

            $today = Carbon::today();
            $monthStart = $today->copy()->startOfMonth();
            $monthEnd = $today->copy()->endOfMonth();

            switch ($filter) {
                case 'new':
                    $query->whereBetween('created_at', [$monthStart->startOfDay(), $monthEnd->endOfDay()]);
                    break;
                case 'active':
                    $query->whereHas('appointments', function ($q) use ($branch, $monthStart, $monthEnd) {
                        if ($branch) {
                            $q->where('branch', $branch);
                        }
                        $q->whereBetween('appointment_date', [$monthStart->toDateString(), $monthEnd->toDateString()]);
                    });
                    break;
                case 'pets':
                    $query->has('pets', '>=', 2);
                    break;
            }

            switch ($sort) {
                case 'name':
                    $query->orderBy('name');
                    break;
                case 'pets':
                    $query->orderByDesc('pets_count');
                    break;
                case 'appointments':
                    $query->orderByDesc('appointments_count');
                    break;
                default:
                    $query->orderByDesc('created_at')->orderByDesc('id');
            }
        }

        $records = $query->get();

        if ($request->input('format') === 'csv') {
            $filename = 'pet-owners-report-' . now()->format('Y-m-d-His') . '.csv';
            $headers = ['ID', 'Client Name', 'Email Address', 'Phone Number', 'Total Pets', 'Total Appointments', 'Member Since'];
            $rows = $records->map(function ($owner) {
                return [
                    $owner->id,
                    $owner->name,
                    $owner->email ?? '',
                    $owner->phone_number ?? '',
                    $owner->pets_count,
                    $owner->appointments_count,
                    $owner->created_at ? $owner->created_at->format('Y-m-d') : '',
                ];
            });

            return \App\Services\ReportService::streamCsv($filename, $headers, $rows);
        }

        $clinic = \App\Services\ReportService::getClinicMetadata($branch);
        $title = 'Pet Owners Directory';
        $subtitle = 'Master Client and Account Registry';
        $reportType = 'pet_owners';

        return view('admin.reports.printable', compact('records', 'clinic', 'title', 'subtitle', 'reportType'));
    }
}
