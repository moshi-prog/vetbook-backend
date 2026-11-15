<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** table => [index name => columns] */
    private function indexes(): array
    {
        return [
            'appointments' => [
                'appointments_branch_status_index' => ['branch', 'status'],
                'appointments_appointment_date_index' => ['appointment_date'],
                'appointments_created_at_index' => ['created_at'],
            ],
            'pets' => [
                'pets_species_index' => ['species'],
                'pets_created_at_index' => ['created_at'],
            ],
            'users' => [
                'users_role_created_at_index' => ['role', 'created_at'],
            ],
            'medical_records' => [
                'medical_records_consultation_date_index' => ['consultation_date'],
                'medical_records_next_visit_date_index' => ['next_visit_date'],
            ],
        ];
    }

    private function hasIndex(string $table, string $name): bool
    {
        foreach (Schema::getIndexes($table) as $index) {
            if (($index['name'] ?? null) === $name) {
                return true;
            }
        }

        return false;
    }

    public function up(): void
    {
        foreach ($this->indexes() as $table => $defs) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            foreach ($defs as $name => $columns) {
                if (!Schema::hasColumns($table, $columns) || $this->hasIndex($table, $name)) {
                    continue;
                }
                Schema::table($table, function (Blueprint $t) use ($columns, $name) {
                    $t->index($columns, $name);
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->indexes() as $table => $defs) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            foreach ($defs as $name => $columns) {
                if (!$this->hasIndex($table, $name)) {
                    continue;
                }
                Schema::table($table, function (Blueprint $t) use ($name) {
                    $t->dropIndex($name);
                });
            }
        }
    }
};

