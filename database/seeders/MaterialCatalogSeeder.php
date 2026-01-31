<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Material;
use App\Models\MaterialVariant;
use App\Models\MaterialCategory;
use App\Models\MaterialUnitConversion;
use App\Models\Unit;

class MaterialCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('══════════════════════════════════════════════════');
        $this->command->info('   SEMBRANDO CATÁLOGO DE MATERIALES');
        $this->command->info('══════════════════════════════════════════════════');

        $units = Unit::all()->keyBy('slug');
        $metro = $units['metro'] ?? null;
        $cono  = $units['cono'] ?? null;
        $pieza = $units['pieza'] ?? null;

        if (!$metro || !$cono || !$pieza) {
            $this->command->error('✗ Faltan unidades base. Ejecuta UnitsSeeder.');
            return;
        }

        $cats = MaterialCategory::all()->keyBy('slug');
        $catTelas   = $cats['telas'] ?? null;
        $catHilos   = $cats['hilos'] ?? null;
        $catAvios   = $cats['avios'] ?? null;
        $catPelones = $cats['pelones'] ?? null;

        if (!$catTelas || !$catHilos) {
            $this->command->error('✗ Faltan categorías base.');
            return;
        }

        // =========================================================================
        // TELAS (base_unit_id = metro, porque se mide en metros)
        // =========================================================================
        $telaAlgodon = Material::updateOrCreate(
            ['slug' => 'algodon-manta-blanca'],
            [
                'uuid' => (string) Str::uuid(),
                'material_category_id' => $catTelas->id,
                'base_unit_id' => $metro->id,
                'name' => 'Algodón Manta Blanca',
                'composition' => '100% Algodón',
                'description' => 'Manta blanca para hipiles tradicionales.',
                'activo' => true,
            ]
        );

        MaterialVariant::updateOrCreate(
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

        $telaYute = Material::updateOrCreate(
            ['slug' => 'tela-yute-natural'],
            [
                'uuid' => (string) Str::uuid(),
                'material_category_id' => $catTelas->id,
                'base_unit_id' => $metro->id,
                'name' => 'Tela Yute Natural',
                'composition' => '100% Yute',
                'description' => 'Tela de yute natural para bolsas artesanales.',
                'activo' => true,
            ]
        );

        MaterialVariant::updateOrCreate(
            ['sku' => 'TEL-YUT-NAT-001'],
            [
                'uuid' => (string) Str::uuid(),
                'material_id' => $telaYute->id,
                'color' => 'Natural',
                'current_stock' => 0,
                'min_stock_alert' => 15,
                'current_value' => 0,
                'average_cost' => 0,
                'last_purchase_cost' => 0,
                'activo' => true,
            ]
        );

        $telaForro = Material::updateOrCreate(
            ['slug' => 'popelina-forro'],
            [
                'uuid' => (string) Str::uuid(),
                'material_category_id' => $catTelas->id,
                'base_unit_id' => $metro->id,
                'name' => 'Popelina Forro',
                'composition' => '65% Polyester 35% Algodón',
                'description' => 'Tela popelina para forro de bolsas.',
                'activo' => true,
            ]
        );

        MaterialVariant::updateOrCreate(
            ['sku' => 'TEL-POP-BLA-001'],
            [
                'uuid' => (string) Str::uuid(),
                'material_id' => $telaForro->id,
                'color' => 'Blanco',
                'current_stock' => 0,
                'min_stock_alert' => 10,
                'current_value' => 0,
                'average_cost' => 0,
                'last_purchase_cost' => 0,
                'activo' => true,
            ]
        );

        // =========================================================================
        // HILOS BORDADO (base_unit_id = cono, se compra en conos pero se consume en metros)
        // =========================================================================
        $hiloBordado = Material::updateOrCreate(
            ['slug' => 'hilo-bordado-polyester'],
            [
                'uuid' => (string) Str::uuid(),
                'material_category_id' => $catHilos->id,
                'base_unit_id' => $cono->id,
                'name' => 'Hilo Bordado Polyester 120D/2',
                'composition' => '100% Polyester',
                'description' => 'Hilo industrial para bordado. Cono 5000m.',
                'activo' => true,
            ]
        );

        MaterialUnitConversion::updateOrCreate(
            [
                'material_id' => $hiloBordado->id,
                'from_unit_id' => $cono->id,
            ],
            [
                'to_unit_id' => $metro->id,
                'conversion_factor' => 5000,
                'label' => '1 Cono = 5000 metros',
            ]
        );

        foreach ([
            ['Rojo', 'HIL-BOR-ROJ-001'],
            ['Rosa', 'HIL-BOR-ROS-001'],
            ['Verde', 'HIL-BOR-VER-001'],
            ['Amarillo', 'HIL-BOR-AMA-001'],
            ['Azul', 'HIL-BOR-AZU-001'],
            ['Negro', 'HIL-BOR-NEG-001'],
            ['Blanco', 'HIL-BOR-BLA-001'],
        ] as [$color, $sku]) {
            MaterialVariant::updateOrCreate(
                ['sku' => $sku],
                [
                    'uuid' => (string) Str::uuid(),
                    'material_id' => $hiloBordado->id,
                    'color' => $color,
                    'current_stock' => 0,
                    'min_stock_alert' => 2,
                    'current_value' => 0,
                    'average_cost' => 0,
                    'last_purchase_cost' => 0,
                    'activo' => true,
                ]
            );
        }

        // =========================================================================
        // HILO COSTURA (base_unit_id = cono)
        // =========================================================================
        $hiloCostura = Material::updateOrCreate(
            ['slug' => 'hilo-costura-algodon'],
            [
                'uuid' => (string) Str::uuid(),
                'material_category_id' => $catHilos->id,
                'base_unit_id' => $cono->id,
                'name' => 'Hilo Costura Algodón',
                'composition' => '100% Algodón',
                'description' => 'Hilo de costura para prendas. Cono 3000m.',
                'activo' => true,
            ]
        );

        MaterialUnitConversion::updateOrCreate(
            [
                'material_id' => $hiloCostura->id,
                'from_unit_id' => $cono->id,
            ],
            [
                'to_unit_id' => $metro->id,
                'conversion_factor' => 3000,
                'label' => '1 Cono = 3000 metros',
            ]
        );

        MaterialVariant::updateOrCreate(
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

        MaterialVariant::updateOrCreate(
            ['sku' => 'HIL-COS-NAT-001'],
            [
                'uuid' => (string) Str::uuid(),
                'material_id' => $hiloCostura->id,
                'color' => 'Natural',
                'current_stock' => 0,
                'min_stock_alert' => 3,
                'current_value' => 0,
                'average_cost' => 0,
                'last_purchase_cost' => 0,
                'activo' => true,
            ]
        );

        // =========================================================================
        // AVÍOS (base_unit_id = pieza, se cuentan por unidades)
        // =========================================================================
        if ($catAvios) {
            $listonSatin = Material::updateOrCreate(
                ['slug' => 'liston-satin-25mm'],
                [
                    'uuid' => (string) Str::uuid(),
                    'material_category_id' => $catAvios->id,
                    'base_unit_id' => $pieza->id,
                    'name' => 'Listón Satín 25mm',
                    'composition' => '100% Polyester',
                    'description' => 'Listón satinado 2.5cm para moños decorativos.',
                    'activo' => true,
                ]
            );

            MaterialVariant::updateOrCreate(
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

            $asas = Material::updateOrCreate(
                ['slug' => 'asas-algodon-acolchadas'],
                [
                    'uuid' => (string) Str::uuid(),
                    'material_category_id' => $catAvios->id,
                    'base_unit_id' => $pieza->id,
                    'name' => 'Asas Algodón Acolchadas 50cm',
                    'composition' => '100% Algodón',
                    'description' => 'Par de asas acolchadas para bolsas.',
                    'activo' => true,
                ]
            );

            MaterialVariant::updateOrCreate(
                ['sku' => 'AVI-ASA-NAT-001'],
                [
                    'uuid' => (string) Str::uuid(),
                    'material_id' => $asas->id,
                    'color' => 'Natural',
                    'current_stock' => 0,
                    'min_stock_alert' => 20,
                    'current_value' => 0,
                    'average_cost' => 0,
                    'last_purchase_cost' => 0,
                    'activo' => true,
                ]
            );

            $etiqueta = Material::updateOrCreate(
                ['slug' => 'etiqueta-bordada-marca'],
                [
                    'uuid' => (string) Str::uuid(),
                    'material_category_id' => $catAvios->id,
                    'base_unit_id' => $pieza->id,
                    'name' => 'Etiqueta Bordada Marca',
                    'composition' => 'Tejido bordado',
                    'description' => 'Etiqueta con logo de marca.',
                    'activo' => true,
                ]
            );

            MaterialVariant::updateOrCreate(
                ['sku' => 'ETI-MAR-001'],
                [
                    'uuid' => (string) Str::uuid(),
                    'material_id' => $etiqueta->id,
                    'color' => 'Negro/Blanco',
                    'current_stock' => 0,
                    'min_stock_alert' => 50,
                    'current_value' => 0,
                    'average_cost' => 0,
                    'last_purchase_cost' => 0,
                    'activo' => true,
                ]
            );

            $bolsaCelofan = Material::updateOrCreate(
                ['slug' => 'bolsa-celofan-35x35'],
                [
                    'uuid' => (string) Str::uuid(),
                    'material_category_id' => $catAvios->id,
                    'base_unit_id' => $pieza->id,
                    'name' => 'Bolsa Celofán 35x35cm',
                    'composition' => 'Celofán transparente',
                    'description' => 'Bolsa para empaque de productos.',
                    'activo' => true,
                ]
            );

            MaterialVariant::updateOrCreate(
                ['sku' => 'EMP-CEL-3535'],
                [
                    'uuid' => (string) Str::uuid(),
                    'material_id' => $bolsaCelofan->id,
                    'color' => 'Transparente',
                    'current_stock' => 0,
                    'min_stock_alert' => 100,
                    'current_value' => 0,
                    'average_cost' => 0,
                    'last_purchase_cost' => 0,
                    'activo' => true,
                ]
            );
        }

        // =========================================================================
        // PELONES / ESTABILIZADORES (base_unit_id = metro, se mide en metros)
        // =========================================================================
        if ($catPelones) {
            $pelonRecortable = Material::updateOrCreate(
                ['slug' => 'pelon-recortable'],
                [
                    'uuid' => (string) Str::uuid(),
                    'material_category_id' => $catPelones->id,
                    'base_unit_id' => $metro->id,
                    'name' => 'Pelón Recortable',
                    'composition' => 'No tejido',
                    'description' => 'Estabilizador recortable para bordado.',
                    'activo' => true,
                ]
            );

            MaterialVariant::updateOrCreate(
                ['sku' => 'PEL-REC-BLA-001'],
                [
                    'uuid' => (string) Str::uuid(),
                    'material_id' => $pelonRecortable->id,
                    'color' => 'Blanco',
                    'current_stock' => 0,
                    'min_stock_alert' => 50,
                    'current_value' => 0,
                    'average_cost' => 0,
                    'last_purchase_cost' => 0,
                    'activo' => true,
                ]
            );
        }

        $this->command->info('✓ Catálogo de materiales sembrado correctamente');
    }
}
