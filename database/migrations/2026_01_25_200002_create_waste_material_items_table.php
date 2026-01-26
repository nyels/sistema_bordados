<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MERMA DE MATERIALES - DETALLE POR ITEM
 *
 * PROPÓSITO:
 * Registra cada material perdido en un evento de merma.
 * Funciona como BOM snapshot para trazabilidad.
 *
 * RELACIÓN:
 * WasteEvent 1:N WasteMaterialItem
 *
 * REGLA DURA: Estos registros son INMUTABLES una vez creados.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waste_material_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // === REFERENCIA AL EVENTO DE MERMA ===
            $table->foreignId('waste_event_id')
                ->constrained('waste_events')
                ->cascadeOnDelete();

            // === REFERENCIA AL MATERIAL ===
            $table->foreignId('material_variant_id')
                ->constrained('material_variants')
                ->restrictOnDelete();

            // === CANTIDAD PERDIDA ===
            // En unidad de consumo del material
            $table->decimal('quantity', 12, 4)
                ->comment('Cantidad perdida en unidad de consumo');

            // === SNAPSHOT DE COSTOS AL MOMENTO DE LA MERMA ===
            $table->decimal('unit_cost_snapshot', 12, 6)
                ->comment('average_cost al momento de la merma');

            $table->decimal('total_cost', 12, 4)
                ->comment('quantity × unit_cost_snapshot');

            // === SNAPSHOT DE UNIDAD ===
            $table->string('unit_symbol', 20)->nullable()
                ->comment('Símbolo de unidad (snapshot)');

            // === NOTAS ESPECÍFICAS DEL ITEM ===
            $table->string('notes', 500)->nullable();

            $table->timestamps();

            // === ÍNDICES ===
            $table->index('waste_event_id');
            $table->index('material_variant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waste_material_items');
    }
};
