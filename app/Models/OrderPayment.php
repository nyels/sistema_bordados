<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class OrderPayment extends Model
{
    protected $table = 'order_payments';

    protected $fillable = [
        'uuid',
        'order_id',
        'amount',
        'payment_method',
        'reference',
        'notes',
        'received_by',
        'payment_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'datetime',
    ];

    // === CONSTANTES ===
    public const METHOD_CASH = 'cash';
    public const METHOD_TRANSFER = 'transfer';
    public const METHOD_CARD = 'card';
    public const METHOD_OTHER = 'other';

    // === BOOT ===
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });

        // Recalcular totales del pedido al registrar pago
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

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    // === ACCESSORS ===

    public function getMethodLabelAttribute(): string
    {
        return match($this->payment_method) {
            self::METHOD_CASH => 'Efectivo',
            self::METHOD_TRANSFER => 'Transferencia',
            self::METHOD_CARD => 'Tarjeta',
            self::METHOD_OTHER => 'Otro',
            default => $this->payment_method,
        };
    }

    public function getMethodIconAttribute(): string
    {
        return match($this->payment_method) {
            self::METHOD_CASH => 'fa-money-bill-wave',
            self::METHOD_TRANSFER => 'fa-university',
            self::METHOD_CARD => 'fa-credit-card',
            self::METHOD_OTHER => 'fa-question-circle',
            default => 'fa-dollar-sign',
        };
    }
}
