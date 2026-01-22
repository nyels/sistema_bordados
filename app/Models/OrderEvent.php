<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderEvent extends Model
{
    protected $table = 'order_events';

    protected $fillable = [
        'order_id',
        'event_type',
        'message',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    // === TIPOS DE EVENTO ===
    public const TYPE_CREATED = 'created';
    public const TYPE_CONFIRMED = 'confirmed';
    public const TYPE_URGENT_MARKED = 'urgent_marked';
    public const TYPE_BLOCKED = 'blocked';
    public const TYPE_UNBLOCKED = 'unblocked';
    public const TYPE_PRODUCTION_STARTED = 'production_started';
    public const TYPE_PRODUCTION_BLOCKED = 'production_blocked';
    public const TYPE_MATERIAL_RESERVED = 'material_reserved';
    public const TYPE_MATERIAL_INSUFFICIENT = 'material_insufficient';
    public const TYPE_READY = 'ready';
    public const TYPE_DELIVERED = 'delivered';
    public const TYPE_CANCELLED = 'cancelled';
    public const TYPE_PAYMENT_RECEIVED = 'payment_received';
    public const TYPE_STATUS_CHANGED = 'status_changed';
    public const TYPE_ITEM_ADDED = 'item_added';
    public const TYPE_ANNEX_CREATED = 'annex_created';

    // === RELACIONES ===
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // === ACCESSORS ===
    public function getEventIconAttribute(): string
    {
        return match($this->event_type) {
            self::TYPE_CREATED => 'fas fa-plus-circle text-primary',
            self::TYPE_CONFIRMED => 'fas fa-check-circle text-info',
            self::TYPE_URGENT_MARKED => 'fas fa-exclamation-circle text-warning',
            self::TYPE_BLOCKED => 'fas fa-ban text-danger',
            self::TYPE_UNBLOCKED => 'fas fa-unlock text-success',
            self::TYPE_PRODUCTION_STARTED => 'fas fa-cogs text-warning',
            self::TYPE_PRODUCTION_BLOCKED => 'fas fa-hand-paper text-danger',
            self::TYPE_MATERIAL_RESERVED => 'fas fa-boxes text-info',
            self::TYPE_MATERIAL_INSUFFICIENT => 'fas fa-exclamation-triangle text-danger',
            self::TYPE_READY => 'fas fa-box-open text-success',
            self::TYPE_DELIVERED => 'fas fa-truck text-primary',
            self::TYPE_CANCELLED => 'fas fa-times-circle text-danger',
            self::TYPE_PAYMENT_RECEIVED => 'fas fa-dollar-sign text-success',
            self::TYPE_STATUS_CHANGED => 'fas fa-exchange-alt text-secondary',
            self::TYPE_ITEM_ADDED => 'fas fa-cart-plus text-info',
            self::TYPE_ANNEX_CREATED => 'fas fa-project-diagram text-info',
            default => 'fas fa-circle text-muted',
        };
    }

    public function getEventColorAttribute(): string
    {
        return match($this->event_type) {
            self::TYPE_CREATED => 'primary',
            self::TYPE_CONFIRMED => 'info',
            self::TYPE_URGENT_MARKED => 'warning',
            self::TYPE_BLOCKED => 'danger',
            self::TYPE_UNBLOCKED => 'success',
            self::TYPE_PRODUCTION_STARTED => 'warning',
            self::TYPE_PRODUCTION_BLOCKED => 'danger',
            self::TYPE_MATERIAL_RESERVED => 'info',
            self::TYPE_MATERIAL_INSUFFICIENT => 'danger',
            self::TYPE_READY => 'success',
            self::TYPE_DELIVERED => 'primary',
            self::TYPE_CANCELLED => 'danger',
            self::TYPE_PAYMENT_RECEIVED => 'success',
            self::TYPE_STATUS_CHANGED => 'secondary',
            self::TYPE_ITEM_ADDED => 'info',
            self::TYPE_ANNEX_CREATED => 'info',
            default => 'secondary',
        };
    }

    public function getEventLabelAttribute(): string
    {
        return match($this->event_type) {
            self::TYPE_CREATED => 'Pedido Creado',
            self::TYPE_CONFIRMED => 'Pedido Confirmado',
            self::TYPE_URGENT_MARKED => 'Marcado Urgente',
            self::TYPE_BLOCKED => 'Bloqueado',
            self::TYPE_UNBLOCKED => 'Desbloqueado',
            self::TYPE_PRODUCTION_STARTED => 'Inicio Produccion',
            self::TYPE_PRODUCTION_BLOCKED => 'Produccion Bloqueada',
            self::TYPE_MATERIAL_RESERVED => 'Material Reservado',
            self::TYPE_MATERIAL_INSUFFICIENT => 'Material Insuficiente',
            self::TYPE_READY => 'Listo para Entrega',
            self::TYPE_DELIVERED => 'Entregado',
            self::TYPE_CANCELLED => 'Cancelado',
            self::TYPE_PAYMENT_RECEIVED => 'Pago Recibido',
            self::TYPE_STATUS_CHANGED => 'Cambio de Estado',
            self::TYPE_ITEM_ADDED => 'Item Agregado',
            self::TYPE_ANNEX_CREATED => 'Anexo Creado',
            default => 'Evento',
        };
    }

    // === FACTORY METHODS ===
    public static function log(
        Order $order,
        string $eventType,
        string $message,
        ?array $metadata = null,
        ?int $userId = null
    ): self {
        return self::create([
            'order_id' => $order->id,
            'event_type' => $eventType,
            'message' => $message,
            'metadata' => $metadata,
            'created_by' => $userId ?? auth()->id(),
        ]);
    }

    // === HELPER: Registrar evento de creacion ===
    public static function logCreated(Order $order): self
    {
        return self::log(
            $order,
            self::TYPE_CREATED,
            "Pedido {$order->order_number} creado para cliente {$order->cliente->nombre} {$order->cliente->apellidos}",
            [
                'cliente_id' => $order->cliente_id,
                'total' => $order->total,
                'items_count' => $order->items()->count(),
            ]
        );
    }

    // === HELPER: Registrar confirmacion ===
    public static function logConfirmed(Order $order): self
    {
        return self::log(
            $order,
            self::TYPE_CONFIRMED,
            "Pedido confirmado. Total: \${$order->total}",
            [
                'total' => $order->total,
                'requires_invoice' => $order->requires_invoice,
                'iva_amount' => $order->iva_amount,
            ]
        );
    }

    // === HELPER: Registrar marcado urgente ===
    public static function logUrgentMarked(Order $order, string $previousLevel): self
    {
        return self::log(
            $order,
            self::TYPE_URGENT_MARKED,
            "Prioridad cambiada de '{$previousLevel}' a '{$order->urgency_level}'",
            [
                'previous_level' => $previousLevel,
                'new_level' => $order->urgency_level,
            ]
        );
    }

    // === HELPER: Registrar bloqueo ===
    public static function logBlocked(Order $order, string $reason): self
    {
        return self::log(
            $order,
            self::TYPE_BLOCKED,
            "Pedido bloqueado: {$reason}",
            ['reason' => $reason]
        );
    }

    // === HELPER: Registrar bloqueo de produccion por material ===
    public static function logProductionBlocked(Order $order, array $missingMaterials): self
    {
        $materialsText = collect($missingMaterials)->map(function ($m) {
            return "{$m['quantity']} {$m['unit']} de {$m['name']}";
        })->implode(', ');

        return self::log(
            $order,
            self::TYPE_PRODUCTION_BLOCKED,
            "Produccion detenida: faltan {$materialsText}",
            [
                'missing_materials' => $missingMaterials,
                'blocked_at' => now()->toDateTimeString(),
            ]
        );
    }

    // === HELPER: Registrar material insuficiente ===
    public static function logMaterialInsufficient(Order $order, string $materialName, float $required, float $available, string $unit): self
    {
        $missing = $required - $available;
        return self::log(
            $order,
            self::TYPE_MATERIAL_INSUFFICIENT,
            "Material insuficiente: {$materialName}. Requerido: {$required} {$unit}, Disponible: {$available} {$unit}, Faltante: {$missing} {$unit}",
            [
                'material' => $materialName,
                'required' => $required,
                'available' => $available,
                'missing' => $missing,
                'unit' => $unit,
            ]
        );
    }

    // === HELPER: Registrar inicio produccion ===
    public static function logProductionStarted(Order $order, array $reservations = []): self
    {
        return self::log(
            $order,
            self::TYPE_PRODUCTION_STARTED,
            "Pedido enviado a produccion. Materiales reservados.",
            [
                'reservations_count' => count($reservations),
                'reservations' => $reservations,
            ]
        );
    }

    // === HELPER: Registrar reserva de material ===
    public static function logMaterialReserved(Order $order, string $materialName, float $quantity, string $unit): self
    {
        return self::log(
            $order,
            self::TYPE_MATERIAL_RESERVED,
            "Reservado: {$quantity} {$unit} de {$materialName}",
            [
                'material' => $materialName,
                'quantity' => $quantity,
                'unit' => $unit,
            ]
        );
    }

    // === HELPER: Registrar listo para entrega ===
    public static function logReady(Order $order): self
    {
        return self::log(
            $order,
            self::TYPE_READY,
            "Pedido listo para entrega",
            null
        );
    }

    // === HELPER: Registrar entrega ===
    public static function logDelivered(Order $order, array $consumedMaterials = []): self
    {
        return self::log(
            $order,
            self::TYPE_DELIVERED,
            "Pedido entregado. Materiales consumidos del inventario.",
            [
                'delivered_date' => $order->delivered_date?->format('Y-m-d'),
                'consumed_materials' => $consumedMaterials,
            ]
        );
    }

    // === HELPER: Registrar cancelacion ===
    public static function logCancelled(Order $order, ?string $reason = null): self
    {
        return self::log(
            $order,
            self::TYPE_CANCELLED,
            "Pedido cancelado" . ($reason ? ": {$reason}" : ""),
            ['reason' => $reason]
        );
    }

    // === HELPER: Registrar pago ===
    public static function logPayment(Order $order, float $amount, string $method): self
    {
        return self::log(
            $order,
            self::TYPE_PAYMENT_RECEIVED,
            "Pago recibido: \${$amount} ({$method})",
            [
                'amount' => $amount,
                'method' => $method,
                'new_balance' => $order->balance,
            ]
        );
    }

}
