<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FASE 1: MEDIDAS INLINE POR ÍTEM
 *
 * Agrega columna JSON para almacenar medidas capturadas
 * directamente en el ítem del pedido.
 *
 * ARQUITECTURA:
 * - measurements: JSON con medidas específicas del ítem
 * - Independiente de client_measurement_id (que sigue existiendo para medidas pre-existentes)
 * - Permite capturar medidas ad-hoc sin crear registro en client_measurements
 *
 * ESTRUCTURA ESPERADA:
 * {
 *   "busto": 85.5,
 *   "cintura": 70.0,
 *   "cadera": 95.5,
 *   "alto_cintura": 40.0,
 *   "largo": 60.0,
 *   "largo_vestido": 120.0
 * }
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Medidas capturadas inline (JSON nullable)
            $table->json('measurements')
                ->nullable()
                ->after('client_measurement_id')
                ->comment('Medidas capturadas inline para este ítem específico');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('measurements');
        });
    }
};
