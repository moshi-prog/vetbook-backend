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
        $response->assertSee('Clinic Staff Accounts');
        $response->assertSee($branchAdmin->name);
        $response->assertSee($superAdmin->name);
    }

    public function test_admin_can_update_profile_name_and_email(): void
    {
        $admin = $this->createSuperAdmin();

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

    public function test_super_admin_profile_update_without_phone_number_preserves_existing_phone_number(): void
    {
        $superAdmin = $this->createSuperAdmin(['phone_number' => '09178889999']);

        $response = $this->actingAs($superAdmin)->post('/admin/profile/update', [
            'name' => 'Updated Super Name',
            'email' => 'updated.super@vetbook.test',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Account details updated successfully.');

        $superAdmin->refresh();
        $this->assertSame('Updated Super Name', $superAdmin->name);
        $this->assertSame('updated.super@vetbook.test', $superAdmin->email);
        $this->assertSame('09178889999', $superAdmin->phone_number);
    }

    public function test_admin_profile_update_validates_unique_email(): void
    {
        $admin1 = $this->createSuperAdmin(['email' => 'admin1@vetbook.test']);
        $admin2 = $this->createAdmin(['email' => 'admin2@vetbook.test']);

        $response = $this->actingAs($admin1)->post('/admin/profile/update', [
            'name' => 'Admin One',
            'email' => 'admin2@vetbook.test',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_branch_admin_cannot_change_email_in_preferences_and_can_update_name_and_phone(): void
    {
        $branchAdmin = $this->createAdmin(['email' => 'branch.locked@vetbook.test']);

        // View renders locked email badge and omits name="email" inside #tab-preferences
        $view = $this->actingAs($branchAdmin)->get('/admin/settings');
        $view->assertStatus(200);
        $html = $view->getContent();
        $this->assertSame(1, preg_match('/id="tab-preferences"[\s\S]*$/', $html, $matches));
        $prefsHtml = $matches[0];
        $this->assertStringContainsString('branch.locked@vetbook.test', $prefsHtml);
        $this->assertStringContainsString('Email is managed exclusively by Admin', $prefsHtml);
        $this->assertStringNotContainsString('name="email"', $prefsHtml);

        // Saving name & personal phone without email succeeds
        $this->actingAs($branchAdmin)->post('/admin/profile/update', [
            'name' => 'Branch Staff Updated',
            'phone_number' => '09179990001',
        ])->assertRedirect()->assertSessionHas('success', 'Account details updated successfully.');

        $branchAdmin->refresh();
        $this->assertSame('Branch Staff Updated', $branchAdmin->name);
        $this->assertSame('09179990001', $branchAdmin->phone_number);
        $this->assertSame('branch.locked@vetbook.test', $branchAdmin->email);

        // Attempting to forge a different email returns 403
        $this->actingAs($branchAdmin)->post('/admin/profile/update', [
            'name' => 'Branch Staff Updated',
            'email' => 'hijacked@vetbook.test',
        ])->assertStatus(403);

        $this->assertSame('branch.locked@vetbook.test', $branchAdmin->fresh()->email);
    }

    public function test_admin_can_change_password_with_valid_current_password(): void
    {
        $admin = $this->createSuperAdmin(['password' => 'oldpassword123']);

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
        $admin = $this->createSuperAdmin(['password' => 'oldpassword123']);

        $response = $this->actingAs($admin)->post('/admin/profile/password', [
            'current_password' => 'wrongpassword',
            'new_password' => 'newpassword456',
            'new_password_confirmation' => 'newpassword456',
        ]);

        $response->assertSessionHasErrors('current_password');

        $admin->refresh();
        $this->assertTrue(Hash::check('oldpassword123', $admin->password));
    }

    public function test_branch_admin_cannot_access_change_password_or_admin_users_tab(): void
    {
        $superAdmin = $this->createSuperAdmin(['name' => 'Visible Super']);
        $branchAdmin = $this->createAdmin(['password' => 'oldpassword123']);

        // Branch admin cannot submit password change endpoint
        $this->actingAs($branchAdmin)->post('/admin/profile/password', [
            'current_password' => 'oldpassword123',
            'new_password' => 'newpassword456',
            'new_password_confirmation' => 'newpassword456',
        ])->assertStatus(403);

        $this->assertTrue(Hash::check('oldpassword123', $branchAdmin->fresh()->password));

        // Settings page hides Change Password, Admin Users tab, and Super-Admin branch management controls
        $response = $this->actingAs($branchAdmin)->get('/admin/settings');
        $response->assertStatus(200);
        $response->assertSee('My Staff Account');
        $response->assertSee('Email is managed exclusively by Admin');
        $response->assertDontSee('Change Password');
        $response->assertDontSee('data-tab="admins"', false);
        $response->assertDontSee('Clinic Staff Accounts');
        $response->assertDontSee('id="addAdminModal"', false);
        $response->assertDontSee('id="addBranchModal"', false);
        $response->assertDontSee('Add New Branch');
        $response->assertDontSee('Deactivate');
        $response->assertDontSee('Visible Super');
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
            'branch' => null,
            'phone_number' => '09171112222',
        ]);

        // Rejected branch (Batangas City Branch) fails validation
        $this->actingAs($superAdmin)->post("/admin/users/{$branchAdmin->id}/update", [
            'name' => 'Updated Admin Officer',
            'email' => 'officer@vetbook.test',
            'phone_number' => '09189998877',
            'branch' => 'Batangas City Branch',
        ])->assertSessionHasErrors('branch');

        $response = $this->actingAs($superAdmin)->post("/admin/users/{$branchAdmin->id}/update", [
            'name' => 'Updated Admin Officer',
            'email' => 'officer@vetbook.test',
            'phone_number' => '09189998877',
            'branch' => 'Mabini Branch',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Admin account for Updated Admin Officer updated successfully.');

        $branchAdmin->refresh();
        $this->assertEquals('Updated Admin Officer', $branchAdmin->name);
        $this->assertEquals('officer@vetbook.test', $branchAdmin->email);
        $this->assertEquals('09189998877', $branchAdmin->phone_number);
        $this->assertEquals('Mabini Branch', $branchAdmin->branch);
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
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Clinic information updated successfully.');

        // Verify values persisted in clinic_settings
        $this->assertEquals('Super Vet Clinic', ClinicSetting::get('clinic_name'));
        $this->assertEquals('Best pet care in Batangas', ClinicSetting::get('clinic_tagline'));
        $this->assertEquals('support@supervet.test', ClinicSetting::get('clinic_email'));
        $this->assertEquals('0999-555-4433', ClinicSetting::get('clinic_phone'));
        $this->assertEquals('BAI-2026-999', ClinicSetting::get('clinic_accreditation'));

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

    public function test_admin_preferences_persist_and_render_selected_values_on_reload(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $response = $this->actingAs($superAdmin)->post('/admin/settings/preferences', [
            'timezone' => 'UTC',
            'date_format' => 'd/m/Y',
            'time_format' => '24',
            'session_timeout' => 240,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success', 'System preferences updated successfully.');

        $this->assertSame('UTC', ClinicSetting::get('pref_timezone'));
        $this->assertSame('d/m/Y', ClinicSetting::get('pref_date_format'));
        $this->assertSame('24', ClinicSetting::get('pref_time_format'));
        $this->assertSame('240', ClinicSetting::get('pref_session_timeout'));

        $reload = $this->actingAs($superAdmin)->get('/admin/settings');
        $reload->assertStatus(200);
        $html = $reload->getContent();

        $this->assertSame(1, preg_match('/<option\s+value="UTC"[^>]*\bselected\b/', $html));
        $this->assertSame(0, preg_match('/<option\s+value="Asia\/Manila"[^>]*\bselected\b/', $html));

        $this->assertSame(1, preg_match('/<option\s+value="d\/m\/Y"[^>]*\bselected\b/', $html));
        $this->assertSame(0, preg_match('/<option\s+value="F j, Y"[^>]*\bselected\b/', $html));

        $this->assertSame(1, preg_match('/<input[^>]*id="t24"[^>]*\bchecked\b/', $html));
        $this->assertSame(0, preg_match('/<input[^>]*id="t12"[^>]*\bchecked\b/', $html));

        $this->assertSame(1, preg_match('/<option\s+value="240"[^>]*\bselected\b/', $html));
        $this->assertSame(0, preg_match('/<option\s+value="60"[^>]*\bselected\b/', $html));
    }

    public function test_admin_preferences_validation_rejects_invalid_values(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $response = $this->actingAs($superAdmin)->post('/admin/settings/preferences', [
            'time_format' => '99',
            'session_timeout' => 2,
        ]);

        $response->assertSessionHasErrors(['time_format', 'session_timeout']);
    }

    public function test_super_admin_sees_read_only_branch_schedules_and_non_deceptive_provisioning_notices(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $response = $this->actingAs($superAdmin)->get('/admin/settings');
        $response->assertStatus(200);

        $response->assertDontSee('Clinic-Wide Operating Schedule');
        $response->assertDontSee('name="hours_weekday"', false);
        $response->assertDontSee('name="hours_saturday"', false);
        $response->assertDontSee('name="hours_sunday"', false);

        $response->assertSee('Branch Operating Schedules');
        $response->assertSee('Mabini Branch Schedule');
        $response->assertSee('Managed exclusively by Mabini Branch Staff');
        $response->assertDontSee('Batangas City Branch');

        $response->assertSee('Multi-branch self-service provisioning is managed via clinic onboarding.');
        $response->assertDontSee('Branch registered in pilot registry');
        $response->assertDontSee('Admin invitation link ready');
    }

    public function test_super_admin_cannot_update_operating_hours_and_branch_admin_updates_own_branch_hours(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $mabiniAdmin = $this->createAdmin(['branch' => 'Mabini Branch']);

        $this->actingAs($superAdmin)->post('/admin/settings/clinic', [
            'clinic_name' => 'BK Petaholic Animal Clinic',
            'email' => 'info@bkpetaholic.com',
            'hours_weekday' => '08:00 AM - 08:00 PM',
        ])->assertStatus(403);

        $this->actingAs($superAdmin)->post('/admin/settings/clinic', [
            'clinic_name' => 'BK Petaholic Animal Clinic',
            'email' => 'info@bkpetaholic.com',
            'hours_saturday' => '08:00 AM - 05:00 PM',
            'hours_sunday' => 'Closed',
        ])->assertStatus(403);

        $this->assertNull(ClinicSetting::get('clinic_hours_weekday'));
        $this->assertNull(ClinicSetting::get('branch_mabini_hours_weekday'));
        $this->assertNull(ClinicSetting::get('branch_batangas_hours_weekday'));

        $mabiniView = $this->actingAs($mabiniAdmin)->get('/admin/settings');
        $mabiniView->assertStatus(200);
        $mabiniView->assertSee('Mabini Branch Operating Schedule');
        $mabiniView->assertSee('name="hours_weekday"', false);
        $mabiniView->assertSee('name="hours_saturday"', false);
        $mabiniView->assertSee('name="hours_sunday"', false);

        $this->actingAs($mabiniAdmin)->post('/admin/settings/clinic', [
            'clinic_name' => 'BK Petaholic Animal Clinic',
            'email' => 'info@bkpetaholic.com',
            'hours_weekday' => '08:30 AM - 06:30 PM',
            'hours_saturday' => '09:00 AM - 04:00 PM',
            'hours_sunday' => 'Closed',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('08:30 AM - 06:30 PM', ClinicSetting::get('branch_mabini_hours_weekday'));
        $this->assertSame('09:00 AM - 04:00 PM', ClinicSetting::get('branch_mabini_hours_saturday'));
        $this->assertSame('Closed', ClinicSetting::get('branch_mabini_hours_sunday'));
        $this->assertNull(ClinicSetting::get('branch_batangas_hours_weekday'));
        $this->assertNull(ClinicSetting::get('clinic_hours_weekday'));
    }

    public function test_chatbot_system_prompt_incorporates_saved_persona_and_live_clinic_hours(): void
    {
        ClinicSetting::set('chatbot_system_prompt', 'Always greet pet parents with Warm Batangas Hospitality.');
        ClinicSetting::set('clinic_hours_weekday', '07:30 AM - 08:00 PM');

        $fallbackPrompt = \App\Services\ChatbotService::buildSystemPrompt();
        $this->assertStringContainsString('Always greet pet parents with Warm Batangas Hospitality.', $fallbackPrompt);
        $this->assertStringContainsString('07:30 AM - 08:00 PM', $fallbackPrompt);

        ClinicSetting::set('branch_mabini_hours_weekday', '08:00 AM - 05:30 PM');
        $prompt = \App\Services\ChatbotService::buildSystemPrompt();

        $this->assertStringContainsString('Always greet pet parents with Warm Batangas Hospitality.', $prompt);
        $this->assertStringContainsString('08:00 AM - 05:30 PM', $prompt);
        $this->assertStringNotContainsString('Batangas City Branch', $prompt);
    }
}
