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
        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('type', ['select', 'color', 'text'])->default('select');
            $table->boolean('is_required')->default(false);
            $table->integer('order')->default(0);
            $table->timestamps();

            // Índices
            $table->index('slug');
        });
        //enum(): Campo que solo acepta valores específicos (select, color, text)
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attributes');
    }
};
