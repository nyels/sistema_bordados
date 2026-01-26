<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

/**
 * Infraestructura mínima de stock v2.
 * Trazabilidad de movimientos de productos terminados.
 *
 * SOLO ESTRUCTURA - Sin lógica de negocio.
 */
class FinishedGoodsMovement extends Model
{
    protected $table = 'finished_goods_movements';

    public const TYPE_PRODUCTION_ENTRY = 'production_entry';
    public const TYPE_SALE_EXIT = 'sale_exit';
    public const TYPE_ADJUSTMENT = 'adjustment';
    public const TYPE_RETURN = 'return';

    protected $fillable = [
        'uuid',
        'product_variant_id',
        'type',
        'reference_type',
        'reference_id',
        'quantity',
        'stock_before',
        'stock_after',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'stock_before' => 'decimal:4',
        'stock_after' => 'decimal:4',
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

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeByVariant(Builder $query, int $variantId): Builder
    {
        return $query->where('product_variant_id', $variantId);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeEntries(Builder $query): Builder
    {
        return $query->whereIn('type', [self::TYPE_PRODUCTION_ENTRY, self::TYPE_RETURN]);
    }

    public function scopeExits(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_SALE_EXIT);
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
        return match ($this->type) {
            self::TYPE_PRODUCTION_ENTRY => 'Entrada Producción',
            self::TYPE_SALE_EXIT => 'Salida Venta',
            self::TYPE_ADJUSTMENT => 'Ajuste',
            self::TYPE_RETURN => 'Devolución',
            default => $this->type,
        };
    }

    public function getTypeColorAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_PRODUCTION_ENTRY => 'success',
            self::TYPE_SALE_EXIT => 'danger',
            self::TYPE_ADJUSTMENT => 'warning',
            self::TYPE_RETURN => 'info',
            default => 'secondary',
        };
    }

    public function getTypeIconAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_PRODUCTION_ENTRY => 'fas fa-plus-circle',
            self::TYPE_SALE_EXIT => 'fas fa-minus-circle',
            self::TYPE_ADJUSTMENT => 'fas fa-balance-scale',
            self::TYPE_RETURN => 'fas fa-undo',
            default => 'fas fa-exchange-alt',
        };
    }

    public function getFormattedQuantityAttribute(): string
    {
        $isEntry = in_array($this->type, [self::TYPE_PRODUCTION_ENTRY, self::TYPE_RETURN]);
        $sign = $isEntry ? '+' : '-';
        return $sign . number_format(abs($this->quantity), 2);
    }

    public function getIsEntryAttribute(): bool
    {
        return in_array($this->type, [self::TYPE_PRODUCTION_ENTRY, self::TYPE_RETURN]);
    }

    public function getIsExitAttribute(): bool
    {
        return $this->type === self::TYPE_SALE_EXIT;
    }
}
