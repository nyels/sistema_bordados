<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{Material, MaterialCategory, Unit, MaterialVariant, MaterialUnitConversion};
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

class MaterialSeeder extends Seeder
{
    public function run(): void
    {
        $u = Unit::all()->keyBy('slug');
        $cat = MaterialCategory::all()->keyBy('slug');
        $hasUuidMat = Schema::hasColumn('materials', 'uuid');
        $hasUuidConv = Schema::hasColumn('material_unit_conversions', 'uuid');

        $catalog = [
            'hilos' => [
                ['n' => 'Madeira Classic #40 Rayón', 'v' => ['1000 Negro', '1001 Blanco', '1024 Oro', '1147 Rojo', '1133 Azul', '1010 Plata', '1069 Amarillo'], 'buy' => 'buy-caja-12', 'f' => 12, 'target' => 'u-cono'],
                ['n' => 'Polyneon #40 Poliéster', 'v' => ['1801 Blanco', '1800 Negro', '1910 Gris'], 'buy' => 'buy-caja-10', 'f' => 10, 'target' => 'u-cono'],
                ['n' => 'Hilo Metálico FS40', 'v' => ['Super Oro', 'Super Plata'], 'buy' => 'buy-caja-10', 'f' => 10, 'target' => 'u-cono'],
            ],
            'agujas' => [
                ['n' => 'Organ DBxK5 (Standard)', 'v' => ['75/11 Punta Bola', '75/11 Punta Aguda', '80/12 Punta Bola', '90/14 Punta Bola'], 'buy' => 'buy-paquete-10', 'f' => 10, 'target' => 'u-pieza'],
                ['n' => 'Schmetz Titanium SES', 'v' => ['75/11 Ball Point', '80/12 Ball Point'], 'buy' => 'buy-caja-100', 'f' => 100, 'target' => 'u-pieza'],
            ],
            'telas' => [
                ['n' => 'Lino Flamé 100%', 'v' => ['Blanco Óptico', 'Hueso', 'Azul Yucateco', 'Rosa Coral', 'Gris Arena', 'Negro', 'Vino'], 'buy' => 'buy-rollo-50', 'f' => 50, 'target' => 'u-metro'],
                ['n' => 'Piqué Algodón Premium', 'v' => ['Rojo', 'Azul Marino', 'Verde Foresta', 'Amarillo'], 'buy' => 'buy-rollo-50', 'f' => 50, 'target' => 'u-metro'],
            ],
            'pelones' => [
                ['n' => 'Pelón Recorte (Cut-away)', 'v' => ['40gr Blanco', '40gr Negro', '60gr Blanco'], 'buy' => 'buy-rollo-100', 'f' => 100, 'target' => 'u-metro'],
                ['n' => 'Soluble en Agua (Solvy)', 'v' => ['Transparente 20 micras'], 'buy' => 'buy-rollo-25', 'f' => 25, 'target' => 'u-metro'],
            ],
            'quimicos' => [
                ['n' => 'Adhesivo Temporal 505', 'v' => ['Spray Estándar'], 'buy' => 'buy-bote-500', 'f' => 500, 'target' => 'u-ml'],
                ['n' => 'Aceite Blanco Mineral', 'v' => ['Aceite Alta Velocidad'], 'buy' => 'buy-bote-500', 'f' => 500, 'target' => 'u-ml'],
            ],
            'avios' => [
                ['n' => 'Botón de Concha 14mm', 'v' => ['Blanco Hoyo 2', 'Negro Hoyo 2', 'Crema Hoyo 2'], 'buy' => 'buy-paquete-10', 'f' => 10, 'target' => 'u-pieza'],
            ]
        ];

        foreach ($catalog as $catSlug => $materials) {
            foreach ($materials as $m) {
                $material = Material::create([
                    'material_category_id' => $cat[$catSlug]->id,
                    'name' => $m['n'],
                    'slug' => Str::slug($m['n']),
                    'uuid' => $hasUuidMat ? (string) Str::uuid() : null,
                    'activo' => true
                ]);

                foreach ($m['v'] as $vName) {
                    MaterialVariant::create([
                        'uuid' => (string) Str::uuid(),
                        'material_id' => $material->id,
                        'color' => $vName,
                        'sku' => strtoupper(substr($catSlug, 0, 2)) . '-' . rand(1000, 9999),
                        'current_stock' => 0,
                        'min_stock_alert' => 0
                    ]);
                }

                $convData = [
                    'material_id' => $material->id,
                    'from_unit_id' => $u[$m['buy']]->id,
                    'to_unit_id' => $u[$m['target']]->id,
                    'conversion_factor' => $m['f']
                ];
                if ($hasUuidConv) $convData['uuid'] = (string) Str::uuid();
                MaterialUnitConversion::create($convData);
            }
        }
        $this->command->info('✓ Catálogo industrial cargado con éxito.');
    }
}
