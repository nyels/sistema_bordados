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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->onDelete('cascade');
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Índices para optimización
            $table->index('slug');
            $table->index('parent_id');
            $table->index('is_active');
        });
        /*Explicación de campos importantes:
        foreignId('parent_id'): Permite categorías anidadas (subcategorías)
        constrained('categories'): Crea la relación con la misma tabla
        onDelete('cascade'): Si se elimina una categoría padre, se eliminan sus hijas
        index(): Mejora velocidad de búsqueda en estos campos */
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
