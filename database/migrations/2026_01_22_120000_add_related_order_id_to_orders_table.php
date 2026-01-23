<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Agregar campo related_order_id para trazabilidad POST-VENTA
 *
 * CONTEXTO:
 * - Un pedido puede "relacionarse" con otro pedido terminado (READY/DELIVERED)
 * - Esto NO es un anexo (que usa order_parent_id y es subordinado)
 * - Es una relación informativa: "Este pedido fue creado porque el cliente
 *   solicitó algo adicional después de entregar PED-XXXX"
 *
 * DIFERENCIAS:
 * | Campo            | Propósito                      | Jerarquía |
 * |------------------|--------------------------------|-----------|
 * | order_parent_id  | Anexos (antes de producción)   | Padre→Hijo|
 * | related_order_id | Post-venta (después de entrega)| Solo ref  |
 *
 * REGLAS DE NEGOCIO:
 * - related_order_id solo acepta pedidos en READY o DELIVERED
 * - El pedido relacionado NO se modifica (es histórico)
 * - El nuevo pedido es completamente independiente (inventario, producción, etc.)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('related_order_id')
                ->nullable()
                ->after('order_parent_id')
                ->constrained('orders')
                ->nullOnDelete()
                ->comment('Pedido original relacionado (post-venta). Solo READY/DELIVERED.');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['related_order_id']);
            $table->dropColumn('related_order_id');
        });
    }
};
