<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UnitsSeeder extends Seeder
{
    public function run(): void
    {
        // =============================================
        // UNIDADES BASE (para inventario/consumo)
        // =============================================
        $baseUnits = [
            ['name' => 'METRO', 'slug' => 'u-metro', 'symbol' => 'm'],
            ['name' => 'PIEZA', 'slug' => 'u-pieza', 'symbol' => 'pz'],
            ['name' => 'CONO', 'slug' => 'u-cono', 'symbol' => 'cono'],
            ['name' => 'LITRO', 'slug' => 'u-litro', 'symbol' => 'lt'],
            ['name' => 'MILILITRO', 'slug' => 'u-ml', 'symbol' => 'ml'],
        ];

        foreach ($baseUnits as $data) {
            Unit::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'uuid' => (string) Str::uuid(),
                    'name' => $data['name'],
                    'symbol' => $data['symbol'],
                    'is_base' => true,
                    'compatible_base_unit_id' => null,
                    'activo' => true,
                ]
            );
        }

        // Obtener IDs
        $metroId = Unit::where('slug', 'u-metro')->value('id');
        $piezaId = Unit::where('slug', 'u-pieza')->value('id');
        $conoId = Unit::where('slug', 'u-cono')->value('id');
        $litroId = Unit::where('slug', 'u-litro')->value('id');
        $mlId = Unit::where('slug', 'u-ml')->value('id');

        // =============================================
        // UNIDADES DE COMPRA (compatibles con base)
        // Slugs corregidos para coincidir con MaterialSeeder
        // =============================================
        $purchaseUnits = [
            // Compatible con METRO
            ['name' => 'ROLLO (25M)', 'slug' => 'buy-rollo-25', 'symbol' => 'r25', 'compatible' => $metroId],
            ['name' => 'ROLLO (50M)', 'slug' => 'buy-rollo-50', 'symbol' => 'r50', 'compatible' => $metroId],
            ['name' => 'ROLLO (100M)', 'slug' => 'buy-rollo-100', 'symbol' => 'r100', 'compatible' => $metroId],

            // Compatible con PIEZA
            ['name' => 'PAQUETE (10 PZ)', 'slug' => 'buy-paquete-10', 'symbol' => 'pq10', 'compatible' => $piezaId],
            ['name' => 'PAQUETE (50 PZ)', 'slug' => 'buy-paquete-50', 'symbol' => 'pq50', 'compatible' => $piezaId],
            ['name' => 'CAJA (100 PZ)', 'slug' => 'buy-caja-100', 'symbol' => 'cj100pz', 'compatible' => $piezaId],
            ['name' => 'BOLSA (25 PZ)', 'slug' => 'buy-bolsa-25', 'symbol' => 'bl25', 'compatible' => $piezaId],

            // Compatible con CONO
            ['name' => 'CAJA (6 CONOS)', 'slug' => 'buy-caja-6', 'symbol' => 'cj6c', 'compatible' => $conoId],
            ['name' => 'CAJA (10 CONOS)', 'slug' => 'buy-caja-10', 'symbol' => 'cj10c', 'compatible' => $conoId],
            ['name' => 'CAJA (12 CONOS)', 'slug' => 'buy-caja-12', 'symbol' => 'cj12c', 'compatible' => $conoId],

            // Compatible con LITRO / ML
            ['name' => 'BOTE (500ML)', 'slug' => 'buy-bote-500', 'symbol' => 'bt500', 'compatible' => $mlId],
            ['name' => 'GALÓN (4LT)', 'slug' => 'buy-galon-4', 'symbol' => 'gal4', 'compatible' => $litroId],
        ];

        foreach ($purchaseUnits as $data) {
            Unit::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'uuid' => (string) Str::uuid(),
                    'name' => $data['name'],
                    'symbol' => $data['symbol'],
                    'is_base' => false,
                    'compatible_base_unit_id' => $data['compatible'],
                    'activo' => true,
                ]
            );
        }

        $this->command->info('✓ Unidades base creadas: ' . count($baseUnits));
        $this->command->info('✓ Unidades de compra creadas: ' . count($purchaseUnits));
    }
}
