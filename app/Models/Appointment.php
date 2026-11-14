<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $fillable = [
        'user_id',
        'pet_id',
        'branch',
        'appointment_date',
        'appointment_time',
        'status',
        'reason',
        'notes',
        'veterinarian',
    ];

    protected function casts(): array
    {
        return [
            'appointment_date' => 'date',
        ];
    }

    // Status Constants
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pet()
    {
        return $this->belongsTo(Pet::class);
    }

    /**
     * Format appointment time based on the clinic's preferred display time format ('12' vs '24').
     */
    public function getFormattedTimeAttribute(): string
    {
        if (empty($this->appointment_time)) {
            return '—';
        }

        $format = ClinicSetting::get('pref_time_format', '12');

        try {
            $carbon = Carbon::parse($this->appointment_time);
            return $format === '24' ? $carbon->format('H:i') : $carbon->format('g:i A');
        } catch (\Throwable $e) {
            return (string) $this->appointment_time;
        }
    }
}
