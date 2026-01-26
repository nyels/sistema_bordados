<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * v2.6-MIN: Tabla invoices — Contenedor fiscal pasivo.
 *
 * REGLA SELLADA:
 * - Solo estructura de datos
 * - NO triggers, NO cálculos, NO eventos
 * - Relación 1:1 con Order (UNIQUE constraint)
 * - Habilita facturación futura sin deuda técnica
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // === RELACIÓN CON ORDER (1:1) ===
            $table->foreignId('order_id')
                ->unique()
                ->constrained('orders')
                ->restrictOnDelete();

            // === IDENTIFICADORES INTERNOS ===
            // Número de factura interno (FAC-2026-0001)
            $table->string('invoice_number', 30)->unique();
            // Serie de facturación (A, B, etc.)
            $table->string('serie', 10);

            // === ESTADO ===
            $table->enum('status', [
                'draft',      // Borrador
                'issued',     // Emitida
                'cancelled'   // Cancelada
            ])->default('draft');

            // === DATOS DEL EMISOR ===
            $table->string('emisor_rfc', 13);
            $table->string('emisor_razon_social', 255);

            // === DATOS DEL RECEPTOR ===
            $table->string('receptor_rfc', 13);
            $table->string('receptor_razon_social', 255);

            // === MONTOS (snapshot del pedido) ===
            $table->decimal('subtotal', 12, 2);
            $table->decimal('iva_rate', 5, 4)->default(0.1600); // 16%
            $table->decimal('iva_amount', 12, 2);
            $table->decimal('total', 12, 2);

            // === FECHAS ===
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            // === AUDITORÍA ===
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // === ÍNDICES ===
            $table->index(['status', 'created_at']);
            $table->index(['serie', 'invoice_number']);
            $table->index('issued_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
