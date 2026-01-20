<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use Illuminate\Support\Facades\Storage;

// Get the latest product
$product = Product::with(['images', 'primaryImage', 'designs.exports'])->latest()->first();

if (!$product) {
    echo "No Products found.\n";
    exit;
}

echo "=== PRODUCT V3: {$product->name} (SKU: {$product->sku}) ===\n";

// 1. IMAGES COLLECTION (Gallery)
echo "\n[ALL IMAGES COUNT]: " . $product->images->count() . "\n";
foreach ($product->images as $img) {
    echo "  - ID: {$img->id} | Primary: " . ($img->is_primary ? 'YES' : 'NO') . "\n";
    echo "    PATH: '{$img->file_path}'\n";
    $exists = file_exists(storage_path('app/public/' . $img->file_path));
    echo "    EXISTS on Disk? " . ($exists ? "YES" : "NO") . "\n";
}

// 2. DESIGN PREVIEW (SVG/Image)
echo "\n[DESIGN EXPORTS DETAILED]\n";
foreach ($product->designs as $design) {
    echo "Design: {$design->name}\n";
    if ($design->exports->isEmpty()) {
        echo "  (No exports found)\n";
    }
    foreach ($design->exports as $export) {
        echo "  > Export ID: {$export->id}\n";
        echo "    File: '{$export->file_path}'\n";

        // Check content type
        $ext = pathinfo($export->file_path, PATHINFO_EXTENSION);
        echo "    Extension: .$ext\n";

        // Check SVG Content
        $hasSvg = !empty($export->svg_content);
        echo "    Has SVG Content? " . ($hasSvg ? "YES (Length: " . strlen($export->svg_content) . ")" : "NO (NULL/Empty)") . "\n";

        // Browser Visibility Logic Simulation
        if ($hasSvg) {
            echo "    [LOGIC] Should render SVG code directly.\n";
        } elseif (in_array(strtolower($ext), ['png', 'jpg', 'jpeg', 'webp'])) {
            echo "    [LOGIC] Should render as IMG tag.\n";
        } else {
            echo "    [LOGIC] Should render as Generic ICON (e.g. .pes).\n";
        }
    }
}
