<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pets', function (Blueprint $table) {
            if (!Schema::hasColumn('pets', 'spayed_neutered')) {
                $table->boolean('spayed_neutered')->default(false)->after('gender');
            }
            if (!Schema::hasColumn('pets', 'microchip_id')) {
                $table->string('microchip_id', 100)->nullable()->after('medical_conditions');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pets', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('pets', 'spayed_neutered')) {
                $cols[] = 'spayed_neutered';
            }
            if (Schema::hasColumn('pets', 'microchip_id')) {
                $cols[] = 'microchip_id';
            }
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};

