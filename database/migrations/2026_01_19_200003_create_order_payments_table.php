<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // === PAGOS DEL PEDIDO ===
        // Permite múltiples pagos parciales
        Schema::create('order_payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();

            // Monto del pago
            $table->decimal('amount', 12, 2);

            // Método de pago
            $table->enum('payment_method', [
                'cash',         // Efectivo
                'transfer',     // Transferencia
                'card',         // Tarjeta
                'other'         // Otro
            ])->default('cash');

            // Referencia externa (número de transferencia, voucher, etc)
            $table->string('reference', 100)->nullable();

            // Notas
            $table->text('notes')->nullable();

            // Auditoría
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('payment_date')->useCurrent();

            $table->timestamps();

            // Índice para reportes de caja
            $table->index(['payment_date', 'payment_method']);
            $table->index(['order_id', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_payments');
    }
};
