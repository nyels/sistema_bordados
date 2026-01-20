<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // === TABLA PRINCIPAL DE PEDIDOS ===
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Número de pedido legible (PED-2024-0001)
            $table->string('order_number', 20)->unique();

            // Cliente
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();

            // Medidas usadas en este pedido (snapshot para auditoría)
            $table->foreignId('client_measurement_id')->nullable()->constrained('client_measurements')->nullOnDelete();

            // Estado del pedido
            $table->enum('status', [
                'draft',        // Borrador
                'confirmed',    // Confirmado
                'in_production',// En producción
                'ready',        // Listo para entrega
                'delivered',    // Entregado
                'cancelled'     // Cancelado
            ])->default('draft');

            // Estado de pago (calculado pero almacenado para queries rápidas)
            $table->enum('payment_status', [
                'pending',  // Sin pago
                'partial',  // Pago parcial
                'paid'      // Pagado completo
            ])->default('pending');

            // Totales
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->decimal('balance', 12, 2)->default(0); // total - amount_paid

            // Fechas importantes
            $table->date('promised_date')->nullable(); // Fecha prometida de entrega
            $table->date('delivered_date')->nullable();

            // Observaciones generales
            $table->text('notes')->nullable();

            // Auditoría
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Índices para reportes
            $table->index(['status', 'created_at']);
            $table->index(['payment_status', 'created_at']);
            $table->index(['cliente_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
