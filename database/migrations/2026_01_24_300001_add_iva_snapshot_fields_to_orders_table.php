<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CIERRE BACKEND POS - IVA SNAPSHOT
 *
 * Agrega campos para persistir el IVA como snapshot operativo.
 * NO es facturación CFDI, es referencia contable visual.
 *
 * REGLAS:
 * - iva_rate = 0 o 16 (tasa aplicada)
 * - total_with_tax = subtotal - discount + iva_amount
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Tasa de IVA aplicada (0 o 16) - snapshot
            $table->decimal('iva_rate', 5, 2)->nullable()->after('iva_amount');

            // Total con impuesto incluido (para referencia rápida)
            $table->decimal('total_with_tax', 12, 2)->nullable()->after('iva_rate');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['iva_rate', 'total_with_tax']);
        });
    }
};
