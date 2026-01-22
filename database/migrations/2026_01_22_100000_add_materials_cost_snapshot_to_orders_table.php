<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GATE INVENTARIO - Snapshot de costo interno
 *
 * Campo inmutable que registra el costo de materiales al momento
 * de iniciar producción (CONFIRMED → IN_PRODUCTION).
 * Calculado usando average_cost vigente de cada MaterialVariant.
 * NO expuesto a UX. Solo para auditoría y reportes internos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('materials_cost_snapshot', 15, 4)
                ->nullable()
                ->after('balance')
                ->comment('Costo de materiales al iniciar produccion - INMUTABLE');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('materials_cost_snapshot');
        });
    }
};
