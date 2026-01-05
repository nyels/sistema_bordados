<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReceptionItem extends Model
{
    protected $table = 'purchase_reception_items';

    protected $fillable = [
        'purchase_reception_id',
        'purchase_item_id',
        'material_variant_id',
        'quantity_received',
        'converted_quantity',
        'unit_cost',
        'inventory_movement_id',
    ];

    protected $casts = [
        'quantity_received' => 'decimal:4',
        'converted_quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    public function reception()
    {
        return $this->belongsTo(PurchaseReception::class, 'purchase_reception_id');
    }

    public function purchaseItem()
    {
        return $this->belongsTo(PurchaseItem::class, 'purchase_item_id');
    }

    public function materialVariant()
    {
        return $this->belongsTo(MaterialVariant::class, 'material_variant_id');
    }

    public function inventoryMovement()
    {
        return $this->belongsTo(InventoryMovement::class, 'inventory_movement_id');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getFormattedQuantityAttribute(): string
    {
        return number_format($this->quantity_received, 2);
    }

    public function getTotalCostAttribute(): float
    {
        return $this->converted_quantity * $this->unit_cost;
    }
}
