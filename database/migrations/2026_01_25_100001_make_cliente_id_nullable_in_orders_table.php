<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Hacer cliente_id nullable en orders.
 *
 * JUSTIFICACIÓN:
 * Permite crear pedidos de "Producción para Stock" sin cliente asociado.
 * Estos pedidos se producen para mantener inventario de productos terminados.
 *
 * REGLA DE NEGOCIO:
 * - cliente_id = NULL → Producción para stock (sin cliente)
 * - cliente_id = INT → Pedido normal con cliente
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Hacer cliente_id nullable (permite producción para stock)
            $table->foreignId('cliente_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Revertir: cliente_id NOT NULL
            // ADVERTENCIA: Fallará si hay registros con cliente_id = NULL
            $table->foreignId('cliente_id')->nullable(false)->change();
        });
    }
};
