<?php

use App\Models\MaterialUnitConversion;
use App\Models\Unit;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Buscar conversiones de HILOS (Material 5)
$conversions = MaterialUnitConversion::where('material_id', 5)->get();

// Unidad 'cono' es ID 4 (según mi búsqueda previa)
$conoUnit = Unit::where('name', 'CONO')->first();
$conoId = $conoUnit ? $conoUnit->id : 4;

foreach ($conversions as $conv) {
    if (str_contains(strtoupper($conv->fromUnit->name), 'CAJA')) {
        $conv->update([
            'intermediate_unit_id' => $conoId,
            'intermediate_qty' => 24
        ]);
        echo "Updated CAJA for Material 5\n";
    }
    if (str_contains(strtoupper($conv->fromUnit->name), 'PAQUETE')) {
        $conv->update([
            'intermediate_unit_id' => $conoId,
            'intermediate_qty' => 6 // Ejemplo
        ]);
        echo "Updated PAQUETE for Material 5\n";
    }
}

// Buscar material 1 (BASES) - ID 1
$basesConv = MaterialUnitConversion::where('material_id', 1)->get();
// Unidad 'pieza' es la base usualmente
$pzUnit = Unit::where('name', 'PIEZA')->first();
$pzId = $pzUnit ? $pzUnit->id : 1;

foreach ($basesConv as $conv) {
    if (str_contains(strtoupper($conv->fromUnit->name), 'CAJA')) {
        $conv->update([
            'intermediate_unit_id' => $pzId,
            'intermediate_qty' => 25
        ]);
        echo "Updated CAJA for Material 1\n";
    }
    if (str_contains(strtoupper($conv->fromUnit->name), 'PAQUETE')) {
        $conv->update([
            'intermediate_unit_id' => $pzId,
            'intermediate_qty' => 5
        ]);
        echo "Updated PAQUETE for Material 1\n";
    }
}
