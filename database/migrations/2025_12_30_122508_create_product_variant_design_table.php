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
        Schema::create('product_variant_design', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->onDelete('cascade');

            // Vínculo directo a la tabla que mencionaste: design_exports
            $table->foreignId('design_export_id')->constrained('design_exports')->onDelete('cascade');

            // Posición técnica (Pecho, Espalda, etc.)
            $table->foreignId('application_type_id')->constrained('application_types');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variant_design');
    }
};
