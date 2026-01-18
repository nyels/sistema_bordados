<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * ============================================================================
 * MIGRACIÓN: Familia de Medición para Unidades
 * ============================================================================
 *
 * Agrega el campo `measurement_family` para filtrado semántico inteligente.
 *
 * FAMILIAS:
 * - linear: Unidades de longitud (metro, rollo, cono, carrete)
 * - discrete: Unidades de conteo (pieza, paquete, bolsa)
 * - time: Unidades de tiempo (minuto, hora) - no aplicable a materiales
 * - universal: Contenedores genéricos compatibles con todo (caja)
 *
 * REGLA DE NEGOCIO:
 * - Empaques "linear" solo aparecen para categorías con inventario en METRO
 * - Empaques "discrete" solo aparecen para categorías con inventario en PIEZA
 * - Empaques "universal" aparecen para cualquier categoría
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Agregar columna
        Schema::table('units', function (Blueprint $table) {
            $table->enum('measurement_family', ['linear', 'discrete', 'time', 'universal'])
                ->nullable()
                ->after('unit_type')
                ->comment('Familia de medición para filtrado semántico');
        });

        // 2. Actualizar unidades existentes con sus familias
        // Unidades canónicas (inventario)
        DB::table('units')->where('name', 'METRO')->update(['measurement_family' => 'linear']);
        DB::table('units')->where('name', 'PIEZA')->update(['measurement_family' => 'discrete']);
        DB::table('units')->where('name', 'MINUTO')->update(['measurement_family' => 'time']);

        // Unidades logísticas (empaques)
        DB::table('units')->where('name', 'CONO')->update(['measurement_family' => 'linear']);
        DB::table('units')->where('name', 'ROLLO')->update(['measurement_family' => 'linear']);
        DB::table('units')->where('name', 'CAJA')->update(['measurement_family' => 'universal']);
        DB::table('units')->where('name', 'PAQUETE')->update(['measurement_family' => 'discrete']);
    }

    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn('measurement_family');
        });
    }
};
