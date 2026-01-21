<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    use SoftDeletes;

    protected $table = 'orders';

    protected $fillable = [
        'uuid',
        'order_number',
        'order_parent_id',
        'cliente_id',
        'client_measurement_id',
        'status',
        'urgency_level',
        'priority',
        'payment_status',
        'subtotal',
        'discount',
        'requires_invoice',
        'iva_amount',
        'total',
        'amount_paid',
        'balance',
        'promised_date',
        'minimum_date',
        'delivered_date',
        'notes',
        'created_by',
        'updated_by',
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
        'total' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance' => 'decimal:2',
        'promised_date' => 'date',
        'minimum_date' => 'date',
        'delivered_date' => 'date',
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

    public function parentOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_parent_id');
    }

    public function annexOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'order_parent_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(OrderEvent::class, 'order_id')->orderBy('created_at', 'desc');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(OrderMessage::class, 'order_id')->orderBy('created_at', 'desc');
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

    // === MÉTODOS DE NEGOCIO ===

    // Tasa de IVA (16%)
    public const IVA_RATE = 0.16;

    public function recalculateTotals(): void
    {
        // Subtotal base de items (sin ajustes)
        $this->subtotal = $this->items()->sum('subtotal');

        // Sumar costos extras de ajustes aprobados
        $approvedExtras = $this->calculateApprovedExtras();

        // Calcular IVA si requiere factura (sobre subtotal + extras - descuento)
        $baseForIva = $this->subtotal + $approvedExtras - $this->discount;
        if ($this->requires_invoice) {
            $this->iva_amount = $baseForIva * self::IVA_RATE;
        } else {
            $this->iva_amount = 0;
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
}
