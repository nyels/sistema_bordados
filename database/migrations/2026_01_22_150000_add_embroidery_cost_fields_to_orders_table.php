<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FASE 3.5: Costo Real de Bordado (Puntadas)
 *
 * Agrega campos para persistir el costo de bordado como SNAPSHOT inmutable.
 *
 * REGLAS CONTABLES:
 * - El costo se calcula UNA VEZ al iniciar producción (CONFIRMED → IN_PRODUCTION)
 * - Se guarda como SNAPSHOT usando la tarifa vigente al momento
 * - NO se recalcula post-producción
 * - Es un costo de fabricación, NO precio ni margen
 *
 * FÓRMULA:
 * embroidery_cost = (total_stitches / 1000) × cost_per_thousand
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Total de puntadas del pedido (suma de todos los diseños vinculados × cantidad)
            $table->unsignedBigInteger('total_stitches_snapshot')
                ->nullable()
                ->after('materials_cost_snapshot')
                ->comment('Puntadas totales al iniciar producción');

            // Costo de bordado calculado: (puntadas/1000) × tarifa
            $table->decimal('embroidery_cost_snapshot', 10, 4)
                ->nullable()
                ->after('total_stitches_snapshot')
                ->comment('Costo de bordado = puntadas/1000 × tarifa');

            // Tarifa por millar vigente al momento del cálculo (para auditoría)
            $table->decimal('cost_per_thousand_snapshot', 10, 4)
                ->nullable()
                ->after('embroidery_cost_snapshot')
                ->comment('Tarifa por millar usada en el cálculo');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'total_stitches_snapshot',
                'embroidery_cost_snapshot',
                'cost_per_thousand_snapshot',
            ]);
        });
    }
};
