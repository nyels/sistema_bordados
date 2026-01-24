<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('material_unit_conversions', function (Blueprint $table) {
            // Campo para distinguir el modo de conversión
            // 'manual' = Directa (el usuario ingresa el factor directamente)
            // 'por_contenido' = Por Contenido (se calcula factor = qty_contenido * factor_unitario)
            $table->enum('conversion_mode', ['manual', 'por_contenido'])
                  ->default('manual')
                  ->after('label')
                  ->comment('Modo de conversión: manual (directa) o por_contenido (calculada)');
        });

        // Actualizar registros existentes: si tienen intermediate_qty > 0, son "por_contenido"
        DB::statement("
            UPDATE material_unit_conversions
            SET conversion_mode = 'por_contenido'
            WHERE intermediate_qty IS NOT NULL AND intermediate_qty > 0
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('material_unit_conversions', function (Blueprint $table) {
            $table->dropColumn('conversion_mode');
        });
    }
};
