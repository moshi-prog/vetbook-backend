<?php

namespace App\Http\Controllers;

use App\Models\Pet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class PetController extends Controller
{
    /**
     * Return all pets belonging to the authenticated user.
     *
     * GET /api/user/pets
     */
    public function index(Request $request): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        $pets = Pet::where('user_id', $user->id)->get();

        return response()->json([
            'pets' => $pets,
        ]);
    }

    /**
     * Create a new pet for the authenticated user.
     *
     * POST /api/pets
     */
    public function store(Request $request): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        $validator = Validator::make($request->all(), [
            'name'               => 'required|string|max:255',
            'species'            => 'required|string|max:50',
            'breed'              => 'nullable|string|max:100',
            'age'                => 'nullable|integer|min:0|max:100',
            'gender'             => 'nullable|string|in:male,female,Male,Female',
            'weight'             => 'nullable|numeric|min:0',
            'medical_conditions' => 'nullable|string',
            'avatar_url'         => 'nullable|string|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 400);
        }

        $pet = Pet::create([
            'user_id'            => $user->id,
            'name'               => $request->name,
            'species'            => $request->species,
            'breed'              => $request->breed,
            'age'                => $request->age,
            'gender'             => $request->gender,
            'weight'             => $request->weight,
            'medical_conditions' => $request->medical_conditions,
            'avatar_url'         => $request->avatar_url,
        ]);

        return response()->json([
            'message' => 'Pet added successfully',
            'pet'     => $pet,
        ], 201);
    }

    /**
     * Return a single pet by ID (must belong to the authenticated user).
     *
     * GET /api/pets/{id}
     */
    public function show($id): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        $pet = Pet::find($id);

        if (! $pet) {
            return response()->json(['message' => 'Pet not found'], 404);
        }

        if ((int) $pet->user_id !== (int) $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json(['pet' => $pet]);
    }

    /**
     * Update a pet (all fields optional on update).
     *
     * PUT /api/pets/{id}
     */
    public function update(Request $request, $id): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        $pet = Pet::find($id);

        if (! $pet) {
            return response()->json(['message' => 'Pet not found'], 404);
        }

        if ((int) $pet->user_id !== (int) $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name'               => 'sometimes|string|max:255',
            'species'            => 'sometimes|string|max:50',
            'breed'              => 'nullable|string|max:100',
            'age'                => 'nullable|integer|min:0|max:100',
            'gender'             => 'nullable|string|in:male,female,Male,Female',
            'weight'             => 'nullable|numeric|min:0',
            'medical_conditions' => 'nullable|string',
            'avatar_url'         => 'nullable|string|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 400);
        }

        $pet->fill($request->only([
            'name',
            'species',
            'breed',
            'age',
            'gender',
            'weight',
            'medical_conditions',
            'avatar_url',
        ]));

        $pet->save();

        return response()->json([
            'message' => 'Pet updated successfully',
            'pet'     => $pet,
        ]);
    }

    /**
     * Delete a pet.
     *
     * DELETE /api/pets/{id}
     */
    public function destroy($id): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        $pet = Pet::find($id);

        if (! $pet) {
            return response()->json(['message' => 'Pet not found'], 404);
        }

        if ((int) $pet->user_id !== (int) $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Delete associated records if not cascaded
        $pet->appointments()->delete();
        $pet->medicalRecords()->delete();
        $pet->delete();

        return response()->json(['message' => 'Pet deleted successfully.']);
    }

    /**
     * Upload or update pet avatar.
     *
     * POST /api/pets/{id}/avatar
     */
    public function updateAvatar(Request $request, $id): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        $pet = Pet::find($id);

        if (! $pet) {
            return response()->json(['message' => 'Pet not found'], 404);
        }

        if ((int) $pet->user_id !== (int) $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image|mimes:jpeg,png,jpg,webp|max:4096',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $path = $request->file('avatar')->store('pet_avatars', 'public');
        $avatarUrl = url('storage/' . $path);

        $pet->avatar_url = $avatarUrl;
        $pet->save();

        return response()->json([
            'message'    => 'Pet avatar updated successfully',
            'avatar_url' => $avatarUrl,
            'pet'        => $pet,
        ]);
    }
}
