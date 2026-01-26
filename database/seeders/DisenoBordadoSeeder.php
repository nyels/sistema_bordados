<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Design;
use App\Models\DesignExport;
use App\Models\Category;

class DisenoBordadoSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('══════════════════════════════════════════════════');
        $this->command->info('   SEMBRANDO DISEÑOS DE BORDADO');
        $this->command->info('══════════════════════════════════════════════════');

        DB::transaction(function () {
            // =============================================
            // CREAR CATEGORÍAS DE DISEÑO
            // =============================================
            $this->command->info('');
            $this->command->info('📁 Creando categorías de diseño...');

            $catFloral = Category::updateOrCreate(
                ['slug' => 'florales'],
                [
                    'name' => 'Florales',
                    'description' => 'Diseños con motivos florales tradicionales',
                    'is_active' => true,
                ]
            );
            $this->command->info("   ✓ {$catFloral->name}");

            $catLogos = Category::updateOrCreate(
                ['slug' => 'logos-marcas'],
                [
                    'name' => 'Logos y Marcas',
                    'description' => 'Diseños de logos empresariales y marcas',
                    'is_active' => true,
                ]
            );
            $this->command->info("   ✓ {$catLogos->name}");

            $catGeometricos = Category::updateOrCreate(
                ['slug' => 'geometricos'],
                [
                    'name' => 'Geométricos',
                    'description' => 'Diseños con patrones geométricos tradicionales',
                    'is_active' => true,
                ]
            );
            $this->command->info("   ✓ {$catGeometricos->name}");

            // =============================================
            // DISEÑO 1: RAMO FLORAL TRADICIONAL
            // =============================================
            $this->command->info('');
            $this->command->info('🌸 Creando diseño: Ramo Floral Tradicional...');

            $design1 = Design::updateOrCreate(
                ['slug' => 'ramo-floral-tradicional'],
                [
                    'name' => 'Ramo Floral Tradicional',
                    'description' => 'Diseño de bordado floral tradicional yucateco con rosas, hojas y tallos. Ideal para hipiles y blusas.',
                    'is_active' => true,
                ]
            );

            DB::table('category_design')->updateOrInsert(
                ['design_id' => $design1->id, 'category_id' => $catFloral->id],
                ['created_at' => now(), 'updated_at' => now()]
            );

            DesignExport::updateOrCreate(
                ['design_id' => $design1->id, 'file_name' => 'ramo_floral.dst'],
                [
                    'application_type' => 'general',
                    'application_label' => 'Bordado Floral Hipil',
                    'file_path' => 'designs/ramo_floral.dst',
                    'file_format' => 'dst',
                    'stitches_count' => 15000,
                    'width_mm' => 150,
                    'height_mm' => 200,
                    'colors_count' => 4,
                    'status' => 'aprobado',
                ]
            );

            $this->command->info("   ✓ {$design1->name}");
            $this->command->info("     Puntadas: 15,000 | Colores: 4 | Estado: Aprobado");

            // =============================================
            // DISEÑO 2: FLOR ESQUINERA
            // =============================================
            $this->command->info('');
            $this->command->info('🌺 Creando diseño: Flor Esquinera...');

            $design2 = Design::updateOrCreate(
                ['slug' => 'flor-esquinera'],
                [
                    'name' => 'Flor Esquinera',
                    'description' => 'Diseño de flor para esquinas de bolsas y accesorios. Dos colores, diseño compacto.',
                    'is_active' => true,
                ]
            );

            DB::table('category_design')->updateOrInsert(
                ['design_id' => $design2->id, 'category_id' => $catFloral->id],
                ['created_at' => now(), 'updated_at' => now()]
            );

            DesignExport::updateOrCreate(
                ['design_id' => $design2->id, 'file_name' => 'flor_esquinera.dst'],
                [
                    'application_type' => 'general',
                    'application_label' => 'Flor Esquina Bolsa',
                    'file_path' => 'designs/flor_esquinera.dst',
                    'file_format' => 'dst',
                    'stitches_count' => 8000,
                    'width_mm' => 80,
                    'height_mm' => 80,
                    'colors_count' => 2,
                    'status' => 'aprobado',
                ]
            );

            $this->command->info("   ✓ {$design2->name}");
            $this->command->info("     Puntadas: 8,000 | Colores: 2 | Estado: Aprobado");

            // =============================================
            // DISEÑO 3: LOGO MARCA ARTESANAL
            // =============================================
            $this->command->info('');
            $this->command->info('🏷️ Creando diseño: Logo Marca...');

            $design3 = Design::updateOrCreate(
                ['slug' => 'logo-bordados-yucatan'],
                [
                    'name' => 'Logo Bordados Yucatán',
                    'description' => 'Logo de marca para etiquetas y productos. Diseño bicolor blanco/negro.',
                    'is_active' => true,
                ]
            );

            DB::table('category_design')->updateOrInsert(
                ['design_id' => $design3->id, 'category_id' => $catLogos->id],
                ['created_at' => now(), 'updated_at' => now()]
            );

            DesignExport::updateOrCreate(
                ['design_id' => $design3->id, 'file_name' => 'logo_marca.dst'],
                [
                    'application_type' => 'general',
                    'application_label' => 'Logo Etiqueta',
                    'file_path' => 'designs/logo_marca.dst',
                    'file_format' => 'dst',
                    'stitches_count' => 3500,
                    'width_mm' => 50,
                    'height_mm' => 30,
                    'colors_count' => 2,
                    'status' => 'aprobado',
                ]
            );

            $this->command->info("   ✓ {$design3->name}");
            $this->command->info("     Puntadas: 3,500 | Colores: 2 | Estado: Aprobado");

            // =============================================
            // DISEÑO 4: CENEFA GEOMÉTRICA
            // =============================================
            $this->command->info('');
            $this->command->info('◆ Creando diseño: Cenefa Geométrica...');

            $design4 = Design::updateOrCreate(
                ['slug' => 'cenefa-geometrica-maya'],
                [
                    'name' => 'Cenefa Geométrica Maya',
                    'description' => 'Cenefa con patrones geométricos inspirados en diseños mayas. Para cuellos y puños.',
                    'is_active' => true,
                ]
            );

            DB::table('category_design')->updateOrInsert(
                ['design_id' => $design4->id, 'category_id' => $catGeometricos->id],
                ['created_at' => now(), 'updated_at' => now()]
            );

            DesignExport::updateOrCreate(
                ['design_id' => $design4->id, 'file_name' => 'cenefa_maya.dst'],
                [
                    'application_type' => 'general',
                    'application_label' => 'Cenefa Cuello/Puño',
                    'file_path' => 'designs/cenefa_maya.dst',
                    'file_format' => 'dst',
                    'stitches_count' => 5500,
                    'width_mm' => 200,
                    'height_mm' => 25,
                    'colors_count' => 3,
                    'status' => 'aprobado',
                ]
            );

            $this->command->info("   ✓ {$design4->name}");
            $this->command->info("     Puntadas: 5,500 | Colores: 3 | Estado: Aprobado");

            // =============================================
            // RESUMEN
            // =============================================
            $totalDesigns = Design::count();
            $totalExports = DesignExport::count();
            $totalCategories = Category::count();

            $this->command->info('');
            $this->command->info('══════════════════════════════════════════════════');
            $this->command->info('   DISEÑOS CREADOS');
            $this->command->info('══════════════════════════════════════════════════');
            $this->command->info("   Categorías:     {$totalCategories}");
            $this->command->info("   Diseños:        {$totalDesigns}");
            $this->command->info("   Design Exports: {$totalExports}");
            $this->command->info('');
        });
    }
}
