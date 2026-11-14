<?php

namespace Tests\Feature;

use App\Models\Pet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AdminPetHealthStatusTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(string $role = 'pet_owner'): User
    {
        return User::create([
            'name'         => ucfirst($role) . ' User',
            'email'        => uniqid($role . '_', true) . '@vetbook.test',
            'password'     => bcrypt('password123'),
            'role'         => $role,
            'phone_number' => '09171234567',
        ]);
    }

    private function createPet(User $owner, array $overrides = []): Pet
    {
        return Pet::create(array_merge([
            'user_id' => $owner->id,
            'name'    => 'Milo',
            'species' => 'Dog',
            'breed'   => 'Aspin',
            'age'     => 3,
        ], $overrides));
    }

    public function test_admin_can_toggle_pet_health_status_and_views_reflect_status(): void
    {
        $admin = $this->createUser('admin');
        $owner = $this->createUser('pet_owner');
        $pet   = $this->createPet($owner);

        $this->actingAs($admin)
            ->get(route('admin.pets.show', $pet->id))
            ->assertOk()
            ->assertSee('id="adminHealthSelect"', false)
            ->assertSee('Healthy');

        $this->actingAs($admin)
            ->get(route('admin.pets.index'))
            ->assertOk()
            ->assertSee('Health Status')
            ->assertSee('Healthy');

        $this->actingAs($admin)
            ->from(route('admin.pets.show', $pet->id))
            ->post(route('admin.pets.update-health-status', $pet->id), [
                'health_status' => 'attention',
            ])
            ->assertRedirect(route('admin.pets.show', $pet->id))
            ->assertSessionHas('success', 'Pet health status updated to Needs Attention.');

        $this->assertDatabaseHas('pets', [
            'id'            => $pet->id,
            'health_status' => 'attention',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.pets.index'))
            ->assertOk()
            ->assertSee('Needs Attention');

        $this->actingAs($admin)
            ->from(route('admin.pets.show', $pet->id))
            ->post(route('admin.pets.update-health-status', $pet->id), [
                'health_status' => 'healthy',
            ])
            ->assertRedirect(route('admin.pets.show', $pet->id))
            ->assertSessionHas('success', 'Pet health status updated to Healthy.');

        $this->assertDatabaseHas('pets', [
            'id'            => $pet->id,
            'health_status' => 'healthy',
        ]);
    }

    public function test_admin_health_status_update_validates_allowed_values(): void
    {
        $admin = $this->createUser('admin');
        $owner = $this->createUser('pet_owner');
        $pet   = $this->createPet($owner);

        $this->actingAs($admin)
            ->from(route('admin.pets.show', $pet->id))
            ->post(route('admin.pets.update-health-status', $pet->id), [
                'health_status' => 'critical',
            ])
            ->assertRedirect(route('admin.pets.show', $pet->id))
            ->assertSessionHasErrors('health_status');

        $this->assertDatabaseHas('pets', [
            'id'            => $pet->id,
            'health_status' => 'healthy',
        ]);
    }

    public function test_non_admin_cannot_update_pet_health_status(): void
    {
        $owner = $this->createUser('pet_owner');
        $pet   = $this->createPet($owner);

        $this->actingAs($owner)
            ->post(route('admin.pets.update-health-status', $pet->id), [
                'health_status' => 'attention',
            ])
            ->assertRedirect(route('admin.login'));

        $this->assertDatabaseHas('pets', [
            'id'            => $pet->id,
            'health_status' => 'healthy',
        ]);
    }

    public function test_pet_owner_api_cannot_set_or_override_health_status(): void
    {
        $owner      = $this->createUser('pet_owner');
        $otherOwner = $this->createUser('pet_owner');
        $token      = JWTAuth::fromUser($owner);

        $createResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/pets', [
                'name'               => 'Bella',
                'species'            => 'Dog',
                'medical_conditions' => 'Seasonal allergies',
                'health_status'      => 'attention',
            ])
            ->assertStatus(201);

        $petId = $createResponse->json('pet.id');

        $this->assertDatabaseHas('pets', [
            'id'            => $petId,
            'user_id'       => $owner->id,
            'health_status' => 'healthy',
        ]);

        // Admin marks pet as needing attention
        Pet::where('id', $petId)->update(['health_status' => 'attention']);

        // Owner updates pet details and attempts to spoof health_status and user_id
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/pets/{$petId}", [
                'name'          => 'Bella Updated',
                'health_status' => 'healthy',
                'user_id'       => $otherOwner->id,
            ])
            ->assertOk();

        $this->assertDatabaseHas('pets', [
            'id'            => $petId,
            'user_id'       => $owner->id,
            'name'          => 'Bella Updated',
            'health_status' => 'attention',
        ]);
    }
}

