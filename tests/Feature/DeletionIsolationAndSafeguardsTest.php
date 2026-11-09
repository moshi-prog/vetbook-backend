<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\Pet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeletionIsolationAndSafeguardsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $owner;
    private Pet $pet;
    private Appointment $appointment;
    private MedicalRecord $medicalRecord;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name'     => 'Clinic Admin',
            'email'    => 'admin@vetbook.test',
            'password' => bcrypt('password123'),
            'role'     => 'admin',
        ]);

        $this->owner = User::create([
            'name'         => 'Jane Doe',
            'email'        => 'jane@example.com',
            'phone_number' => '09171234567',
            'password'     => bcrypt('password123'),
            'role'         => 'pet_owner',
        ]);

        $this->pet = Pet::create([
            'user_id'            => $this->owner->id,
            'name'               => 'Barky',
            'species'            => 'Dog',
            'breed'              => 'Beagle',
            'gender'             => 'male',
            'age'                => 4,
            'weight'             => 12.0,
            'medical_conditions' => 'None',
        ]);

        $this->appointment = Appointment::create([
            'user_id'          => $this->owner->id,
            'pet_id'           => $this->pet->id,
            'branch'           => 'Mabini Branch',
            'appointment_date' => now()->addDays(2)->toDateString(),
            'appointment_time' => '10:00:00',
            'status'           => 'pending',
            'reason'           => 'Annual Vaccination',
        ]);

        $this->medicalRecord = MedicalRecord::create([
            'pet_id'            => $this->pet->id,
            'veterinarian'      => 'Dr. Smith',
            'consultation_date' => now()->subDays(5)->toDateString(),
            'diagnosis'         => 'Healthy routine checkup',
            'treatment'         => 'Vitamin booster administered',
            'prescriptions'     => 'Vitamins daily for 1 week',
            'notes'             => 'Good overall health',
        ]);
    }

    public function test_single_appointment_deletion_does_not_delete_pet_medical_record_or_user(): void
    {
        $response = $this->actingAs($this->admin)
            ->delete(route('admin.appointments.destroy', $this->appointment->id));

        $response->assertRedirect(route('admin.appointments.index'));
        $this->assertDatabaseMissing('appointments', ['id' => $this->appointment->id]);

        // Isolation guarantees: Pet, MedicalRecord, and User must remain untouched
        $this->assertDatabaseHas('pets', ['id' => $this->pet->id]);
        $this->assertDatabaseHas('medical_records', ['id' => $this->medicalRecord->id]);
        $this->assertDatabaseHas('users', ['id' => $this->owner->id]);
    }

    public function test_bulk_appointment_deletion_does_not_delete_pet_medical_record_or_user(): void
    {
        $appt2 = Appointment::create([
            'user_id'          => $this->owner->id,
            'pet_id'           => $this->pet->id,
            'branch'           => 'Mabini Branch',
            'appointment_date' => now()->addDays(4)->toDateString(),
            'appointment_time' => '11:00:00',
            'status'           => 'approved',
            'reason'           => 'Follow-up',
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.appointments.bulk-delete'), [
                'selected_ids' => [$this->appointment->id, $appt2->id],
            ]);

        $response->assertRedirect(route('admin.appointments.index'));
        $this->assertDatabaseMissing('appointments', ['id' => $this->appointment->id]);
        $this->assertDatabaseMissing('appointments', ['id' => $appt2->id]);

        // Isolation guarantees
        $this->assertDatabaseHas('pets', ['id' => $this->pet->id]);
        $this->assertDatabaseHas('medical_records', ['id' => $this->medicalRecord->id]);
        $this->assertDatabaseHas('users', ['id' => $this->owner->id]);
    }

    public function test_single_medical_record_deletion_does_not_delete_pet_appointment_or_user(): void
    {
        $response = $this->actingAs($this->admin)
            ->delete(route('admin.medical-records.destroy', $this->medicalRecord->id), [
                'redirect_to' => 'index',
            ]);

        $response->assertRedirect(route('admin.medical-records.index'));
        $this->assertDatabaseMissing('medical_records', ['id' => $this->medicalRecord->id]);

        // Isolation guarantees
        $this->assertDatabaseHas('pets', ['id' => $this->pet->id]);
        $this->assertDatabaseHas('appointments', ['id' => $this->appointment->id]);
        $this->assertDatabaseHas('users', ['id' => $this->owner->id]);
    }

    public function test_bulk_medical_record_deletion_does_not_delete_pet_appointment_or_user(): void
    {
        $mr2 = MedicalRecord::create([
            'pet_id'            => $this->pet->id,
            'veterinarian'      => 'Dr. Adams',
            'consultation_date' => now()->subDay()->toDateString(),
            'diagnosis'         => 'Ear cleaning',
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.medical-records.bulk-delete'), [
                'selected_ids' => [$this->medicalRecord->id, $mr2->id],
            ]);

        $response->assertRedirect(route('admin.medical-records.index'));
        $this->assertDatabaseMissing('medical_records', ['id' => $this->medicalRecord->id]);
        $this->assertDatabaseMissing('medical_records', ['id' => $mr2->id]);

        // Isolation guarantees
        $this->assertDatabaseHas('pets', ['id' => $this->pet->id]);
        $this->assertDatabaseHas('appointments', ['id' => $this->appointment->id]);
        $this->assertDatabaseHas('users', ['id' => $this->owner->id]);
    }

    public function test_single_pet_deletion_cascades_to_appointments_and_medical_records_but_preserves_owner(): void
    {
        $response = $this->actingAs($this->admin)
            ->delete(route('admin.pets.destroy', $this->pet->id));

        $response->assertRedirect(route('admin.pets.index'));
        $this->assertDatabaseMissing('pets', ['id' => $this->pet->id]);

        // Cascades to appointments and medical records for this pet
        $this->assertDatabaseMissing('appointments', ['id' => $this->appointment->id]);
        $this->assertDatabaseMissing('medical_records', ['id' => $this->medicalRecord->id]);

        // Critical guarantee: Never deletes owner User account
        $this->assertDatabaseHas('users', ['id' => $this->owner->id]);
    }

    public function test_bulk_pet_deletion_cascades_to_appointments_and_medical_records_but_preserves_owner(): void
    {
        $pet2 = Pet::create([
            'user_id' => $this->owner->id,
            'name'    => 'Whiskers',
            'species' => 'Cat',
        ]);

        $appt2 = Appointment::create([
            'user_id'          => $this->owner->id,
            'pet_id'           => $pet2->id,
            'branch'           => 'Mabini Branch',
            'appointment_date' => now()->addDays(3)->toDateString(),
            'appointment_time' => '14:00:00',
            'status'           => 'pending',
            'reason'           => 'Checkup',
        ]);

        $mr2 = MedicalRecord::create([
            'pet_id'            => $pet2->id,
            'veterinarian'      => 'Dr. Smith',
            'consultation_date' => now()->subDays(2)->toDateString(),
            'diagnosis'         => 'De-worming',
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.pets.bulk-delete'), [
                'selected_ids' => [$this->pet->id, $pet2->id],
            ]);

        $response->assertRedirect(route('admin.pets.index'));
        $this->assertDatabaseMissing('pets', ['id' => $this->pet->id]);
        $this->assertDatabaseMissing('pets', ['id' => $pet2->id]);
        $this->assertDatabaseMissing('appointments', ['id' => $this->appointment->id]);
        $this->assertDatabaseMissing('appointments', ['id' => $appt2->id]);
        $this->assertDatabaseMissing('medical_records', ['id' => $this->medicalRecord->id]);
        $this->assertDatabaseMissing('medical_records', ['id' => $mr2->id]);

        // Critical guarantee: Owner User account still exists
        $this->assertDatabaseHas('users', ['id' => $this->owner->id]);
    }

    public function test_admin_appointments_views_show_safe_deletion_banner(): void
    {
        // 1. Appointments Index page (contains single modal and bulk actions modal)
        $response = $this->actingAs($this->admin)
            ->get(route('admin.appointments.index'));

        $response->assertOk();
        $response->assertSee('Safe Deletion:');
        $response->assertSee('The pet profile and medical records will remain completely untouched.');
        $response->assertSeeText('Safe Deletion: The pet profile and medical records will remain completely untouched.');

        // 2. Appointments Show page
        $showResponse = $this->actingAs($this->admin)
            ->get(route('admin.appointments.show', $this->appointment->id));

        $showResponse->assertOk();
        $showResponse->assertSee('Safe Deletion:');
        $showResponse->assertSee('The pet profile and medical records will remain completely untouched.');
        $showResponse->assertSeeText('Safe Deletion: The pet profile and medical records will remain completely untouched.');
    }

    public function test_admin_pets_views_show_warning_banner(): void
    {
        // 1. Pets Index page (contains single pet modal and bulk actions modal)
        $response = $this->actingAs($this->admin)
            ->get(route('admin.pets.index'));

        $response->assertOk();
        $response->assertSee('Warning:');
        $response->assertSee('You are deleting Pet Profiles. This removes the pet from the owner\'s mobile app and deletes all appointments and medical records.', false);
        $response->assertSeeText('Warning: You are deleting Pet Profiles. This removes the pet from the owner\'s mobile app and deletes all appointments and medical records.');

        // 2. Pet Show page
        $showResponse = $this->actingAs($this->admin)
            ->get(route('admin.pets.show', $this->pet->id));

        $showResponse->assertOk();
        $showResponse->assertSee('Warning:');
        $showResponse->assertSee('You are deleting Pet Profiles. This removes the pet from the owner\'s mobile app and deletes all appointments and medical records.', false);
        $showResponse->assertSeeText('Warning: You are deleting Pet Profiles. This removes the pet from the owner\'s mobile app and deletes all appointments and medical records.');
    }
}
