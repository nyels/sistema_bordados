<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductCategory;
use App\Models\ProductExtra;
use App\Models\Product;
use App\Models\DesignExport; // Tu tabla existente
use App\Models\Application_types; // Tu tabla existente
use Illuminate\Support\Facades\DB;

class ProductSystemSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // 1. Crear Categoría
            $cat = ProductCategory::create([
                'name' => 'Cosmetiqueras',
                'slug' => 'cosmetiqueras',
                'description' => 'Línea de viaje y belleza'
            ]);

            // 2. Crear Extras
            $extra1 = ProductExtra::create([
                'name' => 'Forro Impermeable',
                'cost_addition' => 15.00,
                'price_addition' => 35.00
            ]);

            $extra2 = ProductExtra::create([
                'name' => 'Cierre Reforzado',
                'cost_addition' => 5.00,
                'price_addition' => 12.00
            ]);

            // 3. Crear Producto Maestro
            $product = Product::create([
                'product_category_id' => $cat->id,
                'name' => 'Cosmetiquera Mezclilla XL',
                'sku' => 'COSM-001',
                'specifications' => ['tela' => 'Mezclilla', 'hilo' => 'Poliéster'],
                'status' => 'active'
            ]);

            $product->extras()->attach([$extra1->id, $extra2->id]);

            // 4. Crear Variantes
            $variant = $product->variants()->create([
                'sku_variant' => 'COSM-001-AZUL',
                'price' => 180.00,
                'stock_alert' => 5
            ]);

            // 5. Vincular con tus datos reales (Ajusta los IDs según tu DB)
            // Tomamos el primer bordado y la primera posición que existan en tu DB
            $designExport = DesignExport::first();
            $appType = Application_types::first();

            if ($designExport && $appType) {
                $variant->designExports()->attach($designExport->id, [
                    'application_type_id' => $appType->id,
                    'notes' => 'Bordado frontal estándar'
                ]);
            }
        });
    }
}
