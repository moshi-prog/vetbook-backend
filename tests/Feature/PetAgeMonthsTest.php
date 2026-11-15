<?php

namespace Tests\Feature;

use App\Models\Pet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class PetAgeMonthsTest extends TestCase
{
    use RefreshDatabase;

    public function test_formatted_age_accessor(): void
    {
        $this->assertSame('4 mos', (new Pet(['age' => 0, 'age_months' => 4]))->formatted_age);
        $this->assertSame('1 mo', (new Pet(['age' => null, 'age_months' => 1]))->formatted_age);
        $this->assertSame('1 yr', (new Pet(['age' => 1, 'age_months' => null]))->formatted_age);
        $this->assertSame('3 yrs', (new Pet(['age' => 3]))->formatted_age);
        $this->assertSame('—', (new Pet())->formatted_age);
    }

    public function test_api_persists_age_months_and_validates_range(): void
    {
        $owner = User::create([
            'name' => 'Owner',
            'email' => 'age@vetbook.test',
            'password' => bcrypt('password123'),
            'role' => 'pet_owner',
        ]);
        $token = JWTAuth::fromUser($owner);

        $create = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/pets', [
                'name' => 'Puppy',
                'species' => 'Dog',
                'age' => 0,
                'age_months' => 5,
            ]);
        $create->assertStatus(201);
        $create->assertJsonPath('pet.age_months', 5);

        $petId = $create->json('pet.id');

        $update = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/pets/{$petId}", ['age' => 2, 'age_months' => null]);
        $update->assertStatus(200);
        $this->assertNull(Pet::find($petId)->age_months);
        $this->assertSame(2, Pet::find($petId)->age);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/pets/{$petId}", ['age_months' => 12])
            ->assertStatus(400);
    }
}

