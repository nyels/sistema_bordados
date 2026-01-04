<?php

namespace Database\Seeders;

use App\Models\{MaterialCategory, Unit};
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

class MaterialCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $u = Unit::all()->keyBy('slug');
        $hasUuid = Schema::hasColumn('material_categories', 'uuid');

        $categories = [
            ['name' => 'HILOS BORDADO', 'slug' => 'hilos', 'unit' => 'u-cono', 'color' => true],
            ['name' => 'TELAS / TEXTILES', 'slug' => 'telas', 'unit' => 'u-metro', 'color' => true],
            ['name' => 'ESTABILIZADORES (PELÓN)', 'slug' => 'pelones', 'unit' => 'u-metro', 'color' => false],
            ['name' => 'AGUJAS E INSTRUMENTAL', 'slug' => 'agujas', 'unit' => 'u-pieza', 'color' => true],
            ['name' => 'QUÍMICOS / LIMPIEZA', 'slug' => 'quimicos', 'unit' => 'u-ml', 'color' => false],
            ['name' => 'REFACCIONES MÁQUINA', 'slug' => 'refacciones', 'unit' => 'u-pieza', 'color' => false],
            ['name' => 'AVÍOS (BOTONES/CIERRES)', 'slug' => 'avios', 'unit' => 'u-pieza', 'color' => true],
        ];

        foreach ($categories as $data) {
            $insert = [
                'name' => $data['name'],
                'base_unit_id' => $u[$data['unit']]->id,
                'has_color' => $data['color'],
                'description' => 'Insumos profesionales de ' . strtolower($data['name']),
                'activo' => true
            ];
            if ($hasUuid) $insert['uuid'] = (string) Str::uuid();

            MaterialCategory::updateOrCreate(['slug' => $data['slug']], $insert);
        }
        $this->command->info('✓ Categorías industriales configuradas.');
    }
}
