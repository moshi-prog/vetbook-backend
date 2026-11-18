<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Medicine;
use Illuminate\Http\Request;

class MedicineController extends Controller
{
    public function store(Request $request)
    {
        $this->ensureStaffBranch();

        $data = $this->normalizePayload($this->validateMedicine($request));
        $medicine = Medicine::create($data);

        return redirect()->back()
            ->with('success', "Medicine '{$medicine->name}' added with stock of {$medicine->stock}.");
    }

    public function updateStock(Request $request, $id)
    {
        $this->ensureStaffBranch();

        $medicine = Medicine::findOrFail($id);
        $this->authorizeBranchAccess($medicine);

        $request->validate([
            'delta' => 'nullable|integer|min:-100000|max:100000|required_without:stock',
            'stock' => 'nullable|integer|min:0|max:100000|required_without:delta',
        ]);

        if ($request->has('delta') && $request->input('delta') !== null && $request->input('delta') !== '') {
            $medicine->stock = max(0, $medicine->stock + (int) $request->input('delta'));
        } else {
            $medicine->stock = max(0, min(100000, (int) $request->input('stock')));
        }

        $medicine->save();
        $medicine->refresh();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'medicine' => $medicine,
                'stock_status' => $medicine->stock_status,
            ]);
        }

        return redirect()->back()
            ->with('success', "Updated stock for '{$medicine->name}' to {$medicine->stock} {$medicine->unit}.");
    }

    public function update(Request $request, $id)
    {
        $this->ensureStaffBranch();

        $medicine = Medicine::findOrFail($id);
        $this->authorizeBranchAccess($medicine);

        $data = $this->normalizePayload($this->validateMedicine($request));
        $medicine->update($data);

        return redirect()->back()
            ->with('success', "Medicine '{$medicine->name}' updated successfully.");
    }

    public function destroy($id)
    {
        $this->ensureStaffBranch();

        $medicine = Medicine::findOrFail($id);
        $this->authorizeBranchAccess($medicine);

        $name = $medicine->name;
        $medicine->delete();

        return redirect()->back()
            ->with('success', "Medicine '{$name}' deleted from inventory.");
    }

    private function ensureStaffBranch(): void
    {
        if (auth()->user()->branch === null) {
            abort(403, 'Unauthorized. Medicine inventory is managed exclusively by Clinic Staff.');
        }
    }

    private function validateMedicine(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:150',
            'category' => 'nullable|string|max:80',
            'species' => 'nullable|string|max:80',
            'unit' => 'nullable|string|max:40',
            'stock' => 'required|integer|min:0|max:100000',
            'low_stock_threshold' => 'nullable|integer|min:0|max:10000',
            'branch' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);
    }

    private function normalizePayload(array $validated): array
    {
        $adminBranch = auth()->user()?->branch;

        $validated['branch'] = $adminBranch
            ? $adminBranch
            : (!empty($validated['branch']) ? $validated['branch'] : null);

        $species = trim((string) ($validated['species'] ?? ''));
        $validated['species'] = $species !== '' ? $species : 'All Pets';

        $unit = trim((string) ($validated['unit'] ?? ''));
        $validated['unit'] = $unit !== '' ? $unit : 'units';

        $validated['low_stock_threshold'] = isset($validated['low_stock_threshold']) && $validated['low_stock_threshold'] !== null
            ? (int) $validated['low_stock_threshold']
            : 5;

        return $validated;
    }

    private function authorizeBranchAccess(Medicine $medicine): void
    {
        $adminBranch = auth()->user()?->branch;
        if ($adminBranch && $medicine->branch && $medicine->branch !== $adminBranch) {
            abort(403, 'Unauthorized branch access.');
        }
    }
}
