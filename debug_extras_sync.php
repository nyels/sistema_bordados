<?php

use App\Models\Product;
use App\Models\ProductExtra;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$products = Product::whereHas('extras')->with('extras')->get();

foreach ($products as $product) {
    echo "------------------------------------------------\n";
    echo "Product: {$product->name} (ID: {$product->id})\n";
    echo "Stored 'extra_services_cost': " . $product->extra_services_cost . "\n";

    $calculatedSum = $product->extras->sum('price_addition');
    echo "Calculated Sum (from relations): " . $calculatedSum . "\n";

    if (abs($product->extra_services_cost - $calculatedSum) > 0.01) {
        echo "!!! MISMATCH DETECTED !!!\n";
    }

    foreach ($product->extras as $extra) {
        echo "  - {$extra->name}: {$extra->price_addition}\n";
    }
}
