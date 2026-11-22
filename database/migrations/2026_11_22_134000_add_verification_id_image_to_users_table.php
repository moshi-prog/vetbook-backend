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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'verification_id_image')) {
                $table->longText('verification_id_image')->nullable();
            }
            if (!Schema::hasColumn('users', 'verification_id_type')) {
                $table->string('verification_id_type', 40)->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('users', 'verification_id_image')) {
                $columns[] = 'verification_id_image';
            }
            if (Schema::hasColumn('users', 'verification_id_type')) {
                $columns[] = 'verification_id_type';
            }
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
