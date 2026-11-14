<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Pet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBranchScopingAndCsvExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_admin_cannot_destroy_appointment_from_another_branch(): void
    {
        $mabiniAdmin = User::create([
            'name' => 'Mabini Admin',
            'email' => 'mabini.admin@vetbook.test',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'branch' => 'Mabini Branch',
        ]);

        $owner = User::create([
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@vetbook.test',
            'password' => bcrypt('password123'),
            'role' => 'pet_owner',
        ]);

        $pet = Pet::create([
            'user_id' => $owner->id,
            'name' => 'Bantay',
            'species' => 'Dog',
        ]);

        $mabiniAppt = Appointment::create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
            'branch' => 'Mabini Branch',
            'appointment_date' => now()->addDay()->toDateString(),
            'appointment_time' => '09:00',
            'status' => 'pending',
            'reason' => 'Checkup',
        ]);

        $batangasAppt = Appointment::create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
            'branch' => 'Batangas City Branch',
            'appointment_date' => now()->addDays(2)->toDateString(),
            'appointment_time' => '10:00',
            'status' => 'pending',
            'reason' => 'Vaccination',
        ]);

        // Cross-branch destroy must abort 403 and keep record
        $forbidden = $this->actingAs($mabiniAdmin)->delete("/admin/appointments/{$batangasAppt->id}");
        $forbidden->assertStatus(403);
        $this->assertDatabaseHas('appointments', ['id' => $batangasAppt->id]);

        // Own-branch destroy succeeds
        $allowed = $this->actingAs($mabiniAdmin)->delete("/admin/appointments/{$mabiniAppt->id}");
        $allowed->assertRedirect('/admin/appointments');
        $this->assertDatabaseMissing('appointments', ['id' => $mabiniAppt->id]);
    }

    public function test_branch_admin_export_filters_by_branch_even_when_ids_are_supplied(): void
    {
        $mabiniAdmin = User::create([
            'name' => 'Mabini Admin',
            'email' => 'mabini.export@vetbook.test',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'branch' => 'Mabini Branch',
        ]);

        $owner = User::create([
            'name' => 'Maria Clara',
            'email' => 'maria.export@vetbook.test',
            'password' => bcrypt('password123'),
            'role' => 'pet_owner',
        ]);

        $pet = Pet::create([
            'user_id' => $owner->id,
            'name' => 'Brownie',
            'species' => 'Dog',
        ]);

        $mabiniAppt = Appointment::create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
            'branch' => 'Mabini Branch',
            'appointment_date' => now()->addDay()->toDateString(),
            'appointment_time' => '09:30',
            'status' => 'approved',
            'reason' => 'Mabini Dental Visit',
        ]);

        $batangasAppt = Appointment::create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
            'branch' => 'Batangas City Branch',
            'appointment_date' => now()->addDays(2)->toDateString(),
            'appointment_time' => '11:00',
            'status' => 'approved',
            'reason' => 'Secret Batangas Surgery',
        ]);

        $response = $this->actingAs($mabiniAdmin)->get(
            "/admin/appointments/export?format=csv&ids={$mabiniAppt->id},{$batangasAppt->id}"
        );

        $response->assertStatus(200);
        $csv = $response->streamedContent();

        $this->assertStringContainsString('Mabini Dental Visit', $csv);
        $this->assertStringNotContainsString('Secret Batangas Surgery', $csv);
    }

    public function test_pet_csv_export_includes_health_status_column(): void
    {
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'super@vetbook.test',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'branch' => null,
        ]);

        $owner = User::create([
            'name' => 'Jose Rizal',
            'email' => 'jose@vetbook.test',
            'password' => bcrypt('password123'),
            'role' => 'pet_owner',
        ]);

        Pet::create([
            'user_id' => $owner->id,
            'name' => 'Chichi',
            'species' => 'Dog',
            'health_status' => 'attention',
        ]);

        Pet::create([
            'user_id' => $owner->id,
            'name' => 'Mittens',
            'species' => 'Cat',
            'health_status' => 'healthy',
        ]);

        $response = $this->actingAs($admin)->get('/admin/pets/export?format=csv');
        $response->assertStatus(200);

        $csv = $response->streamedContent();
        $this->assertStringContainsString('Health Status', $csv);
        $this->assertStringContainsString('attention', $csv);
        $this->assertStringContainsString('healthy', $csv);
    }
}

