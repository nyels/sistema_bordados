<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== VERIFICACION DE EXTRAS EN ORDER_ITEMS ===\n\n";

$items = DB::table('order_items')
    ->join('orders', 'order_items.order_id', '=', 'orders.id')
    ->leftJoin('product_variants', 'order_items.product_variant_id', '=', 'product_variants.id')
    ->whereIn('orders.status', ['draft', 'confirmed'])
    ->select(
        'order_items.id',
        'order_items.product_name',
        'order_items.unit_price as item_unit_price',
        'product_variants.price as variant_price'
    )
    ->orderBy('order_items.id', 'desc')
    ->limit(10)
    ->get();

foreach ($items as $item) {
    $extras = DB::table('order_item_extras')
        ->join('product_extras', 'order_item_extras.product_extra_id', '=', 'product_extras.id')
        ->where('order_item_extras.order_item_id', $item->id)
        ->select('product_extras.name', 'order_item_extras.quantity', 'order_item_extras.unit_price', 'order_item_extras.total_price')
        ->get();

    $extrasTotal = $extras->sum('total_price');
    $variantPrice = $item->variant_price ?? 0;
    $calculatedPrice = $variantPrice + $extrasTotal;
    $diff = $item->item_unit_price - $calculatedPrice;

    echo "Item #{$item->id}: {$item->product_name}\n";
    echo "  unit_price guardado: \${$item->item_unit_price}\n";
    echo "  variant_price: \${$variantPrice}\n";
    echo "  extras_total: \${$extrasTotal}\n";
    echo "  calculado (variant + extras): \${$calculatedPrice}\n";
    echo "  diferencia: \${$diff}\n";

    if ($extras->count() > 0) {
        echo "  Extras:\n";
        foreach ($extras as $extra) {
            echo "    - {$extra->name} x{$extra->quantity} @ \${$extra->unit_price} = \${$extra->total_price}\n";
        }
    }
    echo "---\n";
}
