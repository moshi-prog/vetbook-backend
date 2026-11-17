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

    public function test_super_admin_sees_all_branch_hotlines_addresses_and_edit_modals(): void
    {
        $super = $this->createAdmin(null);

        $response = $this->actingAs($super)->get('/admin/settings');
        $response->assertStatus(200);
        $html = $response->getContent();

        // Tab 1 editable inputs for Super Admin (including visible phone input)
        $this->assertMatchesRegularExpression('/<input[^>]*type="text"[^>]*name="phone"/', $html);
        $response->assertSee('General Clinic Hotline');
        $response->assertSee('Mabini Branch Support Hotline');
        $response->assertSee('Batangas City Branch Support Hotline');
        $response->assertSee('name="branch_mabini_phone"', false);
        $response->assertSee('name="branch_batangas_phone"', false);
        $response->assertSee('name="branch_mabini_address"', false);
        $response->assertSee('name="branch_batangas_address"', false);

        // Tab 2 Edit buttons, Add New Branch, and modals for both branches with editable address input
        $response->assertSee('Add New Branch');
        $response->assertSee('data-bs-target="#editBranchModal-mabini"', false);
        $response->assertSee('data-bs-target="#editBranchModal-batangas"', false);
        $response->assertSee('id="editBranchModal-mabini"', false);
        $response->assertSee('id="editBranchModal-batangas"', false);
        $response->assertSee('name="address"', false);

        // Tab 5 & 6 Super Admin exclusive sections & clarified helper text
        $response->assertSee('data-tab="admins"', false);
        $response->assertSee('Change Password');
        $response->assertSee('Personal staff contact only. To update your branch clinic hotline for pet owners and the AI chatbot, use Clinic Information or Branches.');
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
        $resMabini->assertSee('Branch address is managed exclusively by Super Admin');

        // Tab 2: Edit only on Mabini branch, no address input inside modal, no Super Admin branch actions
        $resMabini->assertSee('data-bs-target="#editBranchModal-mabini"', false);
        $resMabini->assertDontSee('data-bs-target="#editBranchModal-batangas"', false);
        $resMabini->assertSee('id="editBranchModal-mabini"', false);
        $resMabini->assertDontSee('id="editBranchModal-batangas"', false);
        $resMabini->assertDontSee('name="address"', false);
        $resMabini->assertSee('Address can only be modified by Super Admin');
        $resMabini->assertDontSee('Add New Branch');
        $resMabini->assertDontSee('Deactivate');

        // Tab 5 Admin Users & Change Password hidden for Branch Admin, email remains editable
        $resMabini->assertDontSee('data-tab="admins"', false);
        $resMabini->assertDontSee('Change Password');
        $resMabini->assertSee('name="email"', false);

        // Batangas City Branch Admin symmetry
        $batangasAdmin = $this->createAdmin('Batangas City Branch');
        $resBatangas = $this->actingAs($batangasAdmin)->get('/admin/settings');
        $resBatangas->assertStatus(200);
        $resBatangas->assertSee('name="branch_batangas_phone"', false);
        $resBatangas->assertDontSee('name="branch_mabini_phone"', false);
        $resBatangas->assertSee('data-bs-target="#editBranchModal-batangas"', false);
        $resBatangas->assertDontSee('data-bs-target="#editBranchModal-mabini"', false);
        $resBatangas->assertDontSee('name="address"', false);
    }

    public function test_branch_admin_permissions_are_enforced_on_clinic_and_branch_endpoints(): void
    {
        $mabiniAdmin = $this->createAdmin('Mabini Branch');

        // Branch admin CAN update own hotline via /admin/settings/clinic
        $this->actingAs($mabiniAdmin)->post('/admin/settings/clinic', [
            'clinic_name' => 'BK Petaholic',
            'email' => 'info@bk.test',
            'phone' => '+63 917 123 4567',
            'branch_mabini_phone' => '0917-888-1111',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('0917-888-1111', ClinicSetting::get('branch_mabini_phone'));

        // Branch admin CAN update own hotline via /admin/settings/branches/mabini
        $this->actingAs($mabiniAdmin)->post('/admin/settings/branches/mabini', [
            'phone' => '0917-999-2222',
        ])->assertRedirect(route('admin.settings.index') . '#branches')->assertSessionHasNoErrors();
        $this->assertSame('0917-999-2222', ClinicSetting::get('branch_mabini_phone'));

        // Branch admin CANNOT submit other branch phone or any address via /admin/settings/clinic
        $this->actingAs($mabiniAdmin)->post('/admin/settings/clinic', [
            'clinic_name' => 'BK Petaholic',
            'email' => 'info@bk.test',
            'branch_batangas_phone' => '0918-000-0000',
        ])->assertStatus(403);

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

        // Branch admin CANNOT edit another branch via /admin/settings/branches/batangas
        $this->actingAs($mabiniAdmin)->post('/admin/settings/branches/batangas', [
            'phone' => '0918-111-2222',
        ])->assertStatus(403);

        $this->assertNull(ClinicSetting::get('branch_mabini_address'));
        $this->assertNull(ClinicSetting::get('branch_batangas_phone'));
    }

    public function test_super_admin_updates_synchronize_across_settings_api_reports_and_chatbot(): void
    {
        $super = $this->createAdmin(null);

        // 1. Update via Clinic Information tab
        $this->actingAs($super)->post('/admin/settings/clinic', [
            'clinic_name' => 'BK Petaholic',
            'email' => 'info@bk.test',
            'phone' => '0917-555-0000',
            'branch_mabini_phone' => '0917-222-3333',
            'branch_batangas_phone' => '0918-444-5555',
            'branch_mabini_address' => '100 New Mabini Ave., Poblacion, Mabini, Batangas',
            'branch_batangas_address' => 'Suite 202 Almario Tower, Kumintang Ilaya, Batangas City',
        ])->assertRedirect()->assertSessionHasNoErrors();

        // 2. Also test updating individual branch via modal endpoint
        $this->actingAs($super)->post('/admin/settings/branches/batangas', [
            'phone' => '0918-777-8888',
            'address' => '2nd Floor Almario Hub, Kumintang Ilaya, Batangas City',
        ])->assertRedirect(route('admin.settings.index') . '#branches')->assertSessionHasNoErrors();

        // Sync check: Mobile /api/branches
        $api = $this->getJson('/api/branches');
        $api->assertStatus(200)
            ->assertJsonPath('data.0.phone', '0917-222-3333')
            ->assertJsonPath('data.0.address', '100 New Mabini Ave., Poblacion, Mabini, Batangas')
            ->assertJsonPath('data.1.phone', '0918-777-8888')
            ->assertJsonPath('data.1.address', '2nd Floor Almario Hub, Kumintang Ilaya, Batangas City');

        // Sync check: ReportService metadata
        $mabiniReport = ReportService::getClinicMetadata('Mabini Branch');
        $this->assertSame('0917-222-3333', $mabiniReport['phone']);
        $this->assertSame('100 New Mabini Ave., Poblacion, Mabini, Batangas', $mabiniReport['address']);

        $batangasReport = ReportService::getClinicMetadata('Batangas City Branch');
        $this->assertSame('0918-777-8888', $batangasReport['phone']);
        $this->assertSame('2nd Floor Almario Hub, Kumintang Ilaya, Batangas City', $batangasReport['address']);

        // Sync check: Settings view reflects updated addresses
        $this->actingAs($super)->get('/admin/settings')
            ->assertSee('100 New Mabini Ave., Poblacion, Mabini, Batangas')
            ->assertSee('2nd Floor Almario Hub, Kumintang Ilaya, Batangas City');

        // Sync check: ChatbotService system prompt contains live phones & addresses + distinct General Admin Desk + accuracy rules
        $prompt = ChatbotService::buildSystemPrompt();
        $this->assertStringContainsString('100 New Mabini Ave., Poblacion, Mabini, Batangas', $prompt);
        $this->assertStringContainsString('2nd Floor Almario Hub, Kumintang Ilaya, Batangas City', $prompt);
        $this->assertStringContainsString('0917-222-3333', $prompt);
        $this->assertStringContainsString('0918-777-8888', $prompt);
        $this->assertStringContainsString('General Admin Desk: 0917-555-0000', $prompt);
        $this->assertStringContainsString('CONTACT & ADDRESS ACCURACY RULES:', $prompt);
        $this->assertStringContainsString('call Mabini Branch (0917-222-3333) or Batangas City Branch (0918-777-8888)', $prompt);
    }

    public function test_chatbot_omits_stale_default_clinic_phone_when_unset(): void
    {
        ClinicSetting::set('branch_mabini_phone', '0999-111-0001');
        ClinicSetting::set('branch_batangas_phone', '0999-222-0002');

        $prompt = ChatbotService::buildSystemPrompt();

        $this->assertStringContainsString('Mabini Hotline: 0999-111-0001 | Batangas City Hotline: 0999-222-0002', $prompt);
        $this->assertStringNotContainsString('0917-123-4567', $prompt);
        $this->assertStringNotContainsString('General Admin Desk:', $prompt);
    }
}
