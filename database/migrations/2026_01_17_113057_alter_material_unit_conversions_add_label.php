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
        Schema::table('material_unit_conversions', function (Blueprint $table) {
            // Eliminar la restricción de unicidad para permitir múltiples presentaciones (ej. Caja 12 y Caja 50)
            $table->dropUnique('material_unit_unique');

            // Agregar campo para etiqueta de presentación (ej. "Caja Master", "Paquete Chico")
            $table->string('label', 100)->nullable()->after('to_unit_id');
        });
    }

    public function down(): void
    {
        Schema::table('material_unit_conversions', function (Blueprint $table) {
            $table->unique(['material_id', 'from_unit_id'], 'material_unit_unique');
            $table->dropColumn('label');
        });
    }
};
