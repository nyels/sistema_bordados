<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ClientMeasurementHistory extends Model
{
    protected $table = 'client_measurement_history';

    protected $fillable = [
        'uuid',
        'cliente_id',
        'order_id',
        'order_item_id',
        'product_id',
        'measurements',
        'source',
        'notes',
        'created_by',
        'captured_at',
    ];

    protected $casts = [
        'measurements' => 'array',
        'captured_at' => 'datetime',
    ];

    // === BOOT ===
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->captured_at)) {
                $model->captured_at = now();
            }
        });
    }

    // === RELACIONES ===

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // === ACCESSORS ===

    public function getSummaryAttribute(): string
    {
        $m = $this->measurements;
        $parts = [];

        if (!empty($m['busto'])) $parts[] = "B: {$m['busto']}";
        if (!empty($m['cintura'])) $parts[] = "Ci: {$m['cintura']}";
        if (!empty($m['cadera'])) $parts[] = "Ca: {$m['cadera']}";
        if (!empty($m['alto_cintura'])) $parts[] = "AC: {$m['alto_cintura']}";
        if (!empty($m['largo'])) $parts[] = "L: {$m['largo']}";
        if (!empty($m['largo_vestido'])) $parts[] = "LV: {$m['largo_vestido']}";

        return count($parts) > 0 ? implode(' | ', $parts) : 'Sin medidas';
    }

    public function getSourceLabelAttribute(): string
    {
        return match($this->source) {
            'order' => 'Pedido',
            'manual' => 'Manual',
            'import' => 'Importado',
            default => $this->source,
        };
    }
}
