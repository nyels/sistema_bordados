<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderMessage extends Model
{
    protected $table = 'order_messages';

    protected $fillable = [
        'order_id',
        'message',
        'visibility',
        'created_by',
    ];

    // === CONSTANTES DE VISIBILIDAD ===
    public const VISIBILITY_ADMIN = 'admin';
    public const VISIBILITY_PRODUCTION = 'production';
    public const VISIBILITY_BOTH = 'both';

    // === RELACIONES ===
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // === SCOPES ===
    public function scopeForAdmin($query)
    {
        return $query->whereIn('visibility', [self::VISIBILITY_ADMIN, self::VISIBILITY_BOTH]);
    }

    public function scopeForProduction($query)
    {
        return $query->whereIn('visibility', [self::VISIBILITY_PRODUCTION, self::VISIBILITY_BOTH]);
    }

    // === ACCESSORS ===
    public function getVisibilityLabelAttribute(): string
    {
        return match($this->visibility) {
            self::VISIBILITY_ADMIN => 'Solo Admin',
            self::VISIBILITY_PRODUCTION => 'Solo Producción',
            self::VISIBILITY_BOTH => 'Todos',
            default => 'Desconocido',
        };
    }

    public function getVisibilityIconAttribute(): string
    {
        return match($this->visibility) {
            self::VISIBILITY_ADMIN => 'fas fa-user-shield text-primary',
            self::VISIBILITY_PRODUCTION => 'fas fa-industry text-warning',
            self::VISIBILITY_BOTH => 'fas fa-users text-success',
            default => 'fas fa-question text-muted',
        };
    }

    // === FACTORY METHOD ===
    public static function log(
        Order $order,
        string $message,
        string $visibility = self::VISIBILITY_BOTH,
        ?int $userId = null
    ): self {
        return self::create([
            'order_id' => $order->id,
            'message' => $message,
            'visibility' => $visibility,
            'created_by' => $userId ?? auth()->id(),
        ]);
    }
}
