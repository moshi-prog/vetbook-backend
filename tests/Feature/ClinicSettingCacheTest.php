<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\ClinicSetting;
use App\Models\Pet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ClinicSettingCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_formatted_time_across_many_appointments_queries_settings_at_most_once(): void
    {
        $owner = User::create([
            'name' => 'Owner',
            'email' => 'cache@vetbook.test',
            'password' => bcrypt('password123'),
            'role' => 'pet_owner',
        ]);
        $pet = Pet::create(['user_id' => $owner->id, 'name' => 'Rex', 'species' => 'Dog']);

        ClinicSetting::set('pref_time_format', '24');

        $appointments = collect(range(1, 15))->map(fn ($i) => Appointment::create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
            'branch' => 'Mabini Branch',
            'appointment_date' => now()->addDays($i)->toDateString(),
            'appointment_time' => '14:30',
            'status' => 'pending',
            'reason' => 'Checkup',
        ]));

        ClinicSetting::clearCache();
        DB::flushQueryLog();
        DB::enableQueryLog();

        foreach ($appointments as $appt) {
            $this->assertSame('14:30', $appt->formatted_time);
            ClinicSetting::get('pref_time_format');
            ClinicSetting::getLogoUrl();
        }

        $settingQueries = collect(DB::getQueryLog())
            ->filter(fn ($q) => str_contains($q['query'], 'clinic_settings'))
            ->count();
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(1, $settingQueries);
    }

    public function test_set_and_direct_model_writes_are_reflected_immediately(): void
    {
        $this->assertSame('fallback', ClinicSetting::get('some_key', 'fallback'));

        ClinicSetting::set('some_key', 'one');
        $this->assertSame('one', ClinicSetting::get('some_key'));

        ClinicSetting::set('some_key', 'two');
        $this->assertSame('two', ClinicSetting::get('some_key'));

        ClinicSetting::set('some_key', null);
        $this->assertSame('fallback', ClinicSetting::get('some_key', 'fallback'));

        $row = ClinicSetting::create(['key' => 'direct_key', 'value' => 'a']);
        $this->assertSame('a', ClinicSetting::get('direct_key'));

        $row->update(['value' => 'b']);
        $this->assertSame('b', ClinicSetting::get('direct_key'));

        $row->delete();
        $this->assertNull(ClinicSetting::get('direct_key'));
    }
}

