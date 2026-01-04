<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_unit_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('materials')->cascadeOnDelete();
            $table->foreignId('from_unit_id')->constrained('units')->restrictOnDelete();
            $table->foreignId('to_unit_id')->constrained('units')->restrictOnDelete();
            $table->decimal('conversion_factor', 12, 4);
            $table->timestamps();

            $table->unique(['material_id', 'from_unit_id'], 'material_unit_unique');
            $table->index('material_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_unit_conversions');
    }
};
