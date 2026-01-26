<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MERMA (WASTE) - EVENTO PRINCIPAL
 *
 * DEFINICIÓN CANÓNICA:
 * - Evento físico irreversible
 * - NO es cancelación
 * - NO es ajuste de inventario
 * - NO es editable ni eliminable
 *
 * TIPOS:
 * - material: Merma de materia prima (MaterialVariant)
 * - wip: Merma en proceso (producción fallida)
 * - finished_product: Merma de producto terminado (ProductVariant)
 *
 * REGLA DURA: Este registro es INMUTABLE una vez creado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waste_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // === TIPO DE MERMA ===
            $table->enum('waste_type', ['material', 'wip', 'finished_product'])
                ->comment('Tipo: material, wip, finished_product');

            // === REFERENCIA OPCIONAL A PEDIDO ===
            // Para merma WIP: pedido donde ocurrió la falla
            // Para merma PT: puede venir de un pedido cancelado
            $table->foreignId('order_id')
                ->nullable()
                ->constrained('orders')
                ->nullOnDelete();

            // === REFERENCIA OPCIONAL A VARIANTE PT ===
            // Solo para waste_type = 'finished_product'
            $table->foreignId('product_variant_id')
                ->nullable()
                ->constrained('product_variants')
                ->nullOnDelete();

            // === CANTIDAD DE MERMA (PT) ===
            // Solo para finished_product: cantidad de unidades perdidas
            $table->decimal('quantity', 10, 4)->default(0)
                ->comment('Cantidad de unidades (solo PT)');

            // === COSTO TOTAL DE MERMA ===
            // Calculado: suma de costos de materiales o costo del PT
            $table->decimal('total_cost', 12, 4)->default(0)
                ->comment('Costo total de la merma en MXN');

            // === MOTIVO DE MERMA ===
            $table->string('reason', 500)
                ->comment('Descripción obligatoria del motivo');

            // === EVIDENCIA (opcional) ===
            $table->string('evidence_path')->nullable()
                ->comment('Ruta a imagen/documento de evidencia');

            // === AUDITORÍA ===
            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamps();

            // === ÍNDICES ===
            $table->index('waste_type');
            $table->index('order_id');
            $table->index('product_variant_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waste_events');
    }
};
