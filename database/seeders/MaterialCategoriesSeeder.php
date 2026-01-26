<?php

namespace Database\Seeders;

use App\Models\MaterialCategory;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class MaterialCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $u = Unit::all()->keyBy('slug');

        $categories = [
            ['name' => 'HILOS BORDADO', 'slug' => 'hilos', 'unit' => 'cono', 'desc' => 'Hilos de poliéster para bordar y bobinas'],
            ['name' => 'TELAS / TEXTILES', 'slug' => 'telas', 'unit' => 'metro', 'desc' => 'Bolsas y textiles base'],
            ['name' => 'ESTABILIZADORES (PELÓN)', 'slug' => 'pelones', 'unit' => 'metro', 'desc' => 'Entretelas y estabilizadores'],
            ['name' => 'AGUJAS E INSTRUMENTAL', 'slug' => 'agujas', 'unit' => 'pieza', 'desc' => 'Agujas e instrumental de bordado'],
            ['name' => 'QUÍMICOS / LIMPIEZA', 'slug' => 'quimicos', 'unit' => 'mililitro', 'desc' => 'Químicos y productos de limpieza'],
            ['name' => 'REFACCIONES MÁQUINA', 'slug' => 'refacciones', 'unit' => 'pieza', 'desc' => 'Refacciones para máquinas de bordado'],
            ['name' => 'AVÍOS (BOTONES/CIERRES)', 'slug' => 'avios', 'unit' => 'pieza', 'desc' => 'Cintas, listones y adornos'],
        ];

        foreach ($categories as $data) {
            $unitId = isset($u[$data['unit']]) ? $u[$data['unit']]->id : null;

            MaterialCategory::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'name' => $data['name'],
                    'default_inventory_unit_id' => $unitId,
                    'allow_unit_override' => true,
                    'description' => $data['desc'],
                    'activo' => true,
                ]
            );
        }

        $this->command->info('✓ Categorías de materiales configuradas.');
    }
}
