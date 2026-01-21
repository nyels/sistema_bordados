<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PRODUCT TYPES - Sistema de tipos de producto para lógica de negocio
     *
     * El campo 'code' es INMUTABLE y se usa para lógica de negocio.
     * El campo 'display_name' es EDITABLE por el usuario sin romper lógica.
     *
     * Códigos predefinidos recomendados:
     * - GARMENT_CUSTOM (Prenda a medida - requiere medidas)
     * - GARMENT_STANDARD (Prenda estándar - no requiere medidas)
     * - ACCESSORY (Accesorio - no requiere medidas)
     * - SERVICE (Servicio - no requiere medidas)
     */
    public function up(): void
    {
        Schema::create('product_types', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Código INMUTABLE para lógica de negocio (no se puede cambiar)
            $table->string('code', 50)->unique();

            // Nombre visible editable por el usuario
            $table->string('display_name', 100);

            // Descripción opcional
            $table->text('description')->nullable();

            // ¿Este tipo de producto requiere medidas del cliente?
            $table->boolean('requires_measurements')->default(false);

            // Para ordenamiento en UI
            $table->integer('sort_order')->default(0);

            // Estado activo/inactivo
            $table->boolean('active')->default(true);

            $table->timestamps();

            // Índices
            $table->index(['active', 'sort_order']);
            $table->index('requires_measurements');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_types');
    }
};
