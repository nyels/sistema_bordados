<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migración para asegurar la integridad del modelo de unidades en materiales.
 *
 * Arquitectura de Unidades:
 * - canonical (consumo): METRO, PIEZA, LITRO - unidad en que se GASTA el material
 * - logistic (compra): CONO, ROLLO, CAJA - unidad en que se COMPRA el material
 * - metric_pack (presentación): ROLLO 50M - presentación con cantidad fija
 *
 * En Material:
 * - base_unit_id: Unidad de COMPRA (logistic)
 * - consumption_unit_id: Unidad de CONSUMO (canonical)
 * - conversion_factor: 1 base_unit = X consumption_unit
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Asegurar que consumption_unit_id existe en materials
        if (!Schema::hasColumn('materials', 'consumption_unit_id')) {
            Schema::table('materials', function (Blueprint $table) {
                $table->unsignedBigInteger('consumption_unit_id')->nullable()->after('base_unit_id');
                $table->foreign('consumption_unit_id')
                    ->references('id')
                    ->on('units')
                    ->nullOnDelete();
            });
        }

        // 2. Asegurar que conversion_factor existe
        if (!Schema::hasColumn('materials', 'conversion_factor')) {
            Schema::table('materials', function (Blueprint $table) {
                $table->decimal('conversion_factor', 15, 4)->nullable()->after('consumption_unit_id');
            });
        }

        // 3. Agregar default_conversion_factor a units para metric_pack
        if (!Schema::hasColumn('units', 'default_conversion_factor')) {
            Schema::table('units', function (Blueprint $table) {
                $table->decimal('default_conversion_factor', 15, 4)->nullable()->after('compatible_base_unit_id')
                    ->comment('Factor predeterminado para presentaciones (metric_pack). Ej: ROLLO 50M = 50');
            });
        }

        // 4. Auto-poblar consumption_unit_id basándose en base_unit.compatible_base_unit_id
        DB::statement("
            UPDATE materials m
            INNER JOIN units u ON m.base_unit_id = u.id
            SET m.consumption_unit_id = u.compatible_base_unit_id
            WHERE m.consumption_unit_id IS NULL
            AND u.compatible_base_unit_id IS NOT NULL
        ");
    }

    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            if (Schema::hasColumn('units', 'default_conversion_factor')) {
                $table->dropColumn('default_conversion_factor');
            }
        });

        Schema::table('materials', function (Blueprint $table) {
            if (Schema::hasColumn('materials', 'consumption_unit_id')) {
                $table->dropForeign(['consumption_unit_id']);
                $table->dropColumn('consumption_unit_id');
            }
        });
    }
};
