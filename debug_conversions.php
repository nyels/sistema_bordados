<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MaterialUnitConversion;

// ID del material que estamos viendo (parece ser 5 por logs anteriores, o podemos buscar el último)
$conversions = MaterialUnitConversion::with(['fromUnit', 'toUnit', 'intermediateUnit'])
    ->orderBy('id', 'desc')
    ->limit(5)
    ->get();

foreach ($conversions as $c) {
    echo "ID: " . $c->id . "\n";
    echo "Material ID: " . $c->material_id . "\n";
    echo "From: " . ($c->fromUnit->name ?? 'null') . "\n";
    echo "Label: " . $c->label . "\n";
    echo "Factor (Total): " . $c->conversion_factor . "\n";
    echo "Intermediate Qty: " . $c->intermediate_qty . "\n";
    echo "Intermediate Unit: " . ($c->intermediateUnit->name ?? 'null') . "\n";

    if ($c->intermediate_qty > 0) {
        $calculatedValue = $c->conversion_factor / $c->intermediate_qty;
        echo "Calculated Value Each: " . $calculatedValue . "\n";
    }
    echo "--------------------------\n";
}
