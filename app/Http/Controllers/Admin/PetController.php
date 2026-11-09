<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pet;
use App\Models\User;
use Illuminate\Http\Request;

class PetController extends Controller
{
    /** Species that get their own filter; everything else counts as "other". */
    private const MAIN_SPECIES = ['dog', 'cat', 'bird', 'rabbit'];

    public function index(Request $request)
    {
        $branch = auth()->user()->branch;

        $species = strtolower((string) $request->input('species', 'all'));
        $sort = $request->input('sort', 'newest');
        $search = trim((string) $request->input('search', ''));

        $query = Pet::with('owner')
            ->withMax(['appointments as last_visit' => function ($q) {
                $q->where('status', 'completed');
            }], 'appointment_date')
            ->with(['appointments' => function ($q) {
                $q->select('id', 'pet_id', 'branch');
            }]);

        // Species filter
        if (in_array($species, self::MAIN_SPECIES, true)) {
            $query->whereRaw('LOWER(species) = ?', [$species]);
        } elseif ($species === 'other') {
            $query->where(function ($q) {
                $q->whereNull('species')
                  ->orWhereRaw('LOWER(species) NOT IN (?, ?, ?, ?)', self::MAIN_SPECIES);
            });
        }

        // Search: pet name, breed, owner name
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('breed', 'like', "%{$search}%")
                  ->orWhereHas('owner', function ($oq) use ($search) {
                      $oq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Sorting
        switch ($sort) {
            case 'name':
                $query->orderBy('name');
                break;
            case 'owner':
                $query->orderBy(
                    User::select('name')->whereColumn('users.id', 'pets.user_id')->limit(1)
                )->orderBy('name');
                break;
            default:
                $sort = 'newest';
                $query->orderByDesc('created_at')->orderByDesc('id');
        }

        $pets = $query->paginate(15)->withQueryString();

        // Species counts (all pets, unfiltered)
        $rawCounts = Pet::selectRaw('LOWER(species) as sp, COUNT(*) as total')
            ->groupBy('sp')
            ->pluck('total', 'sp');

        $totalCount = (int) $rawCounts->sum();
        $speciesCounts = [
            'all' => $totalCount,
            'dog' => (int) ($rawCounts['dog'] ?? 0),
            'cat' => (int) ($rawCounts['cat'] ?? 0),
            'bird' => (int) ($rawCounts['bird'] ?? 0),
            'rabbit' => (int) ($rawCounts['rabbit'] ?? 0),
        ];
        $speciesCounts['other'] = $totalCount
            - $speciesCounts['dog'] - $speciesCounts['cat']
            - $speciesCounts['bird'] - $speciesCounts['rabbit'];

        $filters = [
            'search' => $search,
            'species' => $species,
            'sort' => $sort,
        ];
        $hasFilters = $search !== '' || ($species !== 'all' && $species !== '') || $sort !== 'newest';

        return view('admin.pets.index', compact(
            'pets', 'totalCount', 'speciesCounts', 'filters', 'hasFilters', 'branch'
        ));
    }

    public function show($id)
    {
        $pet = Pet::with([
            'owner',
            'appointments' => function ($q) {
                $q->orderByDesc('appointment_date')->orderByDesc('appointment_time');
            },
            'medicalRecords' => function ($q) {
                $q->orderByDesc('consultation_date')->orderByDesc('id');
            },
        ])->findOrFail($id);

        return view('admin.pets.show', compact('pet'));
    }

    public function destroy($id)
    {
        $pet = Pet::findOrFail($id);

        // Delete associated records cleanly
        $pet->appointments()->delete();
        $pet->medicalRecords()->delete();
        $pet->delete();

        return redirect()->route('admin.pets.index')->with('success', 'Pet deleted successfully.');
    }

    /**
     * Bulk delete multiple pets and their related records.
     */
    public function bulkDestroy(Request $request)
    {
        $ids = $request->input('selected_ids', $request->input('ids', []));
        if (is_string($ids)) {
            $ids = array_filter(explode(',', $ids));
        }

        if (empty($ids) || !is_array($ids)) {
            return back()->with('error', 'No pets selected for deletion.');
        }

        $pets = Pet::whereIn('id', $ids)->get();
        $count = $pets->count();

        foreach ($pets as $pet) {
            $pet->appointments()->delete();
            $pet->medicalRecords()->delete();
            $pet->delete();
        }

        return redirect()->route('admin.pets.index')
            ->with('success', "{$count} pet(s) and their records permanently deleted.");
    }

    /**
     * Export pets to Branded PDF / Print view or CSV.
     */
    public function export(Request $request)
    {
        $branch = auth()->user()->branch;
        $species = strtolower((string) $request->input('species', 'all'));
        $search = trim((string) $request->input('search', ''));
        $sort = $request->input('sort', 'newest');

        $query = Pet::with(['owner', 'appointments']);

        if ($request->filled('ids')) {
            $ids = is_array($request->ids) ? $request->ids : explode(',', (string) $request->ids);
            $query->whereIn('id', array_filter($ids));
        } else {
            // Species filter
            if (in_array($species, self::MAIN_SPECIES, true)) {
                $query->whereRaw('LOWER(species) = ?', [$species]);
            } elseif ($species === 'other') {
                $query->where(function ($q) {
                    $q->whereNull('species')
                      ->orWhereRaw('LOWER(species) NOT IN (?, ?, ?, ?)', self::MAIN_SPECIES);
                });
            }

            // Search: pet name, breed, owner name
            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('breed', 'like', "%{$search}%")
                      ->orWhereHas('owner', fn($oq) => $oq->where('name', 'like', "%{$search}%"));
                });
            }

            // Sorting
            switch ($sort) {
                case 'name':
                    $query->orderBy('name');
                    break;
                case 'owner':
                    $query->orderBy(
                        User::select('name')->whereColumn('users.id', 'pets.user_id')->limit(1)
                    )->orderBy('name');
                    break;
                default:
                    $query->orderByDesc('created_at')->orderByDesc('id');
            }
        }

        $records = $query->get();

        if ($request->input('format') === 'csv') {
            $filename = 'pet-registry-report-' . now()->format('Y-m-d-His') . '.csv';
            $headers = ['ID', 'Pet Name', 'Species', 'Breed', 'Age', 'Weight (kg)', 'Gender', 'Owner Name', 'Owner Phone', 'Owner Email', 'Medical Conditions'];
            $rows = $records->map(function ($pet) {
                return [
                    $pet->id,
                    $pet->name,
                    ucfirst($pet->species ?: 'Other'),
                    $pet->breed ?: '',
                    $pet->age !== null ? $pet->age : '',
                    $pet->weight !== null ? number_format($pet->weight, 1) : '',
                    ucfirst($pet->gender ?: ''),
                    $pet->owner->name ?? 'Unknown',
                    $pet->owner->phone_number ?? '',
                    $pet->owner->email ?? '',
                    $pet->medical_conditions ?? '',
                ];
            });

            return \App\Services\ReportService::streamCsv($filename, $headers, $rows);
        }

        $clinic = \App\Services\ReportService::getClinicMetadata($branch);
        $title = 'Pet Registry Report';
        $subtitle = 'Master Patient & Animal Roster Registry';
        $reportType = 'pets';

        return view('admin.reports.printable', compact('records', 'clinic', 'title', 'subtitle', 'reportType'));
    }
}
