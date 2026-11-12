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
        $this->assertNotNull($avatarUrl);
        $this->assertStringContainsString('/storage/avatars/user_', $avatarUrl);

        $user->refresh();
        $this->assertSame($avatarUrl, $user->avatar_url);
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
        $this->assertStringContainsString('/storage/avatars/', $avatarUrl);

        $user->refresh();
        $this->assertSame($avatarUrl, $user->avatar_url);
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
        $this->assertNotNull($avatarUrl);
        $this->assertStringContainsString('/storage/pet_avatars/pet_', $avatarUrl);

        $pet->refresh();
        $this->assertSame($avatarUrl, $pet->avatar_url);
    }

    public function test_pet_can_be_created_with_base64_avatar(): void
    {
        Storage::fake('public');

        $user = User::create([
            'name' => 'Pet Owner 2',
            'email' => 'petowner2@example.com',
            'password' => bcrypt('password'),
            'role' => 'pet_owner',
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/pets', [
                'name' => 'Luna',
                'species' => 'Cat',
                'breed' => 'Persian',
                'age' => 2,
                'avatar' => $this->sampleBase64Png,
            ]);

        $response->assertStatus(201);
        $avatarUrl = $response->json('pet.avatar_url');
        $this->assertNotNull($avatarUrl);
        $this->assertStringContainsString('/storage/pet_avatars/', $avatarUrl);

        $petId = $response->json('pet.id');
        $pet = Pet::find($petId);
        $this->assertNotNull($pet);
        $this->assertSame($avatarUrl, $pet->avatar_url);
    }
}
