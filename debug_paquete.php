<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$unit = App\Models\Unit::where('name', 'like', '%PAQUETE%')->first();
echo json_encode($unit, JSON_PRETTY_PRINT);
