<?php

use App\Models\Product;

$id = 7;
$product = Product::with(['images', 'designs', 'primaryImage'])->find($id);

if (!$product) {
    echo "Product $id not found.\n";
    exit;
}

echo "Product: {$product->name} (ID: {$product->id})\n";
echo "Images Count: " . $product->images->count() . "\n";
foreach ($product->images as $img) {
    echo " - Image: {$img->url} (Primary: {$img->is_primary})\n";
}

echo "Designs Count: " . $product->designs->count() . "\n";
foreach ($product->designs as $design) {
    echo " - Design: {$design->name}\n";
}

echo "Primary Image URL (Accessor): " . $product->primary_image_url . "\n";
