<?php

namespace Tests\Feature;

use App\Models\MedicalRecord;
use App\Models\Medicine;
use App\Models\Pet;
use App\Models\User;
use App\Services\ChatbotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMedicineStockTest extends TestCase
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
            'phone_number' => '09171234567',
        ]);
    }

    private function createPetWithOwner(): Pet
    {
        $owner = User::create([
            'name' => 'Pet Owner',
            'email' => uniqid('owner_', true) . '@vetbook.test',
            'password' => bcrypt('password123'),
            'role' => 'pet_owner',
            'phone_number' => '09181234567',
        ]);

        return Pet::create([
            'user_id' => $owner->id,
            'name' => 'Buddy',
            'species' => 'Dog',
            'breed' => 'Aspin',
            'age' => 3,
        ]);
    }

    public function test_admin_can_view_medical_records_page_with_medicine_stock_modal(): void
    {
        $admin = $this->createAdmin();

        Medicine::create([
            'name' => 'Amoxicillin 250mg',
            'category' => 'Antibiotic',
            'species' => 'All Pets',
            'unit' => 'tablets',
            'stock' => 24,
            'low_stock_threshold' => 5,
            'branch' => null,
        ]);

        $response = $this->actingAs($admin)->get('/admin/medical-records');

        $response->assertStatus(200)
            ->assertSee('Medicine &amp; Stock', false)
            ->assertSee('id="medicineStockModal"', false)
            ->assertSee('Amoxicillin 250mg')
            ->assertSee('In Stock (24)');
    }

    public function test_admin_can_add_medicine_with_initial_stock(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post('/admin/medical-records/medicines', [
            'name' => 'Doxycycline 100mg',
            'category' => 'Antibiotic',
            'species' => 'Dogs',
            'unit' => 'capsules',
            'stock' => 40,
            'low_stock_threshold' => 10,
            'branch' => 'Mabini Branch',
            'notes' => 'Store below 25C',
        ]);

        $response->assertRedirect()
            ->assertSessionHas('success', "Medicine 'Doxycycline 100mg' added with stock of 40.");

        $this->assertDatabaseHas('medicines', [
            'name' => 'Doxycycline 100mg',
            'category' => 'Antibiotic',
            'species' => 'Dogs',
            'unit' => 'capsules',
            'stock' => 40,
            'low_stock_threshold' => 10,
            'branch' => 'Mabini Branch',
        ]);

        // Branch admin cannot create medicine assigned to another branch
        $branchAdmin = $this->createAdmin('Batangas City Branch');
        $this->actingAs($branchAdmin)->post('/admin/medical-records/medicines', [
            'name' => 'Ivermectin',
            'category' => 'Dewormer',
            'stock' => 12,
            'branch' => 'Mabini Branch',
        ])->assertRedirect();

        $this->assertDatabaseHas('medicines', [
            'name' => 'Ivermectin',
            'branch' => 'Batangas City Branch',
        ]);
    }

    public function test_admin_can_increment_decrement_and_set_stock_with_zero_floor(): void
    {
        $admin = $this->createAdmin();
        $med = Medicine::create([
            'name' => 'NexGard Chewable',
            'category' => 'Flea & Tick',
            'species' => 'Dogs',
            'unit' => 'tablets',
            'stock' => 2,
            'low_stock_threshold' => 5,
        ]);

        // Increment +3 -> 5 (still low stock <= 5)
        $this->actingAs($admin)
            ->patchJson("/admin/medical-records/medicines/{$med->id}/stock", ['delta' => 3])
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'stock_status' => 'low',
            ])
            ->assertJsonPath('medicine.stock', 5);

        // Set exact stock -> 15 (in_stock)
        $this->actingAs($admin)
            ->patchJson("/admin/medical-records/medicines/{$med->id}/stock", ['stock' => 15])
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'stock_status' => 'in_stock',
            ])
            ->assertJsonPath('medicine.stock', 15);

        // Decrement by -100 -> clamped at 0 ('out')
        $this->actingAs($admin)
            ->patchJson("/admin/medical-records/medicines/{$med->id}/stock", ['delta' => -100])
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'stock_status' => 'out',
            ])
            ->assertJsonPath('medicine.stock', 0);

        $this->assertSame(0, $med->fresh()->stock);
    }

    public function test_branch_admin_cannot_update_stock_or_delete_another_branch_medicine(): void
    {
        $mabiniAdmin = $this->createAdmin('Mabini Branch');
        $batangasMed = Medicine::create([
            'name' => 'Rabies Vaccine',
            'category' => 'Vaccine',
            'species' => 'All Pets',
            'unit' => 'vials',
            'stock' => 10,
            'branch' => 'Batangas City Branch',
        ]);

        $this->actingAs($mabiniAdmin)
            ->patchJson("/admin/medical-records/medicines/{$batangasMed->id}/stock", ['delta' => -1])
            ->assertStatus(403);

        $this->actingAs($mabiniAdmin)
            ->put("/admin/medical-records/medicines/{$batangasMed->id}", [
                'name' => 'Tampered',
                'stock' => 0,
            ])
            ->assertStatus(403);

        $this->actingAs($mabiniAdmin)
            ->delete("/admin/medical-records/medicines/{$batangasMed->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('medicines', [
            'id' => $batangasMed->id,
            'stock' => 10,
            'name' => 'Rabies Vaccine',
        ]);
    }

    public function test_saving_medical_record_with_dispensed_medicines_decrements_stock_safely(): void
    {
        $admin = $this->createAdmin();
        $pet = $this->createPetWithOwner();

        $med = Medicine::create([
            'name' => 'Drontal Plus',
            'category' => 'Dewormer',
            'species' => 'Dogs',
            'unit' => 'tablets',
            'stock' => 5,
        ]);

        $lowMed = Medicine::create([
            'name' => 'Ear Drops',
            'category' => 'Other',
            'species' => 'All Pets',
            'unit' => 'bottles',
            'stock' => 1,
        ]);

        // Form visible with quick-pick bar
        $this->actingAs($admin)->get('/admin/medical-records/create')
            ->assertStatus(200)
            ->assertSee('Quick-Pick from Medicine Stock')
            ->assertSee('Drontal Plus');

        // Store with structured dispensed_medicines (including requesting more than remaining on $lowMed -> floors at 0)
        $response = $this->actingAs($admin)->post('/admin/medical-records', [
            'pet_id' => $pet->id,
            'veterinarian' => 'Dr. Santos',
            'consultation_date' => '2026-11-17',
            'diagnosis' => 'Intestinal worms',
            'prescriptions' => '• Drontal Plus (Dewormer)',
            'dispensed_medicines' => [
                ['medicine_id' => $med->id, 'quantity' => 2],
                ['medicine_id' => $lowMed->id, 'quantity' => 4],
            ],
        ]);

        $response->assertRedirect(route('admin.medical-records.pet', $pet->id));
        $this->assertSame(3, $med->fresh()->stock);
        $this->assertSame(0, $lowMed->fresh()->stock);

        // Store without dispensed_medicines works identically to before
        $responsePlain = $this->actingAs($admin)->post('/admin/medical-records', [
            'pet_id' => $pet->id,
            'consultation_date' => '2026-11-17',
            'diagnosis' => 'Annual checkup',
        ]);

        $responsePlain->assertRedirect(route('admin.medical-records.pet', $pet->id));
        $this->assertSame(2, MedicalRecord::where('pet_id', $pet->id)->count());
    }

    public function test_chatbot_system_prompt_includes_live_medicine_stock_and_statuses(): void
    {
        // When no medicines exist
        $emptyPrompt = ChatbotService::buildSystemPrompt();
        $this->assertStringContainsString('CLINIC PET MEDICINE INVENTORY & LIVE STOCK AVAILABILITY:', $emptyPrompt);
        $this->assertStringContainsString('not currently shown in our live inventory', $emptyPrompt);

        // Populate medicines covering In Stock, Low Stock, and Out of Stock
        Medicine::create([
            'name' => 'Amoxicillin 250mg',
            'category' => 'Antibiotic',
            'species' => 'Dogs & Cats',
            'unit' => 'tablets',
            'stock' => 34,
            'low_stock_threshold' => 5,
            'branch' => null,
        ]);

        Medicine::create([
            'name' => 'Heartgard Chew',
            'category' => 'Dewormer',
            'species' => 'Dogs',
            'unit' => 'boxes',
            'stock' => 3,
            'low_stock_threshold' => 5,
            'branch' => 'Mabini Branch',
        ]);

        Medicine::create([
            'name' => 'FVRCP Vaccine',
            'category' => 'Vaccine',
            'species' => 'Cats',
            'unit' => 'vials',
            'stock' => 0,
            'low_stock_threshold' => 5,
            'branch' => 'Batangas City Branch',
        ]);

        $prompt = ChatbotService::buildSystemPrompt();

        $this->assertStringContainsString(
            '• Amoxicillin 250mg (Antibiotic | For: Dogs & Cats | Branch: All Branches): In Stock — 34 tablets remaining',
            $prompt
        );
        $this->assertStringContainsString(
            '• Heartgard Chew (Dewormer | For: Dogs | Branch: Mabini Branch): Low Stock — 3 boxes remaining',
            $prompt
        );
        $this->assertStringContainsString(
            '• FVRCP Vaccine (Vaccine | For: Cats | Branch: Batangas City Branch): Out of Stock (0 vials remaining)',
            $prompt
        );
        $this->assertStringContainsString('not currently shown in our live inventory', $prompt);
        $this->assertStringContainsString('NEVER prescribe dosages', $prompt);
    }

    public function test_admin_can_update_and_delete_a_medicine(): void
    {
        $admin = $this->createAdmin();
        $med = Medicine::create([
            'name' => 'Vitamin C Syrup',
            'category' => 'Vitamin',
            'species' => 'All Pets',
            'unit' => 'bottles',
            'stock' => 8,
        ]);

        $this->actingAs($admin)->put("/admin/medical-records/medicines/{$med->id}", [
            'name' => 'Vitamin C + Zinc Syrup',
            'category' => 'Vitamin',
            'species' => 'All Pets',
            'unit' => 'bottles',
            'stock' => 12,
        ])->assertRedirect();

        $this->assertDatabaseHas('medicines', [
            'id' => $med->id,
            'name' => 'Vitamin C + Zinc Syrup',
            'stock' => 12,
        ]);

        $this->actingAs($admin)->delete("/admin/medical-records/medicines/{$med->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('medicines', ['id' => $med->id]);
    }
}
