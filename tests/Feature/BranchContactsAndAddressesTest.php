<?php

namespace Tests\Feature;

use App\Models\ClinicSetting;
use App\Models\User;
use App\Services\ChatbotService;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchContactsAndAddressesTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(?string $branch = null): User
    {
        return User::create([
            'name' => $branch ? "{$branch} Admin" : 'Super Admin',
            'email' => uniqid('admin_', true) . '@vetbook.test',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'branch' => $branch,
            'phone_number' => '09170000001',
        ]);
    }

    public function test_super_admin_sees_only_branch_addresses_editable_and_hotlines_read_only(): void
    {
        $super = $this->createAdmin(null);

        $response = $this->actingAs($super)->get('/admin/settings');
        $response->assertStatus(200);
        $html = $response->getContent();

        // Tab 1: hidden phone, NO branch hotline inputs, editable Mabini address + lock badge for Mabini hotline
        $this->assertMatchesRegularExpression('/<input[^>]*type="hidden"[^>]*name="phone"/', $html);
        $response->assertDontSee('General Clinic Hotline');
        $response->assertDontSee('name="branch_mabini_phone"', false);
        $response->assertDontSee('name="branch_batangas_phone"', false);
        $response->assertSee('name="branch_mabini_address"', false);
        $response->assertDontSee('name="branch_batangas_address"', false);
        $response->assertSee('Managed exclusively by Mabini Branch Staff');
        $response->assertDontSee('Batangas City Branch');

        // Tab 2: Mabini edit modal exists with editable address and read-only phone
        $response->assertSee('Add New Branch');
        $response->assertSee('data-bs-target="#editBranchModal-mabini"', false);
        $response->assertDontSee('data-bs-target="#editBranchModal-batangas"', false);
        $response->assertSee('id="editBranchModal-mabini"', false);
        $response->assertDontSee('id="editBranchModal-batangas"', false);
        $response->assertSee('name="address"', false);
        $response->assertSee('Contact number can only be updated by the assigned Branch Staff.');
        $this->assertSame(0, preg_match('/id="editBranchModal-[^"]+"[\s\S]*?name="phone"/', $html));

        // Tab 5 & 6 Super Admin exclusive sections & hidden personal mobile number field
        $response->assertSee('data-tab="admins"', false);
        $response->assertSee('Change Password');
        $response->assertDontSee('Personal Staff Mobile Number');
        $response->assertDontSee('Personal staff contact only.');
    }

    public function test_branch_admin_only_sees_own_branch_contact_and_read_only_address(): void
    {
        $mabiniAdmin = $this->createAdmin('Mabini Branch');
        $resMabini = $this->actingAs($mabiniAdmin)->get('/admin/settings');
        $resMabini->assertStatus(200);
        $mabiniHtml = $resMabini->getContent();

        // Tab 1: Only Mabini hotline editable, hidden phone, no other hotline or editable address inputs
        $resMabini->assertSee('name="branch_mabini_phone"', false);
        $resMabini->assertDontSee('name="branch_batangas_phone"', false);
        $resMabini->assertDontSee('name="branch_mabini_address"', false);
        $resMabini->assertDontSee('name="branch_batangas_address"', false);
        $this->assertMatchesRegularExpression('/<input[^>]*type="hidden"[^>]*name="phone"/', $mabiniHtml);
        $resMabini->assertSee('Branch address is managed exclusively by Admin');

        // Tab 2: Edit only on Mabini branch, editable name="phone", no address input inside modal
        $resMabini->assertSee('data-bs-target="#editBranchModal-mabini"', false);
        $resMabini->assertDontSee('data-bs-target="#editBranchModal-batangas"', false);
        $resMabini->assertSee('id="editBranchModal-mabini"', false);
        $resMabini->assertDontSee('id="editBranchModal-batangas"', false);
        $resMabini->assertDontSee('name="address"', false);
        $resMabini->assertSee('Address can only be modified by Admin');
        $this->assertMatchesRegularExpression('/id="editBranchModal-mabini"[\s\S]*?name="phone"/', $mabiniHtml);
        $resMabini->assertDontSee('Add New Branch');
        $resMabini->assertDontSee('Deactivate');

        // Tab 5 Admin Users & Change Password hidden for Branch Admin; email locked in Preferences & Account; personal mobile visible
        $resMabini->assertDontSee('data-tab="admins"', false);
        $resMabini->assertDontSee('Change Password');
        $resMabini->assertSee('Email is managed exclusively by Admin');
        $resMabini->assertSee('Personal Staff Mobile Number');
        $resMabini->assertSee('Personal staff contact only. To update your branch clinic hotline for pet owners and the AI chatbot, use Clinic Information or Branches.');
        $this->assertSame(1, preg_match('/id="tab-preferences"[\s\S]*$/', $mabiniHtml, $prefsMatch));
        $this->assertStringNotContainsString('name="email"', $prefsMatch[0]);
    }

    public function test_super_admin_cannot_modify_branch_hotlines_and_branch_admins_cannot_modify_addresses(): void
    {
        $super = $this->createAdmin(null);
        $mabiniAdmin = $this->createAdmin('Mabini Branch');

        // Super Admin CANNOT submit branch_mabini_phone via /admin/settings/clinic
        $this->actingAs($super)->post('/admin/settings/clinic', [
            'clinic_name' => 'BK Petaholic',
            'email' => 'info@bk.test',
            'branch_mabini_phone' => '0917-000-0001',
        ])->assertStatus(403);

        // Super Admin CANNOT submit phone to /admin/settings/branches/{branch}
        $this->actingAs($super)->post('/admin/settings/branches/mabini', [
            'phone' => '0917-000-0001',
            'address' => '100 New Mabini Ave.',
        ])->assertStatus(403);

        // Branch admin CANNOT submit address via /admin/settings/clinic
        $this->actingAs($mabiniAdmin)->post('/admin/settings/clinic', [
            'clinic_name' => 'BK Petaholic',
            'email' => 'info@bk.test',
            'branch_mabini_address' => 'Unauthorized New Street',
        ])->assertStatus(403);

        // Branch admin CANNOT submit address via /admin/settings/branches/{own}
        $this->actingAs($mabiniAdmin)->post('/admin/settings/branches/mabini', [
            'phone' => '0917-999-2222',
            'address' => 'Unauthorized New Street',
        ])->assertStatus(403);

        // Removed Batangas route returns 404
        $this->actingAs($mabiniAdmin)->post('/admin/settings/branches/batangas', [
            'phone' => '0918-111-2222',
        ])->assertStatus(404);

        $this->assertNull(ClinicSetting::get('branch_mabini_address'));
        $this->assertNull(ClinicSetting::get('branch_mabini_phone'));
    }

    public function test_exclusive_updates_synchronize_across_settings_api_reports_and_chatbot(): void
    {
        $super = $this->createAdmin(null);
        $mabiniAdmin = $this->createAdmin('Mabini Branch');

        // 1. Super Admin updates Mabini address via Clinic Information
        $this->actingAs($super)->post('/admin/settings/clinic', [
            'clinic_name' => 'BK Petaholic',
            'email' => 'info@bk.test',
            'phone' => '0917-555-0000',
            'branch_mabini_address' => '100 New Mabini Ave., Poblacion, Mabini, Batangas',
        ])->assertRedirect()->assertSessionHasNoErrors();

        // 2. Super Admin updates Mabini branch address via Branch modal (without sending phone)
        $this->actingAs($super)->post('/admin/settings/branches/mabini', [
            'address' => '142 Updated Mabini Ave., Poblacion, Mabini, Batangas',
        ])->assertRedirect(route('admin.settings.index') . '#branches')
            ->assertSessionHas('success', 'Branch address updated successfully.');

        // 3. Mabini Branch Staff updates Mabini hotline
        $this->actingAs($mabiniAdmin)->post('/admin/settings/clinic', [
            'clinic_name' => 'BK Petaholic',
            'email' => 'info@bk.test',
            'branch_mabini_phone' => '0917-222-3333',
        ])->assertRedirect()->assertSessionHasNoErrors();

        // Sync check: Mobile /api/branches
        $api = $this->getJson('/api/branches');
        $api->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.phone', '0917-222-3333')
            ->assertJsonPath('data.0.address', '142 Updated Mabini Ave., Poblacion, Mabini, Batangas');

        // Sync check: ReportService metadata
        $mabiniReport = ReportService::getClinicMetadata('Mabini Branch');
        $this->assertSame('0917-222-3333', $mabiniReport['phone']);
        $this->assertSame('142 Updated Mabini Ave., Poblacion, Mabini, Batangas', $mabiniReport['address']);

        // Sync check: Settings view reflects updated address & hotline
        $this->actingAs($super)->get('/admin/settings')
            ->assertSee('142 Updated Mabini Ave., Poblacion, Mabini, Batangas')
            ->assertSee('0917-222-3333')
            ->assertDontSee('Batangas City Branch');

        // Sync check: ChatbotService system prompt contains live Mabini phone & address + distinct General Admin Desk + accuracy rules
        $prompt = ChatbotService::buildSystemPrompt();
        $this->assertStringContainsString('142 Updated Mabini Ave., Poblacion, Mabini, Batangas', $prompt);
        $this->assertStringContainsString('0917-222-3333', $prompt);
        $this->assertStringContainsString('General Admin Desk: 0917-555-0000', $prompt);
        $this->assertStringContainsString('CONTACT & ADDRESS ACCURACY RULES:', $prompt);
        $this->assertStringContainsString('call Mabini Branch (0917-222-3333)', $prompt);
        $this->assertStringNotContainsString('Batangas City Branch', $prompt);
    }

    public function test_chatbot_omits_stale_default_clinic_phone_when_unset(): void
    {
        ClinicSetting::set('branch_mabini_phone', '0999-111-0001');

        $prompt = ChatbotService::buildSystemPrompt();

        $this->assertStringContainsString('Mabini Hotline: 0999-111-0001', $prompt);
        $this->assertStringNotContainsString('0917-123-4567', $prompt);
        $this->assertStringNotContainsString('General Admin Desk:', $prompt);
        $this->assertStringNotContainsString('Batangas City', $prompt);
    }

    public function test_staff_settings_displays_wheel_time_picker_and_syncs_structured_schedules_with_branches_api(): void
    {
        $super = $this->createAdmin(null);
        $mabiniStaff = $this->createAdmin('Mabini Branch');

        // System Admin sees read-only branch schedule summary and NO wheel modal
        $this->actingAs($super)->get('/admin/settings')
            ->assertStatus(200)
            ->assertSee('Branch Operating Schedules')
            ->assertDontSee('id="wheelTimePickerModal"', false);

        // Mabini Branch Staff sees the wheel modal and Opening/Closing schedule controls
        $res = $this->actingAs($mabiniStaff)->get('/admin/settings');
        $res->assertStatus(200)
            ->assertSee('id="wheelTimePickerModal"', false)
            ->assertSee('id="wheelHours"', false)
            ->assertSee('id="wheelMinutes"', false)
            ->assertSee('id="wheelPeriods"', false)
            ->assertSee('OPENING TIME')
            ->assertSee('CLOSING TIME')
            ->assertSee('id="scheduleInput-weekday"', false)
            ->assertSee('id="scheduleInput-saturday"', false)
            ->assertSee('id="scheduleInput-sunday"', false);

        // Mabini Branch Staff updates structured hours via form inputs
        $this->actingAs($mabiniStaff)->post('/admin/settings/clinic', [
            'clinic_name'        => 'BK Petaholic',
            'email'              => 'info@bk.test',
            'branch_mabini_phone' => '0917-123-4567',
            'hours_weekday'      => '08:30 AM - 05:30 PM',
            'hours_saturday'     => '09:00 AM - 03:00 PM',
            'hours_sunday'       => 'Closed',
        ])->assertRedirect()->assertSessionHasNoErrors();

        // Updates reflect immediately in GET /api/branches
        $branchesRes = $this->getJson('/api/branches');
        $branchesRes->assertStatus(200)
            ->assertJsonPath('data.0.weekday_open', '08:30')
            ->assertJsonPath('data.0.weekday_close', '17:30')
            ->assertJsonPath('data.0.saturday_open', '09:00')
            ->assertJsonPath('data.0.saturday_close', '15:00')
            ->assertJsonPath('data.0.saturday_closed', false)
            ->assertJsonPath('data.0.sunday_closed', true);

        // Reloading /admin/settings for Mabini Staff reflects persisted schedule values
        $this->actingAs($mabiniStaff)->get('/admin/settings')
            ->assertStatus(200)
            ->assertSee('08:30 AM')
            ->assertSee('05:30 PM')
            ->assertSee('03:00 PM')
            ->assertSee('Closed on this day');
    }

    public function test_accreditation_is_hidden_for_admin_only_and_never_overwritten_when_omitted(): void
    {
        ClinicSetting::set('clinic_accreditation', 'BAI-KEEP-2026');
        ClinicSetting::set('accreditation', 'BAI-KEEP-2026');

        $super = $this->createAdmin(null);
        $mabiniStaff = $this->createAdmin('Mabini Branch');

        // Top-level Admin does NOT see Accreditation / License No. field
        $this->actingAs($super)->get('/admin/settings')
            ->assertStatus(200)
            ->assertDontSee('Accreditation / License No.')
            ->assertDontSee('name="accreditation"', false);

        // Mabini Branch Staff DOES see and can edit it
        $this->actingAs($mabiniStaff)->get('/admin/settings')
            ->assertStatus(200)
            ->assertSee('Accreditation / License No.')
            ->assertSee('name="accreditation"', false)
            ->assertSee('BAI-KEEP-2026');

        // Admin submitting Clinic Information without accreditation preserves existing stored value
        $this->actingAs($super)->post('/admin/settings/clinic', [
            'clinic_name' => 'BK Petaholic',
            'email'       => 'info@bk.test',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('BAI-KEEP-2026', ClinicSetting::get('clinic_accreditation'));
        $this->assertSame('BAI-KEEP-2026', ClinicSetting::get('accreditation'));

        // Staff CAN update accreditation cleanly
        $this->actingAs($mabiniStaff)->post('/admin/settings/clinic', [
            'clinic_name'   => 'BK Petaholic',
            'email'         => 'info@bk.test',
            'accreditation' => 'BAI-STAFF-2027',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('BAI-STAFF-2027', ClinicSetting::get('clinic_accreditation'));
    }
}
