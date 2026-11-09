<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\ClinicSetting;
use App\Models\Pet;
use App\Models\SmsLog;
use App\Models\User;
use App\Services\SMSService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminSmsNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.philsms.api_token' => 'dummy-test-token',
            'services.philsms.base_url' => 'https://dashboard.philsms.com/api/v3',
            'services.philsms.sender_id' => 'VetBook',
        ]);

        Http::fake([
            'https://dashboard.philsms.com/api/v3/sms/send' => Http::response([
                'status' => 'success',
                'data' => ['uid' => 'msg-test-12345'],
            ], 200),
        ]);
    }

    private function createAdmin(): User
    {
        return User::create([
            'name' => 'Admin User',
            'email' => 'admin@vetbook.test',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'branch' => null,
        ]);
    }

    private function createOwnerAndPet(): array
    {
        $owner = User::create([
            'name' => 'Maria Clara',
            'email' => 'maria@test.com',
            'phone_number' => '09171234567',
            'password' => bcrypt('password123'),
            'role' => 'pet_owner',
        ]);

        $pet = Pet::create([
            'user_id' => $owner->id,
            'name' => 'Bantay',
            'species' => 'Dog',
            'breed' => 'Aspin',
        ]);

        return [$owner, $pet];
    }

    public function test_format_template_replaces_all_placeholders(): void
    {
        [$owner, $pet] = $this->createOwnerAndPet();

        $appointment = Appointment::create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
            'branch' => 'Mabini Branch',
            'appointment_date' => '2026-12-25',
            'appointment_time' => '10:30 AM',
            'status' => 'pending',
            'reason' => 'Checkup',
        ]);

        $appointment->load(['user', 'pet']);

        ClinicSetting::set('sms_template_confirmation', 'Dear {owner_name}, appointment for {pet_name} on {date} at {time} at {branch} is locked in!');

        $formatted = SMSService::formatTemplate(
            'sms_template_confirmation',
            'Default message',
            $appointment
        );

        $this->assertEquals(
            'Dear Maria Clara, appointment for Bantay on Dec 25, 2026 at 10:30 AM at Mabini Branch is locked in!',
            $formatted
        );
    }

    public function test_approve_appointment_sends_sms_using_custom_confirmation_template(): void
    {
        $admin = $this->createAdmin();
        [$owner, $pet] = $this->createOwnerAndPet();

        ClinicSetting::set('sms_template_confirmation', 'CONFIRMED: {pet_name} for {owner_name} on {date} at {time}.');

        $appointment = Appointment::create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
            'branch' => 'Mabini Branch',
            'appointment_date' => '2026-11-20',
            'appointment_time' => '02:00 PM',
            'status' => 'pending',
            'reason' => 'Vaccination',
        ]);

        $response = $this->actingAs($admin)
            ->from('/admin/appointments')
            ->post("/admin/appointments/{$appointment->id}/approve");

        $response->assertRedirect('/admin/appointments');
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'approved',
        ]);

        $log = SmsLog::where('appointment_id', $appointment->id)
            ->where('type', 'confirmation')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('639171234567', $log->recipient_phone);
        $this->assertEquals('CONFIRMED: Bantay for Maria Clara on Nov 20, 2026 at 02:00 PM.', $log->message);
        $this->assertEquals('delivered', $log->status);
    }

    public function test_reschedule_appointment_sends_sms_using_custom_rescheduled_template(): void
    {
        $admin = $this->createAdmin();
        [$owner, $pet] = $this->createOwnerAndPet();

        ClinicSetting::set('sms_template_rescheduled', 'RESCHEDULED: {pet_name} moves to {date} at {time}. See you at {branch}!');

        $appointment = Appointment::create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
            'branch' => 'Batangas City Branch',
            'appointment_date' => now()->addDays(2)->toDateString(),
            'appointment_time' => '09:00 AM',
            'status' => 'approved',
            'reason' => 'Checkup',
        ]);

        $newDate = now()->addDays(5)->toDateString();
        $response = $this->actingAs($admin)
            ->from('/admin/appointments')
            ->post("/admin/appointments/{$appointment->id}/reschedule", [
                'appointment_date' => $newDate,
                'appointment_time' => '03:30 PM',
            ]);

        $response->assertRedirect('/admin/appointments');
        $this->assertEquals($newDate, \Carbon\Carbon::parse($appointment->fresh()->appointment_date)->toDateString());
        $this->assertEquals('03:30 PM', $appointment->fresh()->appointment_time);

        $log = SmsLog::where('appointment_id', $appointment->id)
            ->where('type', 'rescheduled')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('639171234567', $log->recipient_phone);
        $formattedDate = \Carbon\Carbon::parse($newDate)->format('M d, Y');
        $this->assertEquals("RESCHEDULED: Bantay moves to {$formattedDate} at 03:30 PM. See you at Batangas City Branch!", $log->message);
    }

    public function test_complete_appointment_sends_sms_using_custom_completed_template(): void
    {
        $admin = $this->createAdmin();
        [$owner, $pet] = $this->createOwnerAndPet();

        ClinicSetting::set('sms_template_completed', 'COMPLETED: Thank you {owner_name} for bringing {pet_name} to {branch}!');

        $appointment = Appointment::create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
            'branch' => 'Mabini Branch',
            'appointment_date' => now()->toDateString(),
            'appointment_time' => '10:00 AM',
            'status' => 'approved',
            'reason' => 'Checkup',
        ]);

        $response = $this->actingAs($admin)
            ->from('/admin/appointments')
            ->post("/admin/appointments/{$appointment->id}/complete");

        $response->assertRedirect('/admin/appointments');
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'completed',
        ]);

        $log = SmsLog::where('appointment_id', $appointment->id)
            ->where('type', 'completed')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('639171234567', $log->recipient_phone);
        $this->assertEquals('COMPLETED: Thank you Maria Clara for bringing Bantay to Mabini Branch!', $log->message);
    }

    public function test_cancel_appointment_sends_sms_using_custom_cancellation_template(): void
    {
        $admin = $this->createAdmin();
        [$owner, $pet] = $this->createOwnerAndPet();

        ClinicSetting::set('sms_template_cancellation', 'CANCELLED: {pet_name}\'s visit on {date} was cancelled. Call {branch} to rebook.');

        $appointment = Appointment::create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
            'branch' => 'Batangas City Branch',
            'appointment_date' => '2026-11-28',
            'appointment_time' => '11:00 AM',
            'status' => 'pending',
            'reason' => 'Checkup',
        ]);

        $response = $this->actingAs($admin)
            ->post("/admin/appointments/{$appointment->id}/cancel", [
                'cancel_reason' => 'Owner requested cancellation',
            ]);

        $response->assertRedirect(route('admin.appointments.index'));
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'cancelled',
        ]);

        $log = SmsLog::where('appointment_id', $appointment->id)
            ->where('type', 'cancellation')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('639171234567', $log->recipient_phone);
        $this->assertEquals("CANCELLED: Bantay's visit on Nov 28, 2026 was cancelled. Call Batangas City Branch to rebook.", $log->message);
    }

    public function test_sms_admin_index_displays_new_types_and_dynamic_templates(): void
    {
        $admin = $this->createAdmin();
        [$owner, $pet] = $this->createOwnerAndPet();

        ClinicSetting::set('sms_template_rescheduled', 'Custom Reschedule SMS text');
        ClinicSetting::set('sms_template_completed', 'Custom Completed SMS text');

        SmsLog::create([
            'user_id' => $owner->id,
            'recipient_phone' => '639171234567',
            'type' => 'rescheduled',
            'message' => 'Unique Reschedule Log 101',
            'status' => 'delivered',
        ]);

        SmsLog::create([
            'user_id' => $owner->id,
            'recipient_phone' => '639171234567',
            'type' => 'completed',
            'message' => 'Unique Completed Log 202',
            'status' => 'delivered',
        ]);

        $response = $this->actingAs($admin)->get('/admin/sms');
        $response->assertStatus(200);

        // Verify custom templates in SMS Templates grid
        $response->assertSee('Custom Reschedule SMS text');
        $response->assertSee('Custom Completed SMS text');

        // Verify table displays pills for rescheduled and completed
        $response->assertSee('pill-rescheduled');
        $response->assertSee('pill-completed');

        // Verify filter dropdown includes new types
        $response->assertSee('value="rescheduled"', false);
        $response->assertSee('value="completed"', false);

        // Test filtering by type=rescheduled
        $filterResponse = $this->actingAs($admin)->get('/admin/sms?type=rescheduled');
        $filterResponse->assertStatus(200);
        $filterResponse->assertSee('Unique Reschedule Log 101');
        $filterResponse->assertDontSee('Unique Completed Log 202');
    }
}
