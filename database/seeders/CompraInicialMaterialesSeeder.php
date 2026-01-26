<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Proveedor;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseReception;
use App\Models\MaterialVariant;
use App\Models\InventoryMovement;
use App\Models\Unit;
use App\Models\User;
use App\Enums\PurchaseStatus;

/**
 * =============================================================================
 * COMPRA INICIAL DE MATERIALES (STOCK OPERATIVO)
 * =============================================================================
 *
 * FLUJO OPERATIVO REAL:
 * 1. Crear orden de compra (status: confirmada)
 * 2. Crear recepción de compra
 * 3. Registrar inventory_movements tipo 'entrada'
 * 4. Actualizar current_stock y average_cost en material_variants
 *
 * PRECIOS REALES MERCADO MEXICANO (Enero 2026):
 * - Tela Algodón Manta: $95-120 MXN/metro
 * - Tela Yute Natural: $85-110 MXN/metro
 * - Hilo Bordado Cono 5000m: $50-65 MXN/cono
 * - Hilo Costura Cono 3000m: $38-50 MXN/cono
 * - Listón Satín: $10-15 MXN/metro
 * - Asas Algodón Par: $25-35 MXN/par
 *
 * DEPENDENCIAS:
 * - ProveedorBordadosSeeder
 * - MaterialCatalogSeeder
 */
class CompraInicialMaterialesSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('══════════════════════════════════════════════════');
        $this->command->info('   SEMBRANDO COMPRA INICIAL DE MATERIALES');
        $this->command->info('══════════════════════════════════════════════════');

        DB::transaction(function () {
            // =============================================
            // VERIFICAR DEPENDENCIAS
            // =============================================
            $user = User::first();
            if (!$user) {
                $this->command->error('   ✗ No hay usuarios. Cree un usuario primero.');
                return;
            }

            $proveedor = Proveedor::where('email', 'ventas@textilesmayab.com.mx')->first();
            if (!$proveedor) {
                $proveedor = Proveedor::first();
            }
            if (!$proveedor) {
                $this->command->error('   ✗ No hay proveedores. Ejecute ProveedorBordadosSeeder primero.');
                return;
            }

            $units = Unit::all()->keyBy('slug');
            $metro = $units['metro'] ?? null;
            $cono = $units['cono'] ?? null;
            $pieza = $units['pieza'] ?? null;

            if (!$metro || !$cono || !$pieza) {
                $this->command->error('   ✗ Faltan unidades. Ejecute UnitsSeeder primero.');
                return;
            }

            // Verificar si ya existe compra de este seeder
            $existingPurchase = Purchase::where('reference', 'SEED-INICIAL-001')->first();
            if ($existingPurchase) {
                $this->command->info('   • Compra inicial ya existe: ' . $existingPurchase->purchase_number);
                $this->command->info('   • Omitiendo creación de nueva compra.');
                return;
            }

            $this->command->info("   ✓ Proveedor: {$proveedor->nombre_proveedor}");
            $this->command->info("   ✓ Usuario: {$user->name}");

            // =============================================
            // CREAR ORDEN DE COMPRA
            // =============================================
            $this->command->info('');
            $this->command->info('📝 Creando orden de compra...');

            $purchase = Purchase::create([
                'uuid' => (string) Str::uuid(),
                'proveedor_id' => $proveedor->id,
                'status' => PurchaseStatus::RECEIVED,
                'subtotal' => 0,
                'tax_rate' => 16.00,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'total' => 0,
                'notes' => 'Compra inicial de materiales para producción. Inventario base operativo.',
                'reference' => 'SEED-INICIAL-001',
                'ordered_at' => now()->subDays(5),
                'expected_at' => now()->subDays(1),
                'received_at' => now(),
                'created_by' => $user->id,
                'received_by' => $user->id,
                'activo' => true,
            ]);

            $this->command->info("   ✓ Orden: {$purchase->purchase_number}");

            // =============================================
            // ITEMS DE COMPRA (PRECIOS REALES 2026)
            // =============================================
            $this->command->info('');
            $this->command->info('📦 Registrando items de compra...');

            $purchaseItemsData = [
                // TELAS
                ['sku' => 'TEL-ALG-BLA-001', 'unit' => 'metro', 'qty' => 50, 'price' => 98.00, 'factor' => 1, 'desc' => 'Algodón Manta Blanca'],
                ['sku' => 'TEL-YUT-NAT-001', 'unit' => 'metro', 'qty' => 40, 'price' => 95.00, 'factor' => 1, 'desc' => 'Yute Natural'],
                ['sku' => 'TEL-POP-BLA-001', 'unit' => 'metro', 'qty' => 30, 'price' => 45.00, 'factor' => 1, 'desc' => 'Popelina Forro Blanco'],

                // HILOS BORDADO (precio por cono 5000m)
                ['sku' => 'HIL-BOR-ROJ-001', 'unit' => 'cono', 'qty' => 6, 'price' => 55.00, 'factor' => 5000, 'desc' => 'Hilo Bordado Rojo'],
                ['sku' => 'HIL-BOR-ROS-001', 'unit' => 'cono', 'qty' => 6, 'price' => 55.00, 'factor' => 5000, 'desc' => 'Hilo Bordado Rosa'],
                ['sku' => 'HIL-BOR-VER-001', 'unit' => 'cono', 'qty' => 6, 'price' => 55.00, 'factor' => 5000, 'desc' => 'Hilo Bordado Verde'],
                ['sku' => 'HIL-BOR-AMA-001', 'unit' => 'cono', 'qty' => 6, 'price' => 55.00, 'factor' => 5000, 'desc' => 'Hilo Bordado Amarillo'],
                ['sku' => 'HIL-BOR-AZU-001', 'unit' => 'cono', 'qty' => 4, 'price' => 55.00, 'factor' => 5000, 'desc' => 'Hilo Bordado Azul'],
                ['sku' => 'HIL-BOR-NEG-001', 'unit' => 'cono', 'qty' => 4, 'price' => 55.00, 'factor' => 5000, 'desc' => 'Hilo Bordado Negro'],
                ['sku' => 'HIL-BOR-BLA-001', 'unit' => 'cono', 'qty' => 4, 'price' => 55.00, 'factor' => 5000, 'desc' => 'Hilo Bordado Blanco'],

                // HILO COSTURA (precio por cono 3000m)
                ['sku' => 'HIL-COS-BLA-001', 'unit' => 'cono', 'qty' => 8, 'price' => 42.00, 'factor' => 3000, 'desc' => 'Hilo Costura Blanco'],
                ['sku' => 'HIL-COS-NAT-001', 'unit' => 'cono', 'qty' => 6, 'price' => 42.00, 'factor' => 3000, 'desc' => 'Hilo Costura Natural'],

                // AVÍOS
                ['sku' => 'LIS-SAT-ROS-001', 'unit' => 'metro', 'qty' => 50, 'price' => 12.00, 'factor' => 1, 'desc' => 'Listón Satín Rosa'],
                ['sku' => 'AVI-ASA-NAT-001', 'unit' => 'pieza', 'qty' => 50, 'price' => 28.00, 'factor' => 1, 'desc' => 'Asas Algodón Par'],
                ['sku' => 'ETI-MAR-001', 'unit' => 'pieza', 'qty' => 200, 'price' => 2.50, 'factor' => 1, 'desc' => 'Etiqueta Bordada'],
                ['sku' => 'EMP-CEL-3535', 'unit' => 'pieza', 'qty' => 500, 'price' => 1.80, 'factor' => 1, 'desc' => 'Bolsa Celofán'],

                // PELÓN
                ['sku' => 'PEL-REC-BLA-001', 'unit' => 'metro', 'qty' => 100, 'price' => 8.50, 'factor' => 1, 'desc' => 'Pelón Recortable'],
            ];

            $subtotal = 0;

            foreach ($purchaseItemsData as $itemData) {
                $variant = MaterialVariant::where('sku', $itemData['sku'])->first();
                if (!$variant) {
                    $this->command->warn("   ⚠ SKU no encontrado: {$itemData['sku']}");
                    continue;
                }

                $unit = $units[$itemData['unit']];
                $itemSubtotal = $itemData['qty'] * $itemData['price'];
                $subtotal += $itemSubtotal;

                // Crear PurchaseItem
                $purchaseItem = PurchaseItem::create([
                    'uuid' => (string) Str::uuid(),
                    'purchase_id' => $purchase->id,
                    'material_variant_id' => $variant->id,
                    'unit_id' => $unit->id,
                    'quantity' => $itemData['qty'],
                    'unit_price' => $itemData['price'],
                    'conversion_factor' => $itemData['factor'],
                    'converted_quantity' => $itemData['qty'] * $itemData['factor'],
                    'converted_unit_cost' => $itemData['price'] / $itemData['factor'],
                    'subtotal' => $itemSubtotal,
                    'quantity_received' => $itemData['qty'],
                    'converted_quantity_received' => $itemData['qty'] * $itemData['factor'],
                    'notes' => $itemData['desc'],
                ]);

                // Calcular valores para inventario
                $convertedQty = $itemData['qty'] * $itemData['factor'];
                $convertedCost = $itemData['price'] / $itemData['factor'];

                // Guardar valores antes
                $stockBefore = $variant->current_stock;
                $valueBefore = $variant->current_value;
                $avgCostBefore = $variant->average_cost;

                // Calcular nuevo costo promedio ponderado
                $totalOldValue = $variant->current_stock * $variant->average_cost;
                $totalNewValue = $convertedQty * $convertedCost;
                $totalQty = $variant->current_stock + $convertedQty;
                $newAvgCost = $totalQty > 0 ? ($totalOldValue + $totalNewValue) / $totalQty : $convertedCost;

                // Actualizar variante
                $variant->update([
                    'current_stock' => $totalQty,
                    'average_cost' => $newAvgCost,
                    'last_purchase_cost' => $convertedCost,
                    'current_value' => $totalQty * $newAvgCost,
                ]);

                // Registrar movimiento de inventario
                InventoryMovement::create([
                    'uuid' => (string) Str::uuid(),
                    'material_variant_id' => $variant->id,
                    'type' => 'entrada',
                    'reference_type' => PurchaseItem::class,
                    'reference_id' => $purchaseItem->id,
                    'quantity' => $convertedQty,
                    'unit_cost' => $convertedCost,
                    'total_cost' => $convertedQty * $convertedCost,
                    'stock_before' => $stockBefore,
                    'stock_after' => $variant->current_stock,
                    'average_cost_before' => $avgCostBefore,
                    'average_cost_after' => $variant->average_cost,
                    'value_before' => $valueBefore,
                    'value_after' => $variant->current_value,
                    'notes' => "Recepción compra {$purchase->purchase_number}",
                    'created_by' => $user->id,
                ]);

                $stockFormatted = number_format($variant->current_stock, 2);
                $costFormatted = number_format($variant->average_cost, 4);
                $this->command->info("   ✓ {$itemData['qty']} {$unit->symbol} × \${$itemData['price']} = \${$itemSubtotal} | {$itemData['desc']}");
                $this->command->info("     → Stock: {$stockFormatted} | Costo: \${$costFormatted}");
            }

            // =============================================
            // ACTUALIZAR TOTALES DE COMPRA
            // =============================================
            $taxAmount = round($subtotal * 0.16, 2);
            $total = $subtotal + $taxAmount;

            $purchase->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total' => $total,
            ]);

            // =============================================
            // CREAR RECEPCIÓN
            // =============================================
            PurchaseReception::create([
                'uuid' => (string) Str::uuid(),
                'purchase_id' => $purchase->id,
                'reception_number' => 'REC-' . $purchase->purchase_number,
                'status' => 'completed',
                'delivery_note' => 'Recepción completa de compra inicial',
                'notes' => 'Inventario base operativo',
                'received_at' => now(),
                'received_by' => $user->id,
            ]);

            // =============================================
            // RESUMEN
            // =============================================
            $this->command->info('');
            $this->command->info('══════════════════════════════════════════════════');
            $this->command->info('   COMPRA INICIAL REGISTRADA');
            $this->command->info('══════════════════════════════════════════════════');
            $this->command->info("   Orden:    {$purchase->purchase_number}");
            $this->command->info("   Subtotal: \$" . number_format($subtotal, 2));
            $this->command->info("   IVA 16%:  \$" . number_format($taxAmount, 2));
            $this->command->info("   Total:    \$" . number_format($total, 2));
            $this->command->info('');
            $this->command->info('   INVENTARIO ACTUALIZADO:');

            $variants = MaterialVariant::where('current_stock', '>', 0)->get();
            foreach ($variants as $v) {
                $stock = number_format($v->current_stock, 2);
                $cost = number_format($v->average_cost, 4);
                $value = number_format($v->current_value, 2);
                $this->command->info("   • {$v->sku}: {$stock} unids @ \${$cost} = \${$value}");
            }

            $this->command->info('');
        });
    }
}
