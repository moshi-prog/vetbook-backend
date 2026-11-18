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
        $response->assertSee('STAFF');
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
        $response->assertSee('ADMIN');
        $response->assertSee('Account Management');
        $response->assertSee('Clinic Staff');
        $response->assertSee('Pet Owner Accounts');
        $response->assertSee('Clinic Staff &amp; Admin Accounts', false);
        $response->assertDontSee('Pending Approvals');
    }

    public function test_admin_is_blocked_from_operational_routes_while_staff_has_access(): void
    {
        $admin = User::create([
            'name' => 'System Admin',
            'email' => 'sysadmin@vetbook.test',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'branch' => null,
        ]);

        $staff = User::create([
            'name' => 'Mabini Staff',
            'email' => 'staff@vetbook.test',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'branch' => 'Mabini Branch',
        ]);

        foreach (['/admin/appointments', '/admin/pets', '/admin/medical-records', '/admin/sms'] as $route) {
            $this->actingAs($admin)->get($route)->assertStatus(403);
            $this->actingAs($staff)->get($route)->assertStatus(200);
        }

        $this->actingAs($admin)->post('/admin/medical-records/medicines', [
            'name' => 'Amoxicillin',
            'stock' => 10,
        ])->assertStatus(403);

        // Sidebar & topbar checks
        $adminDash = $this->actingAs($admin)->get('/admin');
        $adminDash->assertOk()
            ->assertDontSee('setTimeout(checkAdminAlerts, 2500)', false)
            ->assertDontSee('<span class="nav-label">Appointments</span>', false)
            ->assertDontSee('<span class="nav-label">Pets</span>', false)
            ->assertDontSee('<span class="nav-label">Medical Records</span>', false)
            ->assertDontSee('<span class="nav-label">SMS Notifications</span>', false);

        $staffDash = $this->actingAs($staff)->get('/admin');
        $staffDash->assertOk()
            ->assertSee('Mabini Branch (Staff)')
            ->assertSee('setTimeout(checkAdminAlerts, 2500)', false)
            ->assertSee('<span class="nav-label">Appointments</span>', false)
            ->assertSee('<span class="nav-label">Pets</span>', false)
            ->assertSee('<span class="nav-label">Medical Records</span>', false)
            ->assertSee('<span class="nav-label">SMS Notifications</span>', false);

        // Pet owner detail view hides operational clinical links from Admin while showing them to Staff
        $owner = User::create([
            'name' => 'Owner Link Check',
            'email' => 'ownerlink@vetbook.test',
            'password' => bcrypt('password123'),
            'role' => 'pet_owner',
        ]);
        $pet = Pet::create([
            'user_id' => $owner->id,
            'name' => 'Pippin',
            'species' => 'Dog',
        ]);
        Appointment::create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
            'branch' => 'Mabini Branch',
            'appointment_date' => now()->addDay()->toDateString(),
            'appointment_time' => '10:00 AM',
            'status' => 'pending',
            'reason' => 'Checkup',
        ]);

        $this->actingAs($admin)->get("/admin/pet-owners/{$owner->id}")
            ->assertOk()
            ->assertSee('Pippin')
            ->assertSee('<div class="pet-tile">', false)
            ->assertDontSee('View Appointments')
            ->assertDontSee("window.location='", false);

        $this->actingAs($staff)->get("/admin/pet-owners/{$owner->id}")
            ->assertOk()
            ->assertSee('Pippin')
            ->assertSee("<a href=\"" . route('admin.pets.show', $pet->id) . "\" class=\"pet-tile\">", false)
            ->assertSee('View Appointments')
            ->assertSee("window.location='", false);
    }
}
