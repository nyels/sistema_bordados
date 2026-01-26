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
use App\Models\Attribute;
use App\Models\AttributeValue;

/**
 * =============================================================================
 * PRODUCTO OPERATIVO: HIPIL BORDADO TRADICIONAL
 * =============================================================================
 *
 * Vestido artesanal yucateco:
 * - Algodón manta blanca
 * - Bordado floral tradicional multicolor
 * - Moños de listón satín en hombros
 * - Requiere medidas del cliente (GARMENT_CUSTOM)
 *
 * CONSUMOS REALES POR TALLA:
 * - S: 1.5m tela, 120m hilo bordado, 40m hilo costura
 * - M: 1.7m tela, 140m hilo bordado, 45m hilo costura
 * - L: 1.9m tela, 160m hilo bordado, 50m hilo costura
 * - XL: 2.2m tela, 180m hilo bordado, 60m hilo costura
 *
 * DEPENDENCIAS:
 * - CompraInicialMaterialesSeeder (para costos)
 * - ProductTypeSeeder
 */
class ProductoHipilOperativoSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('══════════════════════════════════════════════════');
        $this->command->info('   SEMBRANDO PRODUCTO: HIPIL BORDADO TRADICIONAL');
        $this->command->info('══════════════════════════════════════════════════');

        DB::transaction(function () {
            // =============================================
            // VERIFICAR TIPO DE PRODUCTO
            // =============================================
            $productType = ProductType::where('code', 'GARMENT_CUSTOM')->first();
            if (!$productType) {
                $this->command->error('   ✗ ProductType GARMENT_CUSTOM no existe.');
                return;
            }

            // =============================================
            // CREAR/VERIFICAR CATEGORÍA
            // =============================================
            $category = ProductCategory::updateOrCreate(
                ['slug' => 'prendas-tradicionales'],
                [
                    'uuid' => (string) Str::uuid(),
                    'name' => 'Prendas Tradicionales',
                    'description' => 'Prendas artesanales con bordado tradicional yucateco',
                    'is_active' => true,
                ]
            );

            $this->command->info("   ✓ Categoría: {$category->name}");
            $this->command->info("   ✓ Tipo: {$productType->display_name}");

            // =============================================
            // OBTENER MATERIALES (deben tener costo)
            // =============================================
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
                $this->command->error('   ✗ Faltan variantes de material.');
                return;
            }

            // =============================================
            // CREAR PRODUCTO
            // =============================================
            $this->command->info('');
            $this->command->info('👗 Creando producto...');

            $product = Product::updateOrCreate(
                ['sku' => 'HIP-TRA-001'],
                [
                    'uuid' => (string) Str::uuid(),
                    'tenant_id' => 1,
                    'product_category_id' => $category->id,
                    'product_type_id' => $productType->id,
                    'name' => 'Hipil Bordado Tradicional',
                    'description' => 'Vestido artesanal de algodón blanco con bordado floral tradicional yucateco en tonos rojos, rosas, verdes y amarillos. Incluye moños decorativos de listón satín rosa en los hombros. Prenda a medida.',
                    'specifications' => json_encode([
                        'material_base' => 'Algodón manta blanca',
                        'tipo_bordado' => 'Floral tradicional yucateco',
                        'colores_bordado' => ['Rojo', 'Rosa', 'Verde', 'Amarillo'],
                        'ancho_tela_cm' => 150,
                    ]),
                    'status' => 'active',
                    'base_price' => 1850.00,
                    'production_lead_time' => 7,
                    'profit_margin' => 45.00,
                ]
            );

            $this->command->info("   ✓ Producto: {$product->name}");
            $this->command->info("   ✓ SKU: {$product->sku}");

            // =============================================
            // CREAR BOM (Lista de Materiales)
            // =============================================
            $this->command->info('');
            $this->command->info('📋 Creando BOM...');

            // Limpiar BOM existente
            DB::table('product_materials')
                ->where('product_id', $product->id)
                ->delete();

            // CONSUMO PROMEDIO (talla M/L)
            $bomItems = [
                // Tela principal
                [
                    'sku' => 'TEL-ALG-BLA-001',
                    'quantity' => 1.8000,
                    'is_primary' => true,
                    'notes' => 'Tela base - consumo promedio talla M/L',
                ],
                // Hilos de bordado (4 colores, consumo por color)
                [
                    'sku' => 'HIL-BOR-ROJ-001',
                    'quantity' => 40.0000, // metros
                    'is_primary' => false,
                    'notes' => 'Hilo bordado rojo - flores principales',
                ],
                [
                    'sku' => 'HIL-BOR-ROS-001',
                    'quantity' => 35.0000,
                    'is_primary' => false,
                    'notes' => 'Hilo bordado rosa - flores secundarias',
                ],
                [
                    'sku' => 'HIL-BOR-VER-001',
                    'quantity' => 40.0000,
                    'is_primary' => false,
                    'notes' => 'Hilo bordado verde - hojas y tallos',
                ],
                [
                    'sku' => 'HIL-BOR-AMA-001',
                    'quantity' => 25.0000,
                    'is_primary' => false,
                    'notes' => 'Hilo bordado amarillo - centros de flores',
                ],
                // Hilo de costura
                [
                    'sku' => 'HIL-COS-BLA-001',
                    'quantity' => 50.0000,
                    'is_primary' => false,
                    'notes' => 'Hilo costura blanco - confección',
                ],
                // Listón para moños
                [
                    'sku' => 'LIS-SAT-ROS-001',
                    'quantity' => 1.0000,
                    'is_primary' => false,
                    'notes' => 'Listón satín rosa - moños decorativos',
                ],
            ];

            $totalMaterialsCost = 0;

            foreach ($bomItems as $item) {
                $variant = $variants[$item['sku']] ?? null;
                if (!$variant) continue;

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

            // Actualizar costo de materiales en producto
            $product->update([
                'materials_cost' => $totalMaterialsCost,
                'production_cost' => $totalMaterialsCost,
            ]);

            $this->command->info("   ─────────────────────────────────");
            $this->command->info("   COSTO MATERIALES: \$" . number_format($totalMaterialsCost, 2));

            // =============================================
            // CREAR VARIANTES POR TALLA
            // =============================================
            $this->command->info('');
            $this->command->info('🎨 Creando variantes...');

            // Obtener atributo talla
            $tallaAttr = Attribute::where('slug', 'talla')->first();

            $tallas = [
                ['value' => 'S', 'price' => 1750.00],
                ['value' => 'M', 'price' => 1850.00],
                ['value' => 'L', 'price' => 1950.00],
                ['value' => 'XL', 'price' => 2100.00],
            ];

            foreach ($tallas as $talla) {
                $skuVariant = "{$product->sku}-BLA-{$talla['value']}";

                $variant = ProductVariant::updateOrCreate(
                    ['sku_variant' => $skuVariant],
                    [
                        'uuid' => (string) Str::uuid(),
                        'product_id' => $product->id,
                        'price' => $talla['price'],
                        'attribute_combinations' => json_encode(['talla' => $talla['value'], 'color' => 'Blanco']),
                        'stock_alert' => 2,
                    ]
                );

                // Relacionar con atributo talla si existe
                if ($tallaAttr) {
                    $tallaValue = AttributeValue::where('attribute_id', $tallaAttr->id)
                        ->where('value', $talla['value'])
                        ->first();

                    if ($tallaValue) {
                        DB::table('product_variant_attribute')->updateOrInsert(
                            [
                                'product_variant_id' => $variant->id,
                                'attribute_id' => $tallaAttr->id,
                            ],
                            [
                                'attribute_value_id' => $tallaValue->id,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]
                        );
                    }
                }

                $this->command->info("   ✓ {$skuVariant} - \${$talla['price']}");
            }

            // =============================================
            // RESUMEN
            // =============================================
            $this->command->info('');
            $this->command->info('══════════════════════════════════════════════════');
            $this->command->info('   PRODUCTO HIPIL CREADO');
            $this->command->info('══════════════════════════════════════════════════');
            $this->command->info("   SKU:              {$product->sku}");
            $this->command->info("   Tipo:             {$productType->display_name}");
            $this->command->info("   Requiere medidas: SÍ");
            $this->command->info("   Variantes:        " . count($tallas));
            $this->command->info("   Items BOM:        " . count($bomItems));
            $this->command->info("   Costo materiales: \$" . number_format($totalMaterialsCost, 2));
            $this->command->info("   Precio base:      \$" . number_format($product->base_price, 2));
            $this->command->info('');
        });
    }
}
