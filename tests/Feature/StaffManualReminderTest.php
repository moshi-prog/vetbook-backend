<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\Appointment;
use App\Models\Pet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StaffManualReminderTest extends TestCase
{
    use RefreshDatabase;

    private function createStaff(?string $branch): User
    {
        return User::create([
            'name'         => $branch ? "{$branch} Staff" : 'System Admin',
            'email'        => uniqid('staff_', true) . '@vetbook.test',
            'password'     => bcrypt('password123'),
            'role'         => 'admin',
            'branch'       => $branch,
            'phone_number' => '09171234567',
        ]);
    }

    private function createOwnerWithPet(?string $pushToken = null): array
    {
        $owner = User::create([
            'name'              => 'Juan Dela Cruz',
            'email'             => uniqid('owner_', true) . '@vetbook.test',
            'password'          => bcrypt('password123'),
            'phone_number'      => '09179998888',
            'role'              => 'pet_owner',
            'email_verified_at' => now(),
            'approval_status'   => 'approved',
            'push_token'        => $pushToken,
        ]);

        $pet = Pet::create([
            'user_id' => $owner->id,
            'name'    => 'Bantay',
            'species' => 'Dog',
            'breed'   => 'Aspin',
        ]);

        return [$owner, $pet];
    }

    public function test_staff_sees_remind_pet_owner_button_on_regular_surgery_and_detail_pages(): void
    {
        $mabiniStaff = $this->createStaff('Mabini Branch');
        $batangasStaff = $this->createStaff('Batangas City Branch');
        [$owner, $pet] = $this->createOwnerWithPet();

        $mabiniRegular = Appointment::create([
            'user_id'          => $owner->id,
            'pet_id'           => $pet->id,
            'branch'           => 'Mabini Branch',
            'appointment_date' => Carbon::tomorrow()->toDateString(),
            'appointment_time' => '10:00',
            'status'           => Appointment::STATUS_APPROVED,
            'reason'           => 'Vaccination',
            'service_type'     => 'vaccination',
        ]);

        $mabiniSurgery = Appointment::create([
            'user_id'          => $owner->id,
            'pet_id'           => $pet->id,
            'branch'           => 'Mabini Branch',
            'appointment_date' => null,
            'appointment_time' => 'ON_CALL',
            'status'           => Appointment::STATUS_PENDING,
            'reason'           => 'Surgery',
            'service_type'     => 'surgery',
            'surgery_status'   => 'awaiting_slot',
        ]);

        $batangasRegular = Appointment::create([
            'user_id'          => $owner->id,
            'pet_id'           => $pet->id,
            'branch'           => 'Batangas City Branch',
            'appointment_date' => Carbon::tomorrow()->toDateString(),
            'appointment_time' => '14:00',
            'status'           => Appointment::STATUS_PENDING,
            'reason'           => 'Diagnostic / Checkup',
            'service_type'     => 'checkup',
        ]);

        $batangasSurgery = Appointment::create([
            'user_id'          => $owner->id,
            'pet_id'           => $pet->id,
            'branch'           => 'Batangas City Branch',
            'appointment_date' => null,
            'appointment_time' => 'ON_CALL',
            'status'           => Appointment::STATUS_PENDING,
            'reason'           => 'Surgery',
            'service_type'     => 'surgery',
            'surgery_status'   => 'awaiting_slot',
        ]);

        // Mabini Branch Staff sees Remind Pet Owner on regular tab, surgery tab, and detail page
        $this->actingAs($mabiniStaff)->get('/admin/appointments?tab=regular')
            ->assertStatus(200)
            ->assertSee('Remind Pet Owner')
            ->assertSee('id="remindPetOwnerModal"', false)
            ->assertDontSee('Assign Surgery Date');

        $this->actingAs($mabiniStaff)->get('/admin/appointments?tab=surgery')
            ->assertStatus(200)
            ->assertSee('Remind Pet Owner')
            ->assertSee('Assign Surgery Date');

        $this->actingAs($mabiniStaff)->get("/admin/appointments/{$mabiniRegular->id}")
            ->assertStatus(200)
            ->assertSee('Remind Pet Owner')
            ->assertSee('id="remindPetOwnerModal"', false);

        // Batangas City Branch Staff also sees Remind Pet Owner on both tabs and detail page
        $this->actingAs($batangasStaff)->get('/admin/appointments?tab=regular')
            ->assertStatus(200)
            ->assertSee('Remind Pet Owner');

        $this->actingAs($batangasStaff)->get('/admin/appointments?tab=surgery')
            ->assertStatus(200)
            ->assertSee('Remind Pet Owner')
            ->assertSee('Assign Surgery Date');

        $this->actingAs($batangasStaff)->get("/admin/appointments/{$batangasSurgery->id}")
            ->assertStatus(200)
            ->assertSee('Remind Pet Owner');
    }

    public function test_staff_can_send_manual_reminder_for_regular_and_surgery_without_touching_auto_reminders(): void
    {
        Http::fake([
            'https://exp.host/*' => Http::response(['data' => ['status' => 'ok']], 200),
        ]);

        $mabiniStaff = $this->createStaff('Mabini Branch');
        [$owner, $pet] = $this->createOwnerWithPet('ExponentPushToken[manualTestToken123]');

        $initial24h = now()->subHours(4)->startOfMinute();
        $regularAppt = Appointment::create([
            'user_id'              => $owner->id,
            'pet_id'               => $pet->id,
            'branch'               => 'Mabini Branch',
            'appointment_date'     => Carbon::tomorrow()->toDateString(),
            'appointment_time'     => '09:30',
            'status'               => Appointment::STATUS_APPROVED,
            'reason'               => 'Diagnostic / Checkup',
            'service_type'         => 'checkup',
            'reminder_24h_sent_at' => $initial24h,
        ]);

        $surgeryAppt = Appointment::create([
            'user_id'          => $owner->id,
            'pet_id'           => $pet->id,
            'branch'           => 'Mabini Branch',
            'appointment_date' => null,
            'appointment_time' => 'ON_CALL',
            'status'           => Appointment::STATUS_PENDING,
            'reason'           => 'Surgery',
            'service_type'     => 'surgery',
            'surgery_status'   => 'awaiting_slot',
        ]);

        // 1. Regular appointment manual reminder
        $regularMsg = 'Please arrive 15 minutes early and bring Bantay\'s vaccination booklet.';
        $res1 = $this->actingAs($mabiniStaff)
            ->from('/admin/appointments?tab=regular')
            ->post("/admin/appointments/{$regularAppt->id}/remind", [
                'message' => $regularMsg,
            ]);

        $res1->assertRedirect('/admin/appointments?tab=regular')
            ->assertSessionHas('success', 'Reminder notification sent to Juan Dela Cruz for Bantay.');

        $notif1 = AppNotification::where('user_id', $owner->id)
            ->where('type', 'staff_manual_reminder')
            ->firstOrFail();

        $this->assertSame($regularMsg, $notif1->message);
        $this->assertSame("Reminder from Mabini Branch (Bantay)", $notif1->title);
        $this->assertSame($regularAppt->id, $notif1->data['appointment_id'] ?? null);
        $this->assertTrue((bool) ($notif1->data['manual_reminder'] ?? false));

        // Automatic reminder timestamps, confirmation, and status remain untouched
        $regularAppt->refresh();
        $this->assertTrue($regularAppt->reminder_24h_sent_at->equalTo($initial24h));
        $this->assertNull($regularAppt->reminder_3h_sent_at);
        $this->assertNull($regularAppt->attendance_confirmed_at);
        $this->assertSame(Appointment::STATUS_APPROVED, $regularAppt->status);

        // Push notification was dispatched
        Http::assertSent(fn ($req) => str_contains($req->url(), 'exp.host')
            && ($req['to'] ?? null) === 'ExponentPushToken[manualTestToken123]'
            && ($req['body'] ?? null) === $regularMsg
            && ($req['data']['type'] ?? null) === 'staff_manual_reminder');

        // 2. Surgery appointment manual reminder
        $surgeryMsg = 'Please keep Bantay fasting for 8 hours before tomorrow morning pre-op check.';
        $res2 = $this->actingAs($mabiniStaff)
            ->from('/admin/appointments?tab=surgery')
            ->post("/admin/appointments/{$surgeryAppt->id}/remind", [
                'message' => $surgeryMsg,
            ]);

        $res2->assertRedirect('/admin/appointments?tab=surgery')
            ->assertSessionHas('success');

        $notif2 = AppNotification::where('user_id', $owner->id)
            ->where('type', 'staff_manual_reminder')
            ->where('id', '!=', $notif1->id)
            ->firstOrFail();

        $this->assertSame($surgeryMsg, $notif2->message);
        $this->assertSame($surgeryAppt->id, $notif2->data['appointment_id'] ?? null);
        $this->assertTrue((bool) ($notif2->data['manual_reminder'] ?? false));

        $surgeryAppt->refresh();
        $this->assertNull($surgeryAppt->reminder_24h_sent_at);
        $this->assertNull($surgeryAppt->reminder_3h_sent_at);
        $this->assertNull($surgeryAppt->attendance_confirmed_at);
        $this->assertSame(Appointment::STATUS_PENDING, $surgeryAppt->status);
    }

    public function test_manual_reminder_validation_branch_ownership_and_admin_restriction(): void
    {
        $systemAdmin = $this->createStaff(null);
        $mabiniStaff = $this->createStaff('Mabini Branch');
        $batangasStaff = $this->createStaff('Batangas City Branch');
        [$owner, $pet] = $this->createOwnerWithPet();

        $mabiniAppt = Appointment::create([
            'user_id'          => $owner->id,
            'pet_id'           => $pet->id,
            'branch'           => 'Mabini Branch',
            'appointment_date' => Carbon::tomorrow()->toDateString(),
            'appointment_time' => '11:00',
            'status'           => Appointment::STATUS_APPROVED,
            'reason'           => 'Vaccination',
            'service_type'     => 'vaccination',
        ]);

        // Empty message rejected by validation
        $this->actingAs($mabiniStaff)
            ->from("/admin/appointments/{$mabiniAppt->id}")
            ->post("/admin/appointments/{$mabiniAppt->id}/remind", [
                'message' => '',
            ])
            ->assertRedirect("/admin/appointments/{$mabiniAppt->id}")
            ->assertSessionHasErrors('message');

        // Cross-branch staff forbidden (403)
        $this->actingAs($batangasStaff)
            ->post("/admin/appointments/{$mabiniAppt->id}/remind", [
                'message' => 'Cross-branch reminder attempt',
            ])
            ->assertStatus(403);

        // Top-level Admin (branch === null) forbidden (403)
        $this->actingAs($systemAdmin)
            ->post("/admin/appointments/{$mabiniAppt->id}/remind", [
                'message' => 'Admin attempt',
            ])
            ->assertStatus(403);

        $this->assertSame(0, AppNotification::where('type', 'staff_manual_reminder')->count());
    }
}
