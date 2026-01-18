<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$units = App\Models\Unit::all(['id', 'name', 'unit_type', 'compatible_base_unit_id']);
echo json_encode($units, JSON_PRETTY_PRINT);
