<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use App\Traits\HasActivityLog;

class MaterialVariant extends Model
{
    use HasActivityLog;

    protected $table = 'material_variants';

    protected $activityLogNameField = 'sku';

    protected $fillable = [
        'uuid',
        'material_id',
        'color',
        'sku',
        'current_stock',
        'min_stock_alert',
        'current_value',
        'average_cost',
        'last_purchase_cost',
        'activo',
    ];

    protected $casts = [
        'current_stock' => 'decimal:4',
        'min_stock_alert' => 'decimal:4',
        'current_value' => 'decimal:6',
        'average_cost' => 'decimal:6',
        'last_purchase_cost' => 'decimal:6',
        'activo' => 'boolean',
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
        });
    }

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('color')->orderBy('sku');
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('current_stock', '<=', 'min_stock_alert');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getDisplayNameAttribute(): string
    {
        $name = $this->material?->name ?? '';
        if ($this->color) {
            $name .= " - {$this->color}";
        }
        return $name;
    }

    public function getFormattedStockAttribute(): string
    {
        $unit = $this->material?->category?->baseUnit;
        $symbol = $unit?->symbol ?? '';
        return number_format($this->current_stock, 2) . ' ' . $symbol;
    }

    public function getFormattedAverageCostAttribute(): string
    {
        return '$' . number_format($this->average_cost, 6);
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->current_stock <= $this->min_stock_alert;
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTODOS DE INVENTARIO
    |--------------------------------------------------------------------------
    */

    public function addStock(float $quantity, float $unitCost): void
    {
        $newValue = $this->current_value + ($quantity * $unitCost);
        $newStock = $this->current_stock + $quantity;

        $this->current_value = $newValue;
        $this->current_stock = $newStock;
        $this->average_cost = $newStock > 0 ? ($newValue / $newStock) : 0;
        $this->last_purchase_cost = $unitCost;
        $this->save();
    }

    public function reduceStock(float $quantity): float
    {
        $costConsumed = $quantity * $this->average_cost;

        $this->current_stock -= $quantity;
        $this->current_value -= $costConsumed;

        if ($this->current_stock < 0) {
            $this->current_stock = 0;
            $this->current_value = 0;
        }

        $this->save();

        return $costConsumed;
    }
}
