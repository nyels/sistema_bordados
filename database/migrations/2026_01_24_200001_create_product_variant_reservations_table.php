<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RESERVA DE STOCK DE PRODUCTOS TERMINADOS v2.2
 *
 * PROPÓSITO:
 * Registrar reservas de ProductVariant (producto terminado) para Orders.
 * SEPARADO de InventoryReservation (que es para MaterialVariant/materias primas).
 *
 * REGLAS:
 * - Reservar ≠ descontar
 * - ProductVariant.current_stock NO cambia al reservar
 * - ProductVariant.reserved_stock SÍ cambia
 * - available_stock = current_stock - reserved_stock
 *
 * IDEMPOTENCIA:
 * - Unique constraint en (order_item_id) previene doble reserva
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variant_reservations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Referencias
            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            $table->foreignId('order_item_id')
                ->constrained('order_items')
                ->cascadeOnDelete();

            $table->foreignId('product_variant_id')
                ->constrained('product_variants')
                ->restrictOnDelete();

            // Cantidad reservada
            $table->decimal('quantity', 14, 4);

            // Estado de la reserva
            $table->enum('status', ['reserved', 'released', 'consumed'])
                ->default('reserved');

            // Auditoría
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('consumed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('consumed_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();

            // IDEMPOTENCIA: Un order_item solo puede tener UNA reserva activa
            $table->unique('order_item_id', 'uq_order_item_reservation');

            // Índices para consultas frecuentes
            $table->index(['order_id', 'status'], 'idx_order_reservation_status');
            $table->index(['product_variant_id', 'status'], 'idx_variant_reservation_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_reservations');
    }
};
