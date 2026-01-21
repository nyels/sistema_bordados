<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega campos de tipo de producto y medidas a order_items.
     *
     * ARQUITECTURA:
     * - product_type_id: SNAPSHOT del tipo de producto al momento del pedido
     * - requires_measurements: SNAPSHOT del flag para auditoría histórica
     * - client_measurement_id: Medidas usadas SOLO si el producto las requiere
     *
     * Esto permite:
     * 1. Auditoría histórica completa
     * 2. Un pedido puede tener items CON y SIN medidas
     * 3. El usuario puede renombrar tipos sin romper lógica
     */
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // SNAPSHOT del tipo de producto al momento del pedido
            $table->foreignId('product_type_id')
                ->nullable()
                ->after('product_variant_id')
                ->constrained('product_types')
                ->nullOnDelete();

            // SNAPSHOT: ¿Este item requería medidas al momento del pedido?
            $table->boolean('requires_measurements')
                ->default(false)
                ->after('product_type_id');

            // FK a medidas del cliente (solo si requires_measurements = true)
            $table->foreignId('client_measurement_id')
                ->nullable()
                ->after('requires_measurements')
                ->constrained('client_measurements')
                ->nullOnDelete();

            // Índices para reportes
            $table->index(['product_type_id', 'requires_measurements']);
            $table->index('client_measurement_id');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['product_type_id']);
            $table->dropForeign(['client_measurement_id']);
            $table->dropColumn(['product_type_id', 'requires_measurements', 'client_measurement_id']);
        });
    }
};
