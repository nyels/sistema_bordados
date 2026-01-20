<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class OrderItem extends Model
{
    protected $table = 'order_items';

    protected $fillable = [
        'uuid',
        'order_id',
        'product_id',
        'product_variant_id',
        'product_name',
        'variant_sku',
        'unit_price',
        'quantity',
        'subtotal',
        'discount',
        'total',
        'embroidery_text',
        'customization_notes',
        'status',
        'is_annex',
        'annexed_at',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'quantity' => 'integer',
        'is_annex' => 'boolean',
        'annexed_at' => 'datetime',
    ];

    // === CONSTANTES ===
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    // === BOOT ===
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });

        // Recalcular totales del pedido al modificar items
        static::saved(function (self $model): void {
            $model->order->recalculateTotals();
        });

        static::deleted(function (self $model): void {
            $model->order->recalculateTotals();
        });
    }

    // === RELACIONES ===

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    // === MÉTODOS ===

    public function calculateTotals(): void
    {
        $this->subtotal = $this->unit_price * $this->quantity;
        $this->total = $this->subtotal - $this->discount;
    }

    // === ACCESSORS ===

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'Pendiente',
            self::STATUS_IN_PROGRESS => 'En Proceso',
            self::STATUS_COMPLETED => 'Completado',
            self::STATUS_CANCELLED => 'Cancelado',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'secondary',
            self::STATUS_IN_PROGRESS => 'warning',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_CANCELLED => 'danger',
            default => 'secondary',
        };
    }
}
