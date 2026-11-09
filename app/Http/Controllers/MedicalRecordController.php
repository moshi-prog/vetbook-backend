<?php

namespace App\Http\Controllers;

use App\Models\MedicalRecord;
use App\Models\Pet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class MedicalRecordController extends Controller
{
    /**
     * Return all medical records for a pet (pet must belong to the user).
     *
     * GET /api/pets/{petId}/medical-records
     */
    public function indexByPet($petId): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        $pet = Pet::find($petId);

        if (! $pet) {
            return response()->json(['message' => 'Pet not found'], 404);
        }

        if ((int) $pet->user_id !== (int) $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $records = MedicalRecord::where('pet_id', $pet->id)
            ->orderBy('consultation_date', 'desc')
            ->get();

        return response()->json([
            'medical_records' => $records,
        ]);
    }

    /**
     * Add a medical record to a pet (pet must belong to the user).
     *
     * POST /api/pets/{petId}/medical-records
     */
    public function store(Request $request, $petId): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        $pet = Pet::find($petId);

        if (! $pet) {
            return response()->json(['message' => 'Pet not found'], 404);
        }

        if ((int) $pet->user_id !== (int) $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'veterinarian'      => 'required|string|max:255',
            'consultation_date' => 'required|date',
            'diagnosis'         => 'nullable|string',
            'treatment'         => 'nullable|string',
            'vaccinations'      => 'nullable|string',
            'prescriptions'     => 'nullable|string',
            'notes'             => 'nullable|string',
            'next_visit_date'   => 'nullable|date|after_or_equal:consultation_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 400);
        }

        $record = MedicalRecord::create([
            'pet_id'            => $pet->id,
            'veterinarian'      => $request->veterinarian,
            'consultation_date' => $request->consultation_date,
            'diagnosis'         => $request->diagnosis,
            'treatment'         => $request->treatment,
            'vaccinations'      => $request->vaccinations,
            'prescriptions'     => $request->prescriptions,
            'notes'             => $request->notes,
            'next_visit_date'   => $request->next_visit_date,
        ]);

        return response()->json([
            'message'         => 'Medical record added',
            'medical_record'  => $record,
        ], 201);
    }

    /**
     * Return a single medical record (its pet must belong to the user).
     *
     * GET /api/medical-records/{id}
     */
    public function show($id): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        $record = MedicalRecord::with('pet')->find($id);

        if (! $record) {
            return response()->json(['message' => 'Medical record not found'], 404);
        }

        if ((int) $record->pet->user_id !== (int) $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json(['medical_record' => $record]);
    }

    /**
     * Update a medical record (all fields optional on update).
     *
     * PUT /api/medical-records/{id}
     */
    public function update(Request $request, $id): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        $record = MedicalRecord::with('pet')->find($id);

        if (! $record) {
            return response()->json(['message' => 'Medical record not found'], 404);
        }

        if ((int) $record->pet->user_id !== (int) $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // If consultation_date isn't being changed, compare next_visit_date
        // against the stored value instead of a missing request field.
        $consultationDate = $request->filled('consultation_date')
            ? 'consultation_date'
            : $record->consultation_date?->toDateString();

        $validator = Validator::make($request->all(), [
            'veterinarian'      => 'sometimes|string|max:255',
            'consultation_date' => 'sometimes|date',
            'diagnosis'         => 'nullable|string',
            'treatment'         => 'nullable|string',
            'vaccinations'      => 'nullable|string',
            'prescriptions'     => 'nullable|string',
            'notes'             => 'nullable|string',
            'next_visit_date'   => 'nullable|date' . ($consultationDate ? '|after_or_equal:' . $consultationDate : ''),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 400);
        }

        $record->fill($request->only([
            'veterinarian',
            'consultation_date',
            'diagnosis',
            'treatment',
            'vaccinations',
            'prescriptions',
            'notes',
            'next_visit_date',
        ]));

        $record->save();

        return response()->json([
            'message'        => 'Medical record updated',
            'medical_record' => $record,
        ]);
    }
}
