<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MedicalRecord;
use App\Models\Pet;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MedicalRecordController extends Controller
{
    /** Days before a follow-up date at which a vaccine is flagged as "due soon". */
    private const DUE_SOON_DAYS = 30;

    public function index(Request $request)
    {
        $branch = auth()->user()->branch;

        $search = trim((string) $request->input('search', ''));
        $period = $request->input('period', 'all');

        $query = MedicalRecord::with([
            'pet.owner',
            'pet.appointments' => function ($q) {
                $q->select('id', 'pet_id', 'branch');
            },
        ]);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('veterinarian', 'like', "%{$search}%")
                  ->orWhere('diagnosis', 'like', "%{$search}%")
                  ->orWhereHas('pet', function ($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%")
                         ->orWhereHas('owner', function ($oq) use ($search) {
                             $oq->where('name', 'like', "%{$search}%");
                         });
                  });
            });
        }

        $today = Carbon::today();
        switch ($period) {
            case 'week':
                $query->whereBetween('consultation_date', [
                    $today->copy()->startOfWeek()->toDateString(),
                    $today->copy()->endOfWeek()->toDateString(),
                ]);
                break;
            case 'month':
                $query->whereBetween('consultation_date', [
                    $today->copy()->startOfMonth()->toDateString(),
                    $today->copy()->endOfMonth()->toDateString(),
                ]);
                break;
            case 'year':
                $query->whereBetween('consultation_date', [
                    $today->copy()->startOfYear()->toDateString(),
                    $today->copy()->endOfYear()->toDateString(),
                ]);
                break;
            default:
                $period = 'all';
        }

        $records = $query->orderByDesc('consultation_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $mStart = $today->copy()->startOfMonth()->toDateString();
        $mEnd = $today->copy()->endOfMonth()->toDateString();
        $tDate = $today->toDateString();

        $statsRow = \Illuminate\Support\Facades\DB::selectOne("
            SELECT 
                COUNT(*) as total,
                COUNT(CASE WHEN consultation_date BETWEEN ? AND ? THEN 1 END) as this_month,
                COUNT(CASE WHEN next_visit_date IS NOT NULL AND next_visit_date >= ? THEN 1 END) as upcoming,
                COUNT(DISTINCT pet_id) as pets
            FROM medical_records
        ", [$mStart, $mEnd, $tDate]);

        $stats = [
            'total' => (int) ($statsRow->total ?? 0),
            'this_month' => (int) ($statsRow->this_month ?? 0),
            'upcoming' => (int) ($statsRow->upcoming ?? 0),
            'pets' => (int) ($statsRow->pets ?? 0),
        ];

        $filters = ['search' => $search, 'period' => $period];
        $hasFilters = $search !== '' || $period !== 'all';

        return view('admin.medical-records.index', compact(
            'records', 'stats', 'filters', 'hasFilters', 'branch'
        ));
    }

    public function show($id)
    {
        $record = MedicalRecord::with([
            'pet.owner',
            'pet.appointments' => function ($q) {
                $q->select('id', 'pet_id', 'branch');
            },
        ])->findOrFail($id);

        return view('admin.medical-records.show', compact('record'));
    }

    public function petRecords($petId)
    {
        $branch = auth()->user()->branch;

        $pet = Pet::with([
            'owner',
            'appointments' => function ($q) {
                $q->select('id', 'pet_id', 'branch');
            },
        ])->findOrFail($petId);

        $records = $pet->medicalRecords()
            ->orderByDesc('consultation_date')
            ->orderByDesc('id')
            ->get();

        $vaccinations = $this->summarizeVaccinations($records);

        return view('admin.medical-records.pet', compact('pet', 'records', 'vaccinations', 'branch'));
    }

    public function create(Request $request)
    {
        return view('admin.medical-records.form', [
            'record' => null,
            'pets' => $this->petOptions(),
            'selectedPetId' => $request->input('pet_id'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        if (empty($data['veterinarian'])) {
            $data['veterinarian'] = null;
        }

        $record = MedicalRecord::create($data);

        return redirect()->route('admin.medical-records.pet', $record->pet_id)
            ->with('success', 'Medical record added successfully.');
    }

    public function edit($id)
    {
        $record = MedicalRecord::findOrFail($id);

        return view('admin.medical-records.form', [
            'record' => $record,
            'pets' => $this->petOptions(),
            'selectedPetId' => $record->pet_id,
        ]);
    }

    public function update(Request $request, $id)
    {
        $record = MedicalRecord::findOrFail($id);
        $data = $this->validated($request);

        if (!$request->has('veterinarian') && $record->veterinarian) {
            $data['veterinarian'] = $record->veterinarian;
        } elseif (empty($data['veterinarian'])) {
            $data['veterinarian'] = null;
        }

        $record->update($data);

        return redirect()->route('admin.medical-records.show', $record->id)
            ->with('success', 'Medical record updated successfully.');
    }

    public function destroy(Request $request, $id)
    {
        $record = MedicalRecord::findOrFail($id);
        $petId = $record->pet_id;
        $record->delete();

        if ($request->input('redirect_to') === 'index') {
            return redirect()->route('admin.medical-records.index')
                ->with('success', 'Medical record deleted successfully.');
        }

        return redirect()->route('admin.medical-records.pet', $petId)
            ->with('success', 'Medical record deleted successfully.');
    }

    /**
     * Bulk delete multiple medical records.
     */
    public function bulkDestroy(Request $request)
    {
        $ids = $request->input('selected_ids', $request->input('ids', []));
        if (is_string($ids)) {
            $ids = array_filter(explode(',', $ids));
        }

        if (empty($ids) || !is_array($ids)) {
            return back()->with('error', 'No medical records selected for deletion.');
        }

        $count = MedicalRecord::whereIn('id', $ids)->delete();

        return redirect()->route('admin.medical-records.index')
            ->with('success', "{$count} medical record(s) permanently deleted.");
    }

    /**
     * Export medical records to Branded PDF / Print view or CSV.
     */
    public function export(Request $request)
    {
        $branch = auth()->user()->branch;
        $search = trim((string) $request->input('search', ''));
        $period = $request->input('period', 'all');

        $query = MedicalRecord::with(['pet.owner', 'pet.appointments']);

        if ($request->filled('ids')) {
            $ids = is_array($request->ids) ? $request->ids : explode(',', (string) $request->ids);
            $query->whereIn('id', array_filter($ids));
        } else {
            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('veterinarian', 'like', "%{$search}%")
                      ->orWhere('diagnosis', 'like', "%{$search}%")
                      ->orWhereHas('pet', function ($pq) use ($search) {
                          $pq->where('name', 'like', "%{$search}%")
                             ->orWhereHas('owner', fn($oq) => $oq->where('name', 'like', "%{$search}%"));
                      });
                });
            }

            $today = Carbon::today();
            switch ($period) {
                case 'week':
                    $query->whereBetween('consultation_date', [
                        $today->copy()->startOfWeek()->toDateString(),
                        $today->copy()->endOfWeek()->toDateString(),
                    ]);
                    break;
                case 'month':
                    $query->whereBetween('consultation_date', [
                        $today->copy()->startOfMonth()->toDateString(),
                        $today->copy()->endOfMonth()->toDateString(),
                    ]);
                    break;
                case 'year':
                    $query->whereBetween('consultation_date', [
                        $today->copy()->startOfYear()->toDateString(),
                        $today->copy()->endOfYear()->toDateString(),
                    ]);
                    break;
            }

            $query->orderByDesc('consultation_date')->orderByDesc('id');
        }

        $records = $query->get();

        if ($request->input('format') === 'csv') {
            $filename = 'medical-records-report-' . now()->format('Y-m-d-His') . '.csv';
            $headers = ['ID', 'Date', 'Pet Name', 'Owner Name', 'Veterinarian', 'Diagnosis', 'Vaccinations', 'Treatment', 'Next Visit Date'];
            $rows = $records->map(function ($r) {
                return [
                    $r->id,
                    $r->consultation_date ? $r->consultation_date->format('Y-m-d') : '',
                    $r->pet->name ?? 'Unknown',
                    $r->pet->owner->name ?? 'Unknown',
                    $r->veterinarian ?? '',
                    $r->diagnosis ?? '',
                    $r->vaccinations ?? '',
                    $r->treatment ?? '',
                    $r->next_visit_date ? $r->next_visit_date->format('Y-m-d') : '',
                ];
            });

            return \App\Services\ReportService::streamCsv($filename, $headers, $rows);
        }

        $clinic = \App\Services\ReportService::getClinicMetadata($branch);
        $title = 'Medical Records Report';
        $subtitle = 'Clinical Consultations, Diagnoses, Treatments & Vaccinations';
        $reportType = 'medical_records';

        return view('admin.reports.printable', compact('records', 'clinic', 'title', 'subtitle', 'reportType'));
    }

    // ------------------------------------------------------------------

    private function validated(Request $request): array
    {
        return $request->validate([
            'pet_id' => 'required|exists:pets,id',
            'veterinarian' => 'nullable|string|max:255',
            'consultation_date' => 'required|date',
            'diagnosis' => 'nullable|string|max:5000',
            'treatment' => 'nullable|string|max:5000',
            'vaccinations' => 'nullable|string|max:5000',
            'prescriptions' => 'nullable|string|max:5000',
            'notes' => 'nullable|string|max:5000',
            'next_visit_date' => 'nullable|date|after_or_equal:consultation_date',
        ]);
    }

    private function petOptions()
    {
        return Pet::with('owner:id,name')
            ->orderBy('name')
            ->get(['id', 'user_id', 'name', 'species']);
    }

    /**
     * Parse the free-text `vaccinations` field of every record into a list of
     * distinct vaccines, each with the last date it was given.
     *
     * Status comes from the follow-up (next_visit_date) on the record where the
     * vaccine was last given: overdue if past, "soon" if within DUE_SOON_DAYS,
     * otherwise up to date.
     */
    private function summarizeVaccinations($records): array
    {
        $today = Carbon::today();
        $found = [];

        foreach ($records as $record) {
            if (!$record->vaccinations) {
                continue;
            }

            $names = preg_split('/[,;\n\r]+/', $record->vaccinations);
            foreach ($names as $name) {
                $name = trim($name);
                if ($name === '') {
                    continue;
                }

                $key = mb_strtolower($name);
                $given = $record->consultation_date;

                if (!isset($found[$key]) || $given->gt($found[$key]['last_given'])) {
                    $found[$key] = [
                        'name' => $name,
                        'last_given' => $given,
                        'next_visit' => $record->next_visit_date,
                    ];
                }
            }
        }

        foreach ($found as &$v) {
            $status = 'ok';
            if ($v['next_visit']) {
                if ($v['next_visit']->lt($today)) {
                    $status = 'overdue';
                } elseif ($v['next_visit']->lte($today->copy()->addDays(self::DUE_SOON_DAYS))) {
                    $status = 'soon';
                }
            }
            $v['status'] = $status;
        }
        unset($v);

        usort($found, fn ($a, $b) => $b['last_given']->timestamp <=> $a['last_given']->timestamp);

        return array_values($found);
    }
}
