<?php

use App\Models\Product;
use App\Models\ProductExtra;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Find the extra by name first to get its ID or just use it to find the product
$extraName = 'Caja de Regalo Rígida (Premium)';
$extra = ProductExtra::where('name', 'like', "%Caja de Regalo%")->first();

if (!$extra) {
    echo "Extra '$extraName' not found in DB.\n";
    // Check all extras
    echo "Listing all extras:\n";
    foreach (ProductExtra::all() as $e) {
        echo "- {$e->name} \n";
    }
    exit;
}

echo "Found Extra: {$extra->name} (ID: {$extra->id}) - Price: {$extra->price_addition}\n";

// Find products with this extra
$products = Product::whereHas('extras', function ($q) use ($extra) {
    $q->where('product_extras.id', $extra->id);
})->with('extras')->get();

if ($products->isEmpty()) {
    echo "No products found with this extra.\n";
}

foreach ($products as $product) {
    echo "\nProduct: {$product->name} (ID: {$product->id})\n";
    foreach ($product->extras as $pe) {
        echo "  - Extra: {$pe->name} | Price: {$pe->price_addition} | Pivot ID: " . ($pe->pivot->id ?? 'N/A') . "\n";
    }
    echo "  Total Extras Logic: " . $product->extras->sum('price_addition') . "\n";
}
