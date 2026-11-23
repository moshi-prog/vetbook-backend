<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\Pet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class FinalizedAppointmentPurgeAndNotificationDeleteTest extends TestCase
{
    use RefreshDatabase;

    private function createStaff(string $branch = 'Mabini Branch'): User
    {
        return User::create([
            'name'         => "{$branch} Staff",
            'email'        => uniqid('staff_', true) . '@vetbook.test',
            'password'     => Hash::make('password123'),
            'role'         => 'admin',
            'branch'       => $branch,
            'phone_number' => '09171234567',
        ]);
    }

    private function createOwner(string $name = 'Pet Owner'): User
    {
        return User::create([
            'name'              => $name,
            'email'             => uniqid('owner_', true) . '@vetbook.test',
            'password'          => Hash::make('password123'),
            'phone_number'      => '09178889999',
            'role'              => 'pet_owner',
            'email_verified_at' => now(),
            'approval_status'   => 'approved',
        ]);
    }

    public function test_completed_and_cancelled_appointments_remain_for_5_minutes_then_auto_delete_without_affecting_medical_records(): void
    {
        $staff = $this->createStaff('Mabini Branch');
        $owner = $this->createOwner('Elena Cruz');

        $pet = Pet::create([
            'user_id' => $owner->id,
            'name'    => 'Kuro',
            'species' => 'Dog',
            'breed'   => 'Shih Tzu',
        ]);

        $medicalRecord = MedicalRecord::create([
            'pet_id'            => $pet->id,
            'veterinarian'      => 'Dr. Cruz',
            'consultation_date' => now()->toDateString(),
            'diagnosis'         => 'Annual checkup clear',
            'treatment'         => 'Routine exam',
        ]);

        $completedAppt = Appointment::create([
            'user_id'          => $owner->id,
            'pet_id'           => $pet->id,
            'branch'           => 'Mabini Branch',
            'appointment_date' => Carbon::tomorrow()->toDateString(),
            'appointment_time' => '09:30',
            'status'           => Appointment::STATUS_APPROVED,
            'reason'           => 'Diagnostic / Checkup',
            'service_type'     => 'checkup',
        ]);

        $cancelledAppt = Appointment::create([
            'user_id'          => $owner->id,
            'pet_id'           => $pet->id,
            'branch'           => 'Mabini Branch',
            'appointment_date' => Carbon::tomorrow()->toDateString(),
            'appointment_time' => '11:00',
            'status'           => Appointment::STATUS_APPROVED,
            'reason'           => 'Vaccination',
            'service_type'     => 'vaccination',
        ]);

        // Mark completed and cancelled via Staff actions
        $this->actingAs($staff)->post("/admin/appointments/{$completedAppt->id}/complete")->assertRedirect();
        $this->actingAs($staff)->post("/admin/appointments/{$cancelledAppt->id}/cancel", [
            'cancel_reason' => 'Rescheduled verbally',
        ])->assertRedirect();

        // At 0 minutes -> both rows remain visible with countdown badge
        $res0 = $this->actingAs($staff)->get('/admin/appointments');
        $res0->assertStatus(200)
            ->assertSee('data-auto-delete-at=', false)
            ->assertSee('9:30 AM')
            ->assertSee('11:00 AM');
        $this->assertDatabaseHas('appointments', ['id' => $completedAppt->id]);
        $this->assertDatabaseHas('appointments', ['id' => $cancelledAppt->id]);

        // At +4 minutes -> still within 5-minute grace window
        $this->travel(4)->minutes();
        $res4 = $this->actingAs($staff)->get('/admin/appointments');
        $res4->assertStatus(200)
            ->assertSee('data-auto-delete-at=', false)
            ->assertSee('9:30 AM')
            ->assertSee('11:00 AM');
        $this->assertDatabaseHas('appointments', ['id' => $completedAppt->id]);
        $this->assertDatabaseHas('appointments', ['id' => $cancelledAppt->id]);

        // Travel +2 more minutes (total 6 minutes > 5 minute grace window) -> purged cleanly
        $this->travel(2)->minutes();
        $res6 = $this->actingAs($staff)->get('/admin/appointments');
        $res6->assertStatus(200)
            ->assertDontSee('data-auto-delete-at=', false)
            ->assertDontSee('9:30 AM')
            ->assertDontSee('11:00 AM');

        $this->assertDatabaseMissing('appointments', ['id' => $completedAppt->id]);
        $this->assertDatabaseMissing('appointments', ['id' => $cancelledAppt->id]);

        // Pet and MedicalRecord remain completely intact
        $this->assertDatabaseHas('pets', ['id' => $pet->id, 'name' => 'Kuro']);
        $this->assertDatabaseHas('medical_records', ['id' => $medicalRecord->id, 'pet_id' => $pet->id]);
    }

    public function test_pet_owner_can_delete_single_notification_and_clear_all_notifications_scoped_to_own_account(): void
    {
        $ownerA = $this->createOwner('Owner A');
        $ownerB = $this->createOwner('Owner B');

        $notifA1 = AppNotification::create([
            'user_id' => $ownerA->id,
            'title'   => 'Alert A1',
            'message' => 'Unread 1',
            'type'    => 'appointment_approved',
            'read_at' => null,
        ]);
        $notifA2 = AppNotification::create([
            'user_id' => $ownerA->id,
            'title'   => 'Alert A2',
            'message' => 'Unread 2',
            'type'    => 'staff_manual_reminder',
            'read_at' => null,
        ]);
        $notifA3 = AppNotification::create([
            'user_id' => $ownerA->id,
            'title'   => 'Alert A3',
            'message' => 'Already read',
            'type'    => 'appointment_completed',
            'read_at' => now()->subHour(),
        ]);

        $notifB1 = AppNotification::create([
            'user_id' => $ownerB->id,
            'title'   => 'Alert B1',
            'message' => 'Belongs to Owner B',
            'type'    => 'appointment_approved',
            'read_at' => null,
        ]);

        $tokenA = JWTAuth::fromUser($ownerA);

        // 1. Delete single unread notification for Owner A -> unread_count becomes 1
        $delOne = $this->withHeader('Authorization', "Bearer {$tokenA}")
            ->deleteJson("/api/notifications/{$notifA1->id}");

        $delOne->assertStatus(200)
            ->assertJson([
                'message'      => 'Notification deleted',
                'unread_count' => 1,
            ]);

        $this->assertDatabaseMissing('app_notifications', ['id' => $notifA1->id]);
        $this->assertDatabaseHas('app_notifications', ['id' => $notifA2->id]);
        $this->assertDatabaseHas('app_notifications', ['id' => $notifA3->id]);

        // 2. Owner A cannot delete Owner B's notification -> 404
        $this->withHeader('Authorization', "Bearer {$tokenA}")
            ->deleteJson("/api/notifications/{$notifB1->id}")
            ->assertStatus(404);

        $this->assertDatabaseHas('app_notifications', ['id' => $notifB1->id]);

        // 3. Owner A clears all own notifications -> unread_count = 0, Owner B untouched
        $clearRes = $this->withHeader('Authorization', "Bearer {$tokenA}")
            ->deleteJson('/api/notifications');

        $clearRes->assertStatus(200)
            ->assertJson([
                'message'       => 'All notifications cleared',
                'deleted_count' => 2,
                'unread_count'  => 0,
            ]);

        $this->assertDatabaseMissing('app_notifications', ['id' => $notifA2->id]);
        $this->assertDatabaseMissing('app_notifications', ['id' => $notifA3->id]);
        $this->assertDatabaseHas('app_notifications', ['id' => $notifB1->id]);
    }
}
