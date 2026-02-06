<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * MIGRACIÓN DE DATOS: Copiar diseños de productos a order_item_design_exports
 *
 * PROPÓSITO:
 * Los pedidos existentes tienen sus items vinculados a productos que tienen diseños
 * en la tabla product_design. Esta migración copia esos diseños a order_item_design_exports
 * para que cada pedido tenga su propio snapshot de diseños (inmutable).
 *
 * ARQUITECTURA:
 * - product_design: Define qué diseños tiene un PRODUCTO (catálogo)
 * - order_item_design_exports: Define qué diseños tiene un ITEM DE PEDIDO (snapshot)
 *
 * REGLA: Después de esta migración, SOLO order_item_design_exports es la fuente
 * de verdad para los diseños de un pedido. La tabla product_design solo se usa
 * para nuevos pedidos (se copia al crear el OrderItem).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Obtener todos los order_items que NO tienen diseños en order_item_design_exports
        // pero cuyo producto SÍ tiene diseños en product_design
        $itemsWithoutSnapshot = DB::table('order_items as oi')
            ->leftJoin('order_item_design_exports as oide', 'oi.id', '=', 'oide.order_item_id')
            ->join('products as p', 'oi.product_id', '=', 'p.id')
            ->join('product_design as pd', 'p.id', '=', 'pd.product_id')
            ->whereNull('oide.id') // Sin snapshot
            ->whereNotNull('pd.design_export_id') // Producto tiene design_export_id específico
            ->select([
                'oi.id as order_item_id',
                'p.id as product_id',
                'pd.design_export_id',
                'pd.application_type_id',
            ])
            ->distinct()
            ->get();

        $migratedCount = 0;
        $now = now();

        foreach ($itemsWithoutSnapshot as $item) {
            // Verificar que no exista ya (doble verificación)
            $exists = DB::table('order_item_design_exports')
                ->where('order_item_id', $item->order_item_id)
                ->where('design_export_id', $item->design_export_id)
                ->exists();

            if (!$exists) {
                DB::table('order_item_design_exports')->insert([
                    'order_item_id' => $item->order_item_id,
                    'design_export_id' => $item->design_export_id,
                    'application_type' => $item->application_type_id,
                    'position' => null,
                    'notes' => 'Migrado desde product_design (snapshot retroactivo)',
                    'sort_order' => 0,
                    'created_by' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $migratedCount++;
            }
        }

        Log::info("MIGRATION: migrate_product_designs_to_order_items - Migrados {$migratedCount} diseños a order_item_design_exports");
    }

    /**
     * Reverse the migrations.
     * NOTA: No eliminamos los snapshots porque son datos valiosos.
     * Si se necesita revertir, hacerlo manualmente con cuidado.
     */
    public function down(): void
    {
        // No eliminar snapshots automáticamente - podría causar pérdida de datos
        Log::warning("MIGRATION ROLLBACK: migrate_product_designs_to_order_items - Los snapshots NO se eliminan automáticamente");
    }
};
