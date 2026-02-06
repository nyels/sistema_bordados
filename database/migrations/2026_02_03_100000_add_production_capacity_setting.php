<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * ============================================================================
 * MIGRACIÓN: Setting de Capacidad de Producción Semanal
 * ============================================================================
 *
 * PROPÓSITO:
 * Define la capacidad máxima de pedidos que pueden procesarse por semana ISO.
 * Este setting es la BASE del motor de planeación de producción.
 *
 * REGLA ERP:
 * - Se define a NIVEL SISTEMA (no por cliente, no por pedido)
 * - La unidad de planeación es SEMANA ISO (year + week)
 * - Valor configurable por administración
 *
 * VALOR POR DEFECTO: 10 pedidos/semana
 * (Ajustable desde configuración del sistema)
 *
 * ============================================================================
 */
return new class extends Migration
{
    public function up(): void
    {
        // Verificar que no exista previamente
        $exists = DB::table('system_settings')
            ->where('key', 'production_max_orders_per_week')
            ->exists();

        if (!$exists) {
            DB::table('system_settings')->insert([
                'key' => 'production_max_orders_per_week',
                'value' => '10',
                'type' => 'integer',
                'group' => 'produccion',
                'label' => 'Capacidad máxima de pedidos por semana',
                'description' => 'Número máximo de pedidos que pueden estar en producción simultáneamente por semana ISO. Los pedidos con estado confirmed, in_production o ready cuentan contra esta capacidad.',
                'options' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('system_settings')
            ->where('key', 'production_max_orders_per_week')
            ->delete();
    }
};
