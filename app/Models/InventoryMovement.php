<?php

namespace App\Models;

use App\Enums\MovementType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class InventoryMovement extends Model
{
    protected $table = 'inventory_movements';

    protected $fillable = [
        'uuid',
        'material_variant_id',
        'type',
        'reference_type',
        'reference_id',
        'quantity',
        'unit_cost',
        'total_cost',
        'stock_before',
        'stock_after',
        'average_cost_before',
        'average_cost_after',
        'value_before',
        'value_after',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'type' => MovementType::class,
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'total_cost' => 'decimal:4',
        'stock_before' => 'decimal:4',
        'stock_after' => 'decimal:4',
        'average_cost_before' => 'decimal:4',
        'average_cost_after' => 'decimal:4',
        'value_before' => 'decimal:4',
        'value_after' => 'decimal:4',
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
            if (empty($model->created_by)) {
                $model->created_by = Auth::id();
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    public function materialVariant()
    {
        return $this->belongsTo(MaterialVariant::class, 'material_variant_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relación polimórfica para obtener el modelo de referencia
     */
    public function reference()
    {
        return $this->morphTo('reference', 'reference_type', 'reference_id');
    }

    /**
     * Obtener la compra asociada (si aplica)
     */
    public function getPurchaseAttribute(): ?Purchase
    {
        if ($this->reference_type === 'App\Models\PurchaseItem') {
            return $this->reference?->purchase;
        }
        if ($this->reference_type === 'App\Models\Purchase') {
            return $this->reference;
        }
        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeByVariant(Builder $query, int $variantId): Builder
    {
        return $query->where('material_variant_id', $variantId);
    }

    public function scopeByType(Builder $query, MovementType $type): Builder
    {
        return $query->where('type', $type->value);
    }

    public function scopeEntries(Builder $query): Builder
    {
        return $query->where('type', MovementType::ENTRY->value);
    }

    public function scopeExits(Builder $query): Builder
    {
        return $query->where('type', MovementType::EXIT->value);
    }

    public function scopeDateRange(Builder $query, ?string $from, ?string $to): Builder
    {
        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }
        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getTypeLabelAttribute(): string
    {
        return $this->type->label();
    }

    public function getTypeColorAttribute(): string
    {
        return $this->type->color();
    }

    public function getTypeIconAttribute(): string
    {
        return $this->type->icon();
    }

    public function getFormattedQuantityAttribute(): string
    {
        $sign = $this->type->affectsStock() > 0 ? '+' : '-';
        return $sign . number_format(abs($this->quantity), 2);
    }

    public function getFormattedUnitCostAttribute(): string
    {
        return '$' . number_format($this->unit_cost, 4);
    }

    public function getFormattedTotalCostAttribute(): string
    {
        return '$' . number_format($this->total_cost, 2);
    }

    /**
     * Obtiene una etiqueta legible para el tipo de referencia
     */
    public function getReferenceLabelAttribute(): string
    {
        $labels = [
            'App\Models\PurchaseItem' => 'Recepción compra',
            'App\Models\Purchase' => 'Compra',
            'App\Models\Order' => 'Pedido',
            'App\Models\OrderItem' => 'Item de pedido',
            'App\Models\Production' => 'Producción',
            'App\Models\InventoryAdjustment' => 'Ajuste de inventario',
            'App\Models\ProductionOrder' => 'Orden de producción',
            'App\Models\WasteEvent' => 'Merma',
            'Manual' => 'Ajuste manual',
        ];

        return $labels[$this->reference_type] ?? class_basename($this->reference_type);
    }
}
