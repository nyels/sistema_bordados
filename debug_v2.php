<?php

use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\Image;

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$id = 7;
echo "--- DEBUG INFORMATION FOR PRODUCT ID: $id ---\n";

// 1. Check Product existence
$product = Product::find($id);
if (!$product) {
    echo "Product not found!\n";
    exit;
}
echo "Product Name: " . $product->name . "\n";

// 2. Check Raw Images Table
echo "\n[RAW SQL] Checking 'images' table for imageable_id = $id:\n";
$rawImages = DB::select("SELECT * FROM images WHERE imageable_id = ? AND imageable_type LIKE '%Product%'", [$id]);
if (empty($rawImages)) {
    echo "NO IMAGES found in DB for this ID.\n";
    // Check if there are any images with just 'Product' or similar (morph map check)
    $morphCheck = DB::select("SELECT * FROM images WHERE imageable_id = ?", [$id]);
    if (!empty($morphCheck)) {
        echo "WARNING: Found records with different morph types:\n";
        foreach ($morphCheck as $img) {
            echo " - ID: {$img->id}, Type: {$img->imageable_type}, URL: {$img->url}\n";
        }
    }
} else {
    echo "Found " . count($rawImages) . " images in DB:\n";
    foreach ($rawImages as $img) {
        echo " - ID: {$img->id}, Type: {$img->imageable_type}, IsPrimary: {$img->is_primary}, URL: {$img->url}\n";
    }
}

// 3. Check Eloquent Relationship
echo "\n[ELOQUENT] \$product->images count: " . $product->images()->count() . "\n";

// 4. Check Designs (Pivot)
echo "\n[RAW SQL] Checking 'product_design' table for product_id = $id:\n";
$rawDesignPivot = DB::select("SELECT * FROM product_design WHERE product_id = ?", [$id]);
echo "Found " . count($rawDesignPivot) . " entries in pivot table.\n";
foreach ($rawDesignPivot as $pivot) {
    echo " - Product: {$pivot->product_id} <-> Design: {$pivot->design_id}\n";
}

// 5. Check Eloquent Designs
echo "\n[ELOQUENT] \$product->designs count: " . $product->designs()->count() . "\n";
