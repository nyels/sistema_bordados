<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Trazabilidad explícita de reservas de inventario.
 *
 * PROPÓSITO:
 * Distinguir si el consumo de inventario proviene de:
 * - Producto base (source_type = 'product')
 * - Servicio Extra (source_type = 'extra')
 *
 * CAMPOS AGREGADOS:
 * - source_type: ENUM('product', 'extra') - Origen de la reserva
 * - source_id: ID del producto o extra que genera la reserva
 *
 * REGLAS:
 * - source_type = 'product' → source_id = product_id
 * - source_type = 'extra' → source_id = product_extra_id
 * - Campos nullable para compatibilidad con reservas existentes
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_reservations', function (Blueprint $table) {
            // Tipo de origen: producto base o extra
            $table->enum('source_type', ['product', 'extra'])
                ->default('product')
                ->after('material_variant_id')
                ->comment('Origen de la reserva: product o extra');

            // ID del origen (product_id o product_extra_id según source_type)
            $table->unsignedBigInteger('source_id')
                ->nullable()
                ->after('source_type')
                ->comment('ID del producto o extra que genera la reserva');

            // Índice para auditoría y reportes
            $table->index(['source_type', 'source_id'], 'idx_reservation_source');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_reservations', function (Blueprint $table) {
            $table->dropIndex('idx_reservation_source');
            $table->dropColumn(['source_type', 'source_id']);
        });
    }
};
