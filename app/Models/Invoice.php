<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * v2.6-MIN: Modelo Invoice — Contenedor fiscal pasivo.
 *
 * REGLA SELLADA:
 * - Solo $fillable, $casts y relación belongsTo(Order)
 * - NO boot, NO gates, NO helpers, NO lógica
 * - Su única función es existir estructuralmente
 */
class Invoice extends Model
{
    protected $table = 'invoices';

    // === CONSTANTES DE ESTADO ===
    public const STATUS_DRAFT = 'draft';
    public const STATUS_ISSUED = 'issued';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'uuid',
        'order_id',
        'invoice_number',
        'serie',
        'status',
        'emisor_rfc',
        'emisor_razon_social',
        'receptor_rfc',
        'receptor_razon_social',
        'subtotal',
        'iva_rate',
        'iva_amount',
        'total',
        'issued_at',
        'cancelled_at',
        'created_by',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'iva_rate' => 'decimal:4',
        'iva_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'issued_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    // === RELACIONES ===

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
