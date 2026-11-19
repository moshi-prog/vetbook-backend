<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicalRecordImage extends Model
{
    protected $fillable = [
        'medical_record_id',
        'uploaded_by_user_id',
        'uploaded_by_role',
        'uploaded_by_name',
        'image_data',
        'caption',
    ];

    protected $appends = ['image_url'];

    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_data;
    }
}
