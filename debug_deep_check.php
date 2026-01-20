<?php

use App\Models\Product;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$id = 7;
$product = Product::find($id);

if (!$product) {
    echo "Product #$id NOT FOUND.\n";
    exit;
}

echo "=== PRODUCT #{$product->id}: {$product->name} ===\n";
echo "Stored 'extra_services_cost': {$product->extra_services_cost}\n";
echo "Stored 'embroidery_cost': {$product->embroidery_cost}\n";
echo "\n";

echo "=== 1. EXTRAS (Pivot Inspection) ===\n";
$extras = DB::table('product_extra_assignment')
    ->join('product_extras', 'product_extra_assignment.product_extra_id', '=', 'product_extras.id')
    ->where('product_id', $id)
    ->select('product_extras.name', 'product_extras.price_addition as catalog_price', 'product_extra_assignment.*')
    ->get();

if ($extras->isEmpty()) {
    echo "No extras linked in 'product_extra_assignment'.\n";
} else {
    foreach ($extras as $e) {
        echo "- Name: {$e->name}\n";
        echo "  Catalog Price: {$e->catalog_price}\n";
        echo "  Pivot Data: " . json_encode($e) . "\n";
    }
}
echo "\n";

echo "=== 2. IMAGES (Polymorphic) ===\n";
$images = DB::table('images')
    ->where('imageable_type', 'App\Models\Product') // Check literal string
    ->where('imageable_id', $id)
    ->get();

if ($images->isEmpty()) {
    // Try distinct slash direction just in case
    $images = DB::table('images')
        ->where('imageable_type', 'App\\Models\\Product')
        ->where('imageable_id', $id)
        ->get();
}

if ($images->isEmpty()) {
    echo "No images found in 'images' table for this product.\n";
} else {
    foreach ($images as $img) {
        echo "- ID: {$img->id} | Is Primary: {$img->is_primary} | Path: {$img->file_path}\n";
    }
}
echo "\n";

echo "=== 3. DESIGNS (Pivot) ===\n";
$designs = DB::table('product_design')
    ->join('designs', 'product_design.design_id', '=', 'designs.id')
    ->where('product_id', $id)
    ->select('designs.name', 'product_design.*')
    ->get();

if ($designs->isEmpty()) {
    echo "No designs linked in 'product_design'.\n";
} else {
    foreach ($designs as $d) {
        echo "- Design: {$d->name} | App Type ID: {$d->application_type_id}\n";
    }
}
