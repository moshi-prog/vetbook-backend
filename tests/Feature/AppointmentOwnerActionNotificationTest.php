<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppNotification;
use App\Models\Pet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AppointmentOwnerActionNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmins(): array
    {
        $superAdmin = User::create([
            'name' => 'Super Administrator',
            'email' => 'super@vetbook.test',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'branch' => null,
        ]);

        $mabiniAdmin = User::create([
            'name' => 'Mabini Branch Admin',
            'email' => 'mabini_admin@vetbook.test',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'branch' => 'Mabini Branch',
        ]);

        $batangasAdmin = User::create([
            'name' => 'Batangas Branch Admin',
            'email' => 'batangas_admin@vetbook.test',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'branch' => 'Batangas City Branch',
        ]);

        return [$superAdmin, $mabiniAdmin, $batangasAdmin];
    }

    private function createPetOwner(): array
    {
        $owner = User::create([
            'name' => 'Juan Luna',
            'email' => 'juanluna@example.com',
            'password' => bcrypt('password123'),
            'role' => 'pet_owner',
            'phone_number' => '09170001122',
        ]);

        $pet = Pet::create([
            'user_id' => $owner->id,
            'name' => 'Hero',
            'species' => 'Dog',
            'breed' => 'German Shepherd',
        ]);

        return [$owner, $pet];
    }

    public function test_pet_owner_cancelling_with_reason_updates_notes_and_notifies_relevant_admins(): void
    {
        [$superAdmin, $mabiniAdmin, $batangasAdmin] = $this->createAdmins();
        [$owner, $pet] = $this->createPetOwner();

        $appointment = Appointment::create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
            'branch' => 'Mabini Branch',
            'appointment_date' => now()->addDays(3)->toDateString(),
            'appointment_time' => '10:00',
            'status' => 'approved',
            'reason' => 'Annual Checkup',
            'notes' => 'Existing client note.',
        ]);

        $token = JWTAuth::fromUser($owner);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/appointments/{$appointment->id}/cancel", [
                'cancel_reason' => 'Schedule conflict — out of town on business',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Appointment cancelled successfully']);

        // Verify appointment status and updated notes
        $freshAppt = $appointment->fresh();
        $this->assertEquals('cancelled', $freshAppt->status);
        $this->assertStringContainsString('Existing client note.', $freshAppt->notes);
        $this->assertStringContainsString('[Cancelled by Pet Owner] Reason: Schedule conflict — out of town on business', $freshAppt->notes);

        // Verify In-App Notification for pet owner
        $ownerNotification = AppNotification::where('user_id', $owner->id)
            ->where('type', 'appointment_cancelled')
            ->first();
        $this->assertNotNull($ownerNotification);
        $this->assertEquals('Appointment Cancelled', $ownerNotification->title);
        $this->assertStringContainsString('You have cancelled your appointment for Hero', $ownerNotification->message);

        // Verify Super Admin received notification
        $superNotification = AppNotification::where('user_id', $superAdmin->id)
            ->where('type', 'appointment_cancelled_by_owner')
            ->first();
        $this->assertNotNull($superNotification);
        $this->assertStringContainsString('Juan Luna cancelled their appointment for Hero', $superNotification->message);
        $this->assertStringContainsString('Schedule conflict — out of town on business', $superNotification->message);
        $this->assertEquals($appointment->id, $superNotification->data['appointment_id']);
        $this->assertEquals($pet->id, $superNotification->data['pet_id']);

        // Verify Mabini Branch Admin received notification
        $mabiniNotification = AppNotification::where('user_id', $mabiniAdmin->id)
            ->where('type', 'appointment_cancelled_by_owner')
            ->first();
        $this->assertNotNull($mabiniNotification);
        $this->assertStringContainsString('Juan Luna cancelled their appointment for Hero', $mabiniNotification->message);

        // Verify Batangas Branch Admin did NOT receive notification (different branch)
        $batangasNotification = AppNotification::where('user_id', $batangasAdmin->id)
            ->where('type', 'appointment_cancelled_by_owner')
            ->first();
        $this->assertNull($batangasNotification);
    }

    public function test_pet_owner_rescheduling_updates_notes_and_notifies_relevant_admins(): void
    {
        [$superAdmin, $mabiniAdmin, $batangasAdmin] = $this->createAdmins();
        [$owner, $pet] = $this->createPetOwner();

        $appointment = Appointment::create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
            'branch' => 'Mabini Branch',
            'appointment_date' => now()->addDays(2)->toDateString(),
            'appointment_time' => '09:00',
            'status' => 'approved',
            'reason' => 'Vaccination',
        ]);

        $token = JWTAuth::fromUser($owner);

        $newDate = now()->addDays(6)->toDateString();
        $newTime = '14:30';

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/appointments/{$appointment->id}/reschedule", [
                'appointment_date' => $newDate,
                'appointment_time' => $newTime,
            ]);

        $response->assertStatus(200);

        // Verify appointment fields & notes
        $freshAppt = $appointment->fresh();
        $this->assertEquals($newTime, $freshAppt->appointment_time);
        $this->assertEquals('pending', $freshAppt->status);
        $this->assertStringContainsString('[Rescheduled by Pet Owner] New schedule:', $freshAppt->notes);
        $this->assertStringContainsString($newTime, $freshAppt->notes);

        // Verify In-App Notification for pet owner
        $ownerNotification = AppNotification::where('user_id', $owner->id)
            ->where('type', 'appointment_reschedule_pending')
            ->first();
        $this->assertNotNull($ownerNotification);
        $this->assertEquals('Reschedule Request Pending', $ownerNotification->title);
        $this->assertStringContainsString("You requested to reschedule Hero's visit", $ownerNotification->message);
        $this->assertStringContainsString("Please wait for the clinic admin to approve your new schedule.", $ownerNotification->message);

        // Verify Super Admin received notification
        $superNotification = AppNotification::where('user_id', $superAdmin->id)
            ->where('type', 'appointment_rescheduled_by_owner')
            ->first();
        $this->assertNotNull($superNotification);
        $this->assertStringContainsString('Juan Luna rescheduled their appointment for Hero', $superNotification->message);
        $this->assertStringContainsString($newTime, $superNotification->message);

        // Verify Mabini Branch Admin received notification
        $mabiniNotification = AppNotification::where('user_id', $mabiniAdmin->id)
            ->where('type', 'appointment_rescheduled_by_owner')
            ->first();
        $this->assertNotNull($mabiniNotification);
        $this->assertStringContainsString('Juan Luna rescheduled their appointment for Hero', $mabiniNotification->message);

        // Verify Batangas Branch Admin did NOT receive notification
        $batangasNotification = AppNotification::where('user_id', $batangasAdmin->id)
            ->where('type', 'appointment_rescheduled_by_owner')
            ->first();
        $this->assertNull($batangasNotification);
    }

    public function test_booking_creates_booking_submitted_notification_for_owner(): void
    {
        [$owner, $pet] = $this->createPetOwner();
        $token = JWTAuth::fromUser($owner);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/appointments', [
                'pet_id' => $pet->id,
                'branch' => 'Mabini Branch',
                'appointment_date' => now()->addDays(5)->toDateString(),
                'appointment_time' => '11:00',
                'reason' => 'Checkup',
            ]);

        $response->assertStatus(201);

        $notification = AppNotification::where('user_id', $owner->id)
            ->where('type', 'appointment_booked')
            ->first();

        $this->assertNotNull($notification);
        $this->assertEquals('Booking Submitted', $notification->title);
        $this->assertStringContainsString("Your appointment request for Hero", $notification->message);
        $this->assertStringContainsString("is awaiting clinic confirmation", $notification->message);
    }

    public function test_admin_approving_creates_appointment_confirmed_notification(): void
    {
        [$superAdmin] = $this->createAdmins();
        [$owner, $pet] = $this->createPetOwner();

        $appointment = Appointment::create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
            'branch' => 'Mabini Branch',
            'appointment_date' => now()->addDays(4)->toDateString(),
            'appointment_time' => '10:00 AM',
            'status' => 'pending',
            'reason' => 'Checkup',
        ]);

        $response = $this->actingAs($superAdmin)
            ->post("/admin/appointments/{$appointment->id}/approve");

        $response->assertStatus(302);

        $notification = AppNotification::where('user_id', $owner->id)
            ->where('type', 'appointment_approved')
            ->first();

        $this->assertNotNull($notification);
        $this->assertEquals('Appointment Confirmed', $notification->title);
        $this->assertStringContainsString("Great news! Your appointment for Hero", $notification->message);
        $this->assertStringContainsString("has been confirmed by the clinic", $notification->message);
    }

    public function test_admin_completing_creates_visit_completed_notification(): void
    {
        [$superAdmin] = $this->createAdmins();
        [$owner, $pet] = $this->createPetOwner();

        $appointment = Appointment::create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
            'branch' => 'Mabini Branch',
            'appointment_date' => now()->toDateString(),
            'appointment_time' => '10:00 AM',
            'status' => 'approved',
            'reason' => 'Checkup',
        ]);

        $response = $this->actingAs($superAdmin)
            ->post("/admin/appointments/{$appointment->id}/complete");

        $response->assertStatus(302);

        $notification = AppNotification::where('user_id', $owner->id)
            ->where('type', 'appointment_completed')
            ->first();

        $this->assertNotNull($notification);
        $this->assertEquals('Visit Completed', $notification->title);
        $this->assertStringContainsString("Thank you for bringing Hero to BK Petaholic today!", $notification->message);
    }

    public function test_admin_rescheduling_creates_rescheduled_by_clinic_notification(): void
    {
        [$superAdmin] = $this->createAdmins();
        [$owner, $pet] = $this->createPetOwner();

        $appointment = Appointment::create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
            'branch' => 'Mabini Branch',
            'appointment_date' => now()->addDays(2)->toDateString(),
            'appointment_time' => '10:00 AM',
            'status' => 'approved',
            'reason' => 'Checkup',
        ]);

        $newDate = now()->addDays(7)->toDateString();
        $response = $this->actingAs($superAdmin)
            ->post("/admin/appointments/{$appointment->id}/reschedule", [
                'appointment_date' => $newDate,
                'appointment_time' => '03:00 PM',
            ]);

        $response->assertStatus(302);

        $notification = AppNotification::where('user_id', $owner->id)
            ->where('type', 'appointment_rescheduled')
            ->first();

        $this->assertNotNull($notification);
        $this->assertEquals('Appointment Rescheduled by Clinic', $notification->title);
        $this->assertStringContainsString("BK Petaholic Clinic has updated Hero's appointment to", $notification->message);
    }

    public function test_admin_cancelling_creates_cancelled_by_clinic_notification(): void
    {
        [$superAdmin] = $this->createAdmins();
        [$owner, $pet] = $this->createPetOwner();

        $appointment = Appointment::create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
            'branch' => 'Mabini Branch',
            'appointment_date' => now()->addDays(3)->toDateString(),
            'appointment_time' => '10:00 AM',
            'status' => 'pending',
            'reason' => 'Checkup',
        ]);

        $response = $this->actingAs($superAdmin)
            ->post("/admin/appointments/{$appointment->id}/cancel", [
                'cancel_reason' => 'Veterinarian on emergency leave',
            ]);

        $response->assertStatus(302);

        $notification = AppNotification::where('user_id', $owner->id)
            ->where('type', 'appointment_cancelled')
            ->first();

        $this->assertNotNull($notification);
        $this->assertEquals('Appointment Cancelled by Clinic', $notification->title);
        $this->assertStringContainsString("Your appointment for Hero", $notification->message);
        $this->assertStringContainsString("has been cancelled by the clinic. Reason: Veterinarian on emergency leave.", $notification->message);
    }

    public function test_pet_owner_cancelling_without_reason_defaults_to_no_reason_provided(): void
    {
        [$superAdmin, $mabiniAdmin] = $this->createAdmins();
        [$owner, $pet] = $this->createPetOwner();

        $appointment = Appointment::create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
            'branch' => 'Mabini Branch',
            'appointment_date' => now()->addDays(3)->toDateString(),
            'appointment_time' => '10:00',
            'status' => 'approved',
            'reason' => 'Checkup',
        ]);

        $token = JWTAuth::fromUser($owner);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/appointments/{$appointment->id}/cancel", []);

        $response->assertStatus(200);

        $freshAppt = $appointment->fresh();
        $this->assertEquals('cancelled', $freshAppt->status);
        $this->assertStringContainsString('[Cancelled by Pet Owner] Reason: No reason provided', $freshAppt->notes);

        $superNotification = AppNotification::where('user_id', $superAdmin->id)
            ->where('type', 'appointment_cancelled_by_owner')
            ->first();
        $this->assertNotNull($superNotification);
        $this->assertStringContainsString('Reason: No reason provided', $superNotification->message);
    }

    public function test_cancelled_appointment_details_page_displays_prominent_cancellation_card(): void
    {
        [$superAdmin] = $this->createAdmins();
        [$owner, $pet] = $this->createPetOwner();

        $appointment = Appointment::create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
            'branch' => 'Mabini Branch',
            'appointment_date' => now()->addDays(3)->toDateString(),
            'appointment_time' => '10:00 AM',
            'status' => 'cancelled',
            'reason' => 'Checkup',
            'notes' => "[Cancelled by Pet Owner] Reason: Schedule conflict\nClient will rebook next week.",
        ]);

        $response = $this->actingAs($superAdmin)
            ->get("/admin/appointments/{$appointment->id}");

        $response->assertStatus(200);
        $response->assertSee('Cancellation Information');
        $response->assertSee('[Cancelled by Pet Owner] Reason: Schedule conflict');
        $response->assertSee('fa-ban');
    }
}

