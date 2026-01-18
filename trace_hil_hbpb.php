<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MaterialVariant;
use Illuminate\Support\Facades\DB;

$v = MaterialVariant::where('sku', 'HIL-HBPB-001')->first();
if (!$v) {
    echo "Variant HIL-HBPB-001 not found.\n";
    exit;
}

echo "--- Variant Data ---\n";
echo "SKU: " . $v->sku . "\n";
echo "Current Stock: " . $v->current_stock . "\n";
echo "Current Value: " . $v->current_value . "\n";
echo "Average Cost: " . $v->average_cost . "\n";

echo "\n--- Inventory Movements (inventory_movements) ---\n";
$movements = DB::table('inventory_movements')->where('material_variant_id', $v->id)->get();
foreach ($movements as $m) {
    echo "ID: {$m->id} | Type: {$m->type} | Qty: {$m->quantity} | Prev Stock: {$m->previous_stock} | Result Stock: {$m->resulting_stock} | Date: {$m->created_at}\n";
}

echo "\n--- Purchase Items (purchase_items) ---\n";
$pItems = DB::table('purchase_items')->where('material_variant_id', $v->id)->get();
foreach ($pItems as $pi) {
    echo "ID: {$pi->id} | Purchase ID: {$pi->purchase_id} | Qty: {$pi->quantity} | Unit Price: {$pi->unit_price} | Subtotal: {$pi->subtotal} | Tax: {$pi->tax_amount} | Total: {$pi->total_amount}\n";
}

echo "\n--- Purchase Reception Items (purchase_reception_items) ---\n";
$rItems = DB::table('purchase_reception_items')->where('material_variant_id', $v->id)->get();
foreach ($rItems as $ri) {
    echo "ID: {$ri->id} | Reception ID: {$ri->purchase_reception_id} | Qty: {$ri->received_quantity} | Date: {$ri->created_at}\n";
}
