<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductType;
use App\Models\Material;
use App\Models\MaterialVariant;
use App\Models\MaterialCategory;
use App\Models\Unit;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\ProductVariant;

/**
 * =============================================================================
 * SEEDER PRODUCTIVO: HIPIL BORDADO TRADICIONAL
 * =============================================================================
 *
 * Producto REAL de taller artesanal:
 * - Vestido/Hipil de algodón blanco
 * - Bordado floral tradicional yucateco
 * - Moños decorativos en hombros
 *
 * ORDEN DE EJECUCIÓN:
 * 1. php artisan db:seed --class=UnitsSeeder (si no existe)
 * 2. php artisan db:seed --class=MaterialCategoriesSeeder (si no existe)
 * 3. php artisan db:seed --class=ProductTypeSeeder (si no existe)
 * 4. php artisan db:seed --class=HipilProductSeeder
 * 5. php artisan db:seed --class=HipilSupplierPurchaseSeeder
 */
class HipilProductSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('══════════════════════════════════════════════════');
        $this->command->info('   SEMBRANDO PRODUCTO: HIPIL BORDADO TRADICIONAL');
        $this->command->info('══════════════════════════════════════════════════');

        DB::transaction(function () {
            // =============================================
            // PASO 1: VERIFICAR/CREAR CATEGORÍA DE PRODUCTO
            // =============================================
            $this->command->info('');
            $this->command->info('📦 Verificando categoría de producto...');

            $productCategory = ProductCategory::firstOrCreate(
                ['slug' => 'prendas-tradicionales'],
                [
                    'uuid' => (string) Str::uuid(),
                    'name' => 'Prendas Tradicionales',
                    'description' => 'Prendas artesanales con bordado tradicional yucateco',
                    'is_active' => true,
                ]
            );
            $this->command->info("   ✓ Categoría: {$productCategory->name}");

            // =============================================
            // PASO 2: VERIFICAR TIPO DE PRODUCTO
            // =============================================
            $this->command->info('');
            $this->command->info('🏷️ Verificando tipo de producto...');

            $productType = ProductType::where('code', 'GARMENT_CUSTOM')->first();
            if (!$productType) {
                $this->command->error('   ✗ ProductType GARMENT_CUSTOM no existe. Ejecute ProductTypeSeeder primero.');
                return;
            }
            $this->command->info("   ✓ Tipo: {$productType->display_name} (requires_measurements: " . ($productType->requires_measurements ? 'SÍ' : 'NO') . ")");

            // =============================================
            // PASO 3: VERIFICAR UNIDADES
            // =============================================
            $this->command->info('');
            $this->command->info('📏 Verificando unidades...');

            $units = Unit::whereIn('slug', ['metro', 'pieza', 'cono'])->get()->keyBy('slug');
            if ($units->count() < 3) {
                $this->command->error('   ✗ Faltan unidades. Ejecute UnitsSeeder primero.');
                return;
            }
            $this->command->info("   ✓ Metro: ID {$units['metro']->id}");
            $this->command->info("   ✓ Pieza: ID {$units['pieza']->id}");
            $this->command->info("   ✓ Cono: ID {$units['cono']->id}");

            // =============================================
            // PASO 4: VERIFICAR/CREAR CATEGORÍAS DE MATERIAL
            // =============================================
            $this->command->info('');
            $this->command->info('📂 Verificando categorías de material...');

            $matCategories = MaterialCategory::whereIn('slug', ['telas', 'hilos', 'avios'])->get()->keyBy('slug');

            // Si no existen, crearlas
            if (!isset($matCategories['telas'])) {
                $matCategories['telas'] = MaterialCategory::create([
                    'name' => 'TELAS / TEXTILES',
                    'slug' => 'telas',
                    'description' => 'Telas y textiles para confección',
                    'activo' => true,
                ]);
            }
            if (!isset($matCategories['hilos'])) {
                $matCategories['hilos'] = MaterialCategory::create([
                    'name' => 'HILOS BORDADO',
                    'slug' => 'hilos',
                    'description' => 'Hilos para bordado industrial y manual',
                    'activo' => true,
                ]);
            }
            if (!isset($matCategories['avios'])) {
                $matCategories['avios'] = MaterialCategory::create([
                    'name' => 'AVÍOS (BOTONES/CIERRES)',
                    'slug' => 'avios',
                    'description' => 'Avíos, listones, cintas y accesorios',
                    'activo' => true,
                ]);
            }

            foreach ($matCategories as $slug => $cat) {
                $this->command->info("   ✓ {$cat->name}");
            }

            // =============================================
            // PASO 5: CREAR MATERIALES Y VARIANTES
            // =============================================
            $this->command->info('');
            $this->command->info('🧵 Creando materiales y variantes...');

            $materialVariants = [];

            // --- TELA ALGODÓN BLANCO ---
            $telaAlgodon = Material::firstOrCreate(
                ['slug' => 'algodon-manta-blanca'],
                [
                    'uuid' => (string) Str::uuid(),
                    'material_category_id' => $matCategories['telas']->id,
                    'name' => 'Algodón Manta Blanca',
                    'composition' => '100% Algodón',
                    'description' => 'Manta de algodón blanca para hipiles y blusas tradicionales',
                    'base_unit_id' => $units['metro']->id,
                    'consumption_unit_id' => $units['metro']->id,
                    'conversion_factor' => 1.0,
                    'has_color' => true,
                    'activo' => true,
                ]
            );
            $this->command->info("   ✓ Material: {$telaAlgodon->name}");

            $materialVariants['tela_blanca'] = MaterialVariant::firstOrCreate(
                ['sku' => 'TEL-ALG-BLA-001'],
                [
                    'uuid' => (string) Str::uuid(),
                    'material_id' => $telaAlgodon->id,
                    'color' => 'Blanco',
                    'current_stock' => 0,
                    'min_stock_alert' => 10,
                    'current_value' => 0,
                    'average_cost' => 0,
                    'last_purchase_cost' => 0,
                    'activo' => true,
                ]
            );
            $this->command->info("   ✓ Variante: {$materialVariants['tela_blanca']->sku} - {$materialVariants['tela_blanca']->color}");

            // --- HILO BORDADO ROJO ---
            $hiloBordado = Material::firstOrCreate(
                ['slug' => 'hilo-bordado-polyester'],
                [
                    'uuid' => (string) Str::uuid(),
                    'material_category_id' => $matCategories['hilos']->id,
                    'name' => 'Hilo Bordado Polyester 120D/2',
                    'composition' => '100% Polyester',
                    'description' => 'Hilo para bordado industrial, alta resistencia',
                    'base_unit_id' => $units['cono']->id,
                    'consumption_unit_id' => $units['metro']->id,
                    'conversion_factor' => 5000.0, // 1 cono = 5000 metros
                    'has_color' => true,
                    'activo' => true,
                ]
            );
            $this->command->info("   ✓ Material: {$hiloBordado->name}");

            // Variantes de hilo en varios colores
            $coloresHilo = [
                ['color' => 'Rojo', 'sku' => 'HIL-BOR-ROJ-001'],
                ['color' => 'Rosa', 'sku' => 'HIL-BOR-ROS-001'],
                ['color' => 'Verde', 'sku' => 'HIL-BOR-VER-001'],
                ['color' => 'Amarillo', 'sku' => 'HIL-BOR-AMA-001'],
            ];

            foreach ($coloresHilo as $ch) {
                $key = 'hilo_' . strtolower($ch['color']);
                $materialVariants[$key] = MaterialVariant::firstOrCreate(
                    ['sku' => $ch['sku']],
                    [
                        'uuid' => (string) Str::uuid(),
                        'material_id' => $hiloBordado->id,
                        'color' => $ch['color'],
                        'current_stock' => 0,
                        'min_stock_alert' => 2,
                        'current_value' => 0,
                        'average_cost' => 0,
                        'last_purchase_cost' => 0,
                        'activo' => true,
                    ]
                );
                $this->command->info("   ✓ Variante: {$materialVariants[$key]->sku} - {$ch['color']}");
            }

            // --- HILO COSTURA ---
            $hiloCostura = Material::firstOrCreate(
                ['slug' => 'hilo-costura-algodon'],
                [
                    'uuid' => (string) Str::uuid(),
                    'material_category_id' => $matCategories['hilos']->id,
                    'name' => 'Hilo Costura Algodón',
                    'composition' => '100% Algodón',
                    'description' => 'Hilo de costura para prendas de algodón',
                    'base_unit_id' => $units['cono']->id,
                    'consumption_unit_id' => $units['metro']->id,
                    'conversion_factor' => 3000.0, // 1 cono = 3000 metros
                    'has_color' => true,
                    'activo' => true,
                ]
            );
            $this->command->info("   ✓ Material: {$hiloCostura->name}");

            $materialVariants['hilo_costura_blanco'] = MaterialVariant::firstOrCreate(
                ['sku' => 'HIL-COS-BLA-001'],
                [
                    'uuid' => (string) Str::uuid(),
                    'material_id' => $hiloCostura->id,
                    'color' => 'Blanco',
                    'current_stock' => 0,
                    'min_stock_alert' => 3,
                    'current_value' => 0,
                    'average_cost' => 0,
                    'last_purchase_cost' => 0,
                    'activo' => true,
                ]
            );
            $this->command->info("   ✓ Variante: {$materialVariants['hilo_costura_blanco']->sku} - Blanco");

            // --- LISTÓN SATÍN ROSA ---
            $listonSatin = Material::firstOrCreate(
                ['slug' => 'liston-satin'],
                [
                    'uuid' => (string) Str::uuid(),
                    'material_category_id' => $matCategories['avios']->id,
                    'name' => 'Listón Satín 2.5cm',
                    'composition' => '100% Polyester',
                    'description' => 'Listón satinado para moños decorativos',
                    'base_unit_id' => $units['metro']->id,
                    'consumption_unit_id' => $units['metro']->id,
                    'conversion_factor' => 1.0,
                    'has_color' => true,
                    'activo' => true,
                ]
            );
            $this->command->info("   ✓ Material: {$listonSatin->name}");

            $materialVariants['liston_rosa'] = MaterialVariant::firstOrCreate(
                ['sku' => 'LIS-SAT-ROS-001'],
                [
                    'uuid' => (string) Str::uuid(),
                    'material_id' => $listonSatin->id,
                    'color' => 'Rosa',
                    'current_stock' => 0,
                    'min_stock_alert' => 20,
                    'current_value' => 0,
                    'average_cost' => 0,
                    'last_purchase_cost' => 0,
                    'activo' => true,
                ]
            );
            $this->command->info("   ✓ Variante: {$materialVariants['liston_rosa']->sku} - Rosa");

            // =============================================
            // PASO 6: CREAR PRODUCTO HIPIL
            // =============================================
            $this->command->info('');
            $this->command->info('👗 Creando producto Hipil...');

            $product = Product::firstOrCreate(
                ['sku' => 'HIP-TRA-BLA-001'],
                [
                    'uuid' => (string) Str::uuid(),
                    'tenant_id' => 1,
                    'product_category_id' => $productCategory->id,
                    'product_type_id' => $productType->id,
                    'name' => 'Hipil Bordado Tradicional',
                    'description' => 'Vestido artesanal de algodón blanco con bordado floral tradicional yucateco en tonos rojos, rosas, verdes y amarillos. Incluye moños decorativos de listón satín rosa en los hombros. Prenda a medida que requiere medidas del cliente.',
                    'specifications' => [
                        'material_base' => 'Algodón manta blanca',
                        'tipo_bordado' => 'Floral tradicional yucateco',
                        'colores_bordado' => ['Rojo', 'Rosa', 'Verde', 'Amarillo'],
                        'largo_estandar_cm' => 105,
                        'ancho_tela_cm' => 140,
                        'consumo_tela_m' => 1.8,
                    ],
                    'status' => 'active',
                    'base_price' => 1850.00, // Precio venta sugerido
                    'production_lead_time' => 7, // 7 días de producción
                    'production_cost' => 0, // Se calculará con BOM
                    'profit_margin' => 45.00,
                ]
            );
            $this->command->info("   ✓ Producto: {$product->name}");
            $this->command->info("   ✓ SKU: {$product->sku}");
            $this->command->info("   ✓ Tipo: {$productType->display_name}");
            $this->command->info("   ✓ Requiere medidas: SÍ");

            // =============================================
            // PASO 7: CREAR VARIANTES DEL PRODUCTO
            // =============================================
            $this->command->info('');
            $this->command->info('🎨 Creando variantes del producto...');

            // Obtener atributos
            $colorAttr = Attribute::where('slug', 'color')->first();
            $tallaAttr = Attribute::where('slug', 'talla')->first();

            if (!$colorAttr || !$tallaAttr) {
                $this->command->error('   ✗ No se encontraron los atributos COLOR y TALLA');
                return;
            }

            // Obtener valores de atributos
            $colorBlanco = AttributeValue::where('attribute_id', $colorAttr->id)
                ->where('value', 'BLANCO')->first();

            // Tallas disponibles para el Hipil
            $tallas = AttributeValue::where('attribute_id', $tallaAttr->id)
                ->whereIn('value', ['S', 'M', 'L', 'XL', 'ESTANDAR'])
                ->get();

            if (!$colorBlanco) {
                $this->command->error('   ✗ No se encontró el color BLANCO');
                return;
            }

            // Precios por talla (ajustes sobre precio base)
            $preciosPorTalla = [
                'S' => 1750.00,
                'M' => 1850.00,
                'L' => 1950.00,
                'XL' => 2050.00,
                'ESTANDAR' => 1850.00,
            ];

            foreach ($tallas as $talla) {
                $skuVariant = "{$product->sku}-BLA-{$talla->value}";
                $precio = $preciosPorTalla[$talla->value] ?? 1850.00;

                // Crear o actualizar variante
                $variant = ProductVariant::firstOrCreate(
                    [
                        'product_id' => $product->id,
                        'sku_variant' => $skuVariant,
                    ],
                    [
                        'uuid' => (string) Str::uuid(),
                        'price' => $precio,
                        'attribute_combinations' => [
                            'color' => 'BLANCO',
                            'talla' => $talla->value,
                        ],
                        'stock_alert' => 2,
                    ]
                );

                // Relacionar con valores de atributos (pivot)
                // Color
                $existsColor = DB::table('product_variant_attribute')
                    ->where('product_variant_id', $variant->id)
                    ->where('attribute_id', $colorAttr->id)
                    ->whereNull('deleted_at')
                    ->exists();

                if (!$existsColor) {
                    DB::table('product_variant_attribute')->insert([
                        'product_variant_id' => $variant->id,
                        'attribute_id' => $colorAttr->id,
                        'attribute_value_id' => $colorBlanco->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                // Talla
                $existsTalla = DB::table('product_variant_attribute')
                    ->where('product_variant_id', $variant->id)
                    ->where('attribute_id', $tallaAttr->id)
                    ->whereNull('deleted_at')
                    ->exists();

                if (!$existsTalla) {
                    DB::table('product_variant_attribute')->insert([
                        'product_variant_id' => $variant->id,
                        'attribute_id' => $tallaAttr->id,
                        'attribute_value_id' => $talla->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $this->command->info("   ✓ Variante: {$skuVariant} - Blanco/{$talla->value} @ \${$precio}");
            }

            // =============================================
            // PASO 8: CREAR BOM (LISTA DE MATERIALES)
            // =============================================
            $this->command->info('');
            $this->command->info('📋 Creando BOM (Lista de Materiales)...');

            // Obtener las variantes de producto creadas para asignar materiales específicos
            $productVariants = ProductVariant::where('product_id', $product->id)->get()->keyBy(function($v) {
                // Extraer la talla del SKU (último segmento)
                $parts = explode('-', $v->sku_variant);
                return end($parts); // S, M, L, XL, ESTANDAR
            });

            // ============================================
            // CONSUMO DE TELA POR TALLA (metros)
            // ============================================
            // Hipil tradicional yucateco - consumos reales:
            // - S: 1.5m (busto ~85cm, largo 95cm)
            // - M: 1.7m (busto ~92cm, largo 100cm)
            // - L: 1.9m (busto ~100cm, largo 105cm)
            // - XL: 2.2m (busto ~110cm, largo 110cm)
            // - ESTANDAR: 1.8m (talla promedio M/L)
            $telasPorTalla = [
                'S' => 1.5000,
                'M' => 1.7000,
                'L' => 1.9000,
                'XL' => 2.2000,
                'ESTANDAR' => 1.8000,
            ];

            // Crear BOM de TELA específico por talla
            foreach ($telasPorTalla as $talla => $metros) {
                if (!isset($productVariants[$talla])) continue;

                $variantId = $productVariants[$talla]->id;

                $exists = DB::table('product_materials')
                    ->where('product_id', $product->id)
                    ->where('material_variant_id', $materialVariants['tela_blanca']->id)
                    ->whereJsonContains('active_for_variants', $variantId)
                    ->whereNull('deleted_at')
                    ->exists();

                if (!$exists) {
                    DB::table('product_materials')->insert([
                        'product_id' => $product->id,
                        'material_variant_id' => $materialVariants['tela_blanca']->id,
                        'quantity' => $metros,
                        'unit_cost' => $materialVariants['tela_blanca']->average_cost,
                        'is_primary' => true,
                        'active_for_variants' => json_encode([$variantId]),
                        'notes' => "Tela para talla {$talla}",
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $this->command->info("   ✓ BOM Tela [{$talla}]: {$metros}m × Algodón Manta");
                }
            }

            // ============================================
            // CONSUMO DE HILOS POR TALLA (metros)
            // ============================================
            // El bordado escala con el tamaño de la prenda
            $hilosPorTalla = [
                'S' => ['bordado' => 120, 'costura' => 40],
                'M' => ['bordado' => 140, 'costura' => 45],
                'L' => ['bordado' => 160, 'costura' => 50],
                'XL' => ['bordado' => 180, 'costura' => 60],
                'ESTANDAR' => ['bordado' => 150, 'costura' => 50],
            ];

            $hilosBordado = [
                'hilo_rojo' => 'Rojo - flores principales',
                'hilo_rosa' => 'Rosa - flores secundarias',
                'hilo_verde' => 'Verde - hojas y tallos',
                'hilo_amarillo' => 'Amarillo - centros de flores',
            ];

            // Crear BOM de HILOS BORDADO específico por talla
            foreach ($hilosPorTalla as $talla => $consumos) {
                if (!isset($productVariants[$talla])) continue;

                $variantId = $productVariants[$talla]->id;

                foreach ($hilosBordado as $key => $desc) {
                    $exists = DB::table('product_materials')
                        ->where('product_id', $product->id)
                        ->where('material_variant_id', $materialVariants[$key]->id)
                        ->whereJsonContains('active_for_variants', $variantId)
                        ->whereNull('deleted_at')
                        ->exists();

                    if (!$exists) {
                        DB::table('product_materials')->insert([
                            'product_id' => $product->id,
                            'material_variant_id' => $materialVariants[$key]->id,
                            'quantity' => $consumos['bordado'],
                            'unit_cost' => $materialVariants[$key]->average_cost,
                            'is_primary' => false,
                            'active_for_variants' => json_encode([$variantId]),
                            'notes' => "Hilo {$desc} [{$talla}]",
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                // Hilo costura
                $exists = DB::table('product_materials')
                    ->where('product_id', $product->id)
                    ->where('material_variant_id', $materialVariants['hilo_costura_blanco']->id)
                    ->whereJsonContains('active_for_variants', $variantId)
                    ->whereNull('deleted_at')
                    ->exists();

                if (!$exists) {
                    DB::table('product_materials')->insert([
                        'product_id' => $product->id,
                        'material_variant_id' => $materialVariants['hilo_costura_blanco']->id,
                        'quantity' => $consumos['costura'],
                        'unit_cost' => $materialVariants['hilo_costura_blanco']->average_cost,
                        'is_primary' => false,
                        'active_for_variants' => json_encode([$variantId]),
                        'notes' => "Hilo costura [{$talla}]",
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $this->command->info("   ✓ BOM Hilos [{$talla}]: {$consumos['bordado']}m bordado + {$consumos['costura']}m costura");
            }

            // ============================================
            // LISTÓN - MATERIAL GLOBAL (igual para todas las tallas)
            // ============================================
            // El listón de los moños es igual independiente de la talla
            // Por eso va como GLOBAL (sin active_for_variants)
            $existsListon = DB::table('product_materials')
                ->where('product_id', $product->id)
                ->where('material_variant_id', $materialVariants['liston_rosa']->id)
                ->whereNull('deleted_at')
                ->exists();

            if (!$existsListon) {
                DB::table('product_materials')->insert([
                    'product_id' => $product->id,
                    'material_variant_id' => $materialVariants['liston_rosa']->id,
                    'quantity' => 1.0000,
                    'unit_cost' => $materialVariants['liston_rosa']->average_cost,
                    'is_primary' => false,
                    'active_for_variants' => null, // NULL = GLOBAL (aplica a todas las variantes)
                    'notes' => "Listón moños - Material común para todas las tallas",
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->command->info("   ✓ BOM Listón: 1m (GLOBAL - todas las tallas)");

            // Contar total de registros BOM creados
            $bomCount = DB::table('product_materials')
                ->where('product_id', $product->id)
                ->whereNull('deleted_at')
                ->count();

            // =============================================
            // PASO 9: CALCULAR Y GUARDAR COSTO TOTAL DE MATERIALES
            // =============================================
            $totalMaterialsCost = DB::table('product_materials')
                ->where('product_id', $product->id)
                ->whereNull('deleted_at')
                ->sum(DB::raw('quantity * unit_cost'));

            $product->update(['materials_cost' => $totalMaterialsCost]);
            $this->command->info("   ✓ Costo total materiales: \${$totalMaterialsCost}");

            // =============================================
            // RESUMEN FINAL
            // =============================================
            $this->command->info('');
            $this->command->info('══════════════════════════════════════════════════');
            $this->command->info('   RESUMEN HIPIL CREADO');
            $this->command->info('══════════════════════════════════════════════════');
            $this->command->info("   Producto: {$product->name}");
            $this->command->info("   SKU: {$product->sku}");
            $this->command->info("   Variantes: " . $productVariants->count());
            $this->command->info("   Registros BOM: {$bomCount} (1 global + específicos por talla)");
            $this->command->info('');
            $this->command->info('   MATERIAL GLOBAL:');
            $this->command->info("   • Listón Rosa: 1m (aplica a todas las variantes)");
            $this->command->info('');
            $this->command->info('   CONSUMO POR TALLA (Específico):');
            foreach ($telasPorTalla as $t => $m) {
                $hilo = $hilosPorTalla[$t] ?? ['bordado' => 0, 'costura' => 0];
                $this->command->info("   • {$t}: {$m}m tela, {$hilo['bordado']}m hilo bordado, {$hilo['costura']}m costura");
            }
            $this->command->info('');
            $this->command->info('   NOTA: Los costos de materiales se actualizarán');
            $this->command->info('         cuando se ejecute HipilSupplierPurchaseSeeder');
            $this->command->info('');
        });
    }
}
