<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_reset_password_with_valid_email_and_phone(): void
    {
        $user = User::create([
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@vetbook.test',
            'phone_number' => '09123456789',
            'password' => Hash::make('oldpassword123'),
            'role' => 'pet_owner',
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/reset-password', [
            'email' => 'juan@vetbook.test',
            'phone_number' => '09123456789',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Password has been reset successfully. You can now log in with your new password.',
        ]);

        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));

        // Verify user can now log in with the new password
        $loginResponse = $this->postJson('/api/login', [
            'email' => 'juan@vetbook.test',
            'password' => 'newpassword123',
        ]);
        $loginResponse->assertStatus(200);
        $loginResponse->assertJsonStructure(['token', 'user']);
    }

    public function test_email_matching_is_case_insensitive(): void
    {
        $user = User::create([
            'name' => 'Maria Santos',
            'email' => 'maria@vetbook.test',
            'phone_number' => '09187654321',
            'password' => Hash::make('secret123'),
            'role' => 'pet_owner',
        ]);

        $response = $this->postJson('/api/reset-password', [
            'email' => 'MARIA@VETBOOK.TEST',
            'phone_number' => '09187654321',
            'password' => 'brandnewpass',
            'password_confirmation' => 'brandnewpass',
        ]);

        $response->assertStatus(200);
        $user->refresh();
        $this->assertTrue(Hash::check('brandnewpass', $user->password));
    }

    public function test_reset_fails_if_account_does_not_match(): void
    {
        User::create([
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@vetbook.test',
            'phone_number' => '09123456789',
            'password' => Hash::make('oldpassword123'),
            'role' => 'pet_owner',
        ]);

        // Wrong phone number
        $response = $this->postJson('/api/reset-password', [
            'email' => 'juan@vetbook.test',
            'phone_number' => '09999999999',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'No account matches the provided email and phone number.',
        ]);
    }

    public function test_reset_fails_if_validation_fails(): void
    {
        // Invalid phone format (not 09...) and mismatch password
        $response = $this->postJson('/api/reset-password', [
            'email' => 'notanemail',
            'phone_number' => '12345',
            'password' => 'short',
            'password_confirmation' => 'diff',
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors']);
    }
}

