<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * =============================================================================
 * MIGRACIÓN: EVOLUCIÓN DEL MODELO DE UNIDADES
 * =============================================================================
 *
 * PROPÓSITO:
 * Introducir campo `unit_type` para clasificación semántica explícita de unidades.
 *
 * TIPOS:
 * - canonical   : Unidad canónica de consumo (metro, litro, pieza)
 * - metric_pack : Presentación métrica derivada (rollo 25m, caja 100pz)
 * - logistic    : Unidad logística pura de compra (cono, saco, paquete)
 *
 * REGLAS DE BACKFILL:
 * - is_base = 1                              → canonical
 * - is_base = 0 AND compatible_base_unit_id  → metric_pack
 * - is_base = 0 AND NO compatible_base_unit  → logistic
 *
 * COMPATIBILIDAD:
 * - Campo `is_base` se mantiene como @deprecated (no se elimina)
 * - Sistema funcional durante toda la transición
 *
 * @see \App\Models\Unit
 * @see \App\Enums\UnitType (si se implementa Enum PHP)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Agregar columna unit_type
        Schema::table('units', function (Blueprint $table) {
            $table->enum('unit_type', ['canonical', 'metric_pack', 'logistic'])
                ->default('logistic')
                ->after('is_base')
                ->comment('Tipo semántico: canonical=consumo, metric_pack=presentación métrica, logistic=compra pura');
        });

        // 2. Backfill basado en datos existentes
        // Regla 1: is_base = true → canonical
        DB::table('units')
            ->where('is_base', true)
            ->update(['unit_type' => 'canonical']);

        // Regla 2: is_base = false AND compatible_base_unit_id IS NOT NULL → metric_pack
        DB::table('units')
            ->where('is_base', false)
            ->whereNotNull('compatible_base_unit_id')
            ->update(['unit_type' => 'metric_pack']);

        // Regla 3: is_base = false AND compatible_base_unit_id IS NULL → logistic (ya es default)
        DB::table('units')
            ->where('is_base', false)
            ->whereNull('compatible_base_unit_id')
            ->update(['unit_type' => 'logistic']);

        // 3. Agregar índice para performance en consultas filtradas
        Schema::table('units', function (Blueprint $table) {
            $table->index('unit_type');
        });

        // 4. Marcar is_base como deprecated en comentario de columna (si MySQL lo soporta)
        // Nota: Esto es informativo, el campo sigue funcional
        DB::statement("ALTER TABLE `units` MODIFY COLUMN `is_base` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '@deprecated Use unit_type instead. Will be removed in future version.'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropIndex(['unit_type']);
            $table->dropColumn('unit_type');
        });

        // Restaurar comentario original de is_base
        DB::statement("ALTER TABLE `units` MODIFY COLUMN `is_base` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'true = unidad de consumo (metro, pieza)'");
    }
};
