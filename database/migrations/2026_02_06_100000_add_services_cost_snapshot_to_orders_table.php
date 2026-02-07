<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega campo para guardar el costo de servicios (extras sin inventario).
 *
 * REGLA DE NEGOCIO:
 * - Los extras sin inventario (consumes_inventory = false) representan servicios
 * - Ejemplos: empaque especial, bordado adicional, mano de obra personalizada
 * - Este costo se suma al total de fabricación pero NO consume materiales
 * - El snapshot se captura al iniciar producción y NO se recalcula
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('services_cost_snapshot', 10, 4)
                ->nullable()
                ->after('cost_per_thousand_snapshot')
                ->comment('Costo de servicios (extras sin inventario) al iniciar producción');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('services_cost_snapshot');
        });
    }
};
