<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$hilos = App\Models\Material::find(5);
if (!$hilos) {
    echo "Material 5 not found\n";
    exit;
}

$cono = App\Models\Unit::where('name', 'CONO')->first();
if (!$cono) {
    echo "Unit CONO not found\n";
    exit;
}

$conversions = App\Models\MaterialUnitConversion::where('material_id', 5)->get();
foreach ($conversions as $c) {
    $unitName = strtoupper($c->fromUnit->name);
    if (str_contains($unitName, 'CAJA')) {
        $c->update([
            'intermediate_unit_id' => $cono->id,
            'intermediate_qty' => 24
        ]);
        echo "Updated CAJA for Hilos (ID {$c->id})\n";
    } elseif (str_contains($unitName, 'PAQUETE')) {
        $c->update([
            'intermediate_unit_id' => $cono->id,
            'intermediate_qty' => 6
        ]);
        echo "Updated PAQUETE for Hilos (ID {$c->id})\n";
    } else {
        echo "Skipped unit: {$c->fromUnit->name} (ID {$c->id})\n";
    }
}
