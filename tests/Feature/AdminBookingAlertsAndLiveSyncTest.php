<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\Appointment;
use App\Models\Pet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AdminBookingAlertsAndLiveSyncTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $mabiniAdmin;
    private User $batangasAdmin;
    private User $owner;
    private Pet $pet;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Super Admin (branch = null)
        $this->superAdmin = User::create([
            'name'     => 'Super Admin',
            'email'    => 'superadmin@vetbook.test',
            'password' => bcrypt('password123'),
            'role'     => 'admin',
            'branch'   => null,
        ]);

        // 2. Mabini Branch Admin
        $this->mabiniAdmin = User::create([
            'name'     => 'Mabini Admin',
            'email'    => 'mabini.admin@vetbook.test',
            'password' => bcrypt('password123'),
            'role'     => 'admin',
            'branch'   => 'Mabini Branch',
        ]);

        // 3. Batangas City Branch Admin
        $this->batangasAdmin = User::create([
            'name'     => 'Batangas Admin',
            'email'    => 'batangas.admin@vetbook.test',
            'password' => bcrypt('password123'),
            'role'     => 'admin',
            'branch'   => 'Batangas City Branch',
        ]);

        // 4. Pet Owner
        $this->owner = User::create([
            'name'         => 'Maria Clara',
            'email'        => 'maria.clara@example.com',
            'phone_number' => '09171234567',
            'password'     => bcrypt('password123'),
            'role'         => 'pet_owner',
        ]);

        // 5. Pet
        $this->pet = Pet::create([
            'user_id'            => $this->owner->id,
            'name'               => 'Brownie',
            'species'            => 'Dog',
            'breed'              => 'Aspin',
            'gender'             => 'male',
            'age'                => 3,
            'weight'             => 14.5,
            'medical_conditions' => 'None',
        ]);
    }

    public function test_booking_creates_notifications_for_super_admin_and_matching_branch_admin(): void
    {
        $token = JWTAuth::fromUser($this->owner);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/appointments', [
            'pet_id'           => $this->pet->id,
            'branch'           => 'Mabini Branch',
            'appointment_date' => now()->addDays(2)->toDateString(),
            'appointment_time' => '10:00',
            'reason'           => 'Vaccination',
        ]);

        $response->assertStatus(201);
        $appointmentId = $response->json('appointment.id');

        // Notification for Super Admin
        $superAdminNotification = AppNotification::where('user_id', $this->superAdmin->id)
            ->where('type', 'appointment_booked')
            ->first();
        $this->assertNotNull($superAdminNotification);
        $this->assertEquals('New Appointment Booked', $superAdminNotification->title);
        $this->assertStringContainsString('Maria Clara booked an appointment for Brownie', $superAdminNotification->message);
        $this->assertStringContainsString('(Mabini Branch)', $superAdminNotification->message);
        $this->assertEquals($appointmentId, $superAdminNotification->data['appointment_id']);

        // Notification for Mabini Branch Admin
        $mabiniNotification = AppNotification::where('user_id', $this->mabiniAdmin->id)
            ->where('type', 'appointment_booked')
            ->first();
        $this->assertNotNull($mabiniNotification);
        $this->assertEquals($appointmentId, $mabiniNotification->data['appointment_id']);

        // Notification must NOT be sent to other branch's admin (Batangas City Branch)
        $batangasNotification = AppNotification::where('user_id', $this->batangasAdmin->id)
            ->where('type', 'appointment_booked')
            ->first();
        $this->assertNull($batangasNotification);
    }

    public function test_admin_appointments_defaults_to_newest_booked_first(): void
    {
        // Appt 1 created first, but appointment date is further in future
        $appt1 = Appointment::create([
            'user_id'          => $this->owner->id,
            'pet_id'           => $this->pet->id,
            'branch'           => 'Mabini Branch',
            'appointment_date' => now()->addDays(10)->toDateString(),
            'appointment_time' => '09:00:00',
            'status'           => 'pending',
            'reason'           => 'Oldest Booking Slot',
            'created_at'       => now()->subMinutes(30),
        ]);

        // Appt 2 created recently, but appointment date is sooner
        $appt2 = Appointment::create([
            'user_id'          => $this->owner->id,
            'pet_id'           => $this->pet->id,
            'branch'           => 'Mabini Branch',
            'appointment_date' => now()->addDays(2)->toDateString(),
            'appointment_time' => '14:00:00',
            'status'           => 'pending',
            'reason'           => 'Newest Booking Slot',
            'created_at'       => now()->subMinute(),
        ]);

        // Default: newest_booked (sorted by created_at desc) -> Appt 2 should be first
        $response = $this->actingAs($this->superAdmin)->get(route('admin.appointments.index'));
        $response->assertOk();

        $appointments = $response->viewData('appointments');
        $this->assertEquals($appt2->id, $appointments->first()->id);

        // Explicit ?sort=appointment_date (sorted by appointment_date desc) -> Appt 1 (10 days in future) first
        $responseDate = $this->actingAs($this->superAdmin)->get(route('admin.appointments.index', ['sort' => 'appointment_date']));
        $responseDate->assertOk();
        $appointmentsDate = $responseDate->viewData('appointments');
        $this->assertEquals($appt1->id, $appointmentsDate->first()->id);

        // Explicit ?sort=oldest (sorted by created_at asc) -> Appt 1 first
        $responseOldest = $this->actingAs($this->superAdmin)->get(route('admin.appointments.index', ['sort' => 'oldest']));
        $responseOldest->assertOk();
        $appointmentsOldest = $responseOldest->viewData('appointments');
        $this->assertEquals($appt1->id, $appointmentsOldest->first()->id);
    }

    public function test_admin_appointments_has_sort_selector_and_live_sync_elements(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.appointments.index'));
        $response->assertOk();

        // Sort selector
        $response->assertSee('Sort: Newest Booked');
        $response->assertSee('Sort: Appointment Date');
        $response->assertSee('Sort: Oldest First');

        // Live table sync banner & poller
        $response->assertSee('id="liveNewBookingBanner"', false);
        $response->assertSee('New appointment received just now!');
        $response->assertSee('admin/appointments/latest-id');
    }

    public function test_admin_latest_id_endpoint_returns_json(): void
    {
        $appt = Appointment::create([
            'user_id'          => $this->owner->id,
            'pet_id'           => $this->pet->id,
            'branch'           => 'Mabini Branch',
            'appointment_date' => now()->addDays(2)->toDateString(),
            'appointment_time' => '10:00:00',
            'status'           => 'pending',
            'reason'           => 'Checkup',
        ]);

        // Super Admin query
        $response = $this->actingAs($this->superAdmin)->getJson(route('admin.appointments.latest-id'));
        $response->assertOk()
            ->assertJson([
                'status'    => 'success',
                'latest_id' => $appt->id,
            ]);

        // Mabini Branch Admin query
        $responseMabini = $this->actingAs($this->mabiniAdmin)->getJson(route('admin.appointments.latest-id'));
        $responseMabini->assertOk()
            ->assertJson([
                'status'    => 'success',
                'latest_id' => $appt->id,
            ]);

        // Batangas Branch Admin query (scoped to their branch, so should be 0)
        $responseBatangas = $this->actingAs($this->batangasAdmin)->getJson(route('admin.appointments.latest-id'));
        $responseBatangas->assertOk()
            ->assertJson([
                'status'    => 'success',
                'latest_id' => 0,
            ]);
    }

    public function test_admin_routes_return_anti_cache_headers(): void
    {
        $response = $this->actingAs($this->superAdmin)->get('/admin/appointments');

        $response->assertOk();
        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);

        $this->assertEquals('no-cache', $response->headers->get('Pragma'));
        $this->assertEquals('Sun, 02 Jan 1990 00:00:00 GMT', $response->headers->get('Expires'));
    }
}

