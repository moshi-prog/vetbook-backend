<?php

namespace Database\Seeders;

use App\Models\Veterinarian;
use Illuminate\Database\Seeder;

class VeterinarianSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vets = [
            [
                'name' => 'Dr. Maria Santos, DVM',
                'license_number' => 'PRC-0089214',
                'specialization' => 'General Veterinary Medicine & Soft Tissue Surgery',
                'phone' => '0917-234-5678',
                'email' => 'dr.santos@bkpetaholic.com',
                'branch' => 'Mabini Branch',
                'is_active' => true,
            ],
            [
                'name' => 'Dr. Juan Reyes, DVM',
                'license_number' => 'PRC-0076543',
                'specialization' => 'Small Animal Internal Medicine & Dermatology',
                'phone' => '0918-345-6789',
                'email' => 'dr.reyes@bkpetaholic.com',
                'branch' => 'Batangas City Branch',
                'is_active' => true,
            ],
            [
                'name' => 'Dr. Alyssa Castillo, DVM',
                'license_number' => 'PRC-0091823',
                'specialization' => 'Veterinary Dentistry & Preventive Care',
                'phone' => '0919-456-7890',
                'email' => 'dr.castillo@bkpetaholic.com',
                'branch' => null, // Available across all branches
                'is_active' => true,
            ],
        ];

        foreach ($vets as $vet) {
            Veterinarian::updateOrCreate(
                ['license_number' => $vet['license_number']],
                $vet
            );
        }
    }
}

