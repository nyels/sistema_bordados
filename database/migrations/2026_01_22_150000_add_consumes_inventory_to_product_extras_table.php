<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRACIÓN: Agregar campo consumes_inventory a product_extras
 *
 * PROPÓSITO:
 * Permitir que algunos extras consuman materiales del inventario
 * (Ej: encajes, listones, moños) mientras otros solo son mano de obra.
 *
 * COMPATIBILIDAD:
 * - Default false: extras existentes NO se ven afectados
 * - Solo cuando true se valida inventario
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_extras', function (Blueprint $table) {
            $table->boolean('consumes_inventory')
                ->default(false)
                ->after('minutes_addition')
                ->comment('Si true, este extra consume materiales del inventario');
        });
    }

    public function down(): void
    {
        Schema::table('product_extras', function (Blueprint $table) {
            $table->dropColumn('consumes_inventory');
        });
    }
};
