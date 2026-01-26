<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

/**
 * MERMA (WASTE EVENT) - MODELO CANÓNICO
 *
 * DEFINICIÓN:
 * Evento físico irreversible de pérdida de material o producto.
 *
 * TIPOS:
 * - material: Merma de materia prima
 * - wip: Merma en proceso (producción fallida)
 * - finished_product: Merma de producto terminado
 *
 * REGLAS DURAS:
 * - NO editable
 * - NO eliminable
 * - NO afecta inventario directamente (es registro contable)
 * - NO toca FinishedGoodsMovement
 * - SOLO registra el evento de merma
 */
class WasteEvent extends Model
{
    protected $table = 'waste_events';

    // === TIPOS DE MERMA ===
    public const TYPE_MATERIAL = 'material';
    public const TYPE_WIP = 'wip';
    public const TYPE_FINISHED_PRODUCT = 'finished_product';

    public const TYPES = [
        self::TYPE_MATERIAL,
        self::TYPE_WIP,
        self::TYPE_FINISHED_PRODUCT,
    ];

    protected $fillable = [
        'uuid',
        'waste_type',
        'order_id',
        'product_variant_id',
        'quantity',
        'total_cost',
        'reason',
        'evidence_path',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'total_cost' => 'decimal:4',
    ];

    /*
    |--------------------------------------------------------------------------
    | BOOT - INMUTABILIDAD
    |--------------------------------------------------------------------------
    */

    protected static function boot(): void
    {
        parent::boot();

        // UUID automático
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->created_by)) {
                $model->created_by = Auth::id();
            }
        });

        // INMUTABILIDAD: Bloquear actualizaciones
        static::updating(function (self $model): void {
            throw new \Exception(
                'MERMA INMUTABLE: Los eventos de merma no pueden modificarse. ' .
                'Este es un registro contable definitivo.'
            );
        });

        // INMUTABILIDAD: Bloquear eliminaciones
        static::deleting(function (self $model): void {
            throw new \Exception(
                'MERMA INMUTABLE: Los eventos de merma no pueden eliminarse. ' .
                'Este es un registro contable definitivo.'
            );
        });
    }

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    /**
     * Pedido relacionado (opcional).
     * Para WIP: pedido donde ocurrió la falla.
     * Para PT: puede venir de un pedido cancelado.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Variante de producto terminado (solo para finished_product).
     */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Usuario que registró la merma.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Items de material asociados a este evento de merma.
     */
    public function materialItems(): HasMany
    {
        return $this->hasMany(WasteMaterialItem::class, 'waste_event_id');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('waste_type', $type);
    }

    public function scopeMaterials(Builder $query): Builder
    {
        return $query->where('waste_type', self::TYPE_MATERIAL);
    }

    public function scopeWip(Builder $query): Builder
    {
        return $query->where('waste_type', self::TYPE_WIP);
    }

    public function scopeFinishedProducts(Builder $query): Builder
    {
        return $query->where('waste_type', self::TYPE_FINISHED_PRODUCT);
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

    public function scopeByOrder(Builder $query, int $orderId): Builder
    {
        return $query->where('order_id', $orderId);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getTypeLabelAttribute(): string
    {
        return match ($this->waste_type) {
            self::TYPE_MATERIAL => 'Merma de Material',
            self::TYPE_WIP => 'Merma en Proceso',
            self::TYPE_FINISHED_PRODUCT => 'Merma de Producto Terminado',
            default => $this->waste_type,
        };
    }

    public function getTypeColorAttribute(): string
    {
        return match ($this->waste_type) {
            self::TYPE_MATERIAL => 'warning',
            self::TYPE_WIP => 'danger',
            self::TYPE_FINISHED_PRODUCT => 'dark',
            default => 'secondary',
        };
    }

    public function getTypeIconAttribute(): string
    {
        return match ($this->waste_type) {
            self::TYPE_MATERIAL => 'fas fa-cubes',
            self::TYPE_WIP => 'fas fa-industry',
            self::TYPE_FINISHED_PRODUCT => 'fas fa-box-open',
            default => 'fas fa-exclamation-triangle',
        };
    }

    public function getFormattedTotalCostAttribute(): string
    {
        return '$' . number_format($this->total_cost, 2);
    }

    public function getFormattedQuantityAttribute(): string
    {
        return number_format($this->quantity, 2);
    }

    /**
     * Indica si la merma tiene evidencia adjunta.
     */
    public function getHasEvidenceAttribute(): bool
    {
        return !empty($this->evidence_path);
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTODOS DE NEGOCIO
    |--------------------------------------------------------------------------
    */

    /**
     * Verifica si es merma de material.
     */
    public function isMaterialWaste(): bool
    {
        return $this->waste_type === self::TYPE_MATERIAL;
    }

    /**
     * Verifica si es merma en proceso (WIP).
     */
    public function isWipWaste(): bool
    {
        return $this->waste_type === self::TYPE_WIP;
    }

    /**
     * Verifica si es merma de producto terminado.
     */
    public function isFinishedProductWaste(): bool
    {
        return $this->waste_type === self::TYPE_FINISHED_PRODUCT;
    }

    /**
     * Recalcula el costo total desde los items de material.
     * SOLO para lectura/auditoría, no modifica el modelo.
     */
    public function calculateMaterialsCost(): float
    {
        return (float) $this->materialItems()->sum('total_cost');
    }
}
