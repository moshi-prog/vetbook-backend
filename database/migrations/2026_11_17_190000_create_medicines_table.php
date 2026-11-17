<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicines', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('species')->default('All Pets');
            $table->string('unit')->default('units');
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('low_stock_threshold')->default(5);
            $table->string('branch')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['name']);
            $table->index(['branch', 'stock']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicines');
    }
};
