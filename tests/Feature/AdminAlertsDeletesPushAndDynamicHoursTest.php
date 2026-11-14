<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\Appointment;
use App\Models\ClinicSetting;
use App\Models\MedicalRecord;
use App\Models\Pet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AdminAlertsDeletesPushAndDynamicHoursTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::create([
            'name' => 'Super Admin',
            'email' => 'admin@vetbook.test',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'branch' => null,
        ]);
    }

    private function createPetOwnerWithPet(): array
    {
        $owner = User::create([
            'name' => 'Maria Makiling',
            'email' => 'maria@test.com',
            'phone_number' => '09171112233',
            'password' => bcrypt('password123'),
            'role' => 'pet_owner',
            'push_token' => 'ExponentPushToken[mock-device-token-12345]',
        ]);

        $pet = Pet::create([
            'user_id' => $owner->id,
            'name' => 'Bantay',
            'species' => 'dog',
            'breed' => 'Aspin',
        ]);

        return [$owner, $pet];
    }

    public function test_admin_can_fetch_unread_alerts_and_mark_as_read(): void
    {
        $admin = $this->createAdmin();

        $notification = AppNotification::create([
            'user_id' => $admin->id,
            'title' => 'Appointment Cancelled by Owner',
            'message' => 'Maria cancelled their appointment for Bantay.',
            'type' => 'appointment_cancelled_by_owner',
            'data' => ['appointment_id' => 99],
        ]);

        $response = $this->actingAs($admin)->getJson('/admin/notifications/unread-alerts');
        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
        $response->assertJsonCount(1, 'alerts');
        $response->assertJsonPath('alerts.0.id', $notification->id);
        $response->assertJsonPath('alerts.0.appointment_id', 99);

        // Mark as read
        $markResponse = $this->actingAs($admin)->postJson("/admin/notifications/{$notification->id}/mark-read");
        $markResponse->assertStatus(200);
        $this->assertNotNull($notification->fresh()->read_at);

        // Fetch again - should be empty since read
        $emptyResponse = $this->actingAs($admin)->getJson('/admin/notifications/unread-alerts');
        $emptyResponse->assertJsonCount(0, 'alerts');
    }

    public function test_admin_can_delete_appointment(): void
    {
        $admin = $this->createAdmin();
        [$owner, $pet] = $this->createPetOwnerWithPet();

        $appointment = Appointment::create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
            'branch' => 'Mabini Branch',
            'appointment_date' => now()->addDays(2)->toDateString(),
            'appointment_time' => '10:00 AM',
            'status' => 'pending',
            'reason' => 'Checkup',
        ]);

        $response = $this->actingAs($admin)->delete("/admin/appointments/{$appointment->id}");
        $response->assertRedirect('/admin/appointments');
        $response->assertSessionHas('success', 'Appointment deleted successfully.');

        $this->assertDatabaseMissing('appointments', ['id' => $appointment->id]);
    }

    public function test_admin_can_delete_pet_owner_and_cascades(): void
    {
        $admin = $this->createAdmin();
        [$owner, $pet] = $this->createPetOwnerWithPet();

        $appointment = Appointment::create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
            'branch' => 'Mabini Branch',
            'appointment_date' => now()->addDays(2)->toDateString(),
            'appointment_time' => '10:00 AM',
            'status' => 'pending',
            'reason' => 'Checkup',
        ]);

        $medicalRecord = MedicalRecord::create([
            'pet_id' => $pet->id,
            'consultation_date' => now()->toDateString(),
            'diagnosis' => 'Healthy pet',
        ]);

        $notification = AppNotification::create([
            'user_id' => $owner->id,
            'title' => 'Welcome',
            'message' => 'Welcome to VetBook',
            'type' => 'welcome',
        ]);

        $response = $this->actingAs($admin)->delete("/admin/pet-owners/{$owner->id}");
        $response->assertRedirect('/admin/pet-owners');

        $this->assertDatabaseMissing('users', ['id' => $owner->id]);
        $this->assertDatabaseMissing('pets', ['id' => $pet->id]);
        $this->assertDatabaseMissing('appointments', ['id' => $appointment->id]);
        $this->assertDatabaseMissing('medical_records', ['id' => $medicalRecord->id]);
        $this->assertDatabaseMissing('app_notifications', ['id' => $notification->id]);
    }

    public function test_user_can_save_push_token_via_api(): void
    {
        [$owner] = $this->createPetOwnerWithPet();
        $token = JWTAuth::fromUser($owner);

        $newToken = 'ExponentPushToken[updated-phone-token-789]';
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/user/push-token', [
                'push_token' => $newToken,
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
        $this->assertEquals($newToken, $owner->fresh()->push_token);
    }

    public function test_admin_actions_dispatch_native_push_notifications(): void
    {
        Http::fake([
            'https://exp.host/--/api/v2/push/send' => Http::response(['data' => ['status' => 'ok']], 200),
        ]);

        $admin = $this->createAdmin();
        [$owner, $pet] = $this->createPetOwnerWithPet();

        $appointment = Appointment::create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
            'branch' => 'Mabini Branch',
            'appointment_date' => now()->addDays(2)->toDateString(),
            'appointment_time' => '10:00 AM',
            'status' => 'pending',
            'reason' => 'Checkup',
        ]);

        // 1. Approve appointment
        $this->actingAs($admin)->post("/admin/appointments/{$appointment->id}/approve");

        Http::assertSent(function ($request) use ($owner) {
            return $request->url() === 'https://exp.host/--/api/v2/push/send' &&
                $request['to'] === $owner->push_token &&
                str_contains($request['title'], 'Appointment Approved');
        });

        // 2. Reschedule appointment
        $this->actingAs($admin)->post("/admin/appointments/{$appointment->id}/reschedule", [
            'appointment_date' => now()->addDays(4)->toDateString(),
            'appointment_time' => '14:00',
        ]);

        Http::assertSent(function ($request) use ($owner) {
            return $request->url() === 'https://exp.host/--/api/v2/push/send' &&
                $request['to'] === $owner->push_token &&
                str_contains($request['title'], 'Appointment Rescheduled');
        });

        // 3. Cancel appointment
        $this->actingAs($admin)->post("/admin/appointments/{$appointment->id}/cancel", [
            'cancel_reason' => 'Emergency surgery in clinic',
        ]);

        Http::assertSent(function ($request) use ($owner) {
            return $request->url() === 'https://exp.host/--/api/v2/push/send' &&
                $request['to'] === $owner->push_token &&
                str_contains($request['title'], 'Appointment Cancelled');
        });
    }

    public function test_branches_endpoint_returns_structured_hours(): void
    {
        ClinicSetting::set('clinic_hours_weekday', '9:00 AM – 3:00 PM');
        ClinicSetting::set('clinic_hours_sunday', 'Sun: Closed');

        $response = $this->getJson('/api/branches');
        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);

        $mabini = collect($data)->firstWhere('id', 'mabini');
        $this->assertNotNull($mabini);
        $this->assertEquals('09:00', $mabini['weekday_open']);
        $this->assertEquals('15:00', $mabini['weekday_close']);
        $this->assertTrue($mabini['sunday_closed']);
        $this->assertNull($mabini['sunday_open']);
        $this->assertNull($mabini['sunday_close']);
    }

    public function test_local_device_token_fallback_succeeds_without_calling_expo_api(): void
    {
        Http::fake();

        [$owner] = $this->createPetOwnerWithPet();
        $token = JWTAuth::fromUser($owner);
        $fallbackToken = 'LocalDeviceToken[android-standalone-apk-test]';

        $saveResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/user/push-token', [
                'push_token' => $fallbackToken,
            ]);
        $saveResponse->assertStatus(200);
        $this->assertSame($fallbackToken, $owner->fresh()->push_token);

        $testPushResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/user/test-push');

        $testPushResponse->assertStatus(200);
        $testPushResponse->assertJsonPath('status', 'success');
        Http::assertNothingSent();
        $this->assertSame($fallbackToken, $owner->fresh()->push_token);
    }
}

