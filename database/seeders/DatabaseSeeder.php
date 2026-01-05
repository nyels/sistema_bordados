<?php

namespace Database\Seeders;

use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Application_types;
use App\Models\Category;
use App\Models\Product;
use App\Models\DesignExport;
use App\Models\ProductVariant;
use App\Models\ProductCategory;
use Illuminate\Support\Str;
use App\Models\Design;
use App\Models\Recomendacion;
use App\Models\Estado;
use App\Models\Cliente;
use App\Models\Giro;
use App\Models\ProductExtra;
use App\Models\MaterialCategory;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'master',
            'email' => 'master@gmail.com',
            'password' => bcrypt('1qaz2wsx'),
        ]);



        $this->call([
            EstadoSeeder::class,
            GiroSeeder::class,
            RecomendacionSeeder::class,
            CategorySeeder::class,
            //AttributeSeeder::class,
            ApplicationTypeSeeder::class,

            SystemSettingsSeeder::class,
            UnitsSeeder::class,
            MaterialCategoriesSeeder::class,
            MaterialSeeder::class,
        ]);

        // Tablas: attributes y attribute_values
        $color = Attribute::create(['name' => 'COLOR', 'slug' => 'color']);
        $talla = Attribute::create(['name' => 'TALLA', 'slug' => 'talla']);

        AttributeValue::create(['attribute_id' => $color->id, 'value' => 'Azul Marino', 'hex_color' => '#0000FF']);

        AttributeValue::create(['attribute_id' => $talla->id, 'value' => 'XS', 'order' => 1]);
        AttributeValue::create(['attribute_id' => $talla->id, 'value' => 'S', 'order' => 2]);
        AttributeValue::create(['attribute_id' => $talla->id, 'value' => 'L', 'order' => 3]);
        AttributeValue::create(['attribute_id' => $talla->id, 'value' => 'M', 'order' => 4]);
        AttributeValue::create(['attribute_id' => $talla->id, 'value' => 'XL', 'order' => 5]);
        // Tabla: application_types (Nombres de columna exactos de tu SQL)
        // Tabla: application_types (Nombres de columna exactos de tu SQL)
        Application_types::create([
            'slug' => 'pecho-izq',
            'nombre_aplicacion' => 'Pecho Izquierdo',
            'descripcion' => 'Bordado estándar en la parte frontal izquierda',
            'activo' => 1
        ]);

        Proveedor::create([
            'nombre_proveedor' => 'IGNIS SOLUCIONES INTEGRALES, S.A. DE C.V.',

            'direccion' => 'C 49 D X 42 Y 46 FCO DE MONTEJO',
            'codigo_postal' => '27160',
            'telefono' => '2421234567',
            'email' => 'ignis@gmail.com',
            'estado_id' => 1,
            'giro_id' => '1',
        ]);
        Proveedor::create([
            'nombre_proveedor' => 'GLOBAL SERVICES 4 IT, S.A. DE C.V.',
            'direccion' => 'C 49 D X 42 Y 46 FCO DE MONTEJO II',
            'codigo_postal' => '27160',
            'telefono' => '2421234567',
            'email' => 'global@gmail.com',
            'estado_id' => 1,
            'giro_id' => '3',
        ]);

        // 1. CATEGORÍA DE PRODUCTO
        $prodCat = ProductCategory::firstOrCreate(
            ['slug' => 'uniformes-empresariales'],
            [
                'uuid' => (string) Str::uuid(), // Agregado
                'name' => 'Uniformes Empresariales',
                'is_active' => true
            ]
        );

        // 2. EXTRAS
        $extraAlforzas = ProductExtra::firstOrCreate(
            ['name' => 'Alforzas Delanteras'],
            [
                'uuid' => (string) Str::uuid(), // Agregado
                'cost_addition' => 45.00,
                'price_addition' => 100.00,
                'minutes_addition' => 30
            ]
        );

        $extraCuelloMao = ProductExtra::firstOrCreate(
            ['name' => 'Cuello Tipo Mao'],
            [
                'uuid' => (string) Str::uuid(), // Agregado
                'cost_addition' => 20.00,
                'price_addition' => 50.00,
                'minutes_addition' => 15
            ]
        );

        // 3. ATRIBUTOS
        $attrColor = Attribute::firstOrCreate(['slug' => 'color'], ['name' => 'Color', 'type' => 'select']);
        $valAzul = AttributeValue::firstOrCreate(['attribute_id' => $attrColor->id, 'value' => 'Azul Marino']);

        $attrTalla = Attribute::firstOrCreate(['slug' => 'talla'], ['name' => 'Talla', 'type' => 'select']);
        $valL = AttributeValue::firstOrCreate(['attribute_id' => $attrTalla->id, 'value' => 'L']);

        // 4. TIPO DE APLICACIÓN
        $appType = Application_types::firstOrCreate(
            ['nombre_aplicacion' => 'Pecho Izquierdo'],
            ['descripcion' => 'Ubicación estándar para logo empresarial', 'activo' => true]
        );

        // 5. DISEÑO Y EXPORTACIÓN
        $design = Design::firstOrCreate(
            ['slug' => 'logo-corp-2024'],
            ['name' => 'Logo Corporativo 2024', 'is_active' => true]
        );

        $export = DesignExport::firstOrCreate(
            ['file_name' => 'logo_final.pes'],
            [
                'design_id' => $design->id,
                'application_type' => 'bordado',
                'application_label' => 'Logo Pecho',
                'file_path' => 'designs/logo_final.pes',
                'file_format' => 'PES',
                'stitches_count' => 8500,
                'width_mm' => 90,
                'height_mm' => 50,
                'status' => 'aprobado'
            ]
        );

        // 6. PRODUCTO
        $product = Product::firstOrCreate(
            ['sku' => 'OX-ML-001'],
            [
                'uuid' => (string) Str::uuid(), // Agregado
                'product_category_id' => $prodCat->id,
                'tenant_id' => 1,
                'name' => 'Camisa Oxford Manga Larga',
                'specifications' => [
                    'tipo_tela'  => 'Oxford Premium',
                    'material'   => '60% Algodón / 40% Poliéster',
                    'color'      => 'Blanco Óptico',
                    'hilo'       => 'Calibre 120',
                    'notas'      => 'Tela resistente a arrugas'
                ],
                'status' => 'active'
            ]
        );

        // 7. ASIGNAR EXTRAS
        $product->extras()->syncWithoutDetaching([$extraAlforzas->id, $extraCuelloMao->id]);

        // 8. VARIANTE DE PRODUCTO
        $variant = ProductVariant::firstOrCreate(
            ['sku_variant' => 'OX-ML-AZU-L'],
            [
                'uuid' => (string) Str::uuid(), // Agregado
                'product_id' => $product->id,
                'price' => 450.00,
                'stock_alert' => 10
            ]
        );

        // 9. RELACIONES PIVOTE
        $variant->attributes()->syncWithoutDetaching([
            $valAzul->id => ['attribute_id' => $attrColor->id],
            $valL->id => ['attribute_id' => $attrTalla->id],
        ]);

        $variant->designExports()->syncWithoutDetaching([
            $export->id => ['application_type_id' => $appType->id, 'notes' => 'Bordado estándar']
        ]);

        // 10. CLIENTE
        $recomendacion = Recomendacion::firstOrCreate(['nombre_recomendacion' => 'Facebook']);
        $estado = Estado::firstOrCreate(['nombre_estado' => 'Activo']);

        Cliente::firstOrCreate(
            ['email' => 'cliente.ejemplo@correo.com'],
            [
                'nombre' => 'Juan',
                'apellidos' => 'Pérez',
                'telefono' => '9991234567',
                'direccion' => 'Calle 60 x 50',
                'ciudad' => 'Mérida',
                'estado_id' => $estado->id,
                'recomendacion_id' => $recomendacion->id,
                'activo' => true,
                'busto' => 105.5,
                'alto_cintura' => 45.0,
                'cintura' => 95.2,
                'cadera' => 110.0,
                'largo' => 75.0,
            ]
        );
    }
}
