<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'approval_status')) {
                $table->string('approval_status')->default('approved');
            }
            if (!Schema::hasColumn('users', 'approved_at')) {
                $table->timestamp('approved_at')->nullable();
            }
            if (!Schema::hasColumn('users', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable();
            }
        });

        Schema::table('appointments', function (Blueprint $table) {
            if (!Schema::hasColumn('appointments', 'service_type')) {
                $table->string('service_type')->nullable();
            }
            if (!Schema::hasColumn('appointments', 'duration_minutes')) {
                $table->integer('duration_minutes')->default(30);
            }
            if (!Schema::hasColumn('appointments', 'surgery_status')) {
                $table->string('surgery_status')->nullable();
            }
            if (!Schema::hasColumn('appointments', 'proposed_date')) {
                $table->date('proposed_date')->nullable();
            }
            if (!Schema::hasColumn('appointments', 'proposed_time')) {
                $table->string('proposed_time')->nullable();
            }
            if (!Schema::hasColumn('appointments', 'reminder_24h_sent_at')) {
                $table->timestamp('reminder_24h_sent_at')->nullable();
            }
            if (!Schema::hasColumn('appointments', 'reminder_3h_sent_at')) {
                $table->timestamp('reminder_3h_sent_at')->nullable();
            }
            if (!Schema::hasColumn('appointments', 'attendance_confirmed_at')) {
                $table->timestamp('attendance_confirmed_at')->nullable();
            }
            if (!Schema::hasColumn('appointments', 'batch_booking_id')) {
                $table->string('batch_booking_id')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $cols = array_filter(['approval_status', 'approved_at', 'approved_by'], fn ($c) => Schema::hasColumn('users', $c));
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });

        Schema::table('appointments', function (Blueprint $table) {
            $cols = array_filter([
                'service_type',
                'duration_minutes',
                'surgery_status',
                'proposed_date',
                'proposed_time',
                'reminder_24h_sent_at',
                'reminder_3h_sent_at',
                'attendance_confirmed_at',
                'batch_booking_id',
            ], fn ($c) => Schema::hasColumn('appointments', $c));
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
