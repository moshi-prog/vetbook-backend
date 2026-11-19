<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\MedicalRecord;
use App\Models\MedicalRecordImage;
use App\Models\Pet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class MedicalRecordImagesTest extends TestCase
{
    use RefreshDatabase;

    private const TINY_PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';
    private const TINY_PNG_DATA_URI = 'data:image/png;base64,' . self::TINY_PNG_BASE64;

    private function fakePngFile(string $name = 'photo.png'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, (string) base64_decode(self::TINY_PNG_BASE64));
    }

    private function createStaff(?string $branch = 'Mabini Branch'): User
    {
        return User::create([
            'name' => $branch ? "{$branch} Staff" : 'System Admin',
            'email' => uniqid('staff_', true) . '@vetbook.test',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'branch' => $branch,
            'phone_number' => '09171234567',
        ]);
    }

    private function createOwner(): User
    {
        return User::create([
            'name' => 'Pet Owner User',
            'email' => uniqid('owner_', true) . '@vetbook.test',
            'password' => bcrypt('password123'),
            'role' => 'pet_owner',
            'phone_number' => '09181234567',
            'is_approved' => true,
        ]);
    }

    private function createPetAndRecord(User $owner): array
    {
        $pet = Pet::create([
            'user_id' => $owner->id,
            'name' => 'Bantay',
            'species' => 'Dog',
            'breed' => 'Aspin',
            'age' => 3,
        ]);

        $record = MedicalRecord::create([
            'pet_id' => $pet->id,
            'veterinarian' => 'Dr. Reyes',
            'consultation_date' => '2026-11-18',
            'diagnosis' => 'Skin Checkup',
        ]);

        return [$pet, $record];
    }

    public function test_staff_can_upload_up_to_5_images_and_pet_owner_can_view_them_via_api(): void
    {
        $staff = $this->createStaff('Mabini Branch');
        $owner = $this->createOwner();
        [$pet, $record] = $this->createPetAndRecord($owner);
        $token = JWTAuth::fromUser($owner);

        $file1 = $this->fakePngFile('xray1.png');
        $file2 = $this->fakePngFile('xray2.png');

        $response = $this->actingAs($staff)->post("/admin/medical-records/{$record->id}/images", [
            'images' => [$file1, $file2],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $images = MedicalRecordImage::where('medical_record_id', $record->id)->get();
        $this->assertCount(2, $images);
        foreach ($images as $img) {
            $this->assertSame('staff', $img->uploaded_by_role);
            $this->assertSame($staff->name, $img->uploaded_by_name);
            $this->assertStringStartsWith('data:image/', $img->image_data);
            $this->assertSame($img->image_data, $img->image_url);
        }

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $owner->id,
            'type' => 'medical_record',
            'title' => 'New Photo Added to Medical Record',
        ]);

        // Pet owner can view the uploaded photos via both indexByPet and show APIs
        $listRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/pets/{$pet->id}/medical-records")
            ->assertStatus(200);

        $listImages = $listRes->json('medical_records.0.images');
        $this->assertCount(2, $listImages);
        $this->assertSame('staff', $listImages[0]['uploaded_by_role']);
        $this->assertNotEmpty($listImages[0]['image_url']);

        $showRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/medical-records/{$record->id}")
            ->assertStatus(200);

        $this->assertCount(2, $showRes->json('medical_record.images'));
    }

    public function test_pet_owner_can_upload_images_to_own_pet_medical_record_and_staff_can_view_them_on_web(): void
    {
        $staff = $this->createStaff('Mabini Branch');
        $owner = $this->createOwner();
        [, $record] = $this->createPetAndRecord($owner);
        $token = JWTAuth::fromUser($owner);

        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/medical-records/{$record->id}/images", [
                'images' => [self::TINY_PNG_DATA_URI],
            ]);

        $res->assertStatus(201)
            ->assertJsonCount(1, 'images')
            ->assertJsonPath('images.0.uploaded_by_role', 'pet_owner')
            ->assertJsonPath('images.0.uploaded_by_name', $owner->name);

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $staff->id,
            'type' => 'medical_record',
            'title' => 'New Medical Record Photo from Pet Owner',
        ]);

        // Staff views them on show & edit web portal pages
        $this->actingAs($staff)->get("/admin/medical-records/{$record->id}")
            ->assertStatus(200)
            ->assertSee('Medical Record Photos (1 / 5)')
            ->assertSee('Pet Owner')
            ->assertSee($owner->name);

        $this->actingAs($staff)->get("/admin/medical-records/{$record->id}/edit")
            ->assertStatus(200)
            ->assertSee('1 / 5 uploaded')
            ->assertSee('Pet Owner');
    }

    public function test_uploading_more_than_5_images_total_is_rejected_for_both_staff_and_pet_owner(): void
    {
        $staff = $this->createStaff('Mabini Branch');
        $owner = $this->createOwner();
        [, $record] = $this->createPetAndRecord($owner);
        $token = JWTAuth::fromUser($owner);

        // Pre-populate 4 images
        for ($i = 0; $i < 4; $i++) {
            MedicalRecordImage::create([
                'medical_record_id' => $record->id,
                'uploaded_by_user_id' => $staff->id,
                'uploaded_by_role' => 'staff',
                'uploaded_by_name' => $staff->name,
                'image_data' => self::TINY_PNG_DATA_URI,
            ]);
        }

        // Pet owner tries uploading 2 more -> rejected with 422
        $ownerOverRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/medical-records/{$record->id}/images", [
                'images' => [self::TINY_PNG_DATA_URI, self::TINY_PNG_DATA_URI],
            ]);
        $ownerOverRes->assertStatus(422)
            ->assertJsonFragment(['message' => 'A medical record can have at most 5 images. Only 1 slot(s) remaining.']);
        $this->assertSame(4, MedicalRecordImage::where('medical_record_id', $record->id)->count());

        // Staff tries uploading 2 more -> rejected with session error
        $staffOverRes = $this->actingAs($staff)->post("/admin/medical-records/{$record->id}/images", [
            'images' => [
                $this->fakePngFile('extra1.png'),
                $this->fakePngFile('extra2.png'),
            ],
        ]);
        $staffOverRes->assertRedirect();
        $staffOverRes->assertSessionHas('error');
        $this->assertSame(4, MedicalRecordImage::where('medical_record_id', $record->id)->count());

        // Uploading 1 final image succeeds (reaching max 5)
        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/medical-records/{$record->id}/images", [
                'images' => [self::TINY_PNG_DATA_URI],
            ])->assertStatus(201);
        $this->assertSame(5, MedicalRecordImage::where('medical_record_id', $record->id)->count());

        // 6th image is rejected
        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/medical-records/{$record->id}/images", [
                'images' => [self::TINY_PNG_DATA_URI],
            ])->assertStatus(422);
    }

    public function test_other_pet_owner_cannot_upload_or_delete_images_on_another_owners_record(): void
    {
        $owner = $this->createOwner();
        $otherOwner = $this->createOwner();
        [, $record] = $this->createPetAndRecord($owner);
        $otherToken = JWTAuth::fromUser($otherOwner);

        $image = MedicalRecordImage::create([
            'medical_record_id' => $record->id,
            'uploaded_by_user_id' => $owner->id,
            'uploaded_by_role' => 'pet_owner',
            'uploaded_by_name' => $owner->name,
            'image_data' => self::TINY_PNG_DATA_URI,
        ]);

        $this->withHeader('Authorization', "Bearer {$otherToken}")
            ->postJson("/api/medical-records/{$record->id}/images", [
                'images' => [self::TINY_PNG_DATA_URI],
            ])
            ->assertStatus(403);

        $this->withHeader('Authorization', "Bearer {$otherToken}")
            ->deleteJson("/api/medical-records/{$record->id}/images/{$image->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('medical_record_images', ['id' => $image->id]);
    }

    public function test_pet_owner_can_delete_own_uploaded_image_but_cannot_delete_staff_uploaded_image(): void
    {
        $staff = $this->createStaff('Mabini Branch');
        $owner = $this->createOwner();
        [, $record] = $this->createPetAndRecord($owner);
        $token = JWTAuth::fromUser($owner);

        $staffImg = MedicalRecordImage::create([
            'medical_record_id' => $record->id,
            'uploaded_by_user_id' => $staff->id,
            'uploaded_by_role' => 'staff',
            'uploaded_by_name' => $staff->name,
            'image_data' => self::TINY_PNG_DATA_URI,
        ]);

        $ownerImg = MedicalRecordImage::create([
            'medical_record_id' => $record->id,
            'uploaded_by_user_id' => $owner->id,
            'uploaded_by_role' => 'pet_owner',
            'uploaded_by_name' => $owner->name,
            'image_data' => self::TINY_PNG_DATA_URI,
        ]);

        // Pet owner cannot delete staff's photo
        $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/medical-records/{$record->id}/images/{$staffImg->id}")
            ->assertStatus(403);
        $this->assertDatabaseHas('medical_record_images', ['id' => $staffImg->id]);

        // Pet owner can delete own uploaded photo
        $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/medical-records/{$record->id}/images/{$ownerImg->id}")
            ->assertStatus(200)
            ->assertJsonCount(1, 'images');
        $this->assertDatabaseMissing('medical_record_images', ['id' => $ownerImg->id]);

        // Staff can delete staff's photo via web route
        $this->actingAs($staff)->delete("/admin/medical-records/{$record->id}/images/{$staffImg->id}")
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertDatabaseMissing('medical_record_images', ['id' => $staffImg->id]);
    }

    public function test_admin_without_branch_is_blocked_from_medical_record_image_web_routes(): void
    {
        $adminWithoutBranch = $this->createStaff(null);
        $owner = $this->createOwner();
        [, $record] = $this->createPetAndRecord($owner);

        $image = MedicalRecordImage::create([
            'medical_record_id' => $record->id,
            'uploaded_by_user_id' => $owner->id,
            'uploaded_by_role' => 'pet_owner',
            'uploaded_by_name' => $owner->name,
            'image_data' => self::TINY_PNG_DATA_URI,
        ]);

        $this->actingAs($adminWithoutBranch)->post("/admin/medical-records/{$record->id}/images", [
            'images' => [$this->fakePngFile('test.png')],
        ])->assertStatus(403);

        $this->actingAs($adminWithoutBranch)->delete("/admin/medical-records/{$record->id}/images/{$image->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('medical_record_images', ['id' => $image->id]);
    }
}
