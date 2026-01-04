<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('slug', 50)->unique();
            $table->text('description')->nullable();
            $table->foreignId('base_unit_id')->constrained('units')->restrictOnDelete();
            $table->boolean('has_color')->default(true);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('activo');
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_categories');
    }
};
