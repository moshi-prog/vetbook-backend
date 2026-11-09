<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PetOwnerController extends Controller
{
    private const FILTERS = ['all', 'new', 'active', 'pets'];
    private const SORTS = ['newest', 'name', 'pets', 'appointments'];

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

        // Base: all registered pet owners across the clinic
        $base = User::where('role', 'pet_owner');

        // Stats (scoped to the admin's visible owners, consolidated to 1 single DB query)
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
        ", $bindings);

        $stats = [
            'total' => (int) ($statsRow->total ?? 0),
            'new_this_month' => (int) ($statsRow->new_this_month ?? 0),
            'active_this_month' => (int) ($statsRow->active_this_month ?? 0),
            'multi_pets' => (int) ($statsRow->multi_pets ?? 0),
        ];

        $query = (clone $base)
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

        $query = User::where('role', 'pet_owner')->withCount(['pets', 'appointments']);

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
