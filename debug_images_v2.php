<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use Illuminate\Support\Facades\Storage;

// Get the latest product to verify what the user is seeing
$product = Product::with(['images', 'primaryImage', 'designs.exports'])->latest()->first();

if (!$product) {
    echo "No Products found.\n";
    exit;
}

echo "=== PRODUCT: {$product->name} (SKU: {$product->sku}) ===\n";

// 1. PRIMARY IMAGE ANALYSIS
echo "\n[PRIMARY IMAGE]\n";
$primary = $product->primaryImage;
if ($primary) {
    echo "DB 'file_path': '{$primary->file_path}'\n";
    $fullPath = storage_path('app/public/' . $primary->file_path);
    echo "File System Check: " . (file_exists($fullPath) ? "EXISTS" : "MISSING") . " at $fullPath\n";
    echo "Asset URL: " . asset('storage/' . $primary->file_path) . "\n";
} else {
    echo "No primary image record found.\n";
}

// 2. DESIGN EXPORTS ANALYSIS
echo "\n[DESIGN EXPORTS PREVIEWS]\n";
foreach ($product->designs as $design) {
    echo "Design: {$design->name}\n";
    foreach ($design->exports as $export) {
        // Skip null paths
        if (!$export->file_path) continue;

        echo "  - DB 'file_path': '{$export->file_path}'\n";

        // Check if file exists in 'storage/app/public/' + path
        // BUT ALSO check if it exists in 'storage/app/' + path (if user stored in private)
        $publicPath = storage_path('app/public/' . $export->file_path);

        echo "  - File Check (Public): " . (file_exists($publicPath) ? "EXISTS" : "MISSING") . "\n";
        echo "  - Generated Asset: " . asset('storage/' . $export->file_path) . "\n";
    }
}
