<?php

namespace Tests\Feature;

use App\Models\MedicalRecord;
use App\Models\Pet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class MedicalRecordAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(string $role = 'pet_owner'): User
    {
        return User::create([
            'name' => ucfirst($role) . ' User',
            'email' => uniqid($role . '_', true) . '@vetbook.test',
            'password' => bcrypt('password123'),
            'role' => $role,
            'phone_number' => '09171234567',
        ]);
    }

    private function createPet(User $owner): Pet
    {
        return Pet::create([
            'user_id' => $owner->id,
            'name' => 'Milo',
            'species' => 'Cat',
            'breed' => 'Puspin',
            'age' => 2,
        ]);
    }

    public function test_pet_owner_cannot_create_or_update_medical_records(): void
    {
        $owner = $this->createUser('pet_owner');
        $pet = $this->createPet($owner);
        $record = MedicalRecord::create([
            'pet_id' => $pet->id,
            'veterinarian' => 'Dr. Reyes',
            'consultation_date' => '2026-11-01',
            'diagnosis' => 'Routine exam',
        ]);

        $token = JWTAuth::fromUser($owner);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/pets/{$pet->id}/medical-records", [
                'veterinarian' => 'Self',
                'consultation_date' => '2026-11-14',
                'prescriptions' => 'Forged Rx',
            ])
            ->assertStatus(403)
            ->assertJson([
                'message' => 'Unauthorized. Only veterinarians or clinic staff may create medical records.',
            ]);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/medical-records/{$record->id}", [
                'diagnosis' => 'Modified by owner',
            ])
            ->assertStatus(403)
            ->assertJson([
                'message' => 'Unauthorized. Only veterinarians or clinic staff may modify medical records.',
            ]);

        $this->assertDatabaseCount('medical_records', 1);
        $this->assertSame('Routine exam', $record->fresh()->diagnosis);
    }

    public function test_pet_owner_retains_read_only_access_to_own_pet_records(): void
    {
        $owner = $this->createUser('pet_owner');
        $otherOwner = $this->createUser('pet_owner');
        $pet = $this->createPet($owner);
        $record = MedicalRecord::create([
            'pet_id' => $pet->id,
            'veterinarian' => 'Dr. Reyes',
            'consultation_date' => '2026-11-01',
            'diagnosis' => 'Healthy',
        ]);

        $ownerToken = JWTAuth::fromUser($owner);
        $otherToken = JWTAuth::fromUser($otherOwner);

        $this->withHeader('Authorization', 'Bearer ' . $ownerToken)
            ->getJson("/api/pets/{$pet->id}/medical-records")
            ->assertStatus(200)
            ->assertJsonCount(1, 'medical_records');

        $this->withHeader('Authorization', 'Bearer ' . $ownerToken)
            ->getJson("/api/medical-records/{$record->id}")
            ->assertStatus(200)
            ->assertJsonPath('medical_record.diagnosis', 'Healthy');

        $this->withHeader('Authorization', 'Bearer ' . $otherToken)
            ->getJson("/api/pets/{$pet->id}/medical-records")
            ->assertStatus(403);
    }

    public function test_admin_and_veterinarian_can_create_and_update_medical_records(): void
    {
        $owner = $this->createUser('pet_owner');
        $pet = $this->createPet($owner);

        foreach (['admin', 'veterinarian'] as $staffRole) {
            $staff = $this->createUser($staffRole);
            $token = JWTAuth::fromUser($staff);

            $createResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
                ->postJson("/api/pets/{$pet->id}/medical-records", [
                    'veterinarian' => 'Dr. Santos',
                    'consultation_date' => '2026-11-10',
                    'diagnosis' => "Checked by {$staffRole}",
                ]);

            $createResponse->assertStatus(201);
            $recordId = $createResponse->json('medical_record.id');

            $this->withHeader('Authorization', 'Bearer ' . $token)
                ->putJson("/api/medical-records/{$recordId}", [
                    'treatment' => "Updated by {$staffRole}",
                ])
                ->assertStatus(200)
                ->assertJsonPath('medical_record.treatment', "Updated by {$staffRole}");
        }
    }

    public function test_admin_medical_record_form_includes_double_submit_guard(): void
    {
        $admin = $this->createUser('admin');

        $response = $this->actingAs($admin)->get('/admin/medical-records/create');

        $response->assertStatus(200)
            ->assertSee('id="saveRecordBtn"', false)
            ->assertSee('Save Medical Record');
    }
}

