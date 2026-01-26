<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductType;
use App\Models\ProductVariant;
use App\Models\MaterialVariant;

/**
 * =============================================================================
 * PRODUCTO OPERATIVO: BOLSA DE YUTE BORDADA
 * =============================================================================
 *
 * Bolsa artesanal:
 * - Tela de yute natural
 * - Forro de popelina
 * - Bordado floral
 * - Asas de algodón acolchadas
 * - Etiqueta de marca
 *
 * CONSUMOS REALES:
 * - Tela yute: 0.60m (bolsa 35x40cm)
 * - Forro popelina: 0.50m
 * - Hilo bordado: 80m total
 * - Hilo costura: 30m
 * - Asas: 1 par
 * - Etiqueta: 1 pieza
 *
 * NO requiere medidas (ACCESSORY)
 *
 * DEPENDENCIAS:
 * - CompraInicialMaterialesSeeder
 * - ProductTypeSeeder
 */
class ProductoBolsaYuteSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('══════════════════════════════════════════════════');
        $this->command->info('   SEMBRANDO PRODUCTO: BOLSA YUTE BORDADA');
        $this->command->info('══════════════════════════════════════════════════');

        DB::transaction(function () {
            // =============================================
            // VERIFICAR TIPO DE PRODUCTO
            // =============================================
            $productType = ProductType::where('code', 'ACCESSORY')->first();
            if (!$productType) {
                $this->command->error('   ✗ ProductType ACCESSORY no existe.');
                return;
            }

            // =============================================
            // CREAR/VERIFICAR CATEGORÍA
            // =============================================
            $category = ProductCategory::updateOrCreate(
                ['slug' => 'bolsas-artesanales'],
                [
                    'uuid' => (string) Str::uuid(),
                    'name' => 'Bolsas Artesanales',
                    'description' => 'Bolsas y accesorios con bordado artesanal',
                    'is_active' => true,
                ]
            );

            $this->command->info("   ✓ Categoría: {$category->name}");
            $this->command->info("   ✓ Tipo: {$productType->display_name}");

            // =============================================
            // OBTENER MATERIALES
            // =============================================
            $variants = MaterialVariant::whereIn('sku', [
                'TEL-YUT-NAT-001',
                'TEL-POP-BLA-001',
                'HIL-BOR-ROJ-001',
                'HIL-BOR-VER-001',
                'HIL-COS-NAT-001',
                'AVI-ASA-NAT-001',
                'ETI-MAR-001',
                'EMP-CEL-3535',
            ])->get()->keyBy('sku');

            if ($variants->count() < 6) {
                $this->command->error('   ✗ Faltan variantes de material.');
                $this->command->info('   Encontradas: ' . $variants->count());
                return;
            }

            // =============================================
            // CREAR PRODUCTO
            // =============================================
            $this->command->info('');
            $this->command->info('👜 Creando producto...');

            $product = Product::updateOrCreate(
                ['sku' => 'BOL-YUT-001'],
                [
                    'uuid' => (string) Str::uuid(),
                    'tenant_id' => 1,
                    'product_category_id' => $category->id,
                    'product_type_id' => $productType->id,
                    'name' => 'Bolsa Yute Bordada Floral',
                    'description' => 'Bolsa artesanal de yute natural con bordado floral en tonos rojos y verdes. Forro de popelina, asas de algodón acolchadas. Medidas: 35x40cm.',
                    'specifications' => json_encode([
                        'material_base' => 'Yute natural',
                        'forro' => 'Popelina blanca',
                        'medidas_cm' => '35x40',
                        'tipo_bordado' => 'Floral bicolor',
                        'colores_bordado' => ['Rojo', 'Verde'],
                    ]),
                    'status' => 'active',
                    'base_price' => 650.00,
                    'production_lead_time' => 3,
                    'profit_margin' => 50.00,
                ]
            );

            $this->command->info("   ✓ Producto: {$product->name}");
            $this->command->info("   ✓ SKU: {$product->sku}");

            // =============================================
            // CREAR BOM
            // =============================================
            $this->command->info('');
            $this->command->info('📋 Creando BOM...');

            // Limpiar BOM existente
            DB::table('product_materials')
                ->where('product_id', $product->id)
                ->delete();

            $bomItems = [
                // Tela yute
                [
                    'sku' => 'TEL-YUT-NAT-001',
                    'quantity' => 0.6000,
                    'is_primary' => true,
                    'notes' => 'Tela yute natural - cuerpo bolsa',
                ],
                // Forro
                [
                    'sku' => 'TEL-POP-BLA-001',
                    'quantity' => 0.5000,
                    'is_primary' => false,
                    'notes' => 'Popelina blanca - forro interior',
                ],
                // Hilos bordado
                [
                    'sku' => 'HIL-BOR-ROJ-001',
                    'quantity' => 50.0000,
                    'is_primary' => false,
                    'notes' => 'Hilo bordado rojo - flores',
                ],
                [
                    'sku' => 'HIL-BOR-VER-001',
                    'quantity' => 30.0000,
                    'is_primary' => false,
                    'notes' => 'Hilo bordado verde - hojas',
                ],
                // Hilo costura
                [
                    'sku' => 'HIL-COS-NAT-001',
                    'quantity' => 30.0000,
                    'is_primary' => false,
                    'notes' => 'Hilo costura natural - confección',
                ],
                // Asas
                [
                    'sku' => 'AVI-ASA-NAT-001',
                    'quantity' => 1.0000,
                    'is_primary' => false,
                    'notes' => 'Par de asas algodón acolchadas',
                ],
                // Etiqueta
                [
                    'sku' => 'ETI-MAR-001',
                    'quantity' => 1.0000,
                    'is_primary' => false,
                    'notes' => 'Etiqueta bordada de marca',
                ],
            ];

            $totalMaterialsCost = 0;

            foreach ($bomItems as $item) {
                $variant = $variants[$item['sku']] ?? null;
                if (!$variant) {
                    $this->command->warn("   ⚠ SKU no encontrado: {$item['sku']}");
                    continue;
                }

                $unitCost = $variant->average_cost;
                $totalCost = $item['quantity'] * $unitCost;
                $totalMaterialsCost += $totalCost;

                DB::table('product_materials')->insert([
                    'product_id' => $product->id,
                    'material_variant_id' => $variant->id,
                    'quantity' => $item['quantity'],
                    'unit_cost' => $unitCost,
                    'is_primary' => $item['is_primary'],
                    'notes' => $item['notes'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $qtyFormatted = number_format($item['quantity'], 2);
                $costFormatted = number_format($totalCost, 2);
                $this->command->info("   ✓ {$qtyFormatted} × {$variant->sku} = \${$costFormatted}");
            }

            // Actualizar costo en producto
            $product->update([
                'materials_cost' => $totalMaterialsCost,
                'production_cost' => $totalMaterialsCost,
            ]);

            $this->command->info("   ─────────────────────────────────");
            $this->command->info("   COSTO MATERIALES: \$" . number_format($totalMaterialsCost, 2));

            // =============================================
            // CREAR VARIANTES (Tamaños)
            // =============================================
            $this->command->info('');
            $this->command->info('🎨 Creando variantes...');

            $tamanios = [
                ['name' => 'Mediana', 'suffix' => 'MED', 'price' => 650.00],
                ['name' => 'Grande', 'suffix' => 'GDE', 'price' => 750.00],
            ];

            foreach ($tamanios as $tam) {
                $skuVariant = "{$product->sku}-{$tam['suffix']}";

                ProductVariant::updateOrCreate(
                    ['sku_variant' => $skuVariant],
                    [
                        'uuid' => (string) Str::uuid(),
                        'product_id' => $product->id,
                        'price' => $tam['price'],
                        'attribute_combinations' => json_encode(['tamanio' => $tam['name']]),
                        'stock_alert' => 3,
                    ]
                );

                $this->command->info("   ✓ {$skuVariant} - \${$tam['price']}");
            }

            // =============================================
            // RESUMEN
            // =============================================
            $this->command->info('');
            $this->command->info('══════════════════════════════════════════════════');
            $this->command->info('   PRODUCTO BOLSA YUTE CREADO');
            $this->command->info('══════════════════════════════════════════════════');
            $this->command->info("   SKU:              {$product->sku}");
            $this->command->info("   Tipo:             {$productType->display_name}");
            $this->command->info("   Requiere medidas: NO");
            $this->command->info("   Variantes:        " . count($tamanios));
            $this->command->info("   Items BOM:        " . count($bomItems));
            $this->command->info("   Costo materiales: \$" . number_format($totalMaterialsCost, 2));
            $this->command->info("   Precio base:      \$" . number_format($product->base_price, 2));
            $this->command->info('');
        });
    }
}
