<?php

namespace Tests\Feature;

use App\Models\MedicalRecord;
use App\Models\Pet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class PetFieldsAndMedicalRecordVetTest extends TestCase
{
    use RefreshDatabase;

    public function test_pet_api_store_and_update_persist_spayed_neutered_and_microchip_id(): void
    {
        $owner = User::create([
            'name' => 'Pet Owner',
            'email' => 'owner@vetbook.test',
            'password' => bcrypt('password123'),
            'role' => 'pet_owner',
        ]);
        $token = JWTAuth::fromUser($owner);

        // Create pet with spayed_neutered = true and microchip_id
        $createRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/pets', [
                'name' => 'Milo',
                'species' => 'Dog',
                'breed' => 'Aspin',
                'age' => 3,
                'gender' => 'Male',
                'weight' => 11.34,
                'spayed_neutered' => true,
                'microchip_id' => '900118000123456',
            ]);

        $createRes->assertStatus(201);
        $createRes->assertJsonPath('pet.spayed_neutered', true);
        $createRes->assertJsonPath('pet.microchip_id', '900118000123456');

        $petId = $createRes->json('pet.id');
        $pet = Pet::findOrFail($petId);
        $this->assertTrue($pet->spayed_neutered);
        $this->assertSame('900118000123456', $pet->microchip_id);

        // Update spayed_neutered to false and clear microchip_id
        $updateRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/pets/{$petId}", [
                'spayed_neutered' => false,
                'microchip_id' => null,
            ]);

        $updateRes->assertStatus(200);
        $updateRes->assertJsonPath('pet.spayed_neutered', false);
        $updateRes->assertJsonPath('pet.microchip_id', null);

        $pet->refresh();
        $this->assertFalse($pet->spayed_neutered);
        $this->assertNull($pet->microchip_id);

        // Reject microchip_id exceeding 100 chars
        $invalidRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/pets/{$petId}", [
                'microchip_id' => str_repeat('9', 101),
            ]);

        $invalidRes->assertStatus(400);
    }

    public function test_admin_medical_record_form_shows_and_persists_attending_veterinarian(): void
    {
        $admin = User::create([
            'name' => 'Vet Admin',
            'email' => 'admin@vetbook.test',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);

        $owner = User::create([
            'name' => 'Owner One',
            'email' => 'owner1@vetbook.test',
            'password' => bcrypt('password123'),
            'role' => 'pet_owner',
        ]);

        $pet = Pet::create([
            'user_id' => $owner->id,
            'name' => 'Luna',
            'species' => 'Cat',
            'breed' => 'Persian',
        ]);

        // Create form renders Attending Veterinarian input
        $createForm = $this->actingAs($admin)->get('/admin/medical-records/create');
        $createForm->assertStatus(200);
        $createForm->assertSee('Attending Veterinarian');
        $createForm->assertSee('name="veterinarian"', false);

        // Store medical record with attending veterinarian
        $storeRes = $this->actingAs($admin)->post('/admin/medical-records', [
            'pet_id' => $pet->id,
            'consultation_date' => '2026-11-10',
            'veterinarian' => 'Dr. Maria Santos, DVM',
            'diagnosis' => 'Annual checkup clear',
            'treatment' => 'None required',
        ]);

        $storeRes->assertRedirect("/admin/medical-records/pet/{$pet->id}");

        $record = MedicalRecord::where('pet_id', $pet->id)->first();
        $this->assertNotNull($record);
        $this->assertSame('Dr. Maria Santos, DVM', $record->veterinarian);

        // Edit form pre-populates saved veterinarian
        $editForm = $this->actingAs($admin)->get("/admin/medical-records/{$record->id}/edit");
        $editForm->assertStatus(200);
        $editForm->assertSee('Dr. Maria Santos, DVM');

        // Update veterinarian and check show + timeline views
        $updateRes = $this->actingAs($admin)->put("/admin/medical-records/{$record->id}", [
            'pet_id' => $pet->id,
            'consultation_date' => '2026-11-10',
            'veterinarian' => 'Dr. Jose Reyes, DVM',
            'diagnosis' => 'Annual checkup clear',
        ]);

        $updateRes->assertRedirect("/admin/medical-records/{$record->id}");
        $this->assertSame('Dr. Jose Reyes, DVM', $record->fresh()->veterinarian);

        $showView = $this->actingAs($admin)->get("/admin/medical-records/{$record->id}");
        $showView->assertStatus(200);
        $showView->assertSee('Dr. Jose Reyes, DVM');

        $petRecordsView = $this->actingAs($admin)->get("/admin/medical-records/pet/{$pet->id}");
        $petRecordsView->assertStatus(200);
        $petRecordsView->assertSee('Dr. Jose Reyes, DVM');
    }

    public function test_medical_record_views_handle_null_consultation_date_safely(): void
    {
        $admin = User::create([
            'name' => 'Vet Admin',
            'email' => 'admin2@vetbook.test',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);
        $this->actingAs($admin);

        $nullRecord = new MedicalRecord([
            'pet_id' => 1,
            'consultation_date' => null,
            'diagnosis' => 'Null date check',
        ]);
        $nullRecord->id = 999;

        $showHtml = view('admin.medical-records.show', ['record' => $nullRecord])->render();
        $this->assertStringContainsString('Consultation on —', $showHtml);

        $timelineHtml = view('admin.medical-records._timeline', [
            'records' => collect([$nullRecord]),
            'showPet' => false,
            'branch' => null,
        ])->render();
        $this->assertStringContainsString('Null date check', $timelineHtml);
        $this->assertStringContainsString('—', $timelineHtml);
    }
}
