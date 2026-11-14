<?php

namespace Tests\Feature;

use App\Models\Pet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AvatarUploadTest extends TestCase
{
    use RefreshDatabase;

    protected string $sampleBase64Png = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    public function test_user_can_upload_avatar_via_base64_json(): void
    {
        Storage::fake('public');

        $user = User::create([
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'password' => bcrypt('password'),
            'role' => 'pet_owner',
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/user/avatar', [
                'avatar' => $this->sampleBase64Png,
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Avatar updated successfully');
        $avatarUrl = $response->json('avatar_url');
        $this->assertSame($this->sampleBase64Png, $avatarUrl);
        $this->assertNotEmpty(Storage::disk('public')->files('avatars'));

        $user->refresh();
        $this->assertSame($this->sampleBase64Png, $user->avatar_url);
    }

    public function test_user_can_upload_avatar_via_multipart(): void
    {
        Storage::fake('public');

        $user = User::create([
            'name' => 'Test User 2',
            'email' => 'testuser2@example.com',
            'password' => bcrypt('password'),
            'role' => 'pet_owner',
        ]);

        $token = JWTAuth::fromUser($user);

        $rawPng = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        $file = UploadedFile::fake()->createWithContent('my_avatar.png', $rawPng);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->post('/api/user/avatar', [
                'avatar' => $file,
            ]);

        $response->assertStatus(200);
        $avatarUrl = $response->json('avatar_url');
        $this->assertNotNull($avatarUrl);
        $this->assertStringStartsWith('data:image/png;base64,', $avatarUrl);
        $this->assertNotEmpty(Storage::disk('public')->files('avatars'));

        $user->refresh();
        $this->assertSame($avatarUrl, $user->avatar_url);
    }

    public function test_updating_profile_preserves_long_data_uri_avatar(): void
    {
        Storage::fake('public');

        // Exceeds VARCHAR(255) to verify TEXT column migration on users table
        $longBase64 = 'data:image/png;base64,' . base64_encode(str_repeat('A', 400));

        $user = User::create([
            'name' => 'Test User 3',
            'email' => 'testuser3@example.com',
            'password' => bcrypt('password'),
            'phone_number' => '09171234567',
            'avatar_url' => $longBase64,
            'role' => 'pet_owner',
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/user/profile', [
                'name' => 'Updated Owner Name',
                'phone_number' => '09181234567',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('user.name', 'Updated Owner Name');
        $response->assertJsonPath('user.phone_number', '09181234567');
        $response->assertJsonPath('user.avatar_url', $longBase64);

        $user->refresh();
        $this->assertSame($longBase64, $user->avatar_url);
        $this->assertGreaterThan(255, strlen($user->avatar_url));
    }

    public function test_pet_avatar_can_be_uploaded_via_base64_json(): void
    {
        Storage::fake('public');

        $user = User::create([
            'name' => 'Pet Owner',
            'email' => 'petowner@example.com',
            'password' => bcrypt('password'),
            'role' => 'pet_owner',
        ]);

        $pet = Pet::create([
            'user_id' => $user->id,
            'name' => 'Milo',
            'species' => 'Dog',
            'breed' => 'Golden Retriever',
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/pets/{$pet->id}/avatar", [
                'avatar' => $this->sampleBase64Png,
            ]);

        $response->assertStatus(200);
        $avatarUrl = $response->json('avatar_url');
        $this->assertSame($this->sampleBase64Png, $avatarUrl);
        $this->assertNotEmpty(Storage::disk('public')->files('pet_avatars'));

        $pet->refresh();
        $this->assertSame($this->sampleBase64Png, $pet->avatar_url);
    }

    public function test_pet_can_be_created_with_long_base64_avatar(): void
    {
        Storage::fake('public');

        $user = User::create([
            'name' => 'Pet Owner 2',
            'email' => 'petowner2@example.com',
            'password' => bcrypt('password'),
            'role' => 'pet_owner',
        ]);

        $token = JWTAuth::fromUser($user);

        // Large valid base64 payload (>255 chars) using 1x1 PNG plus padding chunk in real payload
        $rawPng = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        $largeDataUri = 'data:image/png;base64,' . base64_encode($rawPng . str_repeat("\0", 300));

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/pets', [
                'name' => 'Luna',
                'species' => 'Cat',
                'breed' => 'Persian',
                'age' => 2,
                'avatar' => $largeDataUri,
            ]);

        $response->assertStatus(201);
        $avatarUrl = $response->json('pet.avatar_url');
        $this->assertSame($largeDataUri, $avatarUrl);
        $this->assertGreaterThan(255, strlen($avatarUrl));
        $this->assertNotEmpty(Storage::disk('public')->files('pet_avatars'));

        $pet = Pet::find($response->json('pet.id'));
        $this->assertNotNull($pet);
        $this->assertSame($largeDataUri, $pet->avatar_url);
    }
}
