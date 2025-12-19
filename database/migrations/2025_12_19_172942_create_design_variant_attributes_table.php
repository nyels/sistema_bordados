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
        Schema::create('design_variant_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('design_variant_id')->constrained()->onDelete('cascade');
            $table->foreignId('attribute_value_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            // Índice compuesto
            $table->unique(['design_variant_id', 'attribute_value_id'], 'variant_attribute_unique');
            $table->index('attribute_value_id');
        });
        //Se especifica nombre corto del índice único porque el nombre automático sería muy largo
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('design_variant_attributes');
    }
};
