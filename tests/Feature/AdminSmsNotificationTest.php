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
use Tymon\JWTAuth\Facades\JWTAuth;

class AdminSmsNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.iprogsms.api_token' => 'dummy-test-token',
            'services.iprogsms.base_url' => 'https://www.iprogsms.com/api/v1',
        ]);

        Http::fake([
            'https://www.iprogsms.com/api/v1/sms_messages' => Http::response([
                'status' => 200,
                'message_id' => 'iprog-msg-12345',
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
            'appointment_time' => '14:00',
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
        $this->assertEquals('CONFIRMED: Bantay for Maria Clara on Nov 20, 2026 at 2:00 PM.', $log->message);
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
                'appointment_time' => '15:30',
            ]);

        $response->assertRedirect('/admin/appointments');
        $this->assertEquals($newDate, \Carbon\Carbon::parse($appointment->fresh()->appointment_date)->toDateString());
        $this->assertEquals('15:30', $appointment->fresh()->appointment_time);

        $log = SmsLog::where('appointment_id', $appointment->id)
            ->where('type', 'rescheduled')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('639171234567', $log->recipient_phone);
        $formattedDate = \Carbon\Carbon::parse($newDate)->format('M d, Y');
        $this->assertEquals("RESCHEDULED: Bantay moves to {$formattedDate} at 3:30 PM. See you at Batangas City Branch!", $log->message);
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

    public function test_format_template_replaces_reason_placeholder_and_default_cancellation_message(): void
    {
        [$owner, $pet] = $this->createOwnerAndPet();

        $withReason = Appointment::create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
            'branch' => 'Mabini Branch',
            'appointment_date' => '2026-11-28',
            'appointment_time' => '11:00 AM',
            'status' => 'cancelled',
            'reason' => 'Checkup',
            'notes' => "Previous note\n\n[Cancelled] Doctor unavailable due to surgery",
        ]);
        $withReason->load(['user', 'pet']);

        $defaultTemplate = 'Hi {owner_name}, your appointment for {pet_name} on {date} has been cancelled by the clinic. Reason: {reason}. — BK Petaholic';
        $formattedWithReason = SMSService::formatTemplate('sms_template_cancellation', $defaultTemplate, $withReason);
        $this->assertSame(
            'Hi Maria Clara, your appointment for Bantay on Nov 28, 2026 has been cancelled by the clinic. Reason: Doctor unavailable due to surgery. — BK Petaholic',
            $formattedWithReason
        );

        $withoutReason = Appointment::create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
            'branch' => 'Mabini Branch',
            'appointment_date' => '2026-11-29',
            'appointment_time' => '02:00 PM',
            'status' => 'cancelled',
            'reason' => 'Vaccination',
            'notes' => null,
        ]);
        $withoutReason->load(['user', 'pet']);

        $formattedFallback = SMSService::formatTemplate('sms_template_cancellation', $defaultTemplate, $withoutReason);
        $this->assertSame(
            'Hi Maria Clara, your appointment for Bantay on Nov 29, 2026 has been cancelled by the clinic. Reason: Schedule adjustment. — BK Petaholic',
            $formattedFallback
        );
    }

    public function test_cancel_appointment_sends_default_sms_including_reason_when_no_custom_template(): void
    {
        config([
            'services.iprogsms.api_token' => 'dummy-test-token',
            'services.iprogsms.base_url' => 'https://www.iprogsms.com/api/v1',
        ]);

        Http::fake([
            'https://www.iprogsms.com/api/v1/sms_messages' => Http::response([
                'status' => 200,
                'message_id' => 'iprog-msg-999',
            ], 200),
        ]);

        $admin = $this->createAdmin();
        [$owner, $pet] = $this->createOwnerAndPet();

        $appointment = Appointment::create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
            'branch' => 'Mabini Branch',
            'appointment_date' => '2026-11-28',
            'appointment_time' => '11:00 AM',
            'status' => 'pending',
            'reason' => 'Checkup',
        ]);

        $this->actingAs($admin)
            ->post("/admin/appointments/{$appointment->id}/cancel", [
                'cancel_reason' => 'Clinic power maintenance',
            ])
            ->assertRedirect(route('admin.appointments.index'));

        $log = SmsLog::where('appointment_id', $appointment->id)
            ->where('type', 'cancellation')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame(
            'Hi Maria Clara, your appointment for Bantay on Nov 28, 2026 has been cancelled by the clinic. Reason: Clinic power maintenance. — BK Petaholic',
            $log->message
        );
    }

    public function test_format_template_extracts_owner_cancel_reason_with_or_without_reason_label(): void
    {
        [$owner, $pet] = $this->createOwnerAndPet();

        $withReasonLabel = Appointment::create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
            'branch' => 'Mabini Branch',
            'appointment_date' => '2026-11-30',
            'appointment_time' => '10:00 AM',
            'status' => 'cancelled',
            'reason' => 'Checkup',
            'notes' => "[Cancelled by Pet Owner] Reason: Travel plans",
        ]);
        $withReasonLabel->load(['user', 'pet']);

        $withoutReasonLabel = Appointment::create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
            'branch' => 'Mabini Branch',
            'appointment_date' => '2026-12-01',
            'appointment_time' => '11:00 AM',
            'status' => 'cancelled',
            'reason' => 'Checkup',
            'notes' => "[Cancelled by Pet Owner] Pet feels better",
        ]);
        $withoutReasonLabel->load(['user', 'pet']);

        $defaultTemplate = 'Hi {owner_name}, your appointment for {pet_name} on {date} has been cancelled by the clinic. Reason: {reason}. — BK Petaholic';
        $this->assertSame(
            'Hi Maria Clara, your appointment for Bantay on Nov 30, 2026 has been cancelled by the clinic. Reason: Travel plans. — BK Petaholic',
            SMSService::formatTemplate('sms_template_cancellation', $defaultTemplate, $withReasonLabel)
        );
        $this->assertSame(
            'Hi Maria Clara, your appointment for Bantay on Dec 01, 2026 has been cancelled by the clinic. Reason: Pet feels better. — BK Petaholic',
            SMSService::formatTemplate('sms_template_cancellation', $defaultTemplate, $withoutReasonLabel)
        );
    }

    public function test_owner_self_cancel_sends_requested_cancellation_sms_instead_of_clinic_cancelled(): void
    {
        [$owner, $pet] = $this->createOwnerAndPet();

        $appointment = Appointment::create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
            'branch' => 'Mabini Branch',
            'appointment_date' => '2026-12-02',
            'appointment_time' => '09:30 AM',
            'status' => 'approved',
            'reason' => 'Checkup',
        ]);

        $token = JWTAuth::fromUser($owner);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/appointments/{$appointment->id}/cancel", [
                'cancel_reason' => 'Schedule conflict',
            ])
            ->assertStatus(200);

        $log = SmsLog::where('appointment_id', $appointment->id)
            ->where('type', 'cancellation')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame(
            'Hi Maria Clara, your appointment for Bantay on Dec 02, 2026 has been cancelled as requested. — BK Petaholic',
            $log->message
        );
    }
}
