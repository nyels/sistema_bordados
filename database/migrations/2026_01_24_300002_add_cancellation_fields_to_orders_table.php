<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CIERRE BACKEND POS - CAMPOS DE CANCELACION
 *
 * Agrega campos de auditoría para cancelación de ventas POS.
 * NUNCA se borran ventas, solo se cancelan con trazabilidad completa.
 *
 * REGLAS:
 * - cancelled_at = timestamp de cancelación
 * - cancelled_by = usuario que canceló
 * - cancel_reason = motivo OBLIGATORIO
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Timestamp de cancelación
            $table->timestamp('cancelled_at')->nullable()->after('delivered_date');

            // Usuario que canceló
            $table->foreignId('cancelled_by')->nullable()->after('cancelled_at')
                ->constrained('users')->nullOnDelete();

            // Motivo de cancelación (OBLIGATORIO al cancelar)
            $table->string('cancel_reason', 255)->nullable()->after('cancelled_by');

            // Índice para queries de cancelaciones
            $table->index('cancelled_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['cancelled_at']);
            $table->dropForeign(['cancelled_by']);
            $table->dropColumn(['cancelled_at', 'cancelled_by', 'cancel_reason']);
        });
    }
};
