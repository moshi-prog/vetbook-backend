<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalRecord extends Model
{
    protected $fillable = [
        'pet_id',
        'veterinarian',
        'consultation_date',
        'diagnosis',
        'treatment',
        'vaccinations',
        'prescriptions',
        'notes',
        'next_visit_date',
    ];

    protected function casts(): array
    {
        return [
            'consultation_date' => 'date',
            'next_visit_date' => 'date',
        ];
    }

    public function pet()
    {
        return $this->belongsTo(Pet::class);
    }
}
