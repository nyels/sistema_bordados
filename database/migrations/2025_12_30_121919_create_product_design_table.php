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
        Schema::create('product_design', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('design_id')->constrained('designs')->onDelete('cascade');

            // Muy importante: ¿En qué parte del producto va el bordado?
            // Usamos tu tabla existente 'application_types' (Pecho, Manga, etc.)
            $table->foreignId('application_type_id')->constrained('application_types');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_design');
    }
};
