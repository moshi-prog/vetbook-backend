<?php

namespace Tests\Feature;

use App\Models\ClinicSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminSettingsAccountTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(array $attributes = []): User
    {
        return User::create(array_merge([
            'name' => 'Branch Admin',
            'email' => 'branch.admin@vetbook.test',
            'password' => 'secret123',
            'role' => 'admin',
            'branch' => 'Mabini Branch',
        ], $attributes));
    }

    private function createSuperAdmin(array $attributes = []): User
    {
        return User::create(array_merge([
            'name' => 'Super Admin',
            'email' => 'super.admin@vetbook.test',
            'password' => 'secret123',
            'role' => 'admin',
            'branch' => null,
        ], $attributes));
    }

    public function test_settings_index_displays_admins_and_account_forms(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $branchAdmin = $this->createAdmin();

        $response = $this->actingAs($superAdmin)->get('/admin/settings');

        $response->assertStatus(200);
        $response->assertSee('My Admin Account');
        $response->assertSee('Change Password');
        $response->assertSee('Admin Staff Accounts');
        $response->assertSee($branchAdmin->name);
        $response->assertSee($superAdmin->name);
    }

    public function test_admin_can_update_profile_name_and_email(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post('/admin/profile/update', [
            'name' => 'Updated Admin Name',
            'email' => 'updated.email@vetbook.test',
            'phone_number' => '09171234567',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Account details updated successfully.');

        $admin->refresh();
        $this->assertEquals('Updated Admin Name', $admin->name);
        $this->assertEquals('updated.email@vetbook.test', $admin->email);
        $this->assertEquals('09171234567', $admin->phone_number);
    }

    public function test_admin_profile_update_validates_unique_email(): void
    {
        $admin1 = $this->createAdmin(['email' => 'admin1@vetbook.test']);
        $admin2 = $this->createSuperAdmin(['email' => 'admin2@vetbook.test']);

        $response = $this->actingAs($admin1)->post('/admin/profile/update', [
            'name' => 'Admin One',
            'email' => 'admin2@vetbook.test',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_admin_can_change_password_with_valid_current_password(): void
    {
        $admin = $this->createAdmin(['password' => 'oldpassword123']);

        $response = $this->actingAs($admin)->post('/admin/profile/password', [
            'current_password' => 'oldpassword123',
            'new_password' => 'newpassword456',
            'new_password_confirmation' => 'newpassword456',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Your password has been changed successfully.');

        $admin->refresh();
        $this->assertTrue(Hash::check('newpassword456', $admin->password));
    }

    public function test_admin_change_password_fails_with_incorrect_current_password(): void
    {
        $admin = $this->createAdmin(['password' => 'oldpassword123']);

        $response = $this->actingAs($admin)->post('/admin/profile/password', [
            'current_password' => 'wrongpassword',
            'new_password' => 'newpassword456',
            'new_password_confirmation' => 'newpassword456',
        ]);

        $response->assertSessionHasErrors('current_password');

        $admin->refresh();
        $this->assertTrue(Hash::check('oldpassword123', $admin->password));
    }

    public function test_super_admin_can_reset_branch_admin_password(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $branchAdmin = $this->createAdmin(['password' => 'initialpassword123']);

        $response = $this->actingAs($superAdmin)->post("/admin/users/{$branchAdmin->id}/reset-password", [
            'new_password' => 'resetpassword789',
            'new_password_confirmation' => 'resetpassword789',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Password for admin has been reset successfully.');

        $branchAdmin->refresh();
        $this->assertTrue(Hash::check('resetpassword789', $branchAdmin->password));
    }

    public function test_branch_admin_cannot_reset_admin_password(): void
    {
        $branchAdmin1 = $this->createAdmin(['email' => 'admin1@vetbook.test']);
        $branchAdmin2 = $this->createAdmin(['email' => 'admin2@vetbook.test']);

        $response = $this->actingAs($branchAdmin1)->post("/admin/users/{$branchAdmin2->id}/reset-password", [
            'new_password' => 'hackedpassword',
            'new_password_confirmation' => 'hackedpassword',
        ]);

        $response->assertStatus(403);
    }

    public function test_super_admin_can_update_admin_user_details(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $branchAdmin = $this->createAdmin([
            'name' => 'Original Admin',
            'email' => 'orig@vetbook.test',
            'branch' => 'Mabini Branch',
            'phone_number' => '09171112222',
        ]);

        $response = $this->actingAs($superAdmin)->post("/admin/users/{$branchAdmin->id}/update", [
            'name' => 'Updated Admin Officer',
            'email' => 'officer@vetbook.test',
            'phone_number' => '09189998877',
            'branch' => 'Batangas City Branch',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Admin account for Updated Admin Officer updated successfully.');

        $branchAdmin->refresh();
        $this->assertEquals('Updated Admin Officer', $branchAdmin->name);
        $this->assertEquals('officer@vetbook.test', $branchAdmin->email);
        $this->assertEquals('09189998877', $branchAdmin->phone_number);
        $this->assertEquals('Batangas City Branch', $branchAdmin->branch);
    }

    public function test_branch_admin_cannot_update_other_admin_users(): void
    {
        $branchAdmin1 = $this->createAdmin(['email' => 'branch1@vetbook.test']);
        $branchAdmin2 = $this->createAdmin(['email' => 'branch2@vetbook.test']);

        $response = $this->actingAs($branchAdmin1)->post("/admin/users/{$branchAdmin2->id}/update", [
            'name' => 'Hacked Admin',
            'email' => 'hacked@vetbook.test',
            'phone_number' => '09990001122',
            'branch' => 'Mabini Branch',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_update_and_persist_clinic_profile_settings(): void
    {
        $mabiniAdmin = $this->createAdmin(['branch' => 'Mabini Branch', 'phone_number' => '09170000001']);
        $superAdmin = $this->createSuperAdmin(['phone_number' => '09170000002']);

        $response = $this->actingAs($superAdmin)->post('/admin/settings/clinic', [
            'clinic_name' => 'Super Vet Clinic',
            'tagline' => 'Best pet care in Batangas',
            'email' => 'support@supervet.test',
            'phone' => '0999-555-4433',
            'accreditation' => 'BAI-2026-999',
            'hours_weekday' => '08:00 AM - 07:00 PM',
            'hours_saturday' => '08:00 AM - 06:00 PM',
            'hours_sunday' => '09:00 AM - 03:00 PM',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Clinic information updated successfully.');

        // Verify values persisted in clinic_settings
        $this->assertEquals('Super Vet Clinic', ClinicSetting::get('clinic_name'));
        $this->assertEquals('Best pet care in Batangas', ClinicSetting::get('clinic_tagline'));
        $this->assertEquals('support@supervet.test', ClinicSetting::get('clinic_email'));
        $this->assertEquals('0999-555-4433', ClinicSetting::get('clinic_phone'));
        $this->assertEquals('BAI-2026-999', ClinicSetting::get('clinic_accreditation'));
        $this->assertEquals('08:00 AM - 07:00 PM', ClinicSetting::get('clinic_hours_weekday'));
        $this->assertEquals('08:00 AM - 06:00 PM', ClinicSetting::get('clinic_hours_saturday'));
        $this->assertEquals('09:00 AM - 03:00 PM', ClinicSetting::get('clinic_hours_sunday'));

        // Verify admin user phone numbers are NOT touched by clinic profile updates
        $mabiniAdmin->refresh();
        $superAdmin->refresh();
        $this->assertEquals('09170000001', $mabiniAdmin->phone_number);
        $this->assertEquals('09170000002', $superAdmin->phone_number);

        // Verify that reloading /admin/settings loads the new persisted values
        $reloadResponse = $this->actingAs($superAdmin)->get('/admin/settings');
        $reloadResponse->assertStatus(200);
        $reloadResponse->assertSee('Super Vet Clinic');
        $reloadResponse->assertSee('Best pet care in Batangas');
        $reloadResponse->assertSee('support@supervet.test');
        $reloadResponse->assertSee('0999-555-4433');

        // General hotline must not leak into branch hotlines
        $branchResponse = $this->getJson('/api/branches');
        $branchResponse->assertStatus(200);
        $branchResponse->assertJsonPath('data.0.id', 'mabini');
        $branchResponse->assertJsonPath('data.0.phone', '0917-123-4567');
        $branchResponse->assertJsonPath('data.0.dialUrl', 'tel:09171234567');
        $branchResponse->assertJsonPath('clinic.name', 'Super Vet Clinic');
        $branchResponse->assertJsonPath('clinic.tagline', 'Best pet care in Batangas');
        $branchResponse->assertJsonPath('clinic.email', 'support@supervet.test');
        $branchResponse->assertJsonPath('clinic.accreditation', 'BAI-2026-999');
    }

    public function test_admin_can_update_sms_notification_templates(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $response = $this->actingAs($superAdmin)->post('/admin/settings/sms', [
            'templates' => [
                'confirmation' => 'Custom: Hello {owner_name}, appointment for {pet_name} confirmed at {time}!',
                'rescheduled' => 'Custom: Rescheduled appointment for {pet_name} to {date} at {time}.',
                'completed' => 'Custom: Thanks for visiting with {pet_name}!',
                'cancellation' => 'Custom: Cancelled appointment for {pet_name}.',
            ],
        ]);

        $response->assertRedirect(route('admin.settings.index') . '#sms');
        $response->assertSessionHas('success', 'SMS notification templates updated successfully.');

        // Verify values persisted in database
        $this->assertEquals(
            'Custom: Hello {owner_name}, appointment for {pet_name} confirmed at {time}!',
            ClinicSetting::get('sms_template_confirmation')
        );
        $this->assertEquals(
            'Custom: Rescheduled appointment for {pet_name} to {date} at {time}.',
            ClinicSetting::get('sms_template_rescheduled')
        );
        $this->assertEquals(
            'Custom: Thanks for visiting with {pet_name}!',
            ClinicSetting::get('sms_template_completed')
        );
        $this->assertEquals(
            'Custom: Cancelled appointment for {pet_name}.',
            ClinicSetting::get('sms_template_cancellation')
        );

        // Verify reloading /admin/settings displays the customized templates
        $reloadResponse = $this->actingAs($superAdmin)->get('/admin/settings');
        $reloadResponse->assertStatus(200);
        $reloadResponse->assertSee('Custom: Hello {owner_name}, appointment for {pet_name} confirmed at {time}!');
        $reloadResponse->assertSee('Custom: Rescheduled appointment for {pet_name} to {date} at {time}.');
        $reloadResponse->assertSee('name="templates[confirmation]"', false);
        $reloadResponse->assertSee('name="templates[rescheduled]"', false);
        $reloadResponse->assertSee('name="templates[completed]"', false);
        $reloadResponse->assertSee('name="templates[cancellation]"', false);
    }
}
