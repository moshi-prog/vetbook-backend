<?php

namespace Tests\Feature;

use App\Models\ClinicSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BranchApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_branches_returns_default_hotlines_when_unset(): void
    {
        $response = $this->getJson('/api/branches');

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'data' => [
                [
                    'id' => 'mabini',
                    'name' => 'Mabini Branch',
                    'address' => '142 Mabini St., Poblacion, Mabini, Batangas',
                    'addressSummary' => '142 Mabini St., Poblacion, Mabini',
                    'hoursWeekday' => 'Mon–Sat: 9:00 AM – 6:00 PM',
                    'hoursSunday' => 'Sun: 10:00 AM – 4:00 PM',
                    'phone' => '0917-123-4567',
                    'dialUrl' => 'tel:09171234567',
                ],
                [
                    'id' => 'batangas_city',
                    'name' => 'Batangas City Branch',
                    'address' => 'N.F. Almario Bldg, Kumintang Ilaya, Batangas City',
                    'addressSummary' => 'N.F. Almario Bldg, Kumintang Ilaya',
                    'hoursWeekday' => 'Mon–Sat: 9:00 AM – 6:00 PM',
                    'hoursSunday' => 'Sun: 10:00 AM – 4:00 PM',
                    'phone' => '0918-765-4321',
                    'dialUrl' => 'tel:09187654321',
                ],
            ],
        ]);
    }

    private function admin(?string $branch): User
    {
        return User::create([
            'name' => 'Admin ' . ($branch ?? 'Super'),
            'email' => uniqid() . '@vetbook.test',
            'password' => 'secret123',
            'role' => 'admin',
            'branch' => $branch,
            'phone_number' => '09000000000',
        ]);
    }

    public function test_branch_hotlines_ignore_admin_phones_and_clinic_phone(): void
    {
        $this->admin('Mabini Branch');
        $this->admin(null);
        ClinicSetting::set('clinic_phone', '0919-444-3322');

        $response = $this->getJson('/api/branches');
        $response->assertJsonPath('data.0.phone', '0917-123-4567');
        $response->assertJsonPath('data.1.phone', '0918-765-4321');
    }

    public function test_response_is_not_cacheable(): void
    {
        $this->getJson('/api/branches')->assertHeader('Cache-Control');
        $this->assertStringContainsString('no-store', $this->getJson('/api/branches')->headers->get('Cache-Control'));
    }

    public function test_saving_mabini_does_not_change_batangas_and_vice_versa(): void
    {
        $super = $this->admin(null);
        ClinicSetting::set('clinic_phone', '0919-000-0000');

        $this->actingAs($super)->post('/admin/settings/branches/mabini', ['phone' => '0999-111-2233'])->assertSessionHasNoErrors();
        $r = $this->getJson('/api/branches');
        $r->assertJsonPath('data.0.phone', '0999-111-2233');
        $r->assertJsonPath('data.1.phone', '0918-765-4321');

        $this->actingAs($super)->post('/admin/settings/branches/batangas', ['phone' => '0922-888-9900']);
        $r = $this->getJson('/api/branches');
        $r->assertJsonPath('data.0.phone', '0999-111-2233');
        $r->assertJsonPath('data.1.phone', '0922-888-9900');
        $r->assertJsonPath('data.1.dialUrl', 'tel:09228889900');

        $this->assertSame('0919-000-0000', ClinicSetting::get('clinic_phone'));
    }

    public function test_update_clinic_does_not_touch_branch_hotlines_or_admin_phones(): void
    {
        $super = $this->admin(null);
        ClinicSetting::set('branch_mabini_phone', '0999-111-2233');
        ClinicSetting::set('branch_batangas_phone', '0922-888-9900');

        $this->actingAs($super)->post('/admin/settings/clinic', [
            'clinic_name' => 'X', 'email' => 'x@y.com', 'phone' => '0911-000-1111',
        ]);

        $this->assertSame('0999-111-2233', ClinicSetting::get('branch_mabini_phone'));
        $this->assertSame('0922-888-9900', ClinicSetting::get('branch_batangas_phone'));
        $this->assertSame('0911-000-1111', ClinicSetting::get('clinic_phone'));
        $this->assertSame('09000000000', $super->fresh()->phone_number);
    }

    public function test_branch_admin_can_only_edit_own_branch_and_phone_is_validated(): void
    {
        $mabini = $this->admin('Mabini Branch');

        $this->actingAs($mabini)->post('/admin/settings/branches/batangas', ['phone' => '0922-888-9900'])->assertStatus(403);
        $this->actingAs($mabini)->post('/admin/settings/branches/mabini', ['phone' => 'abc'])->assertSessionHasErrors('phone');
        $this->actingAs($mabini)->post('/admin/settings/branches/mabini', ['phone' => '0977-111-2222'])->assertSessionHasNoErrors();
        $this->assertSame('0977-111-2222', ClinicSetting::get('branch_mabini_phone'));
        $this->assertNull(ClinicSetting::get('branch_batangas_phone'));
    }

    public function test_branch_admin_can_update_operating_schedule_independently(): void
    {
        $mabini = $this->admin('Mabini Branch');
        $batangas = $this->admin('Batangas City Branch');

        $this->actingAs($mabini)->post('/admin/settings/clinic', [
            'clinic_name' => 'BK Clinic',
            'email' => 'info@bk.test',
            'phone' => '0917-123-4567',
            'hours_weekday' => '8:00 AM – 5:00 PM',
            'hours_saturday' => '8:00 AM – 5:00 PM',
            'hours_sunday' => '9:00 AM – 3:00 PM',
        ])->assertSessionHasNoErrors();

        $this->assertSame('8:00 AM – 5:00 PM', ClinicSetting::get('branch_mabini_hours_weekday'));
        $this->assertSame('8:00 AM – 5:00 PM', ClinicSetting::get('branch_mabini_hours_saturday'));
        $this->assertSame('9:00 AM – 3:00 PM', ClinicSetting::get('branch_mabini_hours_sunday'));
        $this->assertNull(ClinicSetting::get('branch_batangas_hours_weekday'));

        $r = $this->getJson('/api/branches');
        $r->assertJsonPath('data.0.hoursWeekday', 'Mon–Sat: 8:00 AM – 5:00 PM');
        $r->assertJsonPath('data.0.hoursSunday', 'Sun: 9:00 AM – 3:00 PM');
        // Batangas should still have its defaults
        $r->assertJsonPath('data.1.hoursWeekday', 'Mon–Sat: 9:00 AM – 6:00 PM');
        $r->assertJsonPath('data.1.hoursSunday', 'Sun: 10:00 AM – 4:00 PM');

        // Now Batangas admin updates with differing Saturday and closed Sunday
        $this->actingAs($batangas)->post('/admin/settings/clinic', [
            'clinic_name' => 'BK Clinic',
            'email' => 'info@bk.test',
            'phone' => '0917-123-4567',
            'hours_weekday' => '8:00 AM – 5:00 PM',
            'hours_saturday' => '9:00 AM – 1:00 PM',
            'hours_sunday' => 'Closed',
        ])->assertSessionHasNoErrors();

        $r2 = $this->getJson('/api/branches');
        $r2->assertJsonPath('data.0.hoursWeekday', 'Mon–Sat: 8:00 AM – 5:00 PM');
        $r2->assertJsonPath('data.0.hoursSunday', 'Sun: 9:00 AM – 3:00 PM');
        $r2->assertJsonPath('data.1.hoursWeekday', 'Mon–Fri: 8:00 AM – 5:00 PM | Sat: 9:00 AM – 1:00 PM');
        $r2->assertJsonPath('data.1.hoursSunday', 'Sun: Closed');
    }

    public function test_get_branches_includes_clinic_profile_and_logo_url(): void
    {
        Storage::fake('public');

        $super = $this->admin(null);

        // Initially no logo
        $r1 = $this->getJson('/api/branches');
        $r1->assertStatus(200);
        $r1->assertJsonPath('clinic.name', 'BK Petaholic Animal Clinic');
        $r1->assertJsonPath('clinic.email', 'info@bkpetaholic.com');
        $r1->assertJsonPath('clinic.accreditation', 'BAI-RVD-2024-0089');
        $this->assertNull($r1->json('clinic.logo_url'));

        // Upload logo and update clinic info
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        $file = UploadedFile::fake()->createWithContent('clinic_logo.png', $png);

        $this->actingAs($super)->post('/admin/settings/clinic', [
            'clinic_name' => 'VetBook Animal Hospital',
            'email' => 'contact@vetbook.ph',
            'phone' => '0917-111-2222',
            'tagline' => 'Leading veterinary care in Batangas',
            'accreditation' => 'BAI-RVD-2026-9999',
            'logo' => $file,
        ])->assertSessionHasNoErrors();

        $savedLogo = ClinicSetting::get('clinic_logo');
        $this->assertNotNull($savedLogo);
        Storage::disk('public')->assertExists($savedLogo);

        $r2 = $this->getJson('/api/branches');
        $r2->assertStatus(200);
        $r2->assertJsonPath('clinic.name', 'VetBook Animal Hospital');
        $r2->assertJsonPath('clinic.tagline', 'Leading veterinary care in Batangas');
        $r2->assertJsonPath('clinic.email', 'contact@vetbook.ph');
        $r2->assertJsonPath('clinic.accreditation', 'BAI-RVD-2026-9999');
        $this->assertStringContainsString('storage/' . $savedLogo, $r2->json('clinic.logo_url'));
    }

    public function test_super_admin_can_save_cropped_base64_logo_and_sidebar_renders_it(): void
    {
        Storage::fake('public');

        $super = $this->admin(null);

        $base64Png = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $this->actingAs($super)->post('/admin/settings/clinic', [
            'clinic_name' => 'Cropped Vet Clinic',
            'email' => 'cropped@vetbook.ph',
            'phone' => '0917-000-1111',
            'cropped_logo' => $base64Png,
        ])->assertSessionHasNoErrors();

        $savedLogo = ClinicSetting::get('clinic_logo');
        $this->assertNotNull($savedLogo);
        $this->assertStringStartsWith('clinic/logo_', $savedLogo);
        $this->assertStringEndsWith('.png', $savedLogo);
        Storage::disk('public')->assertExists($savedLogo);

        // Sidebar renders the custom logo
        $view = $this->actingAs($super)->get('/admin/settings');
        $view->assertStatus(200);
        $view->assertSee('storage/' . $savedLogo);
        $view->assertSee('id="triggerLogoPickerBtn"', false);
        $view->assertSee('id="cropperModal"', false);
    }

    public function test_branch_admin_cannot_upload_or_modify_clinic_logo(): void
    {
        Storage::fake('public');

        ClinicSetting::set('clinic_logo', 'clinic/original_logo.png');

        $mabini = $this->admin('Mabini Branch');

        $base64Png = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        // Branch admin tries to submit cropped logo
        $this->actingAs($mabini)->post('/admin/settings/clinic', [
            'clinic_name' => 'Mabini Branch Admin Edit',
            'email' => 'mabini@vetbook.ph',
            'phone' => '0917-000-1111',
            'cropped_logo' => $base64Png,
        ])->assertSessionHasNoErrors();

        // Logo remains untouched
        $this->assertSame('clinic/original_logo.png', ClinicSetting::get('clinic_logo'));

        // Branch admin views settings: shows read-only and no cropper modal or trigger button
        $view = $this->actingAs($mabini)->get('/admin/settings');
        $view->assertStatus(200);
        $view->assertSee('Read-Only');
        $view->assertSee('managed exclusively by Super Admin');
        $view->assertDontSee('id="triggerLogoPickerBtn"', false);
        $view->assertDontSee('id="cropperModal"', false);
    }

    public function test_logo_url_uses_https_on_ngrok_or_forwarded_https(): void
    {
        ClinicSetting::set('clinic_logo', 'clinic/sample.png');

        // Request with X-Forwarded-Proto: https
        $r1 = $this->withHeaders(['X-Forwarded-Proto' => 'https'])->getJson('/api/branches');
        $r1->assertStatus(200);
        $this->assertStringStartsWith('https://', $r1->json('clinic.logo_url'));

        // Logo URL containing ngrok
        ClinicSetting::set('clinic_logo', 'http://vetbook-test.ngrok-free.app/storage/clinic/sample.png');
        $r2 = $this->getJson('/api/branches');
        $r2->assertStatus(200);
        $this->assertSame('https://vetbook-test.ngrok-free.app/storage/clinic/sample.png', $r2->json('clinic.logo_url'));
    }

    public function test_branches_endpoint_parses_leading_zero_and_saturday_schedule_correctly(): void
    {
        ClinicSetting::set('clinic_hours_weekday', '09:00 AM - 012:00 PM');
        ClinicSetting::set('clinic_hours_saturday', 'Sat: 09:00 AM - 03:00 PM');

        $response = $this->getJson('/api/branches');
        $response->assertStatus(200);

        $mabini = collect($response->json('data'))->firstWhere('id', 'mabini');
        $this->assertNotNull($mabini);
        $this->assertSame('09:00', $mabini['weekday_open']);
        $this->assertSame('12:00', $mabini['weekday_close']);
        $this->assertSame('09:00', $mabini['saturday_open']);
        $this->assertSame('15:00', $mabini['saturday_close']);
        $this->assertFalse($mabini['saturday_closed']);
    }
}