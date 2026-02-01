<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class OrderItem extends Model
{
    protected $table = 'order_items';

    protected $fillable = [
        'uuid',
        'order_id',
        'product_id',
        'product_variant_id',
        'product_type_id',
        'requires_measurements',
        'client_measurement_id',
        'measurements', // JSON - medidas inline capturadas en el pedido
        'measurement_history_id',
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
        'estimated_extras_cost',
        'real_extras_cost',
        'has_pending_adjustments',
        'final_total',
        'personalization_type',
        'design_approved',
        'design_approved_at',
        'design_approved_by',
        'design_file',
        'design_original_name',
        'design_status',
        'design_notes',
        'custom_text',
        'time_multiplier_snapshot',
        'estimated_lead_time',
        'measurements_hash_at_approval',
        'production_completed',
        'production_completed_at',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'quantity' => 'integer',
        'product_type_id' => 'integer',
        'requires_measurements' => 'boolean',
        'client_measurement_id' => 'integer',
        'measurements' => 'array', // JSON - medidas inline
        'is_annex' => 'boolean',
        'annexed_at' => 'datetime',
        'estimated_extras_cost' => 'decimal:2',
        'real_extras_cost' => 'decimal:2',
        'has_pending_adjustments' => 'boolean',
        'final_total' => 'decimal:2',
        'design_approved' => 'boolean',
        'design_approved_at' => 'datetime',
        'time_multiplier_snapshot' => 'decimal:2',
        'estimated_lead_time' => 'integer',
        'production_completed' => 'boolean',
        'production_completed_at' => 'datetime',
    ];

    // === CONSTANTES ===
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    // Tipos de personalización (snapshot - valores inmutables)
    public const PERSONALIZATION_NONE = 'none';
    public const PERSONALIZATION_TEXT = 'text';
    public const PERSONALIZATION_MEASUREMENTS = 'measurements';
    public const PERSONALIZATION_DESIGN = 'design';

    // Estados de diseño
    public const DESIGN_STATUS_PENDING = 'pending';
    public const DESIGN_STATUS_IN_REVIEW = 'in_review';
    public const DESIGN_STATUS_APPROVED = 'approved';
    public const DESIGN_STATUS_REJECTED = 'rejected';

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

    /**
     * Tipo de producto (snapshot al momento del pedido)
     */
    public function productType(): BelongsTo
    {
        return $this->belongsTo(ProductType::class, 'product_type_id');
    }

    /**
     * Medidas del cliente asociadas a este item (FK legacy)
     */
    public function measurement(): BelongsTo
    {
        return $this->belongsTo(ClientMeasurement::class, 'client_measurement_id');
    }

    /**
     * Historial de medidas asociado a este item
     */
    public function measurementHistory(): BelongsTo
    {
        return $this->belongsTo(ClientMeasurementHistory::class, 'measurement_history_id');
    }

    /**
     * Reservas de inventario de MATERIALES asociadas a este item.
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(InventoryReservation::class, 'order_item_id');
    }

    /**
     * Reserva de stock de PRODUCTO TERMINADO asociada a este item.
     * REGLA v2.2: Un item solo puede tener UNA reserva de producto terminado.
     */
    public function stockReservation()
    {
        return $this->hasOne(ProductVariantReservation::class, 'order_item_id');
    }

    /**
     * Verifica si este item tiene una reserva de stock activa.
     */
    public function hasActiveStockReservation(): bool
    {
        return $this->stockReservation()
            ->where('status', ProductVariantReservation::STATUS_RESERVED)
            ->exists();
    }

    /**
     * Ajustes de precio asociados a este item
     */
    public function adjustments(): HasMany
    {
        return $this->hasMany(OrderItemAdjustment::class, 'order_item_id');
    }

    /**
     * Ajustes pendientes de aprobación
     */
    public function pendingAdjustments(): HasMany
    {
        return $this->adjustments()->where('status', OrderItemAdjustment::STATUS_PENDING);
    }

    /**
     * Ajustes aprobados
     */
    public function approvedAdjustments(): HasMany
    {
        return $this->adjustments()->where('status', OrderItemAdjustment::STATUS_APPROVED);
    }

    /**
     * Diseños técnicos (DesignExport) vinculados a este item.
     * Solo se permite vincular diseños con status = 'aprobado'.
     * Un item personalizado puede tener múltiples diseños (logo + nombre, etc.)
     */
    public function designExports(): BelongsToMany
    {
        return $this->belongsToMany(DesignExport::class, 'order_item_design_exports')
            ->withPivot(['application_type', 'position', 'notes', 'sort_order', 'created_by'])
            ->withTimestamps()
            ->orderBy('order_item_design_exports.sort_order');
    }

    // === MÉTODOS ===

    /**
     * FASE 2: Determina si el ítem tiene información completa para CAPTURA.
     *
     * ALCANCE: Este método verifica readiness para CONFIRMACIÓN del pedido,
     * NO es el gate de PRODUCCIÓN. Para validar inicio de producción,
     * usar exclusivamente Order::canStartProduction().
     *
     * REGLAS DE CAPTURA:
     * R1: requires_measurements=true Y measurements=NULL → PENDING
     * R2: personalization_type='design' Y design_approved=false → PENDING
     * R3: personalization_type IN ('design','text') Y sin diseños técnicos → PENDING
     *
     * NOTA: Bloqueos adicionales de producción (ajustes pendientes, medidas
     * cambiadas post-aprobación, inventario) se validan en Order::canStartProduction().
     *
     * @return bool TRUE si el item tiene información completa para confirmar
     */
    public function isReady(): bool
    {
        // R1: Requiere medidas y no las tiene
        if ($this->requires_measurements && empty($this->measurements)) {
            return false;
        }

        // R2: Requiere diseño aprobado y no lo tiene
        if ($this->blocksProductionForDesign()) {
            return false;
        }

        // R3: Requiere diseños técnicos y no los tiene
        if ($this->blocksProductionForTechnicalDesigns()) {
            return false;
        }

        return true;
    }

    /**
     * Obtiene el motivo por el cual el ítem está PENDING.
     * Retorna NULL si el item está READY.
     *
     * @return string|null Descripción del pendiente
     */
    public function getPendingReason(): ?string
    {
        if ($this->requires_measurements && empty($this->measurements)) {
            return 'Medidas del cliente pendientes';
        }

        if ($this->blocksProductionForDesign()) {
            return 'Diseño pendiente de aprobación';
        }

        if ($this->blocksProductionForTechnicalDesigns()) {
            return 'Diseño técnico sin vincular';
        }

        return null; // READY
    }

    /**
     * Obtiene el código de la regla que bloquea el item.
     * Útil para badges en UI (R1, R2, R3).
     *
     * @return string|null Código de regla o NULL si READY
     */
    public function getPendingRuleCode(): ?string
    {
        if ($this->requires_measurements && empty($this->measurements)) {
            return 'R1';
        }

        if ($this->blocksProductionForDesign()) {
            return 'R2';
        }

        if ($this->blocksProductionForTechnicalDesigns()) {
            return 'R3';
        }

        return null;
    }

    public function calculateTotals(): void
    {
        $this->subtotal = $this->unit_price * $this->quantity;
        $this->total = $this->subtotal - $this->discount;
    }

    /**
     * Actualiza el flag has_pending_adjustments basado en ajustes existentes.
     */
    public function updatePendingAdjustmentsFlag(): void
    {
        $hasPending = $this->pendingAdjustments()->exists();
        if ($this->has_pending_adjustments !== $hasPending) {
            $this->has_pending_adjustments = $hasPending;
            $this->saveQuietly();
            // Recalcular totales del pedido
            $this->order->recalculateTotals();
        }
    }

    /**
     * Recalcula real_extras_cost sumando ajustes aprobados.
     */
    public function recalculateRealExtrasCost(): void
    {
        $this->real_extras_cost = $this->approvedAdjustments()->sum('real_cost');
        $this->calculateFinalTotal();
        $this->saveQuietly();
    }

    /**
     * Calcula final_total incluyendo ajustes aprobados.
     * final_total = total + real_extras_cost (si hay) o estimated_extras_cost
     */
    public function calculateFinalTotal(): void
    {
        $extrasCost = $this->real_extras_cost ?? $this->estimated_extras_cost ?? 0;
        $this->final_total = (float) $this->total + (float) $extrasCost;
    }

    /**
     * Verifica si el item tiene ajustes pendientes que bloquean producción.
     */
    public function hasPendingAdjustmentsBlocking(): bool
    {
        return $this->has_pending_adjustments;
    }

    /**
     * Verifica si el item requiere diseño aprobado para producción.
     * REGLA: personalization_type = 'design' → requiere aprobación
     */
    public function requiresDesignApproval(): bool
    {
        return $this->personalization_type === self::PERSONALIZATION_DESIGN;
    }

    /**
     * Verifica si el diseño está aprobado (si es requerido).
     * Items sin tipo 'design' siempre retornan true.
     */
    public function isDesignApproved(): bool
    {
        if (!$this->requiresDesignApproval()) {
            return true;
        }
        return $this->design_approved === true;
    }

    /**
     * Bloquea producción si:
     * - Tiene personalización tipo 'design' Y no está aprobado
     */
    public function blocksProductionForDesign(): bool
    {
        return $this->requiresDesignApproval() && !$this->design_approved;
    }

    /**
     * Snapshot de medidas al momento de aprobación del diseño.
     * Se guarda cuando design_approved = true para detectar cambios posteriores.
     */
    protected ?string $measurementsAtApproval = null;

    /**
     * Verifica si las medidas han cambiado después de aprobación del diseño.
     * REGLA: Si design_approved=true Y measurements actuales ≠ measurements al aprobar → BLOQUEA
     *
     * @return bool True si hay cambios que bloquean producción
     */
    public function hasMeasurementsChangedAfterApproval(): bool
    {
        // Solo aplica a items con diseño aprobado
        if (!$this->design_approved) {
            return false;
        }

        // Si no requiere medidas, no hay riesgo
        if (!$this->requires_measurements) {
            return false;
        }

        // Verificar integridad: si tiene medidas y diseño aprobado,
        // comparar hash de medidas actuales vs hash almacenado
        $currentMeasurementsHash = $this->getMeasurementsHash();
        $approvalMeasurementsHash = $this->measurements_hash_at_approval ?? null;

        // Si no hay hash de aprobación, asumir que está OK (legacy data)
        if ($approvalMeasurementsHash === null) {
            return false;
        }

        return $currentMeasurementsHash !== $approvalMeasurementsHash;
    }

    /**
     * Genera hash de las medidas actuales para comparación.
     */
    public function getMeasurementsHash(): ?string
    {
        if (empty($this->measurements)) {
            return null;
        }

        // Normalizar y ordenar para hash consistente
        $normalized = $this->measurements;
        if (is_array($normalized)) {
            ksort($normalized);
            return md5(json_encode($normalized));
        }

        return md5(json_encode($normalized));
    }

    /**
     * Guarda el hash de medidas al aprobar diseño.
     * Llamado desde OrderService::approveItemDesign()
     */
    public function snapshotMeasurementsForApproval(): void
    {
        $this->measurements_hash_at_approval = $this->getMeasurementsHash();
    }

    /**
     * Verifica si el item requiere vinculación de diseños técnicos.
     * REGLA: Solo items con personalization_type = 'design' o 'text' requieren diseños.
     */
    public function requiresTechnicalDesigns(): bool
    {
        return in_array($this->personalization_type, [
            self::PERSONALIZATION_DESIGN,
            self::PERSONALIZATION_TEXT,
        ]);
    }

    /**
     * Verifica si el item tiene al menos un diseño técnico vinculado.
     */
    public function hasTechnicalDesigns(): bool
    {
        return $this->designExports()->exists();
    }

    /**
     * Obtiene el conteo de diseños técnicos vinculados.
     */
    public function getTechnicalDesignsCountAttribute(): int
    {
        return $this->designExports()->count();
    }

    /**
     * Verifica si el item está listo para producción en términos de diseños.
     * REGLA: Si requiere diseños técnicos, debe tener al menos uno vinculado.
     */
    public function hasRequiredTechnicalDesigns(): bool
    {
        if (!$this->requiresTechnicalDesigns()) {
            return true; // No requiere diseños → OK
        }
        return $this->hasTechnicalDesigns();
    }

    /**
     * Bloquea producción si requiere diseños técnicos y no los tiene.
     */
    public function blocksProductionForTechnicalDesigns(): bool
    {
        return $this->requiresTechnicalDesigns() && !$this->hasTechnicalDesigns();
    }

    /**
     * Verifica si el item puede ser modificado.
     * REGLA: Items de pedidos post-producción son INMUTABLES.
     */
    public function isMutationBlocked(): bool
    {
        return $this->order->isMutationBlocked();
    }

    /**
     * Lista de campos que NO pueden modificarse post-producción.
     */
    public const IMMUTABLE_FIELDS_POST_PRODUCTION = [
        'product_id',
        'product_variant_id',
        'product_type_id',
        'product_name',
        'variant_sku',
        'unit_price',
        'quantity',
        'subtotal',
        'discount',
        'total',
        'embroidery_text',
        'customization_notes',
        'measurements',
        'personalization_type',
        'time_multiplier_snapshot',
        'estimated_lead_time',
    ];

    /**
     * Valida que los cambios propuestos no violen reglas de inmutabilidad.
     *
     * @param array $changes Campos que se intentan modificar
     * @throws \Exception Si se intenta modificar un campo inmutable
     */
    public function validateMutationAllowed(array $changes): void
    {
        if (!$this->isMutationBlocked()) {
            return; // Mutaciones permitidas
        }

        $blockedChanges = array_intersect(
            array_keys($changes),
            self::IMMUTABLE_FIELDS_POST_PRODUCTION
        );

        if (!empty($blockedChanges)) {
            $fieldList = implode(', ', $blockedChanges);
            throw new \Exception(
                "Mutación bloqueada: el pedido está en producción. " .
                "Campos protegidos del item: {$fieldList}. " .
                "Para modificar, cree un pedido anexo."
            );
        }
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

    /**
     * Indica si este item tiene medidas asociadas (inline o FK)
     */
    public function getHasMeasurementsAttribute(): bool
    {
        // Prioridad: medidas inline > medidas FK
        if (!empty($this->measurements)) {
            return true;
        }
        return $this->client_measurement_id !== null;
    }

    /**
     * Resumen de medidas para display
     */
    public function getMeasurementSummaryAttribute(): ?string
    {
        return $this->measurement?->summary;
    }

    /**
     * Nombre del tipo de producto (desde snapshot)
     */
    public function getProductTypeNameAttribute(): ?string
    {
        return $this->productType?->display_name;
    }
}
