<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PurchaseItem extends Model
{
    protected $table = 'purchase_items';

    protected $fillable = [
        'uuid',
        'purchase_id',
        'material_variant_id',
        'unit_id',
        'quantity',
        'unit_price',
        'conversion_factor',
        'converted_quantity',
        'converted_unit_cost',
        'subtotal',
        'quantity_received',
        'converted_quantity_received',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'conversion_factor' => 'decimal:4',
        'converted_quantity' => 'decimal:4',
        'converted_unit_cost' => 'decimal:4',
        'subtotal' => 'decimal:4',
        'quantity_received' => 'decimal:4',
        'converted_quantity_received' => 'decimal:4',
    ];

    /*
    |--------------------------------------------------------------------------
    | BOOT
    |--------------------------------------------------------------------------
    */

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            $model->calculateConversions();
        });

        static::updating(function (self $model): void {
            if ($model->isDirty(['quantity', 'unit_price', 'conversion_factor'])) {
                $model->calculateConversions();
            }
        });

        static::saved(function (self $model): void {
            $model->purchase->recalculateTotals();
        });

        static::deleted(function (self $model): void {
            $model->purchase->recalculateTotals();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'purchase_id');
    }

    public function materialVariant()
    {
        return $this->belongsTo(MaterialVariant::class, 'material_variant_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getFormattedSubtotalAttribute(): string
    {
        return '$' . number_format($this->subtotal, 2);
    }

    public function getFormattedUnitPriceAttribute(): string
    {
        return '$' . number_format($this->unit_price, 2);
    }

    public function getFormattedConvertedUnitCostAttribute(): string
    {
        return '$' . number_format($this->converted_unit_cost, 4);
    }

    public function getPendingQuantityAttribute(): float
    {
        return max(0, $this->quantity - $this->quantity_received);
    }

    public function getPendingConvertedQuantityAttribute(): float
    {
        return max(0, $this->converted_quantity - $this->converted_quantity_received);
    }

    public function getIsFullyReceivedAttribute(): bool
    {
        return $this->quantity_received >= $this->quantity;
    }

    public function getReceivedPercentageAttribute(): float
    {
        if ($this->quantity <= 0) {
            return 0;
        }
        return min(100, ($this->quantity_received / $this->quantity) * 100);
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTODOS
    |--------------------------------------------------------------------------
    */

    protected function calculateConversions(): void
    {
        $this->converted_quantity = $this->quantity * $this->conversion_factor;
        $this->subtotal = $this->quantity * $this->unit_price;

        if ($this->converted_quantity > 0) {
            $this->converted_unit_cost = $this->subtotal / $this->converted_quantity;
        } else {
            $this->converted_unit_cost = 0;
        }
    }

    public function recalculate(): void
    {
        $this->calculateConversions();
        $this->save();
    }

    public function addReceivedQuantity(float $quantity): void
    {
        $convertedQty = $quantity * $this->conversion_factor;

        $this->quantity_received += $quantity;
        $this->converted_quantity_received += $convertedQty;
        $this->save();
    }
}
