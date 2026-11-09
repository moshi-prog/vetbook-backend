<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Pet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $response = $this->get('/admin');
        $response->assertRedirect('/admin/login');
    }

    public function test_admin_can_view_dashboard_with_layout_and_data(): void
    {
        $admin = User::create([
            'name' => 'Dr. Jane Vet',
            'email' => 'admin@vetbook.test',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'branch' => 'Main Clinic',
        ]);

        $petOwner = User::create([
            'name' => 'John Doe',
            'email' => 'john@test.com',
            'password' => bcrypt('password123'),
            'role' => 'pet_owner',
        ]);

        $pet = Pet::create([
            'user_id' => $petOwner->id,
            'name' => 'Buddy',
            'species' => 'Dog',
            'breed' => 'Golden Retriever',
            'age' => 3,
        ]);

        Appointment::create([
            'user_id' => $petOwner->id,
            'pet_id' => $pet->id,
            'branch' => 'Main Clinic',
            'appointment_date' => now()->toDateString(),
            'appointment_time' => '10:00 AM',
            'status' => 'pending',
            'reason' => 'Checkup',
        ]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertStatus(200);
        $response->assertSee('VetBook');
        $response->assertSee('ADMIN');
        $response->assertSee('Dr. Jane Vet');
        $response->assertSee('Main Clinic');
        $response->assertSee('Total Appointments');
        $response->assertSee('Pending Approvals');
        $response->assertSee('Registered Pets');
        $response->assertSee('Recent Appointments');
        $response->assertSee('Buddy');
        $response->assertSee('John Doe');
    }

    public function test_admin_without_branch_shows_all_branches(): void
    {
        $admin = User::create([
            'name' => 'Dr. Alex',
            'email' => 'alex@vetbook.test',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'branch' => null,
        ]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertStatus(200);
        $response->assertSee('All Branches');
        $response->assertSee('Appointments by Branch');
    }
}
