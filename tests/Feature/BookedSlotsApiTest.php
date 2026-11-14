<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Pet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class BookedSlotsApiTest extends TestCase
{
    use RefreshDatabase;

    private function createPetForOwner(): Pet
    {
        $owner = User::create([
            'name' => 'Slot Tester',
            'email' => uniqid('owner_', true) . '@vetbook.test',
            'password' => bcrypt('password123'),
            'role' => 'pet_owner',
            'phone_number' => '09171234567',
        ]);

        return Pet::create([
            'user_id' => $owner->id,
            'name' => 'Bantay',
            'species' => 'Dog',
            'breed' => 'Aspin',
            'age' => 3,
        ]);
    }

    public function test_booked_slots_endpoint_is_public_and_filters_by_branch_date_and_active_status(): void
    {
        $pet = $this->createPetForOwner();
        $targetDate = '2026-11-18';
        $otherDate = '2026-11-19';

        // Pending and approved on target branch/date -> included & normalized to HH:MM
        Appointment::create([
            'user_id' => $pet->user_id,
            'pet_id' => $pet->id,
            'branch' => 'Mabini Branch',
            'appointment_date' => $targetDate,
            'appointment_time' => '09:30:00',
            'status' => Appointment::STATUS_PENDING,
        ]);

        Appointment::create([
            'user_id' => $pet->user_id,
            'pet_id' => $pet->id,
            'branch' => 'Mabini Branch',
            'appointment_date' => $targetDate,
            'appointment_time' => '14:30',
            'status' => Appointment::STATUS_APPROVED,
        ]);

        // Duplicate active slot -> deduplicated
        Appointment::create([
            'user_id' => $pet->user_id,
            'pet_id' => $pet->id,
            'branch' => 'Mabini Branch',
            'appointment_date' => $targetDate,
            'appointment_time' => '14:30',
            'status' => Appointment::STATUS_APPROVED,
        ]);

        // Cancelled & completed -> excluded
        Appointment::create([
            'user_id' => $pet->user_id,
            'pet_id' => $pet->id,
            'branch' => 'Mabini Branch',
            'appointment_date' => $targetDate,
            'appointment_time' => '10:00',
            'status' => Appointment::STATUS_CANCELLED,
        ]);

        Appointment::create([
            'user_id' => $pet->user_id,
            'pet_id' => $pet->id,
            'branch' => 'Mabini Branch',
            'appointment_date' => $targetDate,
            'appointment_time' => '11:00',
            'status' => Appointment::STATUS_COMPLETED,
        ]);

        // Other branch -> excluded
        Appointment::create([
            'user_id' => $pet->user_id,
            'pet_id' => $pet->id,
            'branch' => 'Batangas City Branch',
            'appointment_date' => $targetDate,
            'appointment_time' => '15:00',
            'status' => Appointment::STATUS_APPROVED,
        ]);

        // Other date -> excluded
        Appointment::create([
            'user_id' => $pet->user_id,
            'pet_id' => $pet->id,
            'branch' => 'Mabini Branch',
            'appointment_date' => $otherDate,
            'appointment_time' => '16:00',
            'status' => Appointment::STATUS_PENDING,
        ]);

        $response = $this->getJson('/api/appointments/booked-slots?branch=' . urlencode('Mabini Branch') . '&date=' . $targetDate);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'booked_slots' => ['09:30', '14:30'],
            ]);
    }

    public function test_booked_slots_validates_required_branch_and_date(): void
    {
        $this->getJson('/api/appointments/booked-slots')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['branch', 'date']);

        $this->getJson('/api/appointments/booked-slots?branch=Mabini+Branch&date=not-a-date')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['date']);
    }

    public function test_appointments_reject_lunch_break_slots_on_store_and_reschedule(): void
    {
        $pet = $this->createPetForOwner();
        $owner = User::findOrFail($pet->user_id);
        $token = JWTAuth::fromUser($owner);
        $futureDate = now()->addDays(3)->toDateString();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/appointments', [
                'pet_id'           => $pet->id,
                'branch'           => 'Mabini Branch',
                'appointment_date' => $futureDate,
                'appointment_time' => '12:00',
                'reason'           => 'Checkup',
            ])
            ->assertStatus(422)
            ->assertJson([
                'message' => 'The clinic is closed for lunch break between 12:00 PM and 1:00 PM. Please select a morning or afternoon slot.',
            ]);

        $appointment = Appointment::create([
            'user_id'          => $owner->id,
            'pet_id'           => $pet->id,
            'branch'           => 'Mabini Branch',
            'appointment_date' => $futureDate,
            'appointment_time' => '09:30',
            'status'           => Appointment::STATUS_PENDING,
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/appointments/{$appointment->id}/reschedule", [
                'appointment_date' => $futureDate,
                'appointment_time' => '12:30',
            ])
            ->assertStatus(422)
            ->assertJson([
                'message' => 'The clinic is closed for lunch break between 12:00 PM and 1:00 PM. Please select a morning or afternoon slot.',
            ]);
    }
}

