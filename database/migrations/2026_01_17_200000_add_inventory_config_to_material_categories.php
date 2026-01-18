<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * =============================================================================
 * MIGRACIÓN: Configuración de Inventario en Categorías
 * =============================================================================
 *
 * Agrega campos para mejorar UX V2:
 * - default_inventory_unit_id: Unidad de inventario por defecto para la categoría
 * - allow_unit_override: Permite que materiales usen una unidad diferente
 *
 * IMPORTANTE: Esta migración NO rompe la lógica existente. Solo agrega
 * configuración opcional para mejorar la experiencia de usuario.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_categories', function (Blueprint $table) {
            // Unidad de inventario por defecto para esta categoría
            // Ejemplo: HILOS → METRO, BOTONES → PIEZA
            $table->foreignId('default_inventory_unit_id')
                ->nullable()
                ->after('description')
                ->constrained('units')
                ->nullOnDelete();

            // Permitir que materiales individuales usen una unidad diferente
            // Si es false, todos los materiales de esta categoría DEBEN usar
            // la unidad por defecto
            $table->boolean('allow_unit_override')
                ->default(true)
                ->after('default_inventory_unit_id');
        });
    }

    public function down(): void
    {
        Schema::table('material_categories', function (Blueprint $table) {
            $table->dropForeign(['default_inventory_unit_id']);
            $table->dropColumn(['default_inventory_unit_id', 'allow_unit_override']);
        });
    }
};
