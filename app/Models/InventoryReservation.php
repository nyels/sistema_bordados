<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class InventoryReservation extends Model
{
    public const STATUS_RESERVED = 'reserved';
    public const STATUS_RELEASED = 'released';
    public const STATUS_CONSUMED = 'consumed';

    protected $table = 'inventory_reservations';

    protected $fillable = [
        'order_id',
        'order_item_id',
        'material_variant_id',
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
}
