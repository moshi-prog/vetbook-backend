<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\ClinicSetting;
use App\Models\MedicalRecord;
use App\Models\Pet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBulkActionsAndExportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $owner;
    private Pet $pet1;
    private Pet $pet2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name'     => 'Super Admin',
            'email'    => 'admin@vetbook.test',
            'password' => bcrypt('password123'),
            'role'     => 'admin',
        ]);

        $this->owner = User::create([
            'name'         => 'Maria Santos',
            'email'        => 'maria@example.com',
            'phone_number' => '09171234567',
            'password'     => bcrypt('password123'),
            'role'         => 'pet_owner',
        ]);

        $this->pet1 = Pet::create([
            'user_id'            => $this->owner->id,
            'name'               => 'Milo',
            'species'            => 'Dog',
            'breed'              => 'Golden Retriever',
            'gender'             => 'male',
            'age'                => 3,
            'weight'             => 28.5,
            'medical_conditions' => 'Chicken allergy',
        ]);

        $this->pet2 = Pet::create([
            'user_id'            => $this->owner->id,
            'name'               => 'Luna',
            'species'            => 'Cat',
            'breed'              => 'Persian',
            'gender'             => 'female',
            'age'                => 2,
            'weight'             => 4.2,
            'medical_conditions' => 'Sensitive skin',
        ]);
    }

    public function test_admin_can_bulk_delete_appointments(): void
    {
        $appt1 = Appointment::create([
            'user_id'          => $this->owner->id,
            'pet_id'           => $this->pet1->id,
            'branch'           => 'Mabini Branch',
            'appointment_date' => now()->toDateString(),
            'appointment_time' => '10:00:00',
            'status'           => 'pending',
            'reason'           => 'Checkup',
        ]);

        $appt2 = Appointment::create([
            'user_id'          => $this->owner->id,
            'pet_id'           => $this->pet2->id,
            'branch'           => 'Batangas City Branch',
            'appointment_date' => now()->addDay()->toDateString(),
            'appointment_time' => '11:00:00',
            'status'           => 'approved',
            'reason'           => 'Vaccine',
        ]);

        $appt3 = Appointment::create([
            'user_id'          => $this->owner->id,
            'pet_id'           => $this->pet1->id,
            'branch'           => 'Mabini Branch',
            'appointment_date' => now()->addDays(2)->toDateString(),
            'appointment_time' => '14:00:00',
            'status'           => 'completed',
            'reason'           => 'Grooming',
        ]);

        $response = $this->actingAs($this->admin)->post('/admin/appointments/bulk-delete', [
            'selected_ids' => [$appt1->id, $appt2->id],
        ]);

        $response->assertRedirect('/admin/appointments');
        $this->assertDatabaseMissing('appointments', ['id' => $appt1->id]);
        $this->assertDatabaseMissing('appointments', ['id' => $appt2->id]);
        $this->assertDatabaseHas('appointments', ['id' => $appt3->id]);
    }

    public function test_admin_can_bulk_delete_pets_with_cascade(): void
    {
        $appt = Appointment::create([
            'user_id'          => $this->owner->id,
            'pet_id'           => $this->pet1->id,
            'branch'           => 'Mabini Branch',
            'appointment_date' => now()->toDateString(),
            'appointment_time' => '10:00:00',
            'status'           => 'pending',
        ]);

        $mr = MedicalRecord::create([
            'pet_id'            => $this->pet1->id,
            'consultation_date' => now()->toDateString(),
            'diagnosis'         => 'Ear Infection',
        ]);

        $response = $this->actingAs($this->admin)->post('/admin/pets/bulk-delete', [
            'selected_ids' => [$this->pet1->id],
        ]);

        $response->assertRedirect('/admin/pets');
        $this->assertDatabaseMissing('pets', ['id' => $this->pet1->id]);
        $this->assertDatabaseMissing('appointments', ['id' => $appt->id]);
        $this->assertDatabaseMissing('medical_records', ['id' => $mr->id]);
        $this->assertDatabaseHas('pets', ['id' => $this->pet2->id]);
    }

    public function test_admin_can_bulk_delete_medical_records(): void
    {
        $mr1 = MedicalRecord::create([
            'pet_id'            => $this->pet1->id,
            'consultation_date' => now()->toDateString(),
            'diagnosis'         => 'Checkup 1',
        ]);

        $mr2 = MedicalRecord::create([
            'pet_id'            => $this->pet2->id,
            'consultation_date' => now()->toDateString(),
            'diagnosis'         => 'Checkup 2',
        ]);

        $mr3 = MedicalRecord::create([
            'pet_id'            => $this->pet1->id,
            'consultation_date' => now()->toDateString(),
            'diagnosis'         => 'Checkup 3',
        ]);

        $response = $this->actingAs($this->admin)->post('/admin/medical-records/bulk-delete', [
            'selected_ids' => [$mr1->id, $mr2->id],
        ]);

        $response->assertRedirect('/admin/medical-records');
        $this->assertDatabaseMissing('medical_records', ['id' => $mr1->id]);
        $this->assertDatabaseMissing('medical_records', ['id' => $mr2->id]);
        $this->assertDatabaseHas('medical_records', ['id' => $mr3->id]);
    }

    public function test_admin_can_bulk_delete_pet_owners_with_cascade(): void
    {
        $owner2 = User::create([
            'name'         => 'Juan Dela Cruz',
            'email'        => 'juan@example.com',
            'phone_number' => '09189998877',
            'password'     => bcrypt('password123'),
            'role'         => 'pet_owner',
        ]);

        $petOfJuan = Pet::create([
            'user_id' => $owner2->id,
            'name'    => 'Rocky',
            'species' => 'Dog',
        ]);

        $response = $this->actingAs($this->admin)->post('/admin/pet-owners/bulk-delete', [
            'selected_ids' => [$owner2->id],
        ]);

        $response->assertRedirect('/admin/pet-owners');
        $this->assertDatabaseMissing('users', ['id' => $owner2->id]);
        $this->assertDatabaseMissing('pets', ['id' => $petOfJuan->id]);
        $this->assertDatabaseHas('users', ['id' => $this->owner->id]);
    }

    public function test_admin_can_export_branded_appointments_report(): void
    {
        ClinicSetting::set('clinic_name', 'BK Petaholic Animal Clinic');
        ClinicSetting::set('clinic_accreditation', 'BAI-RVD-2024-0089');

        Appointment::create([
            'user_id'          => $this->owner->id,
            'pet_id'           => $this->pet1->id,
            'branch'           => 'Mabini Branch',
            'appointment_date' => now()->toDateString(),
            'appointment_time' => '10:00:00',
            'status'           => 'approved',
            'reason'           => 'Vaccine Booster',
        ]);

        // Printable PDF View
        $response = $this->actingAs($this->admin)->get('/admin/appointments/export');
        $response->assertStatus(200);
        $response->assertSee('BK Petaholic Animal Clinic');
        $response->assertSee('Appointments Report');
        $response->assertSee('Milo');
        $response->assertSee('Maria Santos');
        $response->assertSee('Vaccine Booster');

        // CSV Export
        $csvResponse = $this->actingAs($this->admin)->get('/admin/appointments/export?format=csv');
        $csvResponse->assertStatus(200);
        $csvResponse->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_admin_can_export_selected_pets_to_csv_and_print(): void
    {
        // Printable View with specific IDs
        $response = $this->actingAs($this->admin)->get("/admin/pets/export?ids={$this->pet1->id}");
        $response->assertStatus(200);
        $response->assertSee('Pet Registry Report');
        $response->assertSee('Milo');
        $response->assertDontSee('Luna');

        // CSV with specific IDs
        $csvResponse = $this->actingAs($this->admin)->get("/admin/pets/export?ids={$this->pet1->id}&format=csv");
        $csvResponse->assertStatus(200);
        $csvResponse->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }
}

