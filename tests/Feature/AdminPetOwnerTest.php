<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Pet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPetOwnerTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_admin_can_view_newly_registered_pet_owner_with_no_appointments(): void
    {
        $branchAdmin = User::create([
            'name' => 'Mabini Admin',
            'email' => 'mabini@vetbook.test',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'branch' => 'Mabini Branch',
        ]);

        $newOwner = User::create([
            'name' => 'New Mobile Client',
            'email' => 'newmobile@example.com',
            'phone_number' => '09123456789',
            'password' => bcrypt('password123'),
            'role' => 'pet_owner',
        ]);

        $response = $this->actingAs($branchAdmin)->get('/admin/pet-owners');

        $response->assertStatus(200);
        $response->assertSee('New Mobile Client');
        $response->assertSee('newmobile@example.com');
        $response->assertSee('09123456789');
        $response->assertSee('New Client');
    }

    public function test_branch_admin_can_open_profile_of_new_owner_with_zero_appointments(): void
    {
        $branchAdmin = User::create([
            'name' => 'Mabini Admin',
            'email' => 'mabini@vetbook.test',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'branch' => 'Mabini Branch',
        ]);

        $newOwner = User::create([
            'name' => 'New Mobile Client',
            'email' => 'newmobile@example.com',
            'phone_number' => '09123456789',
            'password' => bcrypt('password123'),
            'role' => 'pet_owner',
        ]);

        $response = $this->actingAs($branchAdmin)->get("/admin/pet-owners/{$newOwner->id}");

        $response->assertStatus(200);
        $response->assertSee('New Mobile Client');
        $response->assertSee('No appointments booked yet');
    }

    public function test_branch_admin_can_open_profile_of_owner_who_only_booked_at_another_branch(): void
    {
        $mabiniAdmin = User::create([
            'name' => 'Mabini Admin',
            'email' => 'mabini@vetbook.test',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'branch' => 'Mabini Branch',
        ]);

        $batangasOwner = User::create([
            'name' => 'Batangas Client',
            'email' => 'batangas@example.com',
            'phone_number' => '09998887777',
            'password' => bcrypt('password123'),
            'role' => 'pet_owner',
        ]);

        $pet = Pet::create([
            'user_id' => $batangasOwner->id,
            'name' => 'Bella',
            'species' => 'Dog',
        ]);

        Appointment::create([
            'user_id' => $batangasOwner->id,
            'pet_id' => $pet->id,
            'branch' => 'Batangas City Branch',
            'appointment_date' => now()->toDateString(),
            'appointment_time' => '10:00 AM',
            'service_type' => 'Checkup',
            'status' => 'confirmed',
        ]);

        // Prior to the fix, this would throw a 403 Access Denied
        $response = $this->actingAs($mabiniAdmin)->get("/admin/pet-owners/{$batangasOwner->id}");

        $response->assertStatus(200);
        $response->assertSee('Batangas Client');
        $response->assertSee('Bella');
    }
}

