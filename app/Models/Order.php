<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Order extends Model
{
    use SoftDeletes;

    protected $table = 'orders';

    protected $fillable = [
        'uuid',
        'order_number',
        'order_parent_id',
        'related_order_id',
        'cliente_id',
        'client_measurement_id',
        'design_export_id',
        'status',
        'urgency_level',
        'priority',
        'payment_status',
        'subtotal',
        'discount',
        'requires_invoice',
        'iva_amount',
        'iva_rate',
        'total_with_tax',
        'total',
        'amount_paid',
        'balance',
        'materials_cost_snapshot',
        'total_stitches_snapshot',
        'embroidery_cost_snapshot',
        'cost_per_thousand_snapshot',
        'services_cost_snapshot',
        'promised_date',
        'minimum_date',
        'delivered_date',
        'cancelled_at',
        'cancelled_by',
        'cancel_reason',
        'notes',
        'created_by',
        'updated_by',
        // Campos POS
        'discount_reason',
        'discount_type',
        'discount_value',
        'payment_method',
        'sold_at',
        'seller_name',
    ];

    // === CONSTANTES DE PRIORIDAD ===
    public const PRIORITY_EXPRESS = 10;
    public const PRIORITY_URGENT = 25;
    public const PRIORITY_NORMAL = 50;
    public const PRIORITY_LOW = 75;

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'requires_invoice' => 'boolean',
        'iva_amount' => 'decimal:2',
        'iva_rate' => 'decimal:2',
        'total_with_tax' => 'decimal:2',
        'total' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance' => 'decimal:2',
        'materials_cost_snapshot' => 'decimal:4',
        'total_stitches_snapshot' => 'integer',
        'embroidery_cost_snapshot' => 'decimal:4',
        'cost_per_thousand_snapshot' => 'decimal:4',
        'services_cost_snapshot' => 'decimal:4',
        'promised_date' => 'date',
        'minimum_date' => 'date',
        'delivered_date' => 'date',
        'cancelled_at' => 'datetime',
        // Campos POS
        'discount_value' => 'decimal:2',
        'sold_at' => 'datetime',
    ];

    // === CONSTANTES DE ESTADO ===
    public const STATUS_DRAFT = 'draft';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_IN_PRODUCTION = 'in_production';
    public const STATUS_READY = 'ready';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_CANCELLED = 'cancelled';

    public const PAYMENT_PENDING = 'pending';
    public const PAYMENT_PARTIAL = 'partial';
    public const PAYMENT_PAID = 'paid';

    // === CONSTANTES DE URGENCIA ===
    public const URGENCY_NORMAL = 'normal';
    public const URGENCY_URGENTE = 'urgente';
    public const URGENCY_EXPRESS = 'express';

    // Multiplicadores de tiempo de producción según urgencia
    public const URGENCY_MULTIPLIERS = [
        self::URGENCY_NORMAL => 1.0,
        self::URGENCY_URGENTE => 0.7,
        self::URGENCY_EXPRESS => 0.5,
    ];

    // === BOOT ===
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->order_number)) {
                $model->order_number = self::generateOrderNumber();
            }
        });

        // ================================================================
        // AUDITORÍA: Validación de transiciones de estado en modelo
        // Previene cambios directos vía Eloquent que puedan corromper inventario
        // ================================================================
        static::updating(function (self $model): void {
            if ($model->isDirty('status')) {
                $oldStatus = $model->getOriginal('status');
                $newStatus = $model->status;

                // Matriz de transiciones válidas
                $allowed = [
                    self::STATUS_DRAFT => [self::STATUS_CONFIRMED, self::STATUS_CANCELLED],
                    self::STATUS_CONFIRMED => [self::STATUS_IN_PRODUCTION, self::STATUS_CANCELLED],
                    self::STATUS_IN_PRODUCTION => [self::STATUS_READY, self::STATUS_CANCELLED],
                    self::STATUS_READY => [self::STATUS_DELIVERED, self::STATUS_CANCELLED],
                    self::STATUS_DELIVERED => [],
                    self::STATUS_CANCELLED => [],
                ];

                if (!in_array($newStatus, $allowed[$oldStatus] ?? [])) {
                    throw new \Exception(
                        "Transición de estado no permitida: {$oldStatus} → {$newStatus}. " .
                        "Use los métodos de OrderService para transiciones seguras."
                    );
                }
            }

            // ================================================================
            // v2.5: INMUTABILIDAD FINANCIERA (CON EXCEPCIÓN DRAFT)
            // REGLA ERP: En DRAFT se permite cambiar IVA aunque haya anticipo.
            // El bloqueo SOLO aplica a estados post-confirmación.
            // ================================================================
            $originalAmountPaid = (float) $model->getOriginal('amount_paid');
            $currentStatus = $model->status;

            // EXCEPCIÓN DRAFT: En borrador, los campos financieros SON editables
            // aunque haya anticipo. El sistema recalcula saldo automáticamente.
            $isDraft = $currentStatus === self::STATUS_DRAFT;

            if ($originalAmountPaid > 0 && !$isDraft) {
                // Verificar si se está intentando modificar campos financieros protegidos
                $protectedChanges = [];

                foreach (self::IMMUTABLE_FINANCIAL_FIELDS as $field) {
                    if ($model->isDirty($field)) {
                        $oldValue = $model->getOriginal($field);
                        $newValue = $model->$field;

                        // Tolerancia para decimales (evitar falsos positivos por redondeo)
                        if (abs((float) $oldValue - (float) $newValue) > 0.01) {
                            $protectedChanges[] = $field;
                        }
                    }
                }

                if (!empty($protectedChanges)) {
                    $fieldList = implode(', ', $protectedChanges);
                    throw new \Exception(
                        "VIOLACIÓN CONTABLE: No se pueden modificar campos financieros " .
                        "en pedidos confirmados cuando hay pagos registrados (\${$originalAmountPaid}). " .
                        "Campos bloqueados: {$fieldList}. " .
                        "Estado actual: {$currentStatus}. " .
                        "Para modificar el total, primero debe revertir todos los pagos o usar nota de crédito."
                    );
                }
            }

            // ================================================================
            // PASO 10: CONGELACIÓN DE promised_date POST-PRODUCCIÓN
            // REGLA ERP: promised_date es INMUTABLE desde IN_PRODUCTION.
            // En CONFIRMED se permite SOLO via OrderService::reschedulePromisedDate()
            // que valida capacidad y registra auditoría.
            // Este guard protege contra bypass directo vía Eloquent.
            // ================================================================
            if ($model->isDirty('promised_date') && $model->isInProduction()) {
                throw new \Exception(
                    "CONGELACIÓN DE AGENDA: No se puede modificar promised_date en estado '{$model->getOriginal('status')}'. " .
                    "La fecha queda congelada desde IN_PRODUCTION."
                );
            }

            // ================================================================
            // v2.5: CIERRE CONTABLE ABSOLUTO
            // Un pedido financieramente cerrado es 100% inmutable
            // ================================================================
            $wasFinanciallyClosed = $model->getOriginal('status') === self::STATUS_DELIVERED
                && $model->getOriginal('payment_status') === self::PAYMENT_PAID;

            if ($wasFinanciallyClosed) {
                // Solo permitir cambios cosméticos (notes) o auditoría (updated_by)
                $allowedChanges = ['notes', 'updated_by', 'updated_at'];
                $actualChanges = array_keys($model->getDirty());
                $blockedChanges = array_diff($actualChanges, $allowedChanges);

                if (!empty($blockedChanges)) {
                    throw new \Exception(
                        "CIERRE CONTABLE: El pedido {$model->order_number} está cerrado " .
                        "(ENTREGADO + PAGADO). Ningún campo financiero u operativo puede modificarse. " .
                        "Cambios bloqueados: " . implode(', ', $blockedChanges)
                    );
                }
            }
        });
    }

    // === GENERADOR DE NÚMERO DE PEDIDO ===
    public static function generateOrderNumber(): string
    {
        $year = date('Y');
        $prefix = "PED-{$year}-";

        $lastOrder = self::withTrashed()
            ->where('order_number', 'like', "{$prefix}%")
            ->orderByRaw('CAST(SUBSTRING(order_number, -4) AS UNSIGNED) DESC')
            ->first();

        if ($lastOrder) {
            $lastNumber = (int) substr($lastOrder->order_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    // === RELACIONES ===

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function measurement(): BelongsTo
    {
        return $this->belongsTo(ClientMeasurement::class, 'client_measurement_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(OrderPayment::class, 'order_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * CIERRE POS: Usuario que canceló el pedido.
     */
    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function parentOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_parent_id');
    }

    public function annexOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'order_parent_id');
    }

    /**
     * Relación: Pedido original relacionado (POST-VENTA).
     * Se usa cuando un cliente solicita algo adicional DESPUÉS de que
     * su pedido original fue entregado (READY/DELIVERED).
     *
     * DIFERENCIA con parentOrder:
     * - parentOrder = anexo (pedido subordinado, antes de producción)
     * - relatedOrder = post-venta (pedido independiente, referencia informativa)
     */
    public function relatedOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'related_order_id');
    }

    /**
     * Relación inversa: Pedidos de post-venta derivados de este pedido.
     */
    public function postSaleOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'related_order_id');
    }

    /**
     * Verifica si este pedido es de post-venta (relacionado con otro cerrado).
     */
    public function isPostSale(): bool
    {
        return $this->related_order_id !== null;
    }

    /**
     * Verifica si el pedido puede generar un pedido post-venta.
     * REGLAS:
     * - Solo pedidos READY o DELIVERED
     * - NO aplica para STOCK_PRODUCTION (no tienen cliente)
     */
    public function canHavePostSale(): bool
    {
        // Stock production no tiene cliente, no puede tener post-venta
        if ($this->isStockProduction()) {
            return false;
        }

        return in_array($this->status, [self::STATUS_READY, self::STATUS_DELIVERED]);
    }

    public function events(): HasMany
    {
        return $this->hasMany(OrderEvent::class, 'order_id')->orderBy('created_at', 'desc');
    }

    public function confirmedEvent(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(OrderEvent::class, 'order_id')
            ->where('event_type', OrderEvent::TYPE_CONFIRMED)
            ->oldest();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(OrderMessage::class, 'order_id')->orderBy('created_at', 'desc');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(InventoryReservation::class, 'order_id');
    }

    /**
     * Reservas de stock de PRODUCTOS TERMINADOS v2.2.
     */
    public function stockReservations(): HasMany
    {
        return $this->hasMany(ProductVariantReservation::class, 'order_id');
    }

    /**
     * Reservas de stock activas (status = 'reserved').
     */
    public function activeStockReservations(): HasMany
    {
        return $this->stockReservations()->where('status', ProductVariantReservation::STATUS_RESERVED);
    }

    /**
     * Relación con el archivo de producción (DesignExport) vinculado.
     * Solo se permite vincular exports con status 'aprobado'.
     */
    public function designExport(): BelongsTo
    {
        return $this->belongsTo(DesignExport::class, 'design_export_id');
    }

    /**
     * v2.6-MIN: Relación 1:1 con Invoice (factura).
     * REGLA: Un pedido puede tener máximo UNA factura.
     */
    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class, 'order_id');
    }

    // === SCOPES ===

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePaymentStatus($query, string $status)
    {
        return $query->where('payment_status', $status);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_DRAFT, self::STATUS_CONFIRMED]);
    }

    /**
     * PASO 12: Scope canónico para pedidos retrasados.
     * Fuente única del predicado "overdue" (promised_date < hoy, no terminal).
     */
    public function scopeOverdue($query)
    {
        return $query->whereNotIn('status', [self::STATUS_DELIVERED, self::STATUS_CANCELLED])
            ->whereNotNull('promised_date')
            ->whereDate('promised_date', '<', today());
    }

    // === MÉTODOS DE NEGOCIO ===

    // Tasa de IVA por defecto (fallback si no hay en settings)
    public const IVA_RATE_DEFAULT = 16;

    /**
     * Obtiene la tasa de IVA vigente desde configuración del sistema.
     * @return float Tasa como porcentaje (ej: 16)
     */
    public static function getDefaultTaxRate(): float
    {
        return (float) SystemSetting::getValue('default_tax_rate', self::IVA_RATE_DEFAULT);
    }

    public function recalculateTotals(): void
    {
        // Subtotal base de items (sin ajustes)
        $this->subtotal = $this->items()->sum('subtotal');

        // Sumar costos extras de ajustes aprobados
        $approvedExtras = $this->calculateApprovedExtras();

        // Calcular IVA si requiere factura (sobre subtotal + extras - descuento)
        // SNAPSHOT: iva_rate se guarda SOLO cuando requires_invoice = true
        $baseForIva = $this->subtotal + $approvedExtras - $this->discount;
        if ($this->requires_invoice) {
            // Usar iva_rate snapshot si ya existe, si no obtener de settings y guardar
            if ($this->iva_rate === null || $this->iva_rate == 0) {
                $this->iva_rate = self::getDefaultTaxRate(); // Snapshot desde settings
            }
            // Calcular usando el snapshot (iva_rate=16 → 0.16)
            $this->iva_amount = $baseForIva * ($this->iva_rate / 100);
            $this->total_with_tax = $this->subtotal + $approvedExtras - $this->discount + $this->iva_amount;
        } else {
            // Sin factura = sin IVA, no guardar snapshot
            $this->iva_amount = 0;
            $this->total_with_tax = $this->subtotal + $approvedExtras - $this->discount;
        }

        // Total = subtotal + extras aprobados - descuento + IVA
        $this->total = $this->subtotal + $approvedExtras - $this->discount + $this->iva_amount;
        $this->amount_paid = $this->payments()->sum('amount');
        $this->balance = $this->total - $this->amount_paid;

        // Actualizar estado de pago
        if ($this->amount_paid <= 0) {
            $this->payment_status = self::PAYMENT_PENDING;
        } elseif ($this->amount_paid >= $this->total) {
            $this->payment_status = self::PAYMENT_PAID;
        } else {
            $this->payment_status = self::PAYMENT_PARTIAL;
        }

        // Recalcular fechas (minimum_date basado en items)
        $this->recalculateDates();

        $this->saveQuietly();
    }

    /**
     * Calcula el total de costos extras de ajustes aprobados.
     */
    public function calculateApprovedExtras(): float
    {
        return (float) $this->items()
            ->join('order_item_adjustments', 'order_items.id', '=', 'order_item_adjustments.order_item_id')
            ->where('order_item_adjustments.status', 'approved')
            ->sum('order_item_adjustments.real_cost');
    }

    /**
     * Verifica si el pedido tiene ajustes pendientes de aprobación.
     */
    public function hasPendingAdjustments(): bool
    {
        return $this->items()->where('has_pending_adjustments', true)->exists();
    }

    /**
     * Recalcula minimum_date basado en los items del pedido.
     * BACKEND es la ÚNICA fuente de verdad para fechas.
     */
    public function recalculateDates(): void
    {
        $this->minimum_date = $this->calculateMinimumDate();
    }

    // Verificar si el pedido es editable (draft o confirmed)
    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_CONFIRMED]);
    }

    // Verificar si está en producción o posterior
    public function isInProduction(): bool
    {
        return in_array($this->status, [
            self::STATUS_IN_PRODUCTION,
            self::STATUS_READY,
            self::STATUS_DELIVERED,
        ]);
    }

    /**
     * FASE v2.3: Verifica si el pedido está ENTREGADO (estado terminal).
     * REGLA ERP: Un pedido DELIVERED es INMUTABLE y NO puede:
     * - Volver a producción
     * - Modificar inventario
     * - Cambiar a ningún otro estado
     *
     * @return bool TRUE si el pedido está entregado
     */
    public function isDelivered(): bool
    {
        return $this->status === self::STATUS_DELIVERED;
    }

    /**
     * FASE v2.3: Verifica si el pedido está en estado TERMINAL (cerrado).
     * Estados terminales: DELIVERED, CANCELLED
     * REGLA: Estados terminales NO permiten ninguna transición de estado.
     *
     * @return bool TRUE si el pedido está en estado terminal
     */
    public function isTerminal(): bool
    {
        return in_array($this->status, [
            self::STATUS_DELIVERED,
            self::STATUS_CANCELLED,
        ]);
    }

    /**
     * CANÓNICO: Verifica si el pedido es "Producción para stock".
     * REGLA DE NEGOCIO:
     * - Pedido SIN cliente (cliente_id = null)
     * - Se produce para inventario, NO para un cliente específico
     *
     * @return bool TRUE si es producción para stock
     */
    public function isStockProduction(): bool
    {
        return $this->cliente_id === null;
    }

    /**
     * FASE v2.3: Verifica si el pedido puede ser entregado.
     * REGLAS:
     * - Solo pedidos READY pueden entregarse
     * - Pedidos ya entregados NO pueden re-entregarse
     *
     * @return bool TRUE si el pedido puede transicionar a DELIVERED
     */
    public function canBeDelivered(): bool
    {
        return $this->status === self::STATUS_READY;
    }

    /**
     * Verifica si el pedido está en un estado que BLOQUEA mutaciones críticas.
     * Estados bloqueados: in_production, ready, delivered
     *
     * Campos INMUTABLES post-producción:
     * - items (no se pueden agregar/eliminar/modificar)
     * - subtotal, discount, iva_amount, total (calculados)
     * - requires_invoice (afecta IVA)
     * - cliente_id (afecta facturación)
     *
     * Campos MUTABLES post-producción:
     * - payment_status, amount_paid, balance (pagos siguen)
     * - status (solo transiciones válidas)
     * - notes (informativo)
     * - delivered_date (al entregar)
     */
    public function isMutationBlocked(): bool
    {
        return $this->isInProduction();
    }

    /**
     * Lista de campos que NO pueden modificarse post-producción.
     */
    public const IMMUTABLE_FIELDS_POST_PRODUCTION = [
        'cliente_id',
        'client_measurement_id',
        'urgency_level',
        'subtotal',
        'discount',
        'requires_invoice',
        'iva_amount',
        'total',
        'materials_cost_snapshot',
        'total_stitches_snapshot',
        'embroidery_cost_snapshot',
        'cost_per_thousand_snapshot',
        'services_cost_snapshot',
        'promised_date',
        'minimum_date',
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
                "Campos protegidos: {$fieldList}. " .
                "Para modificar, primero revierta a estado 'confirmado' (requiere autorización)."
            );
        }
    }

    /**
     * Verifica si se pueden agregar/eliminar items.
     * REGLA: Post-producción NO se pueden modificar items directamente.
     *        Se debe crear un pedido anexo.
     */
    public function canModifyItems(): bool
    {
        return !$this->isMutationBlocked();
    }

    /**
     * Obtiene el motivo del bloqueo de mutación.
     */
    public function getMutationBlockReason(): ?string
    {
        if (!$this->isMutationBlocked()) {
            return null;
        }

        return match($this->status) {
            self::STATUS_IN_PRODUCTION => 'El pedido está en producción. Los materiales ya fueron reservados.',
            self::STATUS_READY => 'El pedido está listo para entrega. La producción ha finalizado.',
            self::STATUS_DELIVERED => 'El pedido ya fue entregado. Es un registro histórico.',
            default => 'El pedido está en un estado que no permite modificaciones.',
        };
    }

    // Verificar si es pedido anexo
    public function isAnnex(): bool
    {
        return $this->order_parent_id !== null;
    }

    /**
     * Calcular fecha mínima según productos, personalización y urgencia.
     * FÓRMULA: MAX(item.estimated_lead_time) × urgency_multiplier
     * donde item.estimated_lead_time = product.lead_time × personalization_multiplier
     */
    public function calculateMinimumDate(): ?\Carbon\Carbon
    {
        // Obtener el máximo tiempo estimado de los items
        // Cada item ya tiene su estimated_lead_time calculado con su multiplicador
        $maxLeadTime = $this->items()->max('estimated_lead_time');

        // Si no hay items o no tienen estimated_lead_time, usar cálculo legacy
        if (!$maxLeadTime) {
            $maxLeadTime = $this->items()
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->max('products.production_lead_time') ?? 0;
        }

        // Aplicar multiplicador de urgencia
        $urgencyMultiplier = self::URGENCY_MULTIPLIERS[$this->urgency_level ?? self::URGENCY_NORMAL];
        $adjustedDays = (int) ceil($maxLeadTime * $urgencyMultiplier);

        return now()->addDays($adjustedDays);
    }

    /**
     * Verifica si hay items con diseño pendiente de aprobación.
     */
    public function hasItemsPendingDesignApproval(): bool
    {
        return $this->items()
            ->where('personalization_type', OrderItem::PERSONALIZATION_DESIGN)
            ->where('design_approved', false)
            ->exists();
    }

    /**
     * Obtiene items que bloquean producción por diseño no aprobado.
     */
    public function getItemsBlockingForDesign(): \Illuminate\Support\Collection
    {
        return $this->items()
            ->where('personalization_type', OrderItem::PERSONALIZATION_DESIGN)
            ->where('design_approved', false)
            ->get();
    }

    // === ACCESSORS ===

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'Borrador',
            self::STATUS_CONFIRMED => 'Confirmado',
            self::STATUS_IN_PRODUCTION => 'En Producción',
            self::STATUS_READY => 'Listo',
            self::STATUS_DELIVERED => 'Entregado',
            self::STATUS_CANCELLED => 'Cancelado',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'secondary',
            self::STATUS_CONFIRMED => 'info',
            self::STATUS_IN_PRODUCTION => 'warning',
            self::STATUS_READY => 'success',
            self::STATUS_DELIVERED => 'primary',
            self::STATUS_CANCELLED => 'danger',
            default => 'secondary',
        };
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return match($this->payment_status) {
            self::PAYMENT_PENDING => 'Pendiente',
            self::PAYMENT_PARTIAL => 'Parcial',
            self::PAYMENT_PAID => 'Pagado',
            default => $this->payment_status,
        };
    }

    public function getPaymentStatusColorAttribute(): string
    {
        return match($this->payment_status) {
            self::PAYMENT_PENDING => 'danger',
            self::PAYMENT_PARTIAL => 'warning',
            self::PAYMENT_PAID => 'success',
            default => 'secondary',
        };
    }

    public function getUrgencyLabelAttribute(): string
    {
        return match($this->urgency_level) {
            self::URGENCY_NORMAL => 'Normal',
            self::URGENCY_URGENTE => 'Urgente',
            self::URGENCY_EXPRESS => 'Express',
            default => 'Normal',
        };
    }

    public function getUrgencyColorAttribute(): string
    {
        return match($this->urgency_level) {
            self::URGENCY_NORMAL => 'secondary',
            self::URGENCY_URGENTE => 'warning',
            self::URGENCY_EXPRESS => 'danger',
            default => 'secondary',
        };
    }

    // =========================================================================
    // === FASE 3: COSTO BASE REAL DE FABRICACIÓN ===
    // =========================================================================

    /**
     * FASE 3: Costo base real de fabricación (materiales BOM + extras).
     *
     * FUENTE CANÓNICA: materials_cost_snapshot
     * - Calculado UNA VEZ al iniciar producción (CONFIRMED → IN_PRODUCTION)
     * - Usa average_cost vigente de cada MaterialVariant al momento del cálculo
     * - INMUTABLE post-producción (protegido en IMMUTABLE_FIELDS_POST_PRODUCTION)
     *
     * COMPOSICIÓN:
     * - Σ (product_materials.quantity × material_variant.average_cost) por item
     * - Σ (product_extra_materials.quantity_required × material_variant.average_cost) por extra con inventario
     *
     * NO INCLUYE (por diseño):
     * - Costos de bordado/puntadas
     * - Mano de obra
     * - Márgenes comerciales
     * - IVA
     *
     * @return float|null Costo en MXN o null si no calculado aún
     */
    public function getManufacturingCostAttribute(): ?float
    {
        return $this->materials_cost_snapshot !== null
            ? (float) $this->materials_cost_snapshot
            : null;
    }

    /**
     * Indica si el costo de fabricación está disponible.
     * REGLA: Solo disponible después de iniciar producción.
     */
    public function getHasManufacturingCostAttribute(): bool
    {
        return $this->materials_cost_snapshot !== null;
    }

    /**
     * Costo de fabricación formateado para UI.
     */
    public function getFormattedManufacturingCostAttribute(): string
    {
        if ($this->materials_cost_snapshot === null) {
            return 'No calculado';
        }
        return '$' . number_format($this->materials_cost_snapshot, 2);
    }

    // =========================================================================
    // === FASE 4: PRECIO DE VENTA ASISTIDO (MARGEN ESTIMADO) ===
    // =========================================================================

    /**
     * FASE 4: Margen bruto estimado = precio de venta - costo de materiales.
     *
     * IMPORTANTE: Este es un ESTIMADO basado únicamente en costo de materiales.
     * NO incluye: mano de obra, tiempo de máquina, costos fijos, etc.
     *
     * REGLAS:
     * - Solo disponible cuando existe materials_cost_snapshot (post-producción)
     * - Se calcula en runtime, NO se persiste
     * - Dato interno, NO visible al cliente
     *
     * @return float|null Margen en MXN o null si no calculado
     */
    public function getEstimatedMarginAttribute(): ?float
    {
        if ($this->materials_cost_snapshot === null) {
            return null;
        }
        return (float) $this->total - (float) $this->materials_cost_snapshot;
    }

    /**
     * FASE 4: Margen bruto como porcentaje del precio de venta.
     *
     * FÓRMULA: ((precio - costo) / precio) × 100
     *
     * INTERPRETACIÓN:
     * - 100% = costo cero (imposible en práctica)
     * - 50% = el costo es la mitad del precio
     * - 0% = precio = costo (sin ganancia)
     * - Negativo = pérdida (precio < costo)
     *
     * @return float|null Porcentaje de margen o null si no calculable
     */
    public function getEstimatedMarginPercentAttribute(): ?float
    {
        if ($this->total <= 0 || $this->materials_cost_snapshot === null) {
            return null;
        }
        return (($this->total - $this->materials_cost_snapshot) / $this->total) * 100;
    }

    /**
     * FASE 4: Indica si el pedido está en PÉRDIDA (precio < costo materiales).
     *
     * ALERTA CRÍTICA: Si retorna true, el operador debe revisar el precio.
     *
     * @return bool TRUE si el margen es negativo
     */
    public function getIsAtLossAttribute(): bool
    {
        $margin = $this->estimated_margin;
        return $margin !== null && $margin < 0;
    }

    /**
     * FASE 4: Nivel de alerta visual según el margen.
     *
     * UMBRALES (configurables a futuro):
     * - danger: margen < 0% (pérdida)
     * - warning: margen < 20% (bajo)
     * - success: margen >= 20% (saludable)
     * - secondary: sin datos
     *
     * @return string Clase Bootstrap para badge/alert
     */
    public function getMarginAlertLevelAttribute(): string
    {
        $pct = $this->estimated_margin_percent;

        if ($pct === null) {
            return 'secondary';
        }

        if ($pct < 0) {
            return 'danger';
        }

        if ($pct < 20) {
            return 'warning';
        }

        return 'success';
    }

    /**
     * FASE 4: Etiqueta descriptiva del nivel de margen.
     *
     * @return string Texto descriptivo para UI
     */
    public function getMarginLevelLabelAttribute(): string
    {
        $pct = $this->estimated_margin_percent;

        if ($pct === null) {
            return 'Sin datos';
        }

        if ($pct < 0) {
            return 'Pérdida';
        }

        if ($pct < 20) {
            return 'Bajo';
        }

        if ($pct < 40) {
            return 'Moderado';
        }

        return 'Saludable';
    }

    /**
     * FASE 4: Margen formateado para UI.
     */
    public function getFormattedEstimatedMarginAttribute(): string
    {
        $margin = $this->estimated_margin;

        if ($margin === null) {
            return 'No calculado';
        }

        $prefix = $margin >= 0 ? '' : '-';
        return $prefix . '$' . number_format(abs($margin), 2);
    }

    // =========================================================================
    // === FASE 3.5: COSTO REAL DE BORDADO (PUNTADAS) ===
    // =========================================================================

    /**
     * FASE 3.5: Calcula el total de puntadas del pedido.
     *
     * FUENTES DE PUNTADAS (en orden de prioridad):
     * 1. DesignExports vinculados al item (personalización)
     * 2. Diseños estándar del producto (Product->designs->stitch_count)
     *
     * FÓRMULA:
     * total_stitches = Σ (puntadas_item × item.quantity)
     *
     * @return int Total de puntadas
     */
    public function calculateTotalStitches(): int
    {
        $totalStitches = 0;

        foreach ($this->items as $item) {
            $itemStitches = 0;

            // PRIORIDAD 1: DesignExports vinculados (personalización)
            if ($item->requiresTechnicalDesigns() && $item->designExports->isNotEmpty()) {
                foreach ($item->designExports as $designExport) {
                    $itemStitches += $designExport->stitches_count ?? 0;
                }
            }
            // PRIORIDAD 2: Diseños estándar del producto
            elseif ($item->product && $item->product->designs->isNotEmpty()) {
                $itemStitches = $item->product->total_stitches ?? 0;
            }

            $totalStitches += $itemStitches * $item->quantity;
        }

        return $totalStitches;
    }

    /**
     * FASE 3.5: Calcula el costo de bordado basado en diseños y precios ajustados.
     *
     * PRIORIDAD DE CÁLCULO:
     * 1. Si el item tiene diseños vinculados: suma (millar × rate_ajustado × qty) por diseño
     * 2. Si no hay diseños: usa Product.embroidery_cost × qty
     *
     * FUENTE DEL RATE:
     * - pivot.rate_per_thousand_adjusted si existe (ajustado en pre-producción)
     * - Product.embroidery_rate_per_thousand como fallback
     *
     * @return float Costo de bordado en MXN
     */
    public function calculateEmbroideryCost(): float
    {
        $totalCost = 0.0;

        foreach ($this->items as $item) {
            $itemCost = $this->calculateItemEmbroideryCost($item);
            $totalCost += $itemCost;
        }

        return round($totalCost, 4);
    }

    /**
     * Calcula el costo de bordado para un item específico.
     *
     * @param OrderItem $item
     * @return float Costo de bordado del item
     */
    protected function calculateItemEmbroideryCost(OrderItem $item): float
    {
        // Cargar diseños si no están cargados
        $designExports = $item->relationLoaded('designExports')
            ? $item->designExports
            : $item->designExports()->get();

        // Si tiene diseños vinculados, calcular por diseño
        if ($designExports->isNotEmpty()) {
            $itemCost = 0.0;
            $baseRate = (float) ($item->product->embroidery_rate_per_thousand ?? 0);

            foreach ($designExports as $design) {
                $stitches = (int) ($design->stitches_count ?? 0);
                $millar = $stitches / 1000;

                // Usar rate ajustado si existe, si no usar el del producto
                $rate = $design->pivot->rate_per_thousand_adjusted !== null
                    ? (float) $design->pivot->rate_per_thousand_adjusted
                    : $baseRate;

                $itemCost += $millar * $rate * $item->quantity;
            }

            return $itemCost;
        }

        // Fallback: usar embroidery_cost del producto
        if ($item->product && $item->product->embroidery_cost > 0) {
            return (float) $item->product->embroidery_cost * $item->quantity;
        }

        return 0.0;
    }

    /**
     * FASE 3.5: Obtiene el costo por millar de puntadas desde configuración.
     *
     * @return float Costo por millar en MXN (default: 1.50)
     */
    public function getEmbroideryCostPerThousand(): float
    {
        $value = SystemSetting::getValue('embroidery_cost_per_thousand', '1.50');
        return (float) $value;
    }

    /**
     * FASE 3.5: Costo de bordado persistido (snapshot).
     * FUENTE CANÓNICA: embroidery_cost_snapshot
     * FALLBACK: Si snapshot es 0/null y pedido en producción, recalcula en tiempo real.
     *
     * @return float|null Costo en MXN o null si no calculado
     */
    public function getEmbroideryCostAttribute(): ?float
    {
        // Si hay snapshot válido, usarlo
        if ($this->embroidery_cost_snapshot !== null && $this->embroidery_cost_snapshot > 0) {
            return (float) $this->embroidery_cost_snapshot;
        }

        // FALLBACK: Recalcular si el pedido está en producción o posterior
        if (in_array($this->status, [self::STATUS_IN_PRODUCTION, self::STATUS_READY, self::STATUS_DELIVERED])) {
            return $this->calculateEmbroideryCost();
        }

        return $this->embroidery_cost_snapshot !== null
            ? (float) $this->embroidery_cost_snapshot
            : null;
    }

    /**
     * FASE 3.5: Indica si el costo de bordado está disponible.
     */
    public function getHasEmbroideryCostAttribute(): bool
    {
        return $this->embroidery_cost !== null;
    }

    /**
     * FASE 3.5: Total de puntadas persistido (snapshot).
     * FALLBACK: Si snapshot es 0/null y pedido en producción, recalcula en tiempo real.
     */
    public function getTotalStitchesAttribute(): ?int
    {
        // Si hay snapshot válido, usarlo
        if ($this->total_stitches_snapshot !== null && $this->total_stitches_snapshot > 0) {
            return $this->total_stitches_snapshot;
        }

        // FALLBACK: Recalcular si el pedido está en producción o posterior
        if (in_array($this->status, [self::STATUS_IN_PRODUCTION, self::STATUS_READY, self::STATUS_DELIVERED])) {
            return $this->calculateTotalStitches();
        }

        return $this->total_stitches_snapshot;
    }

    /**
     * FASE 3.5: Puntadas formateadas para UI (con separador de miles).
     */
    public function getFormattedTotalStitchesAttribute(): string
    {
        $stitches = $this->total_stitches;

        if ($stitches === null || $stitches === 0) {
            return '0';
        }
        return number_format($stitches);
    }

    /**
     * FASE 3.5: Costo de bordado formateado para UI.
     */
    public function getFormattedEmbroideryCostAttribute(): string
    {
        $cost = $this->embroidery_cost;

        if ($cost === null) {
            return 'No calculado';
        }
        return '$' . number_format($cost, 2);
    }

    /**
     * FASE 3.5: Costo TOTAL de fabricación (materiales + bordado + servicios).
     * Esta es la fuente canónica para calcular margen real.
     *
     * FÓRMULA:
     * total_manufacturing_cost = materials_cost_snapshot + embroidery_cost_snapshot + services_cost_snapshot
     *
     * NOTA: materials_cost_snapshot YA incluye ajustes BOM + extras con inventario
     *
     * @return float|null Costo total en MXN o null si no calculado
     */
    public function getTotalManufacturingCostAttribute(): ?float
    {
        // Solo disponible si el snapshot de materiales existe
        if ($this->materials_cost_snapshot === null) {
            return null;
        }

        $materialsCost = (float) $this->materials_cost_snapshot;
        // Usar el accessor embroidery_cost que tiene fallback
        $embroideryCost = (float) ($this->embroidery_cost ?? 0);
        // Agregar costo de servicios (extras sin inventario)
        $servicesCost = (float) ($this->services_cost_snapshot ?? 0);

        return $materialsCost + $embroideryCost + $servicesCost;
    }

    /**
     * Costo de servicios (extras sin inventario) formateado.
     */
    public function getFormattedServicesCostAttribute(): string
    {
        if ($this->services_cost_snapshot === null) {
            return 'Sin calcular';
        }
        return '$' . number_format($this->services_cost_snapshot, 2);
    }

    /**
     * Costo de servicios como float.
     */
    public function getServicesCostAttribute(): ?float
    {
        return $this->services_cost_snapshot !== null
            ? (float) $this->services_cost_snapshot
            : null;
    }

    /**
     * FASE 3.5: Indica si el costo total de fabricación está disponible.
     */
    public function getHasTotalManufacturingCostAttribute(): bool
    {
        return $this->materials_cost_snapshot !== null;
    }

    /**
     * FASE 3.5: Costo total de fabricación formateado para UI.
     */
    public function getFormattedTotalManufacturingCostAttribute(): string
    {
        $cost = $this->total_manufacturing_cost;

        if ($cost === null) {
            return 'No calculado';
        }
        return '$' . number_format($cost, 2);
    }

    /**
     * FASE 3.5: Margen REAL considerando materiales + bordado.
     * Actualiza el cálculo de margen para incluir costo de bordado.
     *
     * FÓRMULA:
     * margen_real = precio_venta - (costo_materiales + costo_bordado)
     *
     * @return float|null Margen en MXN o null si no calculable
     */
    public function getRealMarginAttribute(): ?float
    {
        $totalCost = $this->total_manufacturing_cost;

        if ($totalCost === null) {
            return null;
        }

        return (float) $this->total - $totalCost;
    }

    /**
     * FASE 3.5: Margen real como porcentaje del precio de venta.
     */
    public function getRealMarginPercentAttribute(): ?float
    {
        if ($this->total <= 0 || $this->total_manufacturing_cost === null) {
            return null;
        }

        return (($this->total - $this->total_manufacturing_cost) / $this->total) * 100;
    }

    /**
     * FASE 3.5: Indica si el pedido está en PÉRDIDA considerando bordado.
     */
    public function getIsAtRealLossAttribute(): bool
    {
        $margin = $this->real_margin;
        return $margin !== null && $margin < 0;
    }

    /**
     * FASE 3.5: Nivel de alerta de margen considerando costo total.
     */
    public function getRealMarginAlertLevelAttribute(): string
    {
        $pct = $this->real_margin_percent;

        if ($pct === null) {
            return 'secondary';
        }

        if ($pct < 0) {
            return 'danger';
        }

        if ($pct < 20) {
            return 'warning';
        }

        return 'success';
    }

    /**
     * FASE 3.5: Etiqueta descriptiva del margen real.
     */
    public function getRealMarginLabelAttribute(): string
    {
        $pct = $this->real_margin_percent;

        if ($pct === null) {
            return 'Sin datos';
        }

        if ($pct < 0) {
            return 'Pérdida';
        }

        if ($pct < 20) {
            return 'Bajo';
        }

        if ($pct < 40) {
            return 'Moderado';
        }

        return 'Saludable';
    }

    /**
     * FASE 3.5: Margen real formateado para UI.
     */
    public function getFormattedRealMarginAttribute(): string
    {
        $margin = $this->real_margin;

        if ($margin === null) {
            return 'No calculado';
        }

        $prefix = $margin >= 0 ? '' : '-';
        return $prefix . '$' . number_format(abs($margin), 2);
    }

    // =========================================================================
    // === FASE v2.5: CIERRE CONTABLE Y GATES FINANCIEROS ===
    // =========================================================================

    /**
     * GATE v2.5: Verifica si los campos financieros pueden editarse.
     *
     * REGLA ERP CORREGIDA:
     * - En DRAFT: SIEMPRE se puede editar (IVA, descuento, etc.)
     *   El saldo se recalcula automáticamente: total - amount_paid
     * - En estados != DRAFT con amount_paid > 0: BLOQUEADO
     * - Si isFinanciallyClosed() → NADA financiero puede cambiar
     *
     * Campos protegidos (post-confirmación con pagos):
     * - subtotal, discount, requires_invoice, iva_amount, total
     *
     * @return bool TRUE si se pueden editar campos financieros
     */
    public function canEditFinancials(): bool
    {
        // EXCEPCIÓN DRAFT: En borrador SIEMPRE se puede editar
        // aunque haya anticipo. El saldo se recalcula.
        if ($this->status === self::STATUS_DRAFT) {
            return true;
        }

        // R1: Post-confirmación con pagos → bloqueado
        if ($this->amount_paid > 0) {
            return false;
        }

        // R2: Si está financieramente cerrado, nada se puede editar
        if ($this->isFinanciallyClosed()) {
            return false;
        }

        return true;
    }

    /**
     * GATE v2.5: Verifica si el pedido está CERRADO CONTABLEMENTE.
     *
     * DEFINICIÓN CANÓNICA:
     * Un pedido está financieramente cerrado cuando:
     * - Estado = DELIVERED (entregado al cliente)
     * - payment_status = PAID (totalmente pagado)
     *
     * CONSECUENCIAS del cierre contable:
     * - total INMUTABLE
     * - amount_paid INMUTABLE (no más pagos, no eliminaciones)
     * - balance = 0 (invariante)
     * - Registro histórico DEFINITIVO
     *
     * @return bool TRUE si el pedido está cerrado contablemente
     */
    public function isFinanciallyClosed(): bool
    {
        return $this->status === self::STATUS_DELIVERED
            && $this->payment_status === self::PAYMENT_PAID;
    }

    /**
     * Obtiene el motivo por el cual no se pueden editar campos financieros.
     *
     * @return string|null NULL si se puede editar, mensaje explicativo si no
     */
    public function getFinancialEditBlockReason(): ?string
    {
        // EXCEPCIÓN DRAFT: En borrador siempre se puede editar
        if ($this->status === self::STATUS_DRAFT) {
            return null;
        }

        if ($this->isFinanciallyClosed()) {
            return 'El pedido está cerrado contablemente (ENTREGADO + PAGADO). ' .
                   'Los datos financieros son inmutables.';
        }

        if ($this->amount_paid > 0) {
            return 'El pedido tiene pagos registrados ($' .
                   number_format($this->amount_paid, 2) . ') y ya fue confirmado. ' .
                   'El total no puede modificarse para evitar descuadres contables. ' .
                   'Use nota de crédito para ajustes.';
        }

        return null;
    }

    /**
     * Lista de campos financieros inmutables cuando hay pagos.
     */
    public const IMMUTABLE_FINANCIAL_FIELDS = [
        'subtotal',
        'discount',
        'requires_invoice',
        'iva_amount',
        'total',
    ];

    // =========================================================================
    // === FASE v2.4: SISTEMA DE PAGOS Y BALANCE ===
    // =========================================================================

    /**
     * GATE v2.4: Verifica si el pedido puede recibir pagos.
     *
     * REGLAS CONTABLES SELLADAS:
     * - NO se aceptan pagos en pedidos CANCELLED
     * - Pedidos con balance <= 0 NO aceptan más pagos (ya pagados)
     * - Todos los demás estados SÍ aceptan pagos
     *
     * @return bool TRUE si el pedido puede recibir pagos
     */
    public function canReceivePayment(): bool
    {
        // R1: Pedidos cancelados NO reciben pagos
        if ($this->status === self::STATUS_CANCELLED) {
            return false;
        }

        // R2: Pedidos ya pagados completamente NO reciben más pagos
        if ($this->balance <= 0) {
            return false;
        }

        return true;
    }

    /**
     * Obtiene el motivo por el cual no se pueden recibir pagos.
     *
     * @return string|null NULL si puede recibir pagos, mensaje si no
     */
    public function getPaymentBlockReason(): ?string
    {
        if ($this->status === self::STATUS_CANCELLED) {
            return 'No se pueden registrar pagos en pedidos cancelados.';
        }

        if ($this->balance <= 0) {
            return 'El pedido ya está completamente pagado.';
        }

        return null;
    }

    /**
     * Calcula el monto máximo que se puede pagar (balance pendiente).
     *
     * @return float Monto máximo aceptable
     */
    public function getMaxPayableAmount(): float
    {
        return max(0, (float) $this->balance);
    }

    /**
     * Valida si un monto de pago es aceptable.
     *
     * REGLAS:
     * - Monto > 0
     * - Monto <= balance (no sobrepago)
     *
     * @param float $amount Monto a validar
     * @return array ['valid' => bool, 'error' => string|null]
     */
    public function validatePaymentAmount(float $amount): array
    {
        if ($amount <= 0) {
            return [
                'valid' => false,
                'error' => 'El monto del pago debe ser mayor a cero.',
            ];
        }

        $maxPayable = $this->getMaxPayableAmount();

        if ($amount > $maxPayable) {
            return [
                'valid' => false,
                'error' => "El monto excede el balance pendiente. Máximo aceptable: \${$maxPayable}",
            ];
        }

        return ['valid' => true, 'error' => null];
    }

    /**
     * Verifica si el pedido es PERSONALIZADO.
     * REGLA: Un pedido es personalizado si AL MENOS UN item tiene
     * personalization_type distinto de 'none'.
     *
     * Tipos de personalización:
     * - 'design': Diseño cliente (logo, imagen)
     * - 'text': Texto bordado (nombre, frase)
     * - 'measurements': Medidas a la medida
     * - 'none': Producto estándar sin personalización
     *
     * @return bool TRUE si el pedido tiene items personalizados
     */
    public function isCustomOrder(): bool
    {
        return $this->items()
            ->where('personalization_type', '!=', OrderItem::PERSONALIZATION_NONE)
            ->exists();
    }

    /**
     * Obtiene la etiqueta de tipo de pedido para la UI.
     */
    public function getOrderTypeLabelAttribute(): string
    {
        return $this->isCustomOrder() ? 'Personalizado' : 'Estándar';
    }

    /**
     * Obtiene el icono de tipo de pedido para la UI.
     */
    public function getOrderTypeIconAttribute(): string
    {
        return $this->isCustomOrder() ? 'fas fa-palette' : 'fas fa-box';
    }

    /**
     * FASE 2: Verifica si TODOS los items del pedido están READY.
     * FUENTE CANÓNICA para decisión de readiness a nivel de pedido.
     *
     * @return bool TRUE si todos los items pasan OrderItem::isReady()
     */
    public function allItemsReady(): bool
    {
        $items = $this->items;

        if ($items->isEmpty()) {
            return false; // Sin items = no ready
        }

        return $items->every(fn($item) => $item->isReady());
    }

    /**
     * FASE 2: Obtiene los items que están PENDING (no ready).
     *
     * @return \Illuminate\Support\Collection Items pendientes
     */
    public function getPendingItems(): \Illuminate\Support\Collection
    {
        return $this->items->filter(fn($item) => !$item->isReady());
    }

    /**
     * FASE 2: Obtiene resumen de pendientes agrupados por tipo.
     * Útil para mostrar en UI de forma estructurada.
     *
     * @return array ['measurements' => [...], 'design' => [...], 'technical' => [...]]
     */
    public function getPendingItemsSummary(): array
    {
        $pending = $this->getPendingItems();

        return [
            'measurements' => $pending->filter(fn($i) =>
                $i->requires_measurements && empty($i->measurements)
            )->values(),
            'design' => $pending->filter(fn($i) =>
                $i->blocksProductionForDesign()
            )->values(),
            'technical' => $pending->filter(fn($i) =>
                $i->blocksProductionForTechnicalDesigns()
            )->values(),
        ];
    }

    /**
     * Verifica si el pedido puede iniciar producción.
     * Considera ajustes, diseños pendientes y diseños técnicos vinculados.
     */
    public function canStartProduction(): bool
    {
        if ($this->status !== self::STATUS_CONFIRMED) {
            return false;
        }

        // R2: Bloqueo por ajustes pendientes
        if ($this->hasPendingAdjustments()) {
            return false;
        }

        // R3: Bloqueo por diseño no aprobado
        if ($this->hasItemsPendingDesignApproval()) {
            return false;
        }

        // R4: Verificar medidas cambiadas post-aprobación
        $hasChangedMeasurements = $this->items()
            ->where('design_approved', true)
            ->get()
            ->contains(fn($item) => $item->hasMeasurementsChangedAfterApproval());

        if ($hasChangedMeasurements) {
            return false;
        }

        // R5: Verificar que items personalizados tengan diseños técnicos vinculados
        if ($this->hasItemsMissingTechnicalDesigns()) {
            return false;
        }

        return true;
    }

    /**
     * Verifica si hay items personalizados sin diseños técnicos vinculados.
     * REGLA: Items con personalization_type = 'design' o 'text' DEBEN tener
     * al menos un DesignExport vinculado antes de iniciar producción.
     */
    public function hasItemsMissingTechnicalDesigns(): bool
    {
        return $this->items()
            ->whereIn('personalization_type', [
                OrderItem::PERSONALIZATION_DESIGN,
                OrderItem::PERSONALIZATION_TEXT,
            ])
            ->get()
            ->contains(fn($item) => !$item->hasTechnicalDesigns());
    }

    /**
     * Obtiene items que bloquean producción por falta de diseños técnicos.
     */
    public function getItemsMissingTechnicalDesigns(): \Illuminate\Support\Collection
    {
        return $this->items()
            ->whereIn('personalization_type', [
                OrderItem::PERSONALIZATION_DESIGN,
                OrderItem::PERSONALIZATION_TEXT,
            ])
            ->get()
            ->filter(fn($item) => !$item->hasTechnicalDesigns());
    }

    /**
     * Obtiene todos los bloqueos de producción detallados.
     */
    public function getProductionBlockers(): array
    {
        $blockers = [];

        if ($this->hasPendingAdjustments()) {
            $blockers[] = [
                'code' => 'R2',
                'type' => 'adjustments',
                'message' => 'Hay ajustes de precio pendientes de aprobación',
            ];
        }

        if ($this->hasItemsPendingDesignApproval()) {
            $items = $this->getItemsBlockingForDesign();
            $blockers[] = [
                'code' => 'R3',
                'type' => 'design_approval',
                'message' => 'Hay diseños pendientes de aprobación',
                'items' => $items->pluck('product_name')->toArray(),
            ];
        }

        $itemsWithChangedMeasurements = $this->items()
            ->where('design_approved', true)
            ->get()
            ->filter(fn($item) => $item->hasMeasurementsChangedAfterApproval());

        if ($itemsWithChangedMeasurements->isNotEmpty()) {
            $blockers[] = [
                'code' => 'R4',
                'type' => 'measurements_changed',
                'message' => 'Las medidas han cambiado después de aprobar el diseño',
                'items' => $itemsWithChangedMeasurements->pluck('product_name')->toArray(),
            ];
        }

        $itemsMissingDesigns = $this->getItemsMissingTechnicalDesigns();
        if ($itemsMissingDesigns->isNotEmpty()) {
            $blockers[] = [
                'code' => 'R5',
                'type' => 'missing_technical_designs',
                'message' => 'Hay productos personalizados sin diseño técnico vinculado',
                'items' => $itemsMissingDesigns->pluck('product_name')->toArray(),
            ];
        }

        // === BLOQUEO POR INVENTARIO (PERSISTIDO) ===
        if ($this->hasProductionInventoryBlock()) {
            $lastBlock = $this->getLastProductionBlockEvent();
            $blockers[] = [
                'code' => 'R5_INV',
                'type' => 'inventory_insufficient',
                'message' => $lastBlock?->message ?? 'Inventario insuficiente (intento previo fallido)',
                'blocked_at' => $lastBlock?->created_at?->toIso8601String(),
            ];
        }

        return $blockers;
    }

    // =========================================================================
    // === MÉTODOS DE LECTURA PARA BLOQUEO POR INVENTARIO (READ-ONLY) ===
    // =========================================================================

    /**
     * Verifica si existe un bloqueo de producción por inventario NO resuelto.
     * REGLA: Un bloqueo se considera resuelto si después de él existe un evento
     * 'production_started' (producción iniciada exitosamente).
     *
     * @return bool TRUE si hay bloqueo activo por inventario
     */
    public function hasProductionInventoryBlock(): bool
    {
        // Solo aplica a pedidos confirmados
        if ($this->status !== self::STATUS_CONFIRMED) {
            return false;
        }

        // Buscar el último evento de bloqueo por inventario
        $lastBlock = $this->getLastProductionBlockEvent();

        if (!$lastBlock) {
            return false;
        }

        // Verificar si después del bloqueo hubo un inicio de producción exitoso
        $productionStartedAfter = $this->events()
            ->where('event_type', OrderEvent::TYPE_PRODUCTION_STARTED)
            ->where('created_at', '>', $lastBlock->created_at)
            ->exists();

        // Si hubo producción iniciada después del bloqueo, el bloqueo está resuelto
        return !$productionStartedAfter;
    }

    /**
     * Obtiene el motivo del último bloqueo de producción por inventario.
     *
     * @return string|null Mensaje del bloqueo o NULL si no hay bloqueo activo
     */
    public function getLastProductionBlockReason(): ?string
    {
        if (!$this->hasProductionInventoryBlock()) {
            return null;
        }

        return $this->getLastProductionBlockEvent()?->message;
    }

    /**
     * Obtiene el último evento de bloqueo de producción por inventario.
     * MÉTODO INTERNO - Solo lee eventos, no recalcula inventario.
     *
     * @return OrderEvent|null
     */
    protected function getLastProductionBlockEvent(): ?OrderEvent
    {
        return $this->events()
            ->where('event_type', OrderEvent::TYPE_PRODUCTION_BLOCKED)
            ->whereJsonContains('metadata->reason', 'inventory_insufficient')
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Obtiene los materiales faltantes del último bloqueo por inventario.
     * Útil para mostrar detalles en la UI sin recalcular.
     *
     * @return array Lista de materiales faltantes del último bloqueo
     */
    public function getLastInventoryBlockDetails(): array
    {
        $lastBlock = $this->getLastProductionBlockEvent();

        if (!$lastBlock || !$this->hasProductionInventoryBlock()) {
            return [];
        }

        return [
            'message' => $lastBlock->message,
            'missing_materials' => $lastBlock->metadata['missing_materials'] ?? [],
            'blocked_at' => $lastBlock->created_at,
            'blocked_by' => $lastBlock->metadata['user_id'] ?? null,
        ];
    }

    /**
     * Obtiene todos los extras con inventario asociados a los productos del pedido.
     * Útil para mostrar en la UI qué extras consumen materiales.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getExtrasWithInventory(): \Illuminate\Support\Collection
    {
        $extras = collect();

        foreach ($this->items as $item) {
            $product = $item->product;
            if (!$product) {
                continue;
            }

            // Cargar extras con materiales si no están cargados
            if (!$product->relationLoaded('extras')) {
                $product->load(['extras.materials.material.consumptionUnit']);
            }

            foreach ($product->extras as $extra) {
                if ($extra->consumesInventory() && !$extras->contains('id', $extra->id)) {
                    $extras->push($extra);
                }
            }
        }

        return $extras;
    }

    /**
     * Verifica si el pedido tiene extras que consumen inventario.
     *
     * @return bool
     */
    public function hasExtrasWithInventory(): bool
    {
        return $this->getExtrasWithInventory()->isNotEmpty();
    }

    // =========================================================================
    // === CIERRE POS: MÉTODOS DE VENTA POS Y CANCELACIÓN ===
    // =========================================================================

    /**
     * Verifica si el pedido es una VENTA POS (mostrador).
     *
     * CRITERIOS:
     * - status = DELIVERED (creado directo como entregado)
     * - notes CONTIENE '[VENTA POS MOSTRADOR' (marca distintiva)
     * - cliente_id puede ser NULL o tener cliente asignado
     *
     * @return bool TRUE si es venta POS
     */
    public function isPosOrder(): bool
    {
        // Venta POS: entregado + tiene notas de POS (cliente es opcional)
        return $this->status === self::STATUS_DELIVERED
            && str_contains($this->notes ?? '', '[VENTA POS MOSTRADOR');
    }

    /**
     * Verifica si el pedido POS puede ser cancelado.
     *
     * REGLAS:
     * - Debe ser venta POS
     * - NO debe estar ya cancelado
     * - NO tiene restricción de tiempo (siempre cancelable)
     *
     * @return bool TRUE si puede cancelarse
     */
    public function canCancelPosOrder(): bool
    {
        if (!$this->isPosOrder()) {
            return false;
        }

        if ($this->status === self::STATUS_CANCELLED) {
            return false;
        }

        if ($this->cancelled_at !== null) {
            return false;
        }

        return true;
    }

    /**
     * Verifica si el pedido está cancelado.
     *
     * @return bool TRUE si está cancelado
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED
            || $this->cancelled_at !== null;
    }

    /**
     * CIERRE CANÓNICO: Verifica si el pedido puede ser cancelado.
     *
     * REGLAS INVIOLABLES:
     * - PERMITIDO: DRAFT, CONFIRMED, IN_PRODUCTION
     * - PROHIBIDO: DELIVERED, CANCELLED
     *
     * NOTA: La cancelación es un ACTO ADMINISTRATIVO.
     * NO genera merma automática. NO revierte inventarios.
     *
     * @return bool TRUE si el pedido puede cancelarse
     */
    public function canCancel(): bool
    {
        // Estados que PERMITEN cancelación
        $cancellableStates = [
            self::STATUS_DRAFT,
            self::STATUS_CONFIRMED,
            self::STATUS_IN_PRODUCTION,
        ];

        return in_array($this->status, $cancellableStates);
    }

    // =========================================================================
    // === PASO 10: GATE DE REPROGRAMACIÓN DE FECHA PROMETIDA ===
    // =========================================================================

    /**
     * GATE ERP: Verifica si promised_date puede modificarse.
     * REGLA CANÓNICA:
     * - DRAFT: SÍ (edición libre)
     * - CONFIRMED: SÍ (reprogramación controlada)
     * - IN_PRODUCTION+: NO (congelado)
     * - TERMINAL: NO (inmutable)
     *
     * @return bool TRUE si promised_date puede cambiarse
     */
    public function canReschedulePromisedDate(): bool
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_CONFIRMED,
        ]);
    }

    /**
     * Obtiene el motivo por el cual NO se puede reprogramar.
     *
     * @return string|null NULL si se puede reprogramar, mensaje si no
     */
    public function getRescheduleBlockReason(): ?string
    {
        if ($this->canReschedulePromisedDate()) {
            return null;
        }

        return match($this->status) {
            self::STATUS_IN_PRODUCTION => 'No se puede reprogramar: el pedido está en producción. Los materiales ya fueron reservados.',
            self::STATUS_READY => 'No se puede reprogramar: la producción ha finalizado.',
            self::STATUS_DELIVERED => 'No se puede reprogramar: el pedido ya fue entregado.',
            self::STATUS_CANCELLED => 'No se puede reprogramar: el pedido está cancelado.',
            default => 'No se puede reprogramar en el estado actual.',
        };
    }

    /**
     * Obtiene el motivo por el cual el pedido NO puede cancelarse.
     *
     * @return string|null NULL si puede cancelarse, mensaje si no
     */
    public function getCancelBlockReason(): ?string
    {
        if ($this->canCancel()) {
            return null;
        }

        return match($this->status) {
            self::STATUS_DELIVERED => 'No se puede cancelar un pedido ya entregado.',
            self::STATUS_CANCELLED => 'El pedido ya está cancelado.',
            default => 'El pedido no puede cancelarse en este estado.',
        };
    }

    /**
     * Obtiene el motivo de cancelación formateado.
     *
     * @return string|null
     */
    public function getCancellationInfoAttribute(): ?array
    {
        if (!$this->isCancelled()) {
            return null;
        }

        return [
            'cancelled_at' => $this->cancelled_at?->format('Y-m-d H:i:s'),
            'cancelled_by' => $this->canceller?->name ?? 'Sistema',
            'reason' => $this->cancel_reason ?? 'Sin motivo especificado',
        ];
    }
}
