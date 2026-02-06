<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItemExtra extends Model
{
    protected $table = 'order_item_extras';

    protected $fillable = [
        'order_item_id',
        'product_extra_id',
        'quantity',
        'unit_price',
        'total_price',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    // === RELACIONES ===

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

    public function productExtra(): BelongsTo
    {
        return $this->belongsTo(ProductExtra::class, 'product_extra_id');
    }

    // === ACCESSORS ===

    public function getExtraNameAttribute(): string
    {
        return $this->productExtra->name ?? 'Extra eliminado';
    }

    // === SCOPES ===

    public function scopeByExtra($query, int $extraId)
    {
        return $query->where('product_extra_id', $extraId);
    }

    // === MÉTODOS DE NEGOCIO ===

    /**
     * Recalcula el total basado en cantidad y precio unitario
     */
    public function recalculateTotal(): self
    {
        $this->total_price = $this->quantity * $this->unit_price;
        return $this;
    }
}
