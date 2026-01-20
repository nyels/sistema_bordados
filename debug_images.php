<?php

use App\Models\Product;
use App\Models\DesignExport;

// Get the latest product
$product = Product::with(['images', 'primaryImage', 'designs.exports'])->latest()->first();

if (!$product) {
    echo "No Products found.\n";
    exit;
}

echo "=== PRODUCT: {$product->name} (ID: {$product->id}) ===\n";

// Check Primary Image Logic
echo "\n[PRIMARY IMAGE DIAGNOSTIC]\n";
$primary = $product->primaryImage;
if ($primary) {
    echo "DB 'file_path': '{$primary->file_path}'\n";
    echo "Generated URL (app/Models/Image.php): '" . asset('storage/' . $primary->file_path) . "'\n";
    echo "File Exists in storage/app/public? " . (file_exists(storage_path('app/public/' . $primary->file_path)) ? 'YES' : 'NO') . "\n";
} else {
    echo "No primary image found via relation.\n";
}

// Check Gallery Images
echo "\n[GALLERY IMAGES - First 2]\n";
foreach ($product->images->take(2) as $img) {
    echo "- Path: '{$img->file_path}' | Exists: " . (file_exists(storage_path('app/public/' . $img->file_path)) ? 'YES' : 'NO') . "\n";
}

// Check Design Exports
echo "\n[DESIGN EXPORTS]\n";
foreach ($product->designs as $design) {
    echo "Design: {$design->name}\n";
    foreach ($design->exports as $export) {
        echo "  - Export Path: '{$export->file_path}'\n";
        echo "  - Generated Asset: '" . asset('storage/' . $export->file_path) . "'\n";
        echo "  - Exists? " . (file_exists(storage_path('app/public/' . $export->file_path)) ? 'YES' : 'NO') . "\n";
    }
}
