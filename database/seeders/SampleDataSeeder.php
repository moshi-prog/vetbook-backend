<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\Pet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $laurrence = User::where('email', 'laurrence@gmail.com')->first();
        $yzabelle = User::where('email', 'yzabelle@gmail.com')->first();

        if ($laurrence) {
            // Restore Laurrence's Pets
            $rio = Pet::create([
                'user_id' => $laurrence->id,
                'name' => 'Rio',
                'species' => 'Dog',
                'breed' => 'Golden Retriever',
                'age' => 2,
                'gender' => 'Male',
                'weight' => 26.5,
                'medical_conditions' => 'Mild skin allergies to chicken',
            ]);

            $wanwan = Pet::create([
                'user_id' => $laurrence->id,
                'name' => 'Wan wan',
                'species' => 'Cat',
                'breed' => 'Persian',
                'age' => 1,
                'gender' => 'Female',
                'weight' => 3.8,
                'medical_conditions' => 'None',
            ]);

            $tiki = Pet::create([
                'user_id' => $laurrence->id,
                'name' => 'Tiki',
                'species' => 'Dog',
                'breed' => 'Shih Tzu',
                'age' => 3,
                'gender' => 'Male',
                'weight' => 5.4,
                'medical_conditions' => 'Sensitive stomach',
            ]);

            // Medical Records for Rio
            MedicalRecord::create([
                'pet_id' => $rio->id,
                'veterinarian' => 'Dr. Maria Santos, DVM',
                'consultation_date' => Carbon::now()->subDays(15)->toDateString(),
                'diagnosis' => 'Allergic Dermatitis & Routine Physical',
                'treatment' => 'Antihistamine injection administered and medicated chlorhexidine bath performed.',
                'vaccinations' => 'Rabies booster (Annual), DHPPi+L 5-in-1',
                'prescriptions' => 'Apoquel 5.4mg — 1 tablet daily for 14 days; Chlorhexidine Shampoo 2x weekly',
                'notes' => 'Owner advised to switch to salmon-based hypoallergenic diet. Patient tolerated exam calmly.',
                'next_visit_date' => Carbon::now()->addDays(45)->toDateString(),
            ]);

            // Medical Record for Wan wan
            MedicalRecord::create([
                'pet_id' => $wanwan->id,
                'veterinarian' => 'Dr. Jose Rizal, DVM',
                'consultation_date' => Carbon::now()->subDays(30)->toDateString(),
                'diagnosis' => 'Annual Feline Wellness & FVRCP Vaccination',
                'treatment' => 'Physical exam: heart clear, teeth normal, clear ear canals.',
                'vaccinations' => 'FVRCP 4-in-1, Deworming (Pyrantel Pamoate)',
                'prescriptions' => 'Broadline topical spot-on for parasite control',
                'notes' => 'Healthy young feline in optimal body condition score (BCS 5/9).',
                'next_visit_date' => Carbon::now()->addMonths(6)->toDateString(),
            ]);

            // Medical Record for Tiki
            MedicalRecord::create([
                'pet_id' => $tiki->id,
                'veterinarian' => 'Dr. Maria Santos, DVM',
                'consultation_date' => Carbon::now()->subDays(7)->toDateString(),
                'diagnosis' => 'Acute Gastroenteritis (Mild)',
                'treatment' => 'Subcutaneous fluids 100mL with vitamin B complex.',
                'vaccinations' => 'Kennel Cough (Bordetella bronchiseptica)',
                'prescriptions' => 'Metronidazole 100mg BID for 5 days, Protexin Pro-Kolin probiotic paste 2mL BID',
                'notes' => 'Bland diet (boiled chicken breast and white rice) recommended for 48 hours.',
                'next_visit_date' => Carbon::now()->addDays(14)->toDateString(),
            ]);

            // Appointments for Laurrence
            Appointment::create([
                'user_id' => $laurrence->id,
                'pet_id' => $rio->id,
                'branch' => 'Mabini Branch',
                'appointment_date' => Carbon::now()->addDays(3)->toDateString(),
                'appointment_time' => '10:30',
                'status' => 'approved',
                'reason' => 'Skin Checkup & Follow-up',
                'notes' => 'Follow up on Apoquel treatment.',
                'veterinarian' => 'Dr. Maria Santos, DVM',
            ]);

            Appointment::create([
                'user_id' => $laurrence->id,
                'pet_id' => $wanwan->id,
                'branch' => 'Mabini Branch',
                'appointment_date' => Carbon::now()->addDays(5)->toDateString(),
                'appointment_time' => '14:00',
                'status' => 'pending',
                'reason' => 'Routine Grooming & Nail Trim',
                'notes' => 'Please handle gently, easily startled.',
            ]);

            Appointment::create([
                'user_id' => $laurrence->id,
                'pet_id' => $tiki->id,
                'branch' => 'Batangas City Branch',
                'appointment_date' => Carbon::now()->addDays(8)->toDateString(),
                'appointment_time' => '11:00',
                'status' => 'approved',
                'reason' => 'Vaccination & Wellness',
                'notes' => 'Annual booster shot.',
                'veterinarian' => 'Dr. Jose Rizal, DVM',
            ]);
        }

        if ($yzabelle) {
            // Restore Yzabelle's Pets
            $milo = Pet::create([
                'user_id' => $yzabelle->id,
                'name' => 'Milo',
                'species' => 'Dog',
                'breed' => 'Beagle',
                'age' => 4,
                'gender' => 'Male',
                'weight' => 14.2,
                'medical_conditions' => 'Ear infection prone',
            ]);

            $luna = Pet::create([
                'user_id' => $yzabelle->id,
                'name' => 'Luna',
                'species' => 'Cat',
                'breed' => 'Siamese',
                'age' => 2,
                'gender' => 'Female',
                'weight' => 3.5,
                'medical_conditions' => 'None',
            ]);

            MedicalRecord::create([
                'pet_id' => $milo->id,
                'veterinarian' => 'Dr. Maria Santos, DVM',
                'consultation_date' => Carbon::now()->subDays(10)->toDateString(),
                'diagnosis' => 'Otitis Externa (Bilateral)',
                'treatment' => 'Deep ear flush and cleaner applied.',
                'vaccinations' => 'Rabies Annual',
                'prescriptions' => 'Otomax ear drops — 4 drops each ear BID for 7 days',
                'notes' => 'Keep ears dry after baths.',
                'next_visit_date' => Carbon::now()->addDays(20)->toDateString(),
            ]);

            Appointment::create([
                'user_id' => $yzabelle->id,
                'pet_id' => $milo->id,
                'branch' => 'Batangas City Branch',
                'appointment_date' => Carbon::now()->addDays(4)->toDateString(),
                'appointment_time' => '15:30',
                'status' => 'pending',
                'reason' => 'Ear Re-check',
                'notes' => 'Check if infection cleared.',
            ]);

            Appointment::create([
                'user_id' => $yzabelle->id,
                'pet_id' => $luna->id,
                'branch' => 'Mabini Branch',
                'appointment_date' => Carbon::now()->addDays(7)->toDateString(),
                'appointment_time' => '09:30',
                'status' => 'approved',
                'reason' => 'Annual Dental Checkup',
                'notes' => 'Routine scaling assessment.',
                'veterinarian' => 'Dr. Jose Rizal, DVM',
            ]);
        }
    }
}
