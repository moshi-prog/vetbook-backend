<?php

namespace App\Console\Commands;

use App\Models\ClinicSetting;
use App\Services\AvatarService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CompressStoredImages extends Command
{
    protected $signature = 'vetbook:compress-images';
    protected $description = 'Compress oversized Base64 avatar and logo images stored in PostgreSQL';

    public function handle(): int
    {
        $usersCount = $this->compressColumn('users', 'avatar_url');
        $petsCount = $this->compressColumn('pets', 'avatar_url');
        $clinicCount = $this->compressColumn('clinic_settings', 'value', 'clinic_logo_data');

        if ($clinicCount > 0) {
            ClinicSetting::clearCache();
        }

        $this->info("Compressed stored images — users: {$usersCount}, pets: {$petsCount}, clinic_settings: {$clinicCount}");

        return 0;
    }

    private function compressColumn(string $table, string $column, ?string $keyFilter = null): int
    {
        if (!Schema::hasTable($table)) {
            return 0;
        }

        $updated = 0;

        DB::table($table)
            ->select(['id', $column])
            ->when($keyFilter !== null, fn ($q) => $q->where('key', $keyFilter))
            ->where($column, 'like', 'data:image/%')
            ->whereRaw("LENGTH({$column}) > 65000")
            ->orderBy('id')
            ->chunkById(25, function ($rows) use ($table, $column, &$updated) {
                foreach ($rows as $row) {
                    $original = (string) $row->{$column};
                    $optimized = AvatarService::optimizeDataUri($original);

                    if (strlen($optimized) < strlen($original)) {
                        DB::table($table)
                            ->where('id', $row->id)
                            ->update([$column => $optimized]);
                        $updated++;
                    }
                }
            });

        return $updated;
    }
}
