<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Proveedor;
use App\Models\Estado;
use App\Models\Giro;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\MaterialVariant;
use App\Models\InventoryMovement;
use App\Models\Unit;
use App\Models\User;
use App\Enums\PurchaseStatus;

/**
 * =============================================================================
 * SEEDER PRODUCTIVO: PROVEEDOR Y COMPRAS PARA HIPIL
 * =============================================================================
 *
 * PRECIOS REALES APROXIMADOS (Mercado Mexicano Enero 2026):
 * - Tela Algodón Manta: $85-120 MXN/metro
 * - Hilo Bordado Cono 5000m: $45-65 MXN/cono
 * - Hilo Costura Cono 3000m: $35-50 MXN/cono
 * - Listón Satín: $8-15 MXN/metro
 *
 * EJECUTAR DESPUÉS DE:
 * php artisan db:seed --class=HipilProductSeeder
 */
class HipilSupplierPurchaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('══════════════════════════════════════════════════');
        $this->command->info('   SEMBRANDO PROVEEDOR Y COMPRAS SIMULADAS');
        $this->command->info('══════════════════════════════════════════════════');

        DB::transaction(function () {
            // =============================================
            // PASO 1: VERIFICAR DEPENDENCIAS
            // =============================================
            $this->command->info('');
            $this->command->info('🔍 Verificando dependencias...');

            // Usuario para created_by
            $user = User::first();
            if (!$user) {
                $this->command->error('   ✗ No hay usuarios en el sistema. Cree un usuario primero.');
                return;
            }
            $this->command->info("   ✓ Usuario: {$user->name}");

            // Estado (Yucatán para contexto regional)
            $estado = Estado::where('nombre_estado', 'LIKE', '%YUCAT%')->first();
            if (!$estado) {
                $estado = Estado::first();
            }
            if (!$estado) {
                $this->command->error('   ✗ No hay estados. Ejecute EstadoSeeder primero.');
                return;
            }
            $this->command->info("   ✓ Estado: {$estado->nombre_estado}");

            // Giro (Tela y Accesorios)
            $giro = Giro::where('nombre_giro', 'LIKE', '%TELA Y ACCESORIOS%')->first();
            if (!$giro) {
                $giro = Giro::first();
            }
            if (!$giro) {
                $this->command->error('   ✗ No hay giros. Ejecute GiroSeeder primero.');
                return;
            }
            $this->command->info("   ✓ Giro: {$giro->nombre_giro}");

            // Unidades
            $units = Unit::whereIn('slug', ['metro', 'cono'])->get()->keyBy('slug');
            if ($units->count() < 2) {
                $this->command->error('   ✗ Faltan unidades metro/cono.');
                return;
            }

            // Material Variants creadas en HipilProductSeeder
            $variants = MaterialVariant::whereIn('sku', [
                'TEL-ALG-BLA-001',
                'HIL-BOR-ROJ-001',
                'HIL-BOR-ROS-001',
                'HIL-BOR-VER-001',
                'HIL-BOR-AMA-001',
                'HIL-COS-BLA-001',
                'LIS-SAT-ROS-001',
            ])->get()->keyBy('sku');

            if ($variants->count() < 7) {
                $this->command->error('   ✗ Faltan variantes de material. Ejecute HipilProductSeeder primero.');
                $this->command->info("   Encontradas: " . $variants->count() . " de 7");
                return;
            }
            $this->command->info("   ✓ Material Variants: {$variants->count()}");

            // =============================================
            // PASO 2: CREAR PROVEEDOR
            // =============================================
            $this->command->info('');
            $this->command->info('🏪 Creando proveedor...');

            $proveedor = Proveedor::firstOrCreate(
                ['email' => 'ventas@textilesmayab.com.mx'],
                [
                    'nombre_proveedor' => 'Textiles del Mayab S.A. de C.V.',
                    'direccion' => 'Calle 60 #501, Centro Histórico',
                    'codigo_postal' => '97000',
                    'telefono' => '999-123-4567',
                    'ciudad' => 'Mérida',
                    'nombre_contacto' => 'María González',
                    'telefono_contacto' => '999-987-6543',
                    'email_contacto' => 'maria.gonzalez@textilesmayab.com.mx',
                    'estado_id' => $estado->id,
                    'giro_id' => $giro->id,
                    'activo' => true,
                ]
            );
            $this->command->info("   ✓ Proveedor: {$proveedor->nombre_proveedor}");
            $this->command->info("   ✓ Ciudad: {$proveedor->ciudad}, {$estado->nombre_estado}");

            // =============================================
            // PASO 3: CREAR ORDEN DE COMPRA
            // =============================================
            $this->command->info('');
            $this->command->info('📝 Creando orden de compra...');

            // Verificar si ya existe una compra de este seeder
            $existingPurchase = Purchase::where('reference', 'SEED-HIPIL-001')->first();
            if ($existingPurchase) {
                $this->command->info("   • Compra ya existe: {$existingPurchase->purchase_number}");
                $this->command->info("   • Omitiendo creación de nueva compra.");
                return;
            }

            $purchase = Purchase::create([
                'uuid' => (string) Str::uuid(),
                'proveedor_id' => $proveedor->id,
                'status' => PurchaseStatus::RECEIVED,
                'subtotal' => 0, // Se calculará con items
                'tax_rate' => 16.00, // IVA México
                'tax_amount' => 0,
                'discount_amount' => 0,
                'total' => 0,
                'notes' => 'Compra inicial de materiales para producción de Hipiles. Seeder de simulación.',
                'reference' => 'SEED-HIPIL-001',
                'ordered_at' => now()->subDays(7),
                'expected_at' => now()->subDays(2),
                'received_at' => now(),
                'created_by' => $user->id,
                'received_by' => $user->id,
                'activo' => true,
            ]);
            $this->command->info("   ✓ Orden: {$purchase->purchase_number}");

            // =============================================
            // PASO 4: CREAR ITEMS DE COMPRA
            // =============================================
            $this->command->info('');
            $this->command->info('📦 Creando items de compra (precios reales 2026)...');

            // PRECIOS REALES APROXIMADOS MERCADO MEXICANO
            $purchaseItems = [
                [
                    'variant_sku' => 'TEL-ALG-BLA-001',
                    'unit_slug' => 'metro',
                    'quantity' => 50.0, // 50 metros de tela
                    'unit_price' => 95.00, // $95 MXN/metro
                    'conversion_factor' => 1.0,
                    'desc' => 'Tela Algodón Manta Blanca',
                ],
                [
                    'variant_sku' => 'HIL-BOR-ROJ-001',
                    'unit_slug' => 'cono',
                    'quantity' => 5.0, // 5 conos
                    'unit_price' => 55.00, // $55 MXN/cono de 5000m
                    'conversion_factor' => 5000.0, // 1 cono = 5000 metros
                    'desc' => 'Hilo Bordado Rojo',
                ],
                [
                    'variant_sku' => 'HIL-BOR-ROS-001',
                    'unit_slug' => 'cono',
                    'quantity' => 5.0,
                    'unit_price' => 55.00,
                    'conversion_factor' => 5000.0,
                    'desc' => 'Hilo Bordado Rosa',
                ],
                [
                    'variant_sku' => 'HIL-BOR-VER-001',
                    'unit_slug' => 'cono',
                    'quantity' => 5.0,
                    'unit_price' => 55.00,
                    'conversion_factor' => 5000.0,
                    'desc' => 'Hilo Bordado Verde',
                ],
                [
                    'variant_sku' => 'HIL-BOR-AMA-001',
                    'unit_slug' => 'cono',
                    'quantity' => 5.0,
                    'unit_price' => 55.00,
                    'conversion_factor' => 5000.0,
                    'desc' => 'Hilo Bordado Amarillo',
                ],
                [
                    'variant_sku' => 'HIL-COS-BLA-001',
                    'unit_slug' => 'cono',
                    'quantity' => 10.0, // 10 conos
                    'unit_price' => 42.00, // $42 MXN/cono de 3000m
                    'conversion_factor' => 3000.0,
                    'desc' => 'Hilo Costura Blanco',
                ],
                [
                    'variant_sku' => 'LIS-SAT-ROS-001',
                    'unit_slug' => 'metro',
                    'quantity' => 50.0, // 50 metros de listón
                    'unit_price' => 12.00, // $12 MXN/metro
                    'conversion_factor' => 1.0,
                    'desc' => 'Listón Satín Rosa',
                ],
            ];

            $subtotal = 0;

            foreach ($purchaseItems as $itemData) {
                $variant = $variants[$itemData['variant_sku']];
                $unit = $units[$itemData['unit_slug']];

                // Crear PurchaseItem
                $item = PurchaseItem::create([
                    'uuid' => (string) Str::uuid(),
                    'purchase_id' => $purchase->id,
                    'material_variant_id' => $variant->id,
                    'unit_id' => $unit->id,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'conversion_factor' => $itemData['conversion_factor'],
                    'converted_quantity' => $itemData['quantity'] * $itemData['conversion_factor'],
                    'converted_unit_cost' => $itemData['unit_price'] / $itemData['conversion_factor'],
                    'subtotal' => $itemData['quantity'] * $itemData['unit_price'],
                    'quantity_received' => $itemData['quantity'], // Todo recibido
                    'converted_quantity_received' => $itemData['quantity'] * $itemData['conversion_factor'],
                    'notes' => $itemData['desc'],
                ]);

                $itemSubtotal = $itemData['quantity'] * $itemData['unit_price'];
                $subtotal += $itemSubtotal;

                $this->command->info("   ✓ {$itemData['quantity']} {$unit->symbol} × \${$itemData['unit_price']} = \${$itemSubtotal} - {$itemData['desc']}");

                // =============================================
                // PASO 5: ACTUALIZAR INVENTARIO
                // =============================================
                $convertedQty = $itemData['quantity'] * $itemData['conversion_factor'];
                $convertedCost = $itemData['unit_price'] / $itemData['conversion_factor'];

                // Guardar valores antes
                $stockBefore = $variant->current_stock;
                $valueBefore = $variant->current_value;
                $avgCostBefore = $variant->average_cost;

                // Actualizar stock de variante
                $variant->addStock($convertedQty, $convertedCost);

                // Registrar movimiento de inventario
                InventoryMovement::create([
                    'uuid' => (string) Str::uuid(),
                    'material_variant_id' => $variant->id,
                    'type' => 'entrada',
                    'reference_type' => PurchaseItem::class,
                    'reference_id' => $item->id,
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
            }

            // =============================================
            // PASO 6: ACTUALIZAR TOTALES DE COMPRA
            // =============================================
            $taxAmount = $subtotal * ($purchase->tax_rate / 100);
            $total = $subtotal + $taxAmount;

            $purchase->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total' => $total,
            ]);

            $this->command->info('');
            $this->command->info('💰 Totales de compra:');
            $this->command->info("   Subtotal: \${$subtotal}");
            $this->command->info("   IVA (16%): \${$taxAmount}");
            $this->command->info("   Total: \${$total}");

            // =============================================
            // PASO 7: ACTUALIZAR COSTOS EN BOM
            // =============================================
            $this->command->info('');
            $this->command->info('📊 Actualizando costos en BOM del producto...');

            // Buscar el producto Hipil
            $product = \App\Models\Product::where('sku', 'HIP-TRA-BLA-001')->first();
            if ($product) {
                $totalMaterialsCost = 0;

                // Actualizar cada línea del BOM con el costo promedio actual
                $bomLines = DB::table('product_materials')
                    ->where('product_id', $product->id)
                    ->whereNull('deleted_at')
                    ->get();

                foreach ($bomLines as $bom) {
                    $variant = MaterialVariant::find($bom->material_variant_id);
                    if ($variant && $variant->average_cost > 0) {
                        $unitCost = $variant->average_cost;
                        $totalCost = $bom->quantity * $unitCost;
                        $totalMaterialsCost += $totalCost;

                        DB::table('product_materials')
                            ->where('id', $bom->id)
                            ->update([
                                'unit_cost' => $unitCost,
                                'updated_at' => now(),
                            ]);

                        $this->command->info("   ✓ {$variant->display_name}: \${$unitCost}/unidad × {$bom->quantity} = \${$totalCost}");
                    }
                }

                // Actualizar costo de producción del producto
                $product->update([
                    'production_cost' => $totalMaterialsCost,
                    'materials_cost' => $totalMaterialsCost,
                ]);

                $this->command->info('');
                $this->command->info("   COSTO MATERIALES TOTAL: \${$totalMaterialsCost}");
            }

            // =============================================
            // RESUMEN FINAL
            // =============================================
            $this->command->info('');
            $this->command->info('══════════════════════════════════════════════════');
            $this->command->info('   RESUMEN COMPRA Y STOCK');
            $this->command->info('══════════════════════════════════════════════════');
            $this->command->info("   Proveedor: {$proveedor->nombre_proveedor}");
            $this->command->info("   Orden: {$purchase->purchase_number}");
            $this->command->info("   Estado: Recibido");
            $this->command->info("   Total Compra: \${$total} MXN");
            $this->command->info('');
            $this->command->info('   STOCK ACTUALIZADO:');

            foreach ($variants as $sku => $variant) {
                $variant->refresh();
                $this->command->info("   • {$variant->display_name}: {$variant->formatted_stock} @ \${$variant->average_cost}/unidad");
            }

            $this->command->info('');
        });
    }
}
