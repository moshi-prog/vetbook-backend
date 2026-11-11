<?php

namespace Tests\Feature;

use App\Mail\VerifyEmailCodeMail;
use App\Models\EmailVerificationCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_unverified_user_and_dispatches_otp_email(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/register', [
            'name'                  => 'Carlos Yulo',
            'email'                 => 'carlos@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'phone_number'          => '09171234567',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'needs_verification' => true,
                'email'              => 'carlos@example.com',
            ]);

        // User must be created but unverified
        $user = User::where('email', 'carlos@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNull($user->email_verified_at);

        // Verification code in DB
        $codeRecord = EmailVerificationCode::where('email', 'carlos@example.com')->first();
        $this->assertNotNull($codeRecord);
        $this->assertEquals(6, strlen($codeRecord->code));
        $this->assertTrue($codeRecord->expires_at->isFuture());

        // Email dispatched
        Mail::assertSent(VerifyEmailCodeMail::class, function ($mail) use ($user, $codeRecord) {
            return $mail->hasTo($user->email)
                && $mail->code === $codeRecord->code
                && $mail->name === $user->name;
        });
    }

    public function test_unverified_user_is_blocked_from_login_with_403(): void
    {
        Mail::fake();

        $user = User::create([
            'name'              => 'Unverified User',
            'email'             => 'unverified@example.com',
            'password'          => Hash::make('password123'),
            'phone_number'      => '09170000000',
            'role'              => 'pet_owner',
            'email_verified_at' => null,
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => 'unverified@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'needs_verification' => true,
                'email'              => 'unverified@example.com',
            ]);
    }

    public function test_verify_email_with_valid_code_activates_account_and_returns_token(): void
    {
        $user = User::create([
            'name'              => 'Pending Verification',
            'email'             => 'pending@example.com',
            'password'          => Hash::make('password123'),
            'phone_number'      => '09171112222',
            'role'              => 'pet_owner',
            'email_verified_at' => null,
        ]);

        EmailVerificationCode::create([
            'email'      => 'pending@example.com',
            'code'       => '123456',
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->postJson('/api/verify-email', [
            'email' => 'pending@example.com',
            'code'  => '123456',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'user'])
            ->assertJson([
                'message' => 'Email verified successfully. Welcome to VetBook!',
            ]);

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);

        // Verification code must be deleted after use
        $this->assertDatabaseMissing('email_verification_codes', [
            'email' => 'pending@example.com',
            'code'  => '123456',
        ]);

        // Verified user can now log in normally
        $loginResponse = $this->postJson('/api/login', [
            'email'    => 'pending@example.com',
            'password' => 'password123',
        ]);
        $loginResponse->assertStatus(200);
        $loginResponse->assertJsonStructure(['token', 'user']);
    }

    public function test_verify_email_fails_with_invalid_or_expired_code(): void
    {
        $user = User::create([
            'name'              => 'Test User',
            'email'             => 'test@example.com',
            'password'          => Hash::make('password123'),
            'phone_number'      => '09173334444',
            'role'              => 'pet_owner',
            'email_verified_at' => null,
        ]);

        EmailVerificationCode::create([
            'email'      => 'test@example.com',
            'code'       => '654321',
            'expires_at' => now()->subMinute(), // Expired
        ]);

        // Expired code
        $expiredResponse = $this->postJson('/api/verify-email', [
            'email' => 'test@example.com',
            'code'  => '654321',
        ]);
        $expiredResponse->assertStatus(400);

        // Incorrect code
        $wrongResponse = $this->postJson('/api/verify-email', [
            'email' => 'test@example.com',
            'code'  => '999999',
        ]);
        $wrongResponse->assertStatus(400);

        $user->refresh();
        $this->assertNull($user->email_verified_at);
    }

    public function test_resend_verification_code_respects_60_second_cooldown(): void
    {
        Mail::fake();

        $user = User::create([
            'name'              => 'Resend User',
            'email'             => 'resend@example.com',
            'password'          => Hash::make('password123'),
            'phone_number'      => '09175556666',
            'role'              => 'pet_owner',
            'email_verified_at' => null,
        ]);

        // First resend request succeeds
        $firstResponse = $this->postJson('/api/resend-verification-code', [
            'email' => 'resend@example.com',
        ]);
        $firstResponse->assertStatus(200);

        // Immediate second resend request fails with 429
        $secondResponse = $this->postJson('/api/resend-verification-code', [
            'email' => 'resend@example.com',
        ]);
        $secondResponse->assertStatus(429)
            ->assertJsonStructure(['message', 'retry_after']);

        // After travel beyond 60s, resending succeeds again
        $this->travel(61)->seconds();

        $thirdResponse = $this->postJson('/api/resend-verification-code', [
            'email' => 'resend@example.com',
        ]);
        $thirdResponse->assertStatus(200);
    }

    public function test_registration_when_mail_mailer_is_log_skips_mail_dispatch_and_logs(): void
    {
        config(['mail.default' => 'log']);
        Mail::fake();

        $response = $this->postJson('/api/register', [
            'name'                  => 'Log Mode User',
            'email'                 => 'logmode@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'phone_number'          => '09178889999',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'needs_verification' => true,
                'email'              => 'logmode@example.com',
            ]);

        // Mail send was skipped completely
        Mail::assertNothingSent();

        // Code record exists in DB
        $codeRecord = EmailVerificationCode::where('email', 'logmode@example.com')->first();
        $this->assertNotNull($codeRecord);
    }

    public function test_verify_email_allows_master_demo_code_123456_when_mail_mailer_is_log(): void
    {
        config(['mail.default' => 'log']);

        $user = User::create([
            'name'              => 'Demo Bypass User',
            'email'             => 'demobypass@example.com',
            'password'          => Hash::make('password123'),
            'phone_number'      => '09179998888',
            'role'              => 'pet_owner',
            'email_verified_at' => null,
        ]);

        // No code generated or different code generated
        $response = $this->postJson('/api/verify-email', [
            'email' => 'demobypass@example.com',
            'code'  => '123456',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'user'])
            ->assertJson([
                'message' => 'Email verified successfully. Welcome to VetBook!',
            ]);

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_verify_email_rejects_wrong_code_even_when_mail_mailer_is_log(): void
    {
        config(['mail.default' => 'log']);

        $user = User::create([
            'name'              => 'Demo Reject User',
            'email'             => 'demoreject@example.com',
            'password'          => Hash::make('password123'),
            'phone_number'      => '09177776666',
            'role'              => 'pet_owner',
            'email_verified_at' => null,
        ]);

        $response = $this->postJson('/api/verify-email', [
            'email' => 'demoreject@example.com',
            'code'  => '999999',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Invalid or expired verification code.',
            ]);

        $user->refresh();
        $this->assertNull($user->email_verified_at);
    }
}

