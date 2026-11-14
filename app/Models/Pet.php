<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pet extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'species',
        'breed',
        'age',
        'gender',
        'spayed_neutered',
        'weight',
        'medical_conditions',
        'microchip_id',
        'health_status',
        'avatar_url',
    ];

    protected function casts(): array
    {
        return [
            'age' => 'integer',
            'weight' => 'decimal:2',
            'spayed_neutered' => 'boolean',
        ];
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function medicalRecords()
    {
        return $this->hasMany(MedicalRecord::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Clinic-controlled health status badge metadata for admin views.
     */
    public function healthBadge(): array
    {
        if (($this->health_status ?? 'healthy') === 'attention') {
            return [
                'is_attention' => true,
                'label'        => 'Needs Attention',
                'bg'           => '#FFF4E5',
                'color'        => '#B26B00',
                'icon'         => 'fa-exclamation-circle',
            ];
        }

        return [
            'is_attention' => false,
            'label'        => 'Healthy',
            'bg'           => '#E8F5E9',
            'color'        => '#2E7D32',
            'icon'         => 'fa-check-circle',
        ];
    }
}
