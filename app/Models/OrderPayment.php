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

            // === VALIDACIONES CONTABLES v2.4 ===
            $model->validatePayment();
        });

        // Recalcular totales del pedido al registrar pago
        static::saved(function (self $model): void {
            $model->order->recalculateTotals();
        });

        // ================================================================
        // v2.5: PROTECCIÓN CONTRA ELIMINACIÓN DE PAGOS
        // REGLA CONTABLE: amount_paid SOLO puede CRECER, nunca decrecer
        // EXCEPCIÓN: En estado DRAFT los anticipos pueden eliminarse
        // ================================================================
        static::deleting(function (self $model): void {
            $order = $model->order;

            // R0: En estado DRAFT sí se permite eliminar anticipos
            // (El pedido aún no ha sido confirmado, es editable)
            if ($order->status === Order::STATUS_DRAFT) {
                return; // Permitir eliminación
            }

            // R1: Pedidos financieramente cerrados NO permiten eliminación de pagos
            if ($order->isFinanciallyClosed()) {
                throw new \Exception(
                    "CIERRE CONTABLE: No se puede eliminar el pago. " .
                    "El pedido {$order->order_number} está cerrado contablemente " .
                    "(ENTREGADO + PAGADO). Los pagos son inmutables."
                );
            }

            // R2: Regla de negocio - pagos solo pueden crecer (después de DRAFT)
            // En un ERP contable, los pagos NO se eliminan, se hacen notas de crédito
            throw new \Exception(
                "VIOLACIÓN CONTABLE: Los pagos registrados NO pueden eliminarse. " .
                "El amount_paid solo puede crecer. " .
                "Para ajustes, registre una nota de crédito o contacte al administrador. " .
                "Pago intentado eliminar: \$" . number_format($model->amount, 2)
            );
        });

        static::deleted(function (self $model): void {
            // Este código NUNCA se ejecutará debido al gate en deleting()
            // Se mantiene por si en el futuro se implementa soft delete de pagos
            $model->order->recalculateTotals();
        });
    }

    /**
     * GATE v2.4: Validaciones contables antes de crear pago.
     * Segunda capa de seguridad (OrderService es la primera).
     *
     * @throws \Exception Si el pago viola reglas contables
     */
    protected function validatePayment(): void
    {
        $order = $this->order;

        // V1: Monto positivo
        if ($this->amount <= 0) {
            throw new \Exception('El monto del pago debe ser mayor a cero.');
        }

        // V2: No pagos en pedidos cancelados
        if ($order->status === Order::STATUS_CANCELLED) {
            throw new \Exception('No se pueden registrar pagos en pedidos cancelados.');
        }

        // V3: No sobrepago
        // Calcular balance actual ANTES de este pago
        $currentPaid = $order->payments()
            ->where('id', '!=', $this->id ?? 0)
            ->sum('amount');
        $currentBalance = $order->total - $currentPaid;

        if ($this->amount > $currentBalance + 0.01) { // Tolerancia de centavos
            throw new \Exception(
                "Sobrepago no permitido. Balance pendiente: \$" .
                number_format($currentBalance, 2) .
                ", monto intentado: \$" . number_format($this->amount, 2)
            );
        }
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
