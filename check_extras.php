<?php

use App\Models\Product;

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$id = 7;
$product = Product::with('extras')->find($id);

if (!$product) {
    echo "Product not found.\n";
    exit;
}

echo "Product: {$product->name}\n";
echo "Base Price (Variant): " . $product->base_price . "\n";
echo "Extras:\n";
foreach ($product->extras as $extra) {
    echo " - {$extra->name}: \${$extra->price_addition}\n";
}
echo "Total Extras: $" . $product->extras->sum('price_addition') . "\n";
