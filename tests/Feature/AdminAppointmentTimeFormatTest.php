<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\ClinicSetting;
use App\Models\Pet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAppointmentTimeFormatTest extends TestCase
{
    use RefreshDatabase;

    private function seedAppointmentContext(string $time = '14:30'): array
    {
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@vetbook.test',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'branch' => null,
        ]);

        $owner = User::create([
            'name' => 'Clara Santos',
            'email' => 'clara@test.com',
            'password' => bcrypt('password123'),
            'role' => 'pet_owner',
        ]);

        $pet = Pet::create([
            'user_id' => $owner->id,
            'name' => 'Milo',
            'species' => 'Dog',
            'breed' => 'Beagle',
        ]);

        $appointment = Appointment::create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
            'branch' => 'Mabini Branch',
            'appointment_date' => now()->addDay()->toDateString(),
            'appointment_time' => $time,
            'status' => 'pending',
            'reason' => 'Annual Checkup',
        ]);

        return [$admin, $owner, $pet, $appointment];
    }

    public function test_appointment_formatted_time_accessor_respects_preference_and_fallbacks(): void
    {
        ClinicSetting::set('pref_time_format', '12');
        $appt12 = new Appointment(['appointment_time' => '14:30']);
        $this->assertSame('2:30 PM', $appt12->formatted_time);

        ClinicSetting::set('pref_time_format', '24');
        $appt24 = new Appointment(['appointment_time' => '14:30']);
        $this->assertSame('14:30', $appt24->formatted_time);

        $empty = new Appointment(['appointment_time' => null]);
        $this->assertSame('—', $empty->formatted_time);
    }

    public function test_dashboard_and_appointments_display_12_hour_time_when_preference_is_12(): void
    {
        [$admin, $owner, $pet, $appointment] = $this->seedAppointmentContext('14:30');
        ClinicSetting::set('pref_time_format', '12');

        $dashboard = $this->actingAs($admin)->get('/admin');
        $dashboard->assertStatus(200);
        $dashboard->assertSee('2:30 PM');
        $dashboard->assertDontSee('>14:30<', false);

        $appointments = $this->actingAs($admin)->get('/admin/appointments');
        $appointments->assertStatus(200);
        $appointments->assertSee('2:30 PM');

        $show = $this->actingAs($admin)->get("/admin/appointments/{$appointment->id}");
        $show->assertStatus(200);
        $show->assertSee('<div class="detail-value">2:30 PM</div>', false);
        // Raw HTML <input type="time"> retains 24-hour value for editing
        $show->assertSee('value="14:30"', false);

        $petShow = $this->actingAs($admin)->get("/admin/pets/{$pet->id}");
        $petShow->assertStatus(200);
        $petShow->assertSee('2:30 PM');

        $ownerShow = $this->actingAs($admin)->get("/admin/pet-owners/{$owner->id}");
        $ownerShow->assertStatus(200);
        $ownerShow->assertSee('<td>2:30 PM</td>', false);
    }

    public function test_dashboard_and_appointments_display_24_hour_time_when_preference_is_24(): void
    {
        [$admin, $owner, $pet, $appointment] = $this->seedAppointmentContext('14:30');
        ClinicSetting::set('pref_time_format', '24');

        $dashboard = $this->actingAs($admin)->get('/admin');
        $dashboard->assertStatus(200);
        $dashboard->assertSee('14:30');
        $dashboard->assertDontSee('2:30 PM');

        $appointments = $this->actingAs($admin)->get('/admin/appointments');
        $appointments->assertStatus(200);
        $appointments->assertSee('14:30');
        $appointments->assertDontSee('2:30 PM');

        $show = $this->actingAs($admin)->get("/admin/appointments/{$appointment->id}");
        $show->assertStatus(200);
        $show->assertSee('<div class="detail-value">14:30</div>', false);
        $show->assertDontSee('<div class="detail-value">2:30 PM</div>', false);

        $petShow = $this->actingAs($admin)->get("/admin/pets/{$pet->id}");
        $petShow->assertStatus(200);
        $petShow->assertSee('14:30');
        $petShow->assertDontSee('2:30 PM');

        $ownerShow = $this->actingAs($admin)->get("/admin/pet-owners/{$owner->id}");
        $ownerShow->assertStatus(200);
        $ownerShow->assertSee('<td>14:30</td>', false);
        $ownerShow->assertDontSee('2:30 PM');
    }
}

