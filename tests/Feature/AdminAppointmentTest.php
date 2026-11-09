<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Pet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAppointmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $response = $this->get('/admin/appointments');
        $response->assertRedirect('/admin/login');
    }

    public function test_admin_can_view_appointments_list(): void
    {
        $admin = User::create([
            'name' => 'Dr. Admin',
            'email' => 'admin@vetbook.test',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'branch' => 'Downtown',
        ]);

        $owner = User::create([
            'name' => 'Sarah Connor',
            'email' => 'sarah@test.com',
            'password' => bcrypt('password123'),
            'role' => 'pet_owner',
        ]);

        $pet = Pet::create([
            'user_id' => $owner->id,
            'name' => 'Max',
            'species' => 'Cat',
            'breed' => 'Siamese',
        ]);

        Appointment::create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
            'branch' => 'Downtown',
            'appointment_date' => now()->toDateString(),
            'appointment_time' => '02:00 PM',
            'status' => 'pending',
            'reason' => 'Annual Vaccination',
        ]);

        $response = $this->actingAs($admin)->get('/admin/appointments');

        $response->assertStatus(200);
        $response->assertSee('Appointments');
        $response->assertSee('Sarah Connor');
        $response->assertSee('Max');
        $response->assertSee('Downtown');
        $response->assertSee('Annual Vaccination');
        $response->assertSee('Pending');
    }

    public function test_filter_by_status(): void
    {
        $admin = User::create([
            'name' => 'Dr. Super',
            'email' => 'super@vetbook.test',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'branch' => null,
        ]);

        $owner = User::create([
            'name' => 'John Wick',
            'email' => 'john@test.com',
            'password' => bcrypt('password123'),
            'role' => 'pet_owner',
        ]);

        $pet = Pet::create([
            'user_id' => $owner->id,
            'name' => 'Daisy',
            'species' => 'Dog',
        ]);

        Appointment::create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
            'branch' => 'Uptown',
            'appointment_date' => now()->toDateString(),
            'appointment_time' => '09:00 AM',
            'status' => 'approved',
            'reason' => 'Dental Care',
        ]);

        Appointment::create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
            'branch' => 'Uptown',
            'appointment_date' => now()->toDateString(),
            'appointment_time' => '11:00 AM',
            'status' => 'pending',
            'reason' => 'General Checkup',
        ]);

        $response = $this->actingAs($admin)->get('/admin/appointments?status=approved');

        $response->assertStatus(200);
        $response->assertSee('Dental Care');
        $response->assertDontSee('General Checkup');
    }

    public function test_search_filter(): void
    {
        $admin = User::create([
            'name' => 'Dr. Super',
            'email' => 'super2@vetbook.test',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'branch' => null,
        ]);

        $owner = User::create([
            'name' => 'Alice Green',
            'email' => 'alice@test.com',
            'password' => bcrypt('password123'),
            'role' => 'pet_owner',
        ]);

        $pet = Pet::create([
            'user_id' => $owner->id,
            'name' => 'Snowball',
            'species' => 'Rabbit',
        ]);

        Appointment::create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
            'branch' => 'West End',
            'appointment_date' => now()->toDateString(),
            'appointment_time' => '03:00 PM',
            'status' => 'completed',
            'reason' => 'Ear Cleaning',
        ]);

        $response = $this->actingAs($admin)->get('/admin/appointments?search=Snowball');

        $response->assertStatus(200);
        $response->assertSee('Snowball');
        $response->assertSee('Alice Green');
    }

    public function test_case_insensitive_search_across_pet_owner_phone_and_branch(): void
    {
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'super_search@vetbook.test',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'branch' => null,
        ]);

        $owner = User::create([
            'name' => 'Juan Dela Cruz',
            'email' => 'juandelacruz@example.com',
            'phone_number' => '09189998877',
            'password' => bcrypt('password123'),
            'role' => 'pet_owner',
        ]);

        $pet = Pet::create([
            'user_id' => $owner->id,
            'name' => 'Brownie',
            'species' => 'Dog',
        ]);

        Appointment::create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
            'branch' => 'Mabini Branch',
            'appointment_date' => now()->toDateString(),
            'appointment_time' => '10:00 AM',
            'status' => 'pending',
            'reason' => 'Rabies Vaccine Booster',
        ]);

        // Lowercase pet search
        $resPet = $this->actingAs($admin)->get('/admin/appointments?search=brownie');
        $resPet->assertStatus(200);
        $resPet->assertSee('Brownie');

        // Lowercase owner search
        $resOwner = $this->actingAs($admin)->get('/admin/appointments?search=juan');
        $resOwner->assertStatus(200);
        $resOwner->assertSee('Juan Dela Cruz');

        // Phone search
        $resPhone = $this->actingAs($admin)->get('/admin/appointments?search=9998877');
        $resPhone->assertStatus(200);
        $resPhone->assertSee('Juan Dela Cruz');

        // Lowercase reason search
        $resReason = $this->actingAs($admin)->get('/admin/appointments?search=rabies');
        $resReason->assertStatus(200);
        $resReason->assertSee('Rabies Vaccine Booster');
    }

    public function test_date_filter_filters_exact_date(): void
    {
        $admin = User::create([
            'name' => 'Super Admin Date',
            'email' => 'super_date@vetbook.test',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'branch' => null,
        ]);

        $owner = User::create([
            'name' => 'Owner Date',
            'email' => 'owner_date@example.com',
            'password' => bcrypt('password123'),
            'role' => 'pet_owner',
        ]);

        $pet = Pet::create([
            'user_id' => $owner->id,
            'name' => 'Milo',
            'species' => 'Cat',
        ]);

        Appointment::create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
            'branch' => 'Mabini Branch',
            'appointment_date' => '2026-11-20',
            'appointment_time' => '10:00 AM',
            'status' => 'pending',
            'reason' => 'Milo Checkup',
        ]);

        Appointment::create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
            'branch' => 'Mabini Branch',
            'appointment_date' => '2026-11-25',
            'appointment_time' => '11:00 AM',
            'status' => 'pending',
            'reason' => 'Milo Other Date',
        ]);

        $response = $this->actingAs($admin)->get('/admin/appointments?date=2026-11-20');
        $response->assertStatus(200);
        $response->assertSee('Milo Checkup');
        $response->assertDontSee('Milo Other Date');
    }

    public function test_status_tabs_preserve_active_search_and_date_parameters(): void
    {
        $admin = User::create([
            'name' => 'Super Admin Query',
            'email' => 'super_query@vetbook.test',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'branch' => null,
        ]);

        $response = $this->actingAs($admin)->get('/admin/appointments?search=Max&date=2026-11-20&status=pending');
        $response->assertStatus(200);

        // Verify status tab links retain search=Max and date=2026-11-20
        $response->assertSee('search=Max');
        $response->assertSee('date=2026-11-20');
        $response->assertSee('status=approved');
        $response->assertSee('name="status" value="pending"', false);
    }

    public function test_cancelled_appointment_displays_cancellation_reason_in_table(): void
    {
        $admin = User::create([
            'name' => 'Super Admin Reason',
            'email' => 'super_reason@vetbook.test',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'branch' => null,
        ]);

        $owner = User::create([
            'name' => 'Owner Reason',
            'email' => 'owner_reason@example.com',
            'password' => bcrypt('password123'),
            'role' => 'pet_owner',
        ]);

        $pet = Pet::create([
            'user_id' => $owner->id,
            'name' => 'Choco',
            'species' => 'Dog',
        ]);

        Appointment::create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
            'branch' => 'Mabini Branch',
            'appointment_date' => now()->toDateString(),
            'appointment_time' => '02:00 PM',
            'status' => 'cancelled',
            'reason' => 'Checkup',
            'notes' => "[Cancelled by Pet Owner] Reason: Emergency family trip out of town",
        ]);

        $response = $this->actingAs($admin)->get('/admin/appointments?status=cancelled');
        $response->assertStatus(200);
        $response->assertSee('Emergency family trip out of town');
    }
}
