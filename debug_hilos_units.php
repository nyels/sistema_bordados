<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$category = App\Models\MaterialCategory::where('name', 'HILOS')->first();
if ($category) {
    echo "Categoría: " . $category->name . "\n";
    $units = $category->allowedUnits()->pluck('name');
    echo "Unidades Asignadas: " . json_encode($units) . "\n";
} else {
    echo "Categoría HILOS no encontrada.\n";
}
