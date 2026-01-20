<?php

use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\Image;
use App\Models\Design;
use Illuminate\Support\Str;

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$id = 7;
echo "--- SEEDING DATA FOR PRODUCT ID: $id ---\n";

$product = Product::find($id);

// 1. Attach a Dummy Image
// Find an existing image to copy URL from, or use a placeholder
$existingImage = Image::first();
$url = $existingImage ? $existingImage->url : 'https://placehold.co/600x400/png';

if ($product->images()->count() == 0) {
    $product->images()->create([
        'url' => $url,
        'is_primary' => true,
        'file_type' => 'image/png',
        'file_size' => 1024,
    ]);
    echo "Added dummy primary image.\n";
} else {
    echo "Images already exist.\n";
}

// 2. Attach a Design
if ($product->designs()->count() == 0) {
    $design = Design::first();
    if ($design) {
        $product->designs()->attach($design->id, ['application_type_id' => 1]); // Assuming 1 exists, or null
        echo "Attached Design: {$design->name}\n";
    } else {
        echo "No designs found in DB to attach.\n";
    }
} else {
    echo "Designs already attached.\n";
}
