<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * RESERVA DE STOCK DE PRODUCTOS TERMINADOS v2.2
 *
 * REGLAS ERP SELLADAS:
 * - Reservar ≠ descontar
 * - current_stock NO cambia
 * - reserved_stock SÍ cambia
 * - Un OrderItem solo puede tener UNA reserva (idempotencia por unique constraint)
 */
class ProductVariantReservation extends Model
{
    public const STATUS_RESERVED = 'reserved';
    public const STATUS_RELEASED = 'released';
    public const STATUS_CONSUMED = 'consumed';

    protected $table = 'product_variant_reservations';

    protected $fillable = [
        'uuid',
        'order_id',
        'order_item_id',
        'product_variant_id',
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

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function consumer(): BelongsTo
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

    public function scopeForVariant(Builder $query, int $productVariantId): Builder
    {
        return $query->where('product_variant_id', $productVariantId);
    }

    public function scopeForOrder(Builder $query, int $orderId): Builder
    {
        return $query->where('order_id', $orderId);
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTODOS DE ESTADO
    |--------------------------------------------------------------------------
    */

    public function isReserved(): bool
    {
        return $this->status === self::STATUS_RESERVED;
    }

    public function isConsumed(): bool
    {
        return $this->status === self::STATUS_CONSUMED;
    }

    public function isReleased(): bool
    {
        return $this->status === self::STATUS_RELEASED;
    }

    /**
     * Marca la reserva como consumida (al entregar el pedido).
     * REGLA: Al consumir, se descuenta current_stock del ProductVariant.
     */
    public function markConsumed(?int $userId = null): void
    {
        $this->update([
            'status' => self::STATUS_CONSUMED,
            'consumed_by' => $userId,
            'consumed_at' => now(),
        ]);
    }

    /**
     * Libera la reserva (al cancelar el pedido).
     * REGLA: Al liberar, se devuelve reserved_stock al ProductVariant.
     */
    public function markReleased(): void
    {
        $this->update([
            'status' => self::STATUS_RELEASED,
            'released_at' => now(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_RESERVED => 'Reservado',
            self::STATUS_RELEASED => 'Liberado',
            self::STATUS_CONSUMED => 'Consumido',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_RESERVED => 'warning',
            self::STATUS_RELEASED => 'secondary',
            self::STATUS_CONSUMED => 'success',
            default => 'secondary',
        };
    }
}
