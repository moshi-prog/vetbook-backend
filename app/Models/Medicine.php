<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Medicine extends Model
{
    protected $fillable = [
        'name',
        'category',
        'species',
        'unit',
        'stock',
        'low_stock_threshold',
        'branch',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'stock' => 'integer',
            'low_stock_threshold' => 'integer',
        ];
    }

    /**
     * Normalized stock availability level: 'out', 'low', or 'in_stock'.
     */
    public function getStockStatusAttribute(): string
    {
        if ($this->stock <= 0) {
            return 'out';
        }

        $threshold = $this->low_stock_threshold ?? 5;
        if ($this->stock <= $threshold) {
            return 'low';
        }

        return 'in_stock';
    }

    /**
     * Scope medicines visible to a specific branch (branch-specific + shared/all-branch stock).
     */
    public function scopeForBranch(Builder $query, ?string $branch): Builder
    {
        if (empty($branch)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($branch) {
            $q->whereNull('branch')
              ->orWhere('branch', $branch);
        });
    }
}
