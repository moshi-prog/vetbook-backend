<?php

namespace Tests\Feature;

use App\Mail\AccountApprovedMail;
use App\Models\EmailVerificationCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AccountAuthorizationIdTest extends TestCase
{
    use RefreshDatabase;

    private const TINY_PNG_DATA_URI = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    private function createAdmin(?string $branch): User
    {
        return User::create([
            'name'              => $branch ? "{$branch} Staff" : 'System Admin',
            'email'             => uniqid('admin_', true) . '@vetbook.test',
            'password'          => Hash::make('password123'),
            'role'              => 'admin',
            'branch'            => $branch,
            'phone_number'      => '09171234567',
            'email_verified_at' => now(),
            'approval_status'   => 'approved',
        ]);
    }

    public function test_registration_requires_and_stores_compressed_verification_id_when_requested_and_hides_from_json(): void
    {
        Mail::fake();

        // 1. When require_verification_id=true and verification_id_image is omitted -> 400 validation error
        $missingResponse = $this->postJson('/api/register', [
            'name'                    => 'Maria Santos',
            'email'                   => 'maria.id@example.com',
            'password'                => 'password123',
            'password_confirmation'   => 'password123',
            'phone_number'            => '09171234567',
            'require_verification_id' => true,
        ]);

        $missingResponse->assertStatus(400)
            ->assertJsonValidationErrors(['verification_id_image']);

        $this->assertNull(User::where('email', 'maria.id@example.com')->first());

        // 2. When malformed base64 image is provided -> 400 error without persisting partial user
        $badImageResponse = $this->postJson('/api/register', [
            'name'                    => 'Maria Santos',
            'email'                   => 'maria.id@example.com',
            'password'                => 'password123',
            'password_confirmation'   => 'password123',
            'phone_number'            => '09171234567',
            'require_verification_id' => true,
            'verification_id_type'    => 'valid_id',
            'verification_id_image'   => 'not-a-valid-data-uri',
        ]);

        $badImageResponse->assertStatus(400)
            ->assertJsonValidationErrors(['verification_id_image']);

        // 3. Valid PNG data URI succeeds, stores verification_id_image in DB, and hides it from API JSON
        $okResponse = $this->postJson('/api/register', [
            'name'                    => 'Maria Santos',
            'email'                   => 'maria.id@example.com',
            'password'                => 'password123',
            'password_confirmation'   => 'password123',
            'phone_number'            => '09171234567',
            'require_verification_id' => true,
            'verification_id_type'    => 'valid_id',
            'verification_id_image'   => self::TINY_PNG_DATA_URI,
        ]);

        $okResponse->assertStatus(201)
            ->assertJson([
                'needs_verification' => true,
                'email'              => 'maria.id@example.com',
            ]);

        $user = User::where('email', 'maria.id@example.com')->firstOrFail();
        $this->assertNotEmpty($user->verification_id_image);
        $this->assertStringStartsWith('data:image/', $user->verification_id_image);
        $this->assertSame('valid_id', $user->verification_id_type);
        $this->assertSame('pending', $user->approval_status);
        $this->assertArrayNotHasKey('verification_id_image', $user->toArray());

        // 4. Verify OTP endpoint returns user JSON WITHOUT verification_id_image blob
        $codeRecord = EmailVerificationCode::where('email', 'maria.id@example.com')->firstOrFail();
        $verifyResponse = $this->postJson('/api/verify-email', [
            'email' => 'maria.id@example.com',
            'code'  => $codeRecord->code,
        ]);

        $verifyResponse->assertStatus(200)
            ->assertJsonMissingPath('user.verification_id_image');
    }

    public function test_staff_can_view_account_authorizations_inspect_id_and_authorize_or_decline_pet_owner(): void
    {
        Mail::fake();

        $staff = $this->createAdmin('Mabini Branch');

        // Unverified pending user MUST stay hidden from Account Authorizations
        $unverified = User::create([
            'name'                  => 'Unverified Ghost',
            'email'                 => 'ghost@example.com',
            'password'              => Hash::make('password123'),
            'phone_number'          => '09170001111',
            'role'                  => 'pet_owner',
            'email_verified_at'     => null,
            'approval_status'       => 'pending',
            'verification_id_image' => self::TINY_PNG_DATA_URI,
            'verification_id_type'  => 'valid_id',
        ]);

        // Verified applicant awaiting authorization
        $applicant1 = User::create([
            'name'                  => 'Carla Mendoza',
            'email'                 => 'carla.mendoza@example.com',
            'password'              => Hash::make('password123'),
            'phone_number'          => '09172223333',
            'role'                  => 'pet_owner',
            'email_verified_at'     => now(),
            'approval_status'       => 'pending',
            'verification_id_image' => self::TINY_PNG_DATA_URI,
            'verification_id_type'  => 'valid_id',
        ]);

        // Second verified applicant to decline
        $applicant2 = User::create([
            'name'                  => 'Marco Reyes',
            'email'                 => 'marco.reyes@example.com',
            'password'              => Hash::make('password123'),
            'phone_number'          => '09174445555',
            'role'                  => 'pet_owner',
            'email_verified_at'     => now(),
            'approval_status'       => 'pending',
            'verification_id_image' => self::TINY_PNG_DATA_URI,
            'verification_id_type'  => 'selfie',
        ]);

        // Staff opens dedicated Account Authorizations page
        $indexRes = $this->actingAs($staff)->get('/admin/account-authorizations');
        $indexRes->assertStatus(200)
            ->assertSee('Account Authorizations')
            ->assertSee('Carla Mendoza')
            ->assertSee('Marco Reyes')
            ->assertSee('Valid ID')
            ->assertSee('Clear Selfie')
            ->assertSee(self::TINY_PNG_DATA_URI)
            ->assertDontSee('Unverified Ghost');

        // Profile page also shows Verification ID card & image
        $showRes = $this->actingAs($staff)->get("/admin/pet-owners/{$applicant1->id}");
        $showRes->assertStatus(200)
            ->assertSee('Verification ID')
            ->assertSee(self::TINY_PNG_DATA_URI);

        // Staff authorizes Carla -> sets approved, sends AccountApprovedMail
        $approveRes = $this->actingAs($staff)
            ->from('/admin/account-authorizations')
            ->post("/admin/pet-owners/{$applicant1->id}/approve");
        $approveRes->assertRedirect('/admin/account-authorizations');

        $applicant1->refresh();
        $this->assertSame('approved', $applicant1->approval_status);
        $this->assertSame($staff->id, $applicant1->approved_by);
        $this->assertNotNull($applicant1->approved_at);
        Mail::assertSent(AccountApprovedMail::class, fn ($mail) => $mail->hasTo('carla.mendoza@example.com'));

        // Staff declines Marco -> sets rejected and appears under Declined tab
        $rejectRes = $this->actingAs($staff)
            ->from('/admin/account-authorizations')
            ->post("/admin/pet-owners/{$applicant2->id}/reject");
        $rejectRes->assertRedirect('/admin/account-authorizations');

        $applicant2->refresh();
        $this->assertSame('rejected', $applicant2->approval_status);

        $this->actingAs($staff)->get('/admin/account-authorizations?filter=rejected')
            ->assertStatus(200)
            ->assertSee('Marco Reyes')
            ->assertDontSee('Carla Mendoza');
    }

    public function test_admin_without_branch_is_forbidden_from_account_authorizations_and_legacy_register_still_works(): void
    {
        Mail::fake();

        $systemAdmin = $this->createAdmin(null);

        $this->actingAs($systemAdmin)
            ->get('/admin/account-authorizations')
            ->assertStatus(403);

        // Legacy registration without require_verification_id or verification_id_image remains 201
        $legacyRes = $this->postJson('/api/register', [
            'name'                  => 'Legacy Owner',
            'email'                 => 'legacy.owner@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'phone_number'          => '09179998888',
        ]);

        $legacyRes->assertStatus(201)
            ->assertJson([
                'needs_verification' => true,
                'email'              => 'legacy.owner@example.com',
            ]);

        $legacyUser = User::where('email', 'legacy.owner@example.com')->firstOrFail();
        $this->assertNull($legacyUser->verification_id_image);
    }
}
