<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Veterinarian extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'license_number',
        'specialization',
        'phone',
        'email',
        'branch',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Scope to only active veterinarians.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to veterinarians available for a specific branch (including roving/all-branch vets).
     */
    public function scopeForBranch(Builder $query, ?string $branch): Builder
    {
        if (empty($branch) || $branch === 'All Branches') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($branch) {
            $q->where('branch', $branch)
              ->orWhereNull('branch')
              ->orWhere('branch', '')
              ->orWhere('branch', 'All Branches');
        });
    }
}

