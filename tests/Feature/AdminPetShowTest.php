<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\Pet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPetShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_comprehensive_pet_details(): void
    {
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@vetbook.test',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);

        $owner = User::create([
            'name' => 'Maria Santos',
            'email' => 'maria@example.com',
            'phone_number' => '09171234567',
            'password' => bcrypt('password123'),
            'role' => 'pet_owner',
        ]);

        $pet = Pet::create([
            'user_id' => $owner->id,
            'name' => 'Milo',
            'species' => 'Dog',
            'breed' => 'Golden Retriever',
            'gender' => 'male',
            'age' => 3,
            'weight' => 28.5,
            'medical_conditions' => 'Allergic to chicken proteins. Sensitive stomach.',
        ]);

        MedicalRecord::create([
            'pet_id' => $pet->id,
            'veterinarian' => 'Dr. Reyes',
            'consultation_date' => now()->subDays(5)->toDateString(),
            'diagnosis' => 'Annual Health Checkup & Vaccinations',
            'treatment' => 'Administered Rabies booster',
            'vaccinations' => 'Rabies booster',
            'next_visit_date' => now()->addMonths(6)->toDateString(),
        ]);

        Appointment::create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
            'branch' => 'Mabini Branch',
            'appointment_date' => now()->addDays(2)->toDateString(),
            'appointment_time' => '10:00:00',
            'status' => 'approved',
            'reason' => 'Vaccination Booster',
            'notes' => 'Owner requested morning slot',
            'veterinarian' => 'Dr. Reyes',
        ]);

        $response = $this->actingAs($admin)->get("/admin/pets/{$pet->id}");

        $response->assertStatus(200);
        $response->assertSee('Milo');
        $response->assertSee('Golden Retriever');
        $response->assertSee('28.5 kg');
        $response->assertSee('3 yrs old');
        $response->assertSee('Maria Santos');
        $response->assertSee('09171234567');
        $response->assertSee('maria@example.com');
        $response->assertSee('Allergic to chicken proteins. Sensitive stomach.');
        $response->assertSee('Dr. Reyes');
        $response->assertSee('Annual Health Checkup &amp; Vaccinations', false);
        $response->assertSee('Mabini Branch');
        $response->assertSee('Delete Pet Profile');
    }

    public function test_admin_can_view_pet_with_minimal_information(): void
    {
        $admin = User::create([
            'name' => 'Mabini Admin',
            'email' => 'mabini@vetbook.test',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'branch' => 'Mabini Branch',
        ]);

        $owner = User::create([
            'name' => 'Anonymous User',
            'email' => 'anon@example.com',
            'password' => bcrypt('password123'),
            'role' => 'pet_owner',
        ]);

        $pet = Pet::create([
            'user_id' => $owner->id,
            'name' => 'Stray Kitty',
            'species' => 'Cat',
        ]);

        $response = $this->actingAs($admin)->get("/admin/pets/{$pet->id}");

        $response->assertStatus(200);
        $response->assertSee('Stray Kitty');
        $response->assertSee('Cat');
        $response->assertSee('Not recorded');
        $response->assertSee('Anonymous User');
        $response->assertSee('No pre-existing conditions or allergies recorded');
    }
}
