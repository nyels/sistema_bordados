<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega campos específicos para ventas POS
 *
 * Campos:
 * - discount_reason: Motivo del descuento (del catálogo)
 * - discount_type: Tipo de descuento (fixed=$ o percent=%)
 * - discount_value: Valor ingresado del descuento (antes de calcular)
 * - payment_method: Método de pago (efectivo, tarjeta, transferencia)
 * - sold_at: Fecha y hora exacta de la venta
 * - seller_name: Snapshot del nombre del vendedor
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Campos de descuento detallados
            $table->string('discount_reason', 255)->nullable()->after('discount')
                ->comment('Motivo del descuento seleccionado');

            $table->enum('discount_type', ['fixed', 'percent'])->nullable()->after('discount_reason')
                ->comment('Tipo: fixed=$ monto fijo, percent=% porcentaje');

            $table->decimal('discount_value', 12, 2)->nullable()->after('discount_type')
                ->comment('Valor ingresado (ej: 50 para $50 o 10 para 10%)');

            // Método de pago
            $table->string('payment_method', 50)->nullable()->after('payment_status')
                ->comment('efectivo, tarjeta, transferencia');

            // Fecha y hora exacta de venta (con precisión de segundos)
            $table->timestamp('sold_at')->nullable()->after('delivered_date')
                ->comment('Fecha y hora exacta de la venta POS');

            // Snapshot del vendedor (para auditoría rápida sin JOIN)
            $table->string('seller_name', 255)->nullable()->after('created_by')
                ->comment('Nombre del vendedor al momento de la venta');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'discount_reason',
                'discount_type',
                'discount_value',
                'payment_method',
                'sold_at',
                'seller_name',
            ]);
        });
    }
};
