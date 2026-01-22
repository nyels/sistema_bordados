<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class InventoryReservation extends Model
{
    public const STATUS_RESERVED = 'reserved';
    public const STATUS_RELEASED = 'released';
    public const STATUS_CONSUMED = 'consumed';

    // Constantes para trazabilidad de origen
    public const SOURCE_PRODUCT = 'product';
    public const SOURCE_EXTRA = 'extra';

    protected $table = 'inventory_reservations';

    protected $fillable = [
        'order_id',
        'order_item_id',
        'material_variant_id',
        'source_type',
        'source_id',
        'quantity',
        'status',
        'created_by',
        'consumed_by',
        'consumed_at',
        'released_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'consumed_at' => 'datetime',
        'released_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function materialVariant()
    {
        return $this->belongsTo(MaterialVariant::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function consumer()
    {
        return $this->belongsTo(User::class, 'consumed_by');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeReserved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_RESERVED);
    }

    public function scopeConsumed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CONSUMED);
    }

    public function scopeReleased(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_RELEASED);
    }

    public function scopeForMaterial(Builder $query, int $materialVariantId): Builder
    {
        return $query->where('material_variant_id', $materialVariantId);
    }

    public function scopeFromProduct(Builder $query): Builder
    {
        return $query->where('source_type', self::SOURCE_PRODUCT);
    }

    public function scopeFromExtra(Builder $query): Builder
    {
        return $query->where('source_type', self::SOURCE_EXTRA);
    }

    public function scopeFromSource(Builder $query, string $type, ?int $id = null): Builder
    {
        $query->where('source_type', $type);
        if ($id !== null) {
            $query->where('source_id', $id);
        }
        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTODOS
    |--------------------------------------------------------------------------
    */

    public function isReserved(): bool
    {
        return $this->status === self::STATUS_RESERVED;
    }

    public function markConsumed(?int $userId = null): void
    {
        $this->update([
            'status' => self::STATUS_CONSUMED,
            'consumed_by' => $userId,
            'consumed_at' => now(),
        ]);
    }

    public function markReleased(): void
    {
        $this->update([
            'status' => self::STATUS_RELEASED,
            'released_at' => now(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTODOS DE TRAZABILIDAD
    |--------------------------------------------------------------------------
    */

    public function isFromProduct(): bool
    {
        return $this->source_type === self::SOURCE_PRODUCT;
    }

    public function isFromExtra(): bool
    {
        return $this->source_type === self::SOURCE_EXTRA;
    }

    /**
     * Obtiene el producto origen (si source_type = 'product').
     */
    public function sourceProduct()
    {
        return $this->belongsTo(Product::class, 'source_id');
    }

    /**
     * Obtiene el extra origen (si source_type = 'extra').
     */
    public function sourceExtra()
    {
        return $this->belongsTo(ProductExtra::class, 'source_id');
    }

    /**
     * Obtiene el nombre del origen para mostrar en UI/reportes.
     */
    public function getSourceNameAttribute(): string
    {
        if ($this->source_type === self::SOURCE_PRODUCT) {
            return $this->sourceProduct?->name ?? "Producto #{$this->source_id}";
        }

        if ($this->source_type === self::SOURCE_EXTRA) {
            return $this->sourceExtra?->name ?? "Extra #{$this->source_id}";
        }

        return 'Desconocido';
    }

    /**
     * Obtiene etiqueta del tipo de origen.
     */
    public function getSourceTypeLabelAttribute(): string
    {
        return match($this->source_type) {
            self::SOURCE_PRODUCT => 'Producto',
            self::SOURCE_EXTRA => 'Extra',
            default => 'N/A',
        };
    }
}
