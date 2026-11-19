<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\Appointment;
use App\Models\EmailVerificationCode;
use App\Models\Pet;
use App\Models\SmsLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class CapstoneDefenseRevisionsTest extends TestCase
{
    use RefreshDatabase;

    private function createStaff(string $branch = 'Mabini Branch'): User
    {
        return User::create([
            'name' => "{$branch} Staff",
            'email' => uniqid('staff_', true) . '@vetbook.test',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'branch' => $branch,
        ]);
    }

    private function createApprovedOwnerWithPets(int $petCount = 1): array
    {
        $owner = User::create([
            'name' => 'Maria Clara',
            'email' => uniqid('owner_', true) . '@vetbook.test',
            'password' => bcrypt('password123'),
            'phone_number' => '09171234567',
            'role' => 'pet_owner',
            'email_verified_at' => now(),
            'approval_status' => 'approved',
        ]);

        $pets = [];
        for ($i = 1; $i <= $petCount; $i++) {
            $pets[] = Pet::create([
                'user_id' => $owner->id,
                'name' => "Pet {$i}",
                'species' => 'Dog',
                'breed' => 'Aspin',
            ]);
        }

        return [$owner, $pets];
    }

    public function test_staff_can_approve_and_reject_pending_pet_owner_registrations_and_unapproved_user_cannot_login(): void
    {
        Mail::fake();

        $mabiniStaff = $this->createStaff('Mabini Branch');
        $batangasStaff = $this->createStaff('Batangas City Branch');

        // 1. Register pet owner (defaults to pending staff approval)
        $reg = $this->postJson('/api/register', [
            'name' => 'Juan Pending',
            'email' => 'juan.pending@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'phone_number' => '09171234567',
        ]);

        $reg->assertStatus(201);
        $owner = User::where('email', 'juan.pending@example.com')->firstOrFail();
        $this->assertSame('pending', $owner->approval_status);

        // Staff received notification
        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $mabiniStaff->id,
            'type' => 'account_approval_needed',
        ]);
        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $batangasStaff->id,
            'type' => 'account_approval_needed',
        ]);

        // Verify email succeeds while signaling pending_staff_approval
        $code = EmailVerificationCode::where('email', 'juan.pending@example.com')->value('code');
        $verify = $this->postJson('/api/verify-email', [
            'email' => 'juan.pending@example.com',
            'code' => $code,
        ]);
        $verify->assertStatus(200)
            ->assertJson([
                'approval_status' => 'pending',
                'pending_staff_approval' => true,
            ]);

        // Unapproved login with enforce_staff_approval is blocked with 403
        $blockedLogin = $this->postJson('/api/login', [
            'email' => 'juan.pending@example.com',
            'password' => 'secret123',
            'enforce_staff_approval' => true,
        ]);
        $blockedLogin->assertStatus(403)
            ->assertJson(['pending_staff_approval' => true]);

        // Staff filters Pending Approval and sees Juan Pending
        $pendingPage = $this->actingAs($mabiniStaff)->get('/admin/pet-owners?filter=pending');
        $pendingPage->assertStatus(200)
            ->assertSee('Juan Pending')
            ->assertSee('Pending Staff Approval')
            ->assertSee('Approve Account');

        // Staff approves account
        $this->actingAs($mabiniStaff)->post("/admin/pet-owners/{$owner->id}/approve")
            ->assertRedirect();

        $owner->refresh();
        $this->assertSame('approved', $owner->approval_status);
        $this->assertNotNull($owner->approved_at);
        $this->assertSame($mabiniStaff->id, $owner->approved_by);
        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $owner->id,
            'type' => 'account_approved',
        ]);

        // Approved user can now sign in cleanly
        $allowedLogin = $this->postJson('/api/login', [
            'email' => 'juan.pending@example.com',
            'password' => 'secret123',
            'enforce_staff_approval' => true,
        ]);
        $allowedLogin->assertStatus(200)
            ->assertJsonStructure(['token', 'user']);

        // 2. Register second owner and reject
        $this->postJson('/api/register', [
            'name' => 'Troll Account',
            'email' => 'troll@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'phone_number' => '09179998888',
        ])->assertStatus(201);

        $troll = User::where('email', 'troll@example.com')->firstOrFail();
        $troll->email_verified_at = now();
        $troll->save();

        $this->actingAs($mabiniStaff)->post("/admin/pet-owners/{$troll->id}/reject")
            ->assertRedirect();

        $this->assertSame('rejected', $troll->fresh()->approval_status);

        $this->postJson('/api/login', [
            'email' => 'troll@example.com',
            'password' => 'secret123',
            'enforce_staff_approval' => true,
        ])->assertStatus(403)
            ->assertJson(['account_rejected' => true]);
    }

    public function test_grooming_takes_two_hours_enforces_3pm_cutoff_and_blocks_overlapping_slots_in_mabini_branch(): void
    {
        [$owner1, $pets1] = $this->createApprovedOwnerWithPets(1);
        [$owner2, $pets2] = $this->createApprovedOwnerWithPets(1);
        $token1 = JWTAuth::fromUser($owner1);
        $token2 = JWTAuth::fromUser($owner2);
        $tomorrow = Carbon::tomorrow()->toDateString();

        // Book 09:00 Grooming at Mabini Branch (1 groomer capacity, 2h duration)
        $res = $this->withHeader('Authorization', "Bearer {$token1}")
            ->postJson('/api/appointments', [
                'pet_id' => $pets1[0]->id,
                'branch' => 'Mabini Branch',
                'appointment_date' => $tomorrow,
                'appointment_time' => '09:00',
                'service_type' => 'Grooming',
                'reason' => 'Grooming',
            ]);

        $res->assertStatus(201);
        $this->assertSame('grooming', $res->json('appointment.service_type'));
        $this->assertSame(120, $res->json('appointment.duration_minutes'));

        // Booked slots for Grooming show overlap + lunch overlap + 3pm cutoff
        $slots = $this->getJson("/api/appointments/booked-slots?branch=Mabini+Branch&date={$tomorrow}&service=Grooming");
        $slots->assertStatus(200);
        $booked = $slots->json('booked_slots');
        $cutoff = $slots->json('cutoff_slots');

        foreach (['07:30', '08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '15:30', '16:00'] as $expectedBlocked) {
            $this->assertContains($expectedBlocked, $booked, "Expected {$expectedBlocked} to be blocked for Grooming");
        }
        $this->assertNotContains('13:00', $booked);
        $this->assertNotContains('15:00', $booked);
        $this->assertContains('15:30', $cutoff);

        // Overlapping Grooming at 10:00 or 08:00 fails with 409
        $this->withHeader('Authorization', "Bearer {$token2}")
            ->postJson('/api/appointments', [
                'pet_id' => $pets2[0]->id,
                'branch' => 'Mabini Branch',
                'appointment_date' => $tomorrow,
                'appointment_time' => '10:00',
                'service_type' => 'Grooming',
                'reason' => 'Grooming',
            ])->assertStatus(409);

        // Grooming after 3:00 PM cutoff fails with 422
        $this->withHeader('Authorization', "Bearer {$token2}")
            ->postJson('/api/appointments', [
                'pet_id' => $pets2[0]->id,
                'branch' => 'Mabini Branch',
                'appointment_date' => $tomorrow,
                'appointment_time' => '15:30',
                'service_type' => 'Grooming',
                'reason' => 'Grooming',
            ])->assertStatus(422);

        // Vet service at 09:00 on same date at Mabini Branch STILL succeeds (separate pool)
        $this->withHeader('Authorization', "Bearer {$token2}")
            ->postJson('/api/appointments', [
                'pet_id' => $pets2[0]->id,
                'branch' => 'Mabini Branch',
                'appointment_date' => $tomorrow,
                'appointment_time' => '09:00',
                'service_type' => 'Vaccination',
                'reason' => 'Vaccination',
            ])->assertStatus(201);
    }

    public function test_batangas_city_branch_allows_three_concurrent_grooming_bookings_and_two_concurrent_vet_bookings(): void
    {
        $tomorrow = Carbon::tomorrow()->toDateString();

        // 3 concurrent Grooming bookings at 09:00 succeed
        for ($i = 1; $i <= 3; $i++) {
            [$owner, $pets] = $this->createApprovedOwnerWithPets(1);
            $token = JWTAuth::fromUser($owner);
            $this->withHeader('Authorization', "Bearer {$token}")
                ->postJson('/api/appointments', [
                    'pet_id' => $pets[0]->id,
                    'branch' => 'Batangas City Branch',
                    'appointment_date' => $tomorrow,
                    'appointment_time' => '09:00',
                    'service_type' => 'Grooming',
                    'reason' => 'Grooming',
                ])->assertStatus(201);
        }

        // 4th concurrent Grooming booking at 09:00 fails
        [$owner4, $pets4] = $this->createApprovedOwnerWithPets(1);
        $token4 = JWTAuth::fromUser($owner4);
        $this->withHeader('Authorization', "Bearer {$token4}")
            ->postJson('/api/appointments', [
                'pet_id' => $pets4[0]->id,
                'branch' => 'Batangas City Branch',
                'appointment_date' => $tomorrow,
                'appointment_time' => '09:00',
                'service_type' => 'Grooming',
                'reason' => 'Grooming',
            ])->assertStatus(409);

        // 2 concurrent Vet bookings at 14:00 succeed
        for ($i = 1; $i <= 2; $i++) {
            [$vOwner, $vPets] = $this->createApprovedOwnerWithPets(1);
            $vToken = JWTAuth::fromUser($vOwner);
            $this->withHeader('Authorization', "Bearer {$vToken}")
                ->postJson('/api/appointments', [
                    'pet_id' => $vPets[0]->id,
                    'branch' => 'Batangas City Branch',
                    'appointment_date' => $tomorrow,
                    'appointment_time' => '14:00',
                    'service_type' => $i === 1 ? 'Diagnostic / Checkup' : 'Vaccination',
                    'reason' => $i === 1 ? 'Diagnostic / Checkup' : 'Vaccination',
                ])->assertStatus(201);
        }

        // 3rd concurrent Vet booking at 14:00 fails
        $this->withHeader('Authorization', "Bearer {$token4}")
            ->postJson('/api/appointments', [
                'pet_id' => $pets4[0]->id,
                'branch' => 'Batangas City Branch',
                'appointment_date' => $tomorrow,
                'appointment_time' => '14:00',
                'service_type' => 'Vaccination',
                'reason' => 'Vaccination',
            ])->assertStatus(409);
    }

    public function test_same_day_booking_is_rejected_when_service_booking_rules_apply(): void
    {
        [$owner, $pets] = $this->createApprovedOwnerWithPets(1);
        $token = JWTAuth::fromUser($owner);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/appointments', [
                'pet_id' => $pets[0]->id,
                'branch' => 'Mabini Branch',
                'appointment_date' => now()->toDateString(),
                'appointment_time' => '15:00',
                'service_type' => 'Diagnostic / Checkup',
                'disallow_same_day' => true,
                'reason' => 'Diagnostic / Checkup',
            ])
            ->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'Same-day appointments are not allowed. Please book at least 1 day in advance (tomorrow onwards).',
            ]);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/appointments', [
                'pet_id' => $pets[0]->id,
                'branch' => 'Mabini Branch',
                'appointment_date' => Carbon::tomorrow()->toDateString(),
                'appointment_time' => '10:00',
                'service_type' => 'Diagnostic / Checkup',
                'disallow_same_day' => true,
                'reason' => 'Diagnostic / Checkup',
            ])
            ->assertStatus(201);
    }

    public function test_on_call_surgery_workflow_staff_proposes_slot_and_pet_owner_can_proceed_or_request_another_time(): void
    {
        $mabiniStaff = $this->createStaff('Mabini Branch');
        $batangasStaff = $this->createStaff('Batangas City Branch');
        [$owner, $pets] = $this->createApprovedOwnerWithPets(3);
        $token = JWTAuth::fromUser($owner);
        $tomorrow = Carbon::tomorrow()->toDateString();

        // Owner submits on-call surgery with NO date and NO time
        $bookRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/appointments', [
                'pet_id' => $pets[0]->id,
                'branch' => 'Mabini Branch',
                'service_type' => 'Surgery',
                'reason' => 'Surgery',
                'notes' => 'Spay procedure',
            ]);

        $bookRes->assertStatus(201);
        $apptId = $bookRes->json('appointment.id');
        $this->assertNull($bookRes->json('appointment.appointment_date'));
        $this->assertSame('ON_CALL', $bookRes->json('appointment.appointment_time'));
        $this->assertSame('awaiting_slot', $bookRes->json('appointment.surgery_status'));
        $this->assertSame('Surgery', $bookRes->json('appointment.reason'));

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $mabiniStaff->id,
            'type' => 'surgery_requested',
        ]);

        // Also seed a regular Vaccination appointment to verify tab separation on Staff portal
        Appointment::create([
            'user_id' => $owner->id,
            'pet_id' => $pets[0]->id,
            'branch' => 'Mabini Branch',
            'appointment_date' => $tomorrow,
            'appointment_time' => '10:00',
            'status' => Appointment::STATUS_PENDING,
            'service_type' => 'vaccination',
            'reason' => 'Annual Booster Shot',
        ]);

        $this->actingAs($mabiniStaff)->get('/admin/appointments?tab=regular')
            ->assertStatus(200)
            ->assertSee('Annual Booster Shot')
            ->assertDontSee('Assign Surgery Date');

        $this->actingAs($mabiniStaff)->get('/admin/appointments?tab=surgery')
            ->assertStatus(200)
            ->assertSee('Awaiting Staff Date')
            ->assertSee('Assign Surgery Date')
            ->assertDontSee('Annual Booster Shot');

        // Staff views appointment and sees coordination card (date-only)
        $this->actingAs($mabiniStaff)->get("/admin/appointments/{$apptId}")
            ->assertStatus(200)
            ->assertSee('Surgery On-Call Coordination')
            ->assertSee('Awaiting Staff Date')
            ->assertDontSee('name="proposed_time"', false);

        // Staff assigns ONLY proposed_date (no proposed_time)
        $proposedDate = Carbon::tomorrow()->addDays(2)->toDateString();
        $this->actingAs($mabiniStaff)->post("/admin/appointments/{$apptId}/propose-surgery-slot", [
            'proposed_date' => $proposedDate,
        ])->assertRedirect();

        $appt = Appointment::findOrFail($apptId);
        $this->assertSame('slot_proposed', $appt->surgery_status);
        $this->assertNull($appt->proposed_time);
        $this->assertSame($proposedDate, Carbon::parse($appt->appointment_date)->toDateString());
        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $owner->id,
            'type' => 'surgery_slot_proposed',
        ]);

        // Owner asks for another date -> clears appointment_date/proposed_date so slot is freed
        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/appointments/{$apptId}/surgery-response", [
                'action' => 'request_another',
                'preferred_notes' => 'Not available that week',
            ])->assertStatus(200);

        $appt->refresh();
        $this->assertSame('reschedule_requested', $appt->surgery_status);
        $this->assertNull($appt->appointment_date);
        $this->assertNull($appt->proposed_date);
        $this->assertStringContainsString('[Owner Requested Another Surgery Date]', (string) $appt->notes);
        $this->assertStringContainsString('Not available that week', (string) $appt->notes);

        // Staff assigns a new date and owner proceeds/confirms
        $this->actingAs($mabiniStaff)->post("/admin/appointments/{$apptId}/propose-surgery-slot", [
            'proposed_date' => $proposedDate,
        ])->assertRedirect();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/appointments/{$apptId}/surgery-response", [
                'action' => 'accept',
            ])->assertStatus(200);

        $appt->refresh();
        $this->assertSame('confirmed', $appt->surgery_status);
        $this->assertSame('approved', $appt->status);
        $this->assertNotNull($appt->attendance_confirmed_at);

        // Mabini Branch daily surgery cap = 1 per day: assigning 2nd surgery on same date is blocked
        $mabiniAppt2Res = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/appointments', [
                'pet_id' => $pets[1]->id,
                'branch' => 'Mabini Branch',
                'service_type' => 'Surgery',
                'reason' => 'Surgery',
            ])->assertStatus(201);
        $mabiniAppt2Id = $mabiniAppt2Res->json('appointment.id');

        $mabiniCapRes = $this->actingAs($mabiniStaff)->post("/admin/appointments/{$mabiniAppt2Id}/propose-surgery-slot", [
            'proposed_date' => $proposedDate,
        ]);
        $mabiniCapRes->assertRedirect();
        $mabiniCapRes->assertSessionHas('error', fn ($msg) => str_contains((string) $msg, 'daily surgery limit (1 surgery per day)'));
        $this->assertNull(Appointment::findOrFail($mabiniAppt2Id)->appointment_date);

        // Assigning another date (+1 day) in Mabini succeeds
        $this->actingAs($mabiniStaff)->post("/admin/appointments/{$mabiniAppt2Id}/propose-surgery-slot", [
            'proposed_date' => Carbon::parse($proposedDate)->addDay()->toDateString(),
        ])->assertSessionHas('success');
        $this->assertSame('slot_proposed', Appointment::findOrFail($mabiniAppt2Id)->surgery_status);

        // Batangas City Branch daily surgery cap = 2 per day: 2 allowed on same date, 3rd blocked
        $batIds = [];
        foreach ([0, 1, 2] as $idx) {
            $bRes = $this->withHeader('Authorization', "Bearer {$token}")
                ->postJson('/api/appointments', [
                    'pet_id' => $pets[$idx]->id,
                    'branch' => 'Batangas City Branch',
                    'service_type' => 'Surgery',
                    'reason' => 'Surgery',
                ])->assertStatus(201);
            $batIds[] = $bRes->json('appointment.id');
        }

        $this->actingAs($batangasStaff)->post("/admin/appointments/{$batIds[0]}/propose-surgery-slot", [
            'proposed_date' => $proposedDate,
        ])->assertSessionHas('success');
        $this->actingAs($batangasStaff)->post("/admin/appointments/{$batIds[1]}/propose-surgery-slot", [
            'proposed_date' => $proposedDate,
        ])->assertSessionHas('success');

        $batCapRes = $this->actingAs($batangasStaff)->post("/admin/appointments/{$batIds[2]}/propose-surgery-slot", [
            'proposed_date' => $proposedDate,
        ]);
        $batCapRes->assertRedirect();
        $batCapRes->assertSessionHas('error', fn ($msg) => str_contains((string) $msg, 'daily surgery limit (2 surgeries per day)'));
        $this->assertNull(Appointment::findOrFail($batIds[2])->appointment_date);
    }

    public function test_simultaneous_multi_pet_booking_creates_appointments_for_all_selected_pets(): void
    {
        [$owner, $pets] = $this->createApprovedOwnerWithPets(3);
        $token = JWTAuth::fromUser($owner);
        $tomorrow = Carbon::tomorrow()->toDateString();

        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/appointments', [
                'pet_ids' => [$pets[0]->id, $pets[1]->id, $pets[2]->id],
                'branch' => 'Mabini Branch',
                'appointment_date' => $tomorrow,
                'appointment_time' => '09:00',
                'service_type' => 'Vaccination',
                'reason' => 'Vaccination',
            ]);

        $res->assertStatus(201);
        $appointments = $res->json('appointments');
        $this->assertCount(3, $appointments);

        $times = array_column($appointments, 'appointment_time');
        $this->assertSame(['09:00', '09:30', '10:00'], $times);
        $batchIds = array_unique(array_column($appointments, 'batch_booking_id'));
        $this->assertCount(1, $batchIds);
        $this->assertNotEmpty($batchIds[0]);
    }

    public function test_1day_reminder_12h_waiting_time_auto_cancellation_and_staff_portal_sync(): void
    {
        $manilaNow = Carbon::now('Asia/Manila')->startOfDay()->setHour(8);
        $this->travelTo($manilaNow->copy()->utc());

        $mabiniStaff = $this->createStaff('Mabini Branch');
        [$owner, $pets] = $this->createApprovedOwnerWithPets(3);
        $token = JWTAuth::fromUser($owner);

        $tomorrowDate = $manilaNow->copy()->addDay()->toDateString();
        $todayDate = $manilaNow->toDateString();

        // 1-day before (tomorrow) appointments: one will be confirmed, one will be ignored past 12h
        $confirmedTomorrowAppt = Appointment::create([
            'user_id' => $owner->id,
            'pet_id' => $pets[0]->id,
            'branch' => 'Mabini Branch',
            'appointment_date' => $tomorrowDate,
            'appointment_time' => '09:00',
            'status' => Appointment::STATUS_APPROVED,
            'reason' => 'Diagnostic / Checkup',
        ]);

        $ignoredTomorrowAppt = Appointment::create([
            'user_id' => $owner->id,
            'pet_id' => $pets[1]->id,
            'branch' => 'Mabini Branch',
            'appointment_date' => $tomorrowDate,
            'appointment_time' => '10:00',
            'status' => Appointment::STATUS_APPROVED,
            'reason' => 'Vaccination',
        ]);

        // Same-day appointment 2 hours from now (10:00 AM) — should NOT trigger any 2h/3h reminder
        $sameDayAppt = Appointment::create([
            'user_id' => $owner->id,
            'pet_id' => $pets[2]->id,
            'branch' => 'Mabini Branch',
            'appointment_date' => $todayDate,
            'appointment_time' => '10:00',
            'status' => Appointment::STATUS_APPROVED,
            'reason' => 'Vaccination',
        ]);

        // Calling /api/notifications 1 day before dispatches ONLY 1-day reminders (no SMS, no 2h/3h reminders)
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/notifications')
            ->assertStatus(200);

        $this->assertSame(0, SmsLog::count());
        $this->assertNotNull($confirmedTomorrowAppt->fresh()->reminder_24h_sent_at);
        $this->assertNotNull($ignoredTomorrowAppt->fresh()->reminder_24h_sent_at);
        $this->assertNull($sameDayAppt->fresh()->reminder_3h_sent_at);

        $this->assertSame(2, AppNotification::where('user_id', $owner->id)->where('type', 'appointment_reminder_24h')->count());
        $this->assertSame(0, AppNotification::where('user_id', $owner->id)->whereIn('type', ['appointment_reminder_3h', 'appointment_reminder_2h'])->count());

        $reminderNotifs = AppNotification::where('user_id', $owner->id)
            ->where('type', 'appointment_reminder_24h')
            ->get();

        foreach ($reminderNotifs as $notif) {
            $this->assertStringContainsString('after a 12-hour waiting time', $notif->message);
            $this->assertTrue((bool) ($notif->data['requires_confirmation'] ?? false));
            $this->assertSame(12, $notif->data['waiting_time_hours'] ?? null);
        }

        // Calling /api/notifications again or running command does NOT create duplicate 1-day reminders
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/notifications')
            ->assertStatus(200);
        $this->artisan('appointments:send-reminders')->assertExitCode(0);
        $this->assertSame(2, AppNotification::where('user_id', $owner->id)->where('type', 'appointment_reminder_24h')->count());
        $this->assertSame(0, SmsLog::count());

        // Pet owner confirms attendance on confirmedTomorrowAppt BEFORE the 12-hour waiting time
        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/appointments/{$confirmedTomorrowAppt->id}/confirm-attendance")
            ->assertStatus(200);

        $this->assertNotNull($confirmedTomorrowAppt->fresh()->attendance_confirmed_at);
        $this->assertSame(Appointment::STATUS_APPROVED, $confirmedTomorrowAppt->fresh()->status);

        // Reminder notification for confirmedTomorrowAppt is immediately marked read
        $confirmedReminderNotif = $reminderNotifs->first(
            fn ($n) => (int) ($n->data['appointment_id'] ?? 0) === $confirmedTomorrowAppt->id
        );
        $this->assertNotNull($confirmedReminderNotif->fresh()->read_at);

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $mabiniStaff->id,
            'type' => 'attendance_confirmed',
        ]);

        // Web portal shows Attendance Confirmed badge
        $this->actingAs($mabiniStaff)->get("/admin/appointments/{$confirmedTomorrowAppt->id}")
            ->assertStatus(200)
            ->assertSee('Attendance Confirmed');

        // Simulate ignoring the 1-day reminder past the 12-hour waiting time (+12 hours 5 mins)
        $this->travel(12)->hours();
        $this->travel(5)->minutes();
        $freshToken = JWTAuth::fromUser($owner);

        $this->withHeader('Authorization', "Bearer {$freshToken}")
            ->getJson('/api/user/appointments')
            ->assertStatus(200);

        $ignoredTomorrowAppt->refresh();
        $confirmedTomorrowAppt->refresh();
        $sameDayAppt->refresh();

        // Ignored appointment is auto-cancelled and its reminder notification is marked read
        $this->assertSame(Appointment::STATUS_CANCELLED, $ignoredTomorrowAppt->status);
        $this->assertStringContainsString('[Auto-Cancelled]', (string) $ignoredTomorrowAppt->notes);
        $this->assertStringContainsString('12-hour waiting time', (string) $ignoredTomorrowAppt->notes);

        $ignoredReminderNotif = $reminderNotifs->first(
            fn ($n) => (int) ($n->data['appointment_id'] ?? 0) === $ignoredTomorrowAppt->id
        );
        $this->assertNotNull($ignoredReminderNotif->fresh()->read_at);

        // Confirmed appointment remains active
        $this->assertSame(Appointment::STATUS_APPROVED, $confirmedTomorrowAppt->status);

        // Same-day 10:00 AM appointment whose time (at 8:05 PM Manila) already passed without confirmation is also auto-cancelled
        $this->assertSame(Appointment::STATUS_CANCELLED, $sameDayAppt->status);

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $owner->id,
            'type' => 'appointment_auto_cancelled',
        ]);
        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $mabiniStaff->id,
            'type' => 'appointment_auto_cancelled',
        ]);

        // Verify Staff portal trigger auto-cancels missed/expired appointments even if pet owner never opens mobile app
        $missedStaffPortalAppt = Appointment::create([
            'user_id' => $owner->id,
            'pet_id' => $pets[2]->id,
            'branch' => 'Mabini Branch',
            'appointment_date' => $todayDate,
            'appointment_time' => '09:00',
            'status' => Appointment::STATUS_APPROVED,
            'reason' => 'Diagnostic / Checkup',
        ]);

        $this->actingAs($mabiniStaff)->get('/admin/appointments')->assertStatus(200);
        $this->assertSame(Appointment::STATUS_CANCELLED, $missedStaffPortalAppt->fresh()->status);
    }
}
