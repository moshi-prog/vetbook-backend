<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Veterinarian;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VeterinarianController extends Controller
{
    private const BRANCHES = ['Mabini Branch', 'Batangas City Branch'];

    /**
     * Display a listing of veterinarians.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $adminBranch = $user?->branch;

        $query = Veterinarian::query()->orderBy('name', 'asc');

        // Branch scoping
        if ($adminBranch) {
            $query->forBranch($adminBranch);
        } elseif ($request->filled('branch') && $request->branch !== 'all') {
            if ($request->branch === 'all_branches_only') {
                $query->whereNull('branch')->orWhere('branch', '')->orWhere('branch', 'All Branches');
            } else {
                $query->where('branch', $request->branch);
            }
        }

        // Status filter
        if ($request->filled('status') && $request->status !== 'all') {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Search filter
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('license_number', 'like', "%{$search}%")
                  ->orWhere('specialization', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $veterinarians = $query->paginate(15)->withQueryString();

        // Calculate stats for current scope
        $statsBase = Veterinarian::query();
        if ($adminBranch) {
            $statsBase->forBranch($adminBranch);
        }
        $stats = [
            'total'    => (clone $statsBase)->count(),
            'active'   => (clone $statsBase)->where('is_active', true)->count(),
            'inactive' => (clone $statsBase)->where('is_active', false)->count(),
            'mabini'   => Veterinarian::where('branch', 'Mabini Branch')->count(),
            'batangas' => Veterinarian::where('branch', 'Batangas City Branch')->count(),
            'roving'   => Veterinarian::whereNull('branch')->orWhere('branch', '')->orWhere('branch', 'All Branches')->count(),
        ];

        return view('admin.veterinarians.index', [
            'veterinarians' => $veterinarians,
            'stats'         => $stats,
            'adminBranch'   => $adminBranch,
            'branches'      => self::BRANCHES,
        ]);
    }

    /**
     * Store a newly created veterinarian in storage.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        $adminBranch = $user?->branch;

        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'license_number' => 'nullable|string|max:100',
            'specialization' => 'nullable|string|max:255',
            'phone'          => 'nullable|string|max:50',
            'email'          => 'nullable|email|max:255',
            'branch'         => ['nullable', 'string', Rule::in(['Mabini Branch', 'Batangas City Branch', 'All Branches', ''])],
            'is_active'      => 'nullable|boolean',
        ]);

        // Branch admins can only assign to their branch or null
        if ($adminBranch) {
            $validated['branch'] = $adminBranch;
        } elseif (!empty($validated['branch']) && $validated['branch'] === 'All Branches') {
            $validated['branch'] = null;
        }

        $validated['is_active'] = $request->has('is_active') ? (bool) $request->input('is_active') : true;

        Veterinarian::create($validated);

        return redirect()->route('admin.veterinarians.index')
            ->with('success', 'Veterinarian profile added successfully.');
    }

    /**
     * Update the specified veterinarian in storage.
     */
    public function update(Request $request, $id)
    {
        $veterinarian = Veterinarian::findOrFail($id);
        $user = auth()->user();
        $adminBranch = $user?->branch;

        if ($adminBranch && $veterinarian->branch && $veterinarian->branch !== $adminBranch) {
            abort(403, 'Unauthorized access to this veterinarian profile.');
        }

        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'license_number' => 'nullable|string|max:100',
            'specialization' => 'nullable|string|max:255',
            'phone'          => 'nullable|string|max:50',
            'email'          => 'nullable|email|max:255',
            'branch'         => ['nullable', 'string', Rule::in(['Mabini Branch', 'Batangas City Branch', 'All Branches', ''])],
            'is_active'      => 'nullable|boolean',
        ]);

        if ($adminBranch) {
            $validated['branch'] = $adminBranch;
        } elseif (isset($validated['branch']) && $validated['branch'] === 'All Branches') {
            $validated['branch'] = null;
        }

        $validated['is_active'] = $request->has('is_active') ? (bool) $request->input('is_active') : false;

        $veterinarian->update($validated);

        return redirect()->route('admin.veterinarians.index')
            ->with('success', 'Veterinarian profile updated successfully.');
    }

    /**
     * Toggle active status of a veterinarian.
     */
    public function toggleStatus($id)
    {
        $veterinarian = Veterinarian::findOrFail($id);
        $user = auth()->user();
        $adminBranch = $user?->branch;

        if ($adminBranch && $veterinarian->branch && $veterinarian->branch !== $adminBranch) {
            abort(403);
        }

        $veterinarian->is_active = !$veterinarian->is_active;
        $veterinarian->save();

        $statusText = $veterinarian->is_active ? 'activated' : 'deactivated';
        return redirect()->route('admin.veterinarians.index')
            ->with('success', "Veterinarian {$veterinarian->name} has been {$statusText}.");
    }

    /**
     * Remove the specified veterinarian from storage.
     */
    public function destroy($id)
    {
        $veterinarian = Veterinarian::findOrFail($id);
        $user = auth()->user();
        $adminBranch = $user?->branch;

        if ($adminBranch && $veterinarian->branch && $veterinarian->branch !== $adminBranch) {
            abort(403, 'Unauthorized access to delete this veterinarian profile.');
        }

        $name = $veterinarian->name;
        $veterinarian->delete();

        return redirect()->route('admin.veterinarians.index')
            ->with('success', "Veterinarian {$name} deleted successfully.");
    }
}

