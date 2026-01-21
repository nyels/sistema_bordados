<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class OrderItemAdjustment extends Model
{
    protected $table = 'order_item_adjustments';

    protected $fillable = [
        'uuid',
        'order_item_id',
        'design_export_id',
        'type',
        'estimated_cost',
        'real_cost',
        'status',
        'reason',
        'rejection_reason',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'estimated_cost' => 'decimal:2',
        'real_cost' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    // === CONSTANTES DE TIPO ===
    public const TYPE_DESIGN = 'design';
    public const TYPE_MATERIAL = 'material';
    public const TYPE_LABOR = 'labor';
    public const TYPE_OTHER = 'other';

    // === CONSTANTES DE ESTADO ===
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    // === BOOT ===
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });

        // Al guardar, actualizar estado de ajustes pendientes en el item
        static::saved(function (self $model): void {
            $model->orderItem->updatePendingAdjustmentsFlag();
        });

        static::deleted(function (self $model): void {
            $model->orderItem->updatePendingAdjustmentsFlag();
        });
    }

    // === RELACIONES ===

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

    public function designExport(): BelongsTo
    {
        return $this->belongsTo(DesignExport::class, 'design_export_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // === SCOPES ===

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    // === MÉTODOS DE NEGOCIO ===

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Calcula la diferencia entre costo real y estimado.
     * Positivo = sobrecosto, Negativo = ahorro.
     */
    public function getDifferenceAttribute(): ?float
    {
        if ($this->real_cost === null) {
            return null;
        }
        return (float) $this->real_cost - (float) $this->estimated_cost;
    }

    /**
     * Costo efectivo a usar en cálculos del pedido.
     * Si está aprobado y tiene real_cost → real_cost
     * Si no → estimated_cost
     */
    public function getEffectiveCostAttribute(): float
    {
        if ($this->isApproved() && $this->real_cost !== null) {
            return (float) $this->real_cost;
        }
        return (float) $this->estimated_cost;
    }

    // === ACCESSORS ===

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            self::TYPE_DESIGN => 'Diseño',
            self::TYPE_MATERIAL => 'Material',
            self::TYPE_LABOR => 'Mano de Obra',
            self::TYPE_OTHER => 'Otro',
            default => $this->type,
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'Pendiente',
            self::STATUS_APPROVED => 'Aprobado',
            self::STATUS_REJECTED => 'Rechazado',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_APPROVED => 'success',
            self::STATUS_REJECTED => 'danger',
            default => 'secondary',
        };
    }
}
