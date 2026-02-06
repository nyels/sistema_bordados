<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ajuste de BOM para un item del pedido.
 * Permite modificar la cantidad de material requerida según las medidas del cliente.
 */
class OrderItemBomAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_item_id',
        'material_variant_id',
        'base_quantity',
        'adjusted_quantity',
        'unit_cost',
        'notes',
        'adjusted_by',
    ];

    protected $casts = [
        'base_quantity' => 'decimal:4',
        'adjusted_quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',
    ];

    /**
     * Item del pedido al que pertenece el ajuste.
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * Variante de material ajustada.
     */
    public function materialVariant(): BelongsTo
    {
        return $this->belongsTo(MaterialVariant::class);
    }

    /**
     * Usuario que realizó el ajuste.
     */
    public function adjustedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }

    /**
     * Calcula la diferencia entre cantidad ajustada y base.
     */
    public function getDifferenceAttribute(): float
    {
        return $this->adjusted_quantity - $this->base_quantity;
    }

    /**
     * Calcula el porcentaje de diferencia.
     */
    public function getDifferencePercentAttribute(): float
    {
        if ($this->base_quantity == 0) {
            return 0;
        }
        return (($this->adjusted_quantity - $this->base_quantity) / $this->base_quantity) * 100;
    }

    /**
     * Indica si hubo cambio respecto al BOM base.
     */
    public function hasChange(): bool
    {
        return abs($this->adjusted_quantity - $this->base_quantity) > 0.0001;
    }
}
