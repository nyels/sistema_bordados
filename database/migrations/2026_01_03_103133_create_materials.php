<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('material_category_id')->constrained('material_categories')->restrictOnDelete();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->string('composition', 100)->nullable();
            $table->text('description')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('activo');
            $table->index('slug');
            $table->index('material_category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};
