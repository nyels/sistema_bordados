<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * MERMA DE MATERIAL - ITEM INDIVIDUAL
 *
 * PROPÓSITO:
 * Registra cada material perdido en un evento de merma.
 * Funciona como BOM snapshot para trazabilidad.
 *
 * RELACIÓN:
 * WasteEvent 1:N WasteMaterialItem
 *
 * REGLAS DURAS:
 * - NO editable (hereda inmutabilidad del WasteEvent)
 * - NO eliminable directamente (cascade desde WasteEvent está bloqueado)
 * - Snapshot de costos al momento del registro
 */
class WasteMaterialItem extends Model
{
    protected $table = 'waste_material_items';

    protected $fillable = [
        'uuid',
        'waste_event_id',
        'material_variant_id',
        'quantity',
        'unit_cost_snapshot',
        'total_cost',
        'unit_symbol',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_cost_snapshot' => 'decimal:6',
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

            // Auto-calcular total_cost si no se proporciona
            if (empty($model->total_cost) && $model->quantity && $model->unit_cost_snapshot) {
                $model->total_cost = $model->quantity * $model->unit_cost_snapshot;
            }
        });

        // INMUTABILIDAD: Bloquear actualizaciones
        static::updating(function (self $model): void {
            throw new \Exception(
                'MERMA INMUTABLE: Los items de merma no pueden modificarse. ' .
                'Este es un registro contable definitivo.'
            );
        });

        // INMUTABILIDAD: Bloquear eliminaciones directas
        static::deleting(function (self $model): void {
            throw new \Exception(
                'MERMA INMUTABLE: Los items de merma no pueden eliminarse. ' .
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
     * Evento de merma padre.
     */
    public function wasteEvent(): BelongsTo
    {
        return $this->belongsTo(WasteEvent::class, 'waste_event_id');
    }

    /**
     * Variante de material asociada.
     */
    public function materialVariant(): BelongsTo
    {
        return $this->belongsTo(MaterialVariant::class, 'material_variant_id');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeByEvent(Builder $query, int $eventId): Builder
    {
        return $query->where('waste_event_id', $eventId);
    }

    public function scopeByMaterial(Builder $query, int $variantId): Builder
    {
        return $query->where('material_variant_id', $variantId);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    /**
     * Nombre del material con color (para display).
     */
    public function getMaterialNameAttribute(): string
    {
        $variant = $this->materialVariant;
        if (!$variant) {
            return 'Material desconocido';
        }
        return $variant->display_name;
    }

    public function getFormattedQuantityAttribute(): string
    {
        $unit = $this->unit_symbol ?? '';
        return number_format($this->quantity, 2) . ' ' . $unit;
    }

    public function getFormattedUnitCostAttribute(): string
    {
        return '$' . number_format($this->unit_cost_snapshot, 4);
    }

    public function getFormattedTotalCostAttribute(): string
    {
        return '$' . number_format($this->total_cost, 2);
    }
}
