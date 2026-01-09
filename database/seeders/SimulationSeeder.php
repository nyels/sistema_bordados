<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\MaterialCategory;
use App\Models\Material;
use App\Models\ProductCategory;
use App\Models\Product;
use App\Models\Design;
use App\Models\Proveedor;
use App\Models\Cliente;
use App\Models\Produccion;
use App\Models\Unit;
use App\Models\Estado;
use App\Models\Giro;
use App\Models\Recomendacion;
use Carbon\Carbon;

class SimulationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * VACÍA EL SISTEMA (EXCEPTO TABLAS MAESTRAS DEL SISTEMA) Y SIMULA UN FLUJO COMPLETO
     * BASADO EN LA IMAGEN DE LA BLUSA BORDADA.
     *
     * @return void
     */
    public function run()
    {
        // 1. TRUNCATE / LIMPIEZA
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $tablesToTruncate = [
            'activity_log',
            'product_extras',
            'product_design',
            'product_material',
            'produccion',
            'produccions',
            'productions',
            'products',
            'design_multimedia',
            'designs',
            'material_variants',
            'materials',
            'material_categories',
            'product_categories',
            'proveedores',
            'proveedors',
            'clientes',
            'purchases',
            'purchase_details',
            'inventories',
            'design_variant_attributes',
            'attribute_values',
            'attributes',
            'giros',
            'recomendaciones',
            'recomendacion',
            'recomendacions',
            'application_types'
        ];

        foreach ($tablesToTruncate as $table) {
            if (\Schema::hasTable($table)) {
                DB::table($table)->truncate();
                $this->command->info("Tabla vaciada: $table");
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 2. CREACIÓN DE DATOS MAESTROS
        $estado = Estado::first();
        $giro = Giro::create(['nombre_giro' => 'TEXTIL', 'activo' => true]);
        $recomendacion = Recomendacion::create(['nombre_recomendacion' => 'INSTAGRAM', 'activo' => true]);

        // Application Types (Ubicación del diseño)
        // Schema: nombre_aplicacion, slug, activo
        $appTypePechoId = DB::table('application_types')->insertGetId([
            'nombre_aplicacion' => 'PECHO',
            'slug' => 'pecho',
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        DB::table('application_types')->insert([
            'nombre_aplicacion' => 'MANGA',
            'slug' => 'manga',
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        Proveedor::unguard();
        Cliente::unguard();

        $proveedor = Proveedor::create([
            'nombre_proveedor' => 'Textiles y Mercería El Centro',
            'nombre_contacto' => 'Juan Pérez',
            'telefono' => '5551234567',
            'email' => 'contacto@textilescentro.com',
            'direccion' => 'Av. Independencia #100',
            'ciudad' => 'Ciudad de México',
            'codigo_postal' => '06000',
            'estado_id' => $estado ? $estado->id : 1,
            'giro_id' => $giro->id,
            'activo' => true,
        ]);

        $cliente = Cliente::create([
            'nombre' => 'Boutique Artesanal',
            'apellidos' => 'Raíces',
            'telefono' => '9999876543',
            'email' => 'ventas@raicesboutique.com',
            'direccion' => 'Calle 60 #400, Centro',
            'ciudad' => 'Mérida',
            'recomendacion_id' => $recomendacion->id,
            'estado_id' => $estado ? $estado->id : 1,
            'activo' => true,
        ]);

        Proveedor::reguard();
        Cliente::reguard();

        // 3.0 UNIDADES
        $metro = Unit::where('name', 'METRO')->orWhere('symbol', 'm')->first();
        if (!$metro) {
            $metro = Unit::create(['name' => 'METRO', 'symbol' => 'm', 'is_base' => true, 'slug' => 'metro']);
        }

        $pieza = Unit::where('name', 'PIEZA')->orWhere('symbol', 'pza')->first();
        if (!$pieza) {
            $pieza = Unit::create(['name' => 'PIEZA', 'symbol' => 'pza', 'is_base' => true, 'slug' => 'pieza']);
        }

        // 3.1 CATEGORÍAS
        $catTelas = MaterialCategory::create(['name' => 'TELAS', 'description' => 'Telas base', 'base_unit_id' => $metro->id, 'has_color' => true]);
        $catHilos = MaterialCategory::create(['name' => 'HILOS BORDADO', 'description' => 'Hilos', 'base_unit_id' => $pieza->id, 'has_color' => true]);
        $catMerceria = MaterialCategory::create(['name' => 'MERCERÍA', 'description' => 'Adornos', 'base_unit_id' => $metro->id, 'has_color' => true]);

        // Productos
        $catRopa = ProductCategory::create([
            'name' => 'ROPA TÍPICA',
            'description' => 'Prendas con bordados tradicionales',
            'slug' => 'ropa-tipica'
        ]);

        // 4. MATERIALES

        // M1: Lino Blanco
        $lino = Material::create([
            'material_category_id' => $catTelas->id,
            'name' => 'LINO BLANCO IMPORTADO',
            'type' => 'fabric',
            'activo' => true
        ]);
        $lino->variants()->create([
            'sku' => 'LIN-WHT-01',
            'color' => '#FFFFFF',
            'current_stock' => 50,
            'current_value' => 50 * 180,
            'average_cost' => 180.00,
            'min_stock_alert' => 10,
            'activo' => true
        ]);
        $linoVarId = $lino->variants->first()->id;


        // M2-M5: Hilos
        $hiloRosa = Material::create([
            'material_category_id' => $catHilos->id,
            'name' => 'HILO BORDAR ROSA FUCSIA',
            'activo' => true
        ]);
        $hiloRosa->variants()->create([
            'sku' => 'HIL-ROS-01',
            'color' => '#FF007F',
            'current_stock' => 20,
            'average_cost' => 45.00,
            'min_stock_alert' => 5,
        ]);
        $hiloRosaVarId = $hiloRosa->variants->first()->id;

        $hiloAmarillo = Material::create([
            'material_category_id' => $catHilos->id,
            'name' => 'HILO BORDAR AMARILLO ORO',
            'activo' => true
        ]);
        $hiloAmarillo->variants()->create([
            'sku' => 'HIL-AMA-01',
            'color' => '#FFD700',
            'current_stock' => 20,
            'average_cost' => 45.00,
            'min_stock_alert' => 5,
        ]);
        $hiloAmarilloVarId = $hiloAmarillo->variants->first()->id;

        $hiloMorado = Material::create([
            'material_category_id' => $catHilos->id,
            'name' => 'HILO BORDAR MORADO OBISPO',
            'activo' => true
        ]);
        $hiloMorado->variants()->create([
            'sku' => 'HIL-MOR-01',
            'color' => '#800080',
            'current_stock' => 15,
            'average_cost' => 45.00,
            'min_stock_alert' => 5,
        ]);
        $hiloMoradoVarId = $hiloMorado->variants->first()->id;

        $hiloVerde = Material::create([
            'material_category_id' => $catHilos->id,
            'name' => 'HILO BORDAR VERDE MUSGO',
            'activo' => true
        ]);
        $hiloVerde->variants()->create([
            'sku' => 'HIL-VER-01',
            'color' => '#4B5320',
            'current_stock' => 18,
            'average_cost' => 45.00,
            'min_stock_alert' => 5,
        ]);
        $hiloVerdeVarId = $hiloVerde->variants->first()->id;

        // M6: Listón
        $liston = Material::create([
            'material_category_id' => $catMerceria->id,
            'name' => 'LISTÓN SATÍN FUCSIA 4CM',
            'activo' => true
        ]);
        $liston->variants()->create([
            'sku' => 'LIS-FUC-04',
            'color' => '#FF00FF',
            'current_stock' => 100,
            'average_cost' => 8.50,
            'min_stock_alert' => 20,
        ]);
        $listonVarId = $liston->variants->first()->id;

        // M7: Encaje
        $encaje = Material::create([
            'material_category_id' => $catMerceria->id,
            'name' => 'ENCAJE BOLILLO BLANCO',
            'activo' => true
        ]);
        $encaje->variants()->create([
            'sku' => 'ENC-BLA-01',
            'color' => '#F8F9FA',
            'current_stock' => 60,
            'average_cost' => 12.00,
            'min_stock_alert' => 10,
        ]);
        $encajeVarId = $encaje->variants->first()->id;

        // 5. DISEÑO (EMB)
        $name = 'BORDADO FLORES ROCOCÓ PECHO';
        $design = Design::create([
            'name' => $name,
            'slug' => Str::slug($name),
            'code' => 'D-2026-001',
            'category_id' => null,
            'stitch_count' => 15400,
            'dimensions' => '25x15 cm',
            'path' => 'designs/simulacion_rococo.dst',
            'image_path' => null,
            'is_active' => true,
        ]);


        // 6. PRODUCTO
        $producto = Product::create([
            'name' => 'BLUSA ARTESANAL LINO BORDADO FLORES',
            'description' => 'Blusa de lino blanco con bordado floral en pecho, detalles de encaje y listón fucsia en hombros.',
            'product_category_id' => $catRopa->id,
            'base_price' => 850.00,
            'sku' => 'BLU-LINO-ROC-001',
            'is_active' => true,
        ]);

        // Relacionar Producto - Diseño (CORREGIDO: application_type_id)
        if (\Schema::hasTable('product_design')) {
            DB::table('product_design')->insert([
                'product_id' => $producto->id,
                'design_id' => $design->id,
                'application_type_id' => $appTypePechoId,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // Relacionar Producto - Materiales (BOM) (CORREGIDO: material_variant_id)
        if (\Schema::hasTable('product_material')) {
            // Lino
            DB::table('product_material')->insert([
                'product_id' => $producto->id,
                'material_variant_id' => $linoVarId,
                'quantity' => 1.20,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            // Encaje
            DB::table('product_material')->insert([
                'product_id' => $producto->id,
                'material_variant_id' => $encajeVarId,
                'quantity' => 2.00,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            // Listón
            DB::table('product_material')->insert([
                'product_id' => $producto->id,
                'material_variant_id' => $listonVarId,
                'quantity' => 1.00,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            // Hilos (0.1 por blusa)
            DB::table('product_material')->insert([
                'product_id' => $producto->id,
                'material_variant_id' => $hiloRosaVarId,
                'quantity' => 0.10,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            DB::table('product_material')->insert([
                'product_id' => $producto->id,
                'material_variant_id' => $hiloAmarilloVarId,
                'quantity' => 0.10,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            DB::table('product_material')->insert([
                'product_id' => $producto->id,
                'material_variant_id' => $hiloMoradoVarId,
                'quantity' => 0.10,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            DB::table('product_material')->insert([
                'product_id' => $producto->id,
                'material_variant_id' => $hiloVerdeVarId,
                'quantity' => 0.10,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // 7. ORDEN DE PRODUCCIÓN 
        $table = \Schema::hasTable('produccions') ? 'produccions' : (\Schema::hasTable('productions') ? 'productions' : 'produccion');
        if (\Schema::hasTable($table)) {
            DB::table($table)->insert([
                'folio' => 'OP-2026-0001',
                'product_id' => $producto->id,
                'cantidad' => 12,
                'fecha_inicio' => Carbon::today(),
                'fecha_entrega_estimada' => Carbon::today()->addDays(5),
                'status' => 'en_proceso',
                'prioridad' => 'alta',
                'notas' => 'Pedido especial urgente para Boutique Raíces.',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        $this->command->info('SIMULACIÓN COMPLETADA: Sistema configurado con datos de "Blusa Artesanal".');
    }
}
