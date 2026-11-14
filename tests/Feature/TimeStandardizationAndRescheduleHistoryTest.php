<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\Appointment;
use App\Models\Pet;
use App\Models\SmsLog;
use App\Models\User;
use App\Services\SMSService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class TimeStandardizationAndRescheduleHistoryTest extends TestCase
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
                'message_id' => 'iprog-msg-999',
            ], 200),
            'https://exp.host/--/api/v2/push/send' => Http::response([
                'data' => ['status' => 'ok'],
            ], 200),
        ]);
    }

    private function createAdmin(?string $branch = null, string $email = 'admin@vetbook.test'): User
    {
        return User::create([
            'name' => 'Clinic Admin',
            'email' => $email,
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'branch' => $branch,
        ]);
    }

    private function createOwnerAndPet(string $phone = '09171234567', ?string $pushToken = null): array
    {
        $owner = User::create([
            'name' => 'Maria Clara',
            'email' => 'maria@test.com',
            'phone_number' => $phone,
            'push_token' => $pushToken,
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

    public function test_normalize_phone_handles_ten_digit_without_leading_zero(): void
    {
        $this->assertSame('639171234567', SMSService::normalizePhone('9171234567'));
        $this->assertSame('639171234567', SMSService::normalizePhone('09171234567'));
        $this->assertSame('639171234567', SMSService::normalizePhone('+639171234567'));
        $this->assertSame('639171234567', SMSService::normalizePhone('639171234567'));
    }

    public function test_send_appointment_reminder_formats_date_and_twelve_hour_time(): void
    {
        [$owner, $pet] = $this->createOwnerAndPet('9171234567');

        $appointment = Appointment::create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
            'branch' => 'Mabini Branch',
            'appointment_date' => '2026-11-20',
            'appointment_time' => '14:30',
            'status' => 'approved',
            'reason' => 'Vaccination',
        ]);

        $result = SMSService::sendAppointmentReminder($appointment->load(['user', 'pet']));

        $this->assertTrue($result['success']);

        $log = SmsLog::where('appointment_id', $appointment->id)
            ->where('type', 'reminder')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('639171234567', $log->recipient_phone);
        $this->assertStringContainsString('on Nov 20, 2026 at 2:30 PM', $log->message);
        $this->assertStringNotContainsString('14:30', $log->message);
    }

    public function test_admin_reschedule_enforces_hi_validation_guards_completed_and_cancelled_and_blocks_lunch(): void
    {
        $admin = $this->createAdmin();
        [$owner, $pet] = $this->createOwnerAndPet();

        $futureDate1 = now()->addDays(2)->toDateString();
        $futureDate2 = now()->addDays(4)->toDateString();

        $approvedAppt = Appointment::create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
            'branch' => 'Mabini Branch',
            'appointment_date' => $futureDate1,
            'appointment_time' => '09:00',
            'status' => 'approved',
            'reason' => 'Checkup',
        ]);

        // 1. Reject 12-hour string instead of H:i
        $invalidFormat = $this->actingAs($admin)
            ->from('/admin/appointments')
            ->post("/admin/appointments/{$approvedAppt->id}/reschedule", [
                'appointment_date' => $futureDate2,
                'appointment_time' => '02:30 PM',
            ]);

        $invalidFormat->assertSessionHasErrors(['appointment_time']);
        $this->assertSame('09:00', $approvedAppt->fresh()->appointment_time);

        // 2. Reject lunch break hour (12:xx)
        $lunchResponse = $this->actingAs($admin)
            ->from('/admin/appointments')
            ->post("/admin/appointments/{$approvedAppt->id}/reschedule", [
                'appointment_date' => $futureDate2,
                'appointment_time' => '12:30',
            ]);

        $lunchResponse->assertRedirect('/admin/appointments');
        $lunchResponse->assertSessionHas('error');
        $this->assertSame('09:00', $approvedAppt->fresh()->appointment_time);

        // 3. Reject completed appointment
        $completedAppt = Appointment::create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
            'branch' => 'Mabini Branch',
            'appointment_date' => $futureDate1,
            'appointment_time' => '10:00',
            'status' => 'completed',
            'reason' => 'Deworming',
        ]);

        $completedResponse = $this->actingAs($admin)
            ->from('/admin/appointments')
            ->post("/admin/appointments/{$completedAppt->id}/reschedule", [
                'appointment_date' => $futureDate2,
                'appointment_time' => '15:00',
            ]);

        $completedResponse->assertRedirect('/admin/appointments');
        $completedResponse->assertSessionHas('error');
        $this->assertSame('10:00', $completedAppt->fresh()->appointment_time);

        // 4. Reject cancelled appointment
        $cancelledAppt = Appointment::create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
            'branch' => 'Mabini Branch',
            'appointment_date' => $futureDate1,
            'appointment_time' => '11:00',
            'status' => 'cancelled',
            'reason' => 'Checkup',
        ]);

        $cancelledResponse = $this->actingAs($admin)
            ->from('/admin/appointments')
            ->post("/admin/appointments/{$cancelledAppt->id}/reschedule", [
                'appointment_date' => $futureDate2,
                'appointment_time' => '16:00',
            ]);

        $cancelledResponse->assertRedirect('/admin/appointments');
        $cancelledResponse->assertSessionHas('error');
        $this->assertSame('11:00', $cancelledAppt->fresh()->appointment_time);
    }

    public function test_admin_reschedule_preserves_history_in_notes_and_formats_notifications_in_twelve_hour(): void
    {
        $admin = $this->createAdmin();
        [$owner, $pet] = $this->createOwnerAndPet('09171234567', 'ExponentPushToken[test-token-123]');

        $oldDate = now()->addDays(2)->toDateString();
        $newDate = now()->addDays(5)->toDateString();

        $appointment = Appointment::create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
            'branch' => 'Mabini Branch',
            'appointment_date' => $oldDate,
            'appointment_time' => '09:00',
            'status' => 'approved',
            'reason' => 'Checkup',
            'notes' => 'Pre-existing clinic note.',
        ]);

        $response = $this->actingAs($admin)
            ->from('/admin/appointments')
            ->post("/admin/appointments/{$appointment->id}/reschedule", [
                'appointment_date' => $newDate,
                'appointment_time' => '14:30',
            ]);

        $response->assertRedirect('/admin/appointments');
        $response->assertSessionHas('success');

        $oldDateFormatted = Carbon::parse($oldDate)->format('M d, Y');
        $newDateFormatted = Carbon::parse($newDate)->format('M d, Y');

        $fresh = $appointment->fresh();
        $this->assertSame('14:30', $fresh->appointment_time);
        $this->assertStringContainsString('Pre-existing clinic note.', $fresh->notes);
        $this->assertStringContainsString(
            "[Rescheduled by Clinic] Previous: {$oldDateFormatted} at 9:00 AM -> New: {$newDateFormatted} at 2:30 PM",
            $fresh->notes
        );

        $notification = AppNotification::where('user_id', $owner->id)
            ->where('type', 'appointment_rescheduled')
            ->first();

        $this->assertNotNull($notification);
        $this->assertStringContainsString(
            "BK Petaholic Clinic has updated Bantay's appointment to {$newDateFormatted} at 2:30 PM (previously {$oldDateFormatted} at 9:00 AM).",
            $notification->message
        );
        $this->assertStringNotContainsString('14:30', $notification->message);
        $this->assertSame($oldDateFormatted, $notification->data['old_date'] ?? null);
        $this->assertSame('9:00 AM', $notification->data['old_time'] ?? null);
        $this->assertSame($newDateFormatted, $notification->data['new_date'] ?? null);
        $this->assertSame('2:30 PM', $notification->data['new_time'] ?? null);

        Http::assertSent(function ($request) use ($owner, $oldDateFormatted, $newDateFormatted) {
            return $request->url() === 'https://exp.host/--/api/v2/push/send'
                && $request['to'] === $owner->push_token
                && str_contains($request['body'], "{$newDateFormatted} at 2:30 PM")
                && str_contains($request['body'], "previously {$oldDateFormatted} at 9:00 AM");
        });
    }

    public function test_api_store_uses_twelve_hour_time_in_sms_and_notifications(): void
    {
        $admin = $this->createAdmin('Mabini Branch');
        [$owner, $pet] = $this->createOwnerAndPet('9171234567');
        $token = JWTAuth::fromUser($owner);

        $bookingDate = now()->addDays(3)->toDateString();
        $formattedDate = Carbon::parse($bookingDate)->format('M d, Y');

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/appointments', [
                'pet_id' => $pet->id,
                'branch' => 'Mabini Branch',
                'appointment_date' => $bookingDate,
                'appointment_time' => '14:30',
                'reason' => 'Annual Checkup',
            ]);

        $response->assertStatus(201);
        $appointmentId = $response->json('appointment.id');

        $sms = SmsLog::where('appointment_id', $appointmentId)->first();
        $this->assertNotNull($sms);
        $this->assertSame('639171234567', $sms->recipient_phone);
        $this->assertStringContainsString("on {$formattedDate} at 2:30 PM", $sms->message);
        $this->assertStringNotContainsString('14:30', $sms->message);

        $ownerNotification = AppNotification::where('user_id', $owner->id)
            ->where('type', 'appointment_booked')
            ->first();
        $this->assertNotNull($ownerNotification);
        $this->assertStringContainsString("on {$formattedDate} at 2:30 PM", $ownerNotification->message);
        $this->assertStringNotContainsString('14:30', $ownerNotification->message);

        $adminNotification = AppNotification::where('user_id', $admin->id)
            ->where('type', 'appointment_booked')
            ->first();
        $this->assertNotNull($adminNotification);
        $this->assertStringContainsString("on {$formattedDate} at 2:30 PM", $adminNotification->message);
        $this->assertStringNotContainsString('14:30', $adminNotification->message);
    }
}
