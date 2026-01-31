<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class OrderMessage extends Model
{
    protected $table = 'order_messages';

    protected $fillable = [
        'order_id',
        'parent_message_id',
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

    public function reads(): HasMany
    {
        return $this->hasMany(OrderMessageRead::class, 'message_id');
    }

    /**
     * Relación con mensaje padre (si es respuesta)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(OrderMessage::class, 'parent_message_id');
    }

    /**
     * Relación con respuestas (hijos)
     */
    public function replies(): HasMany
    {
        return $this->hasMany(OrderMessage::class, 'parent_message_id')
                    ->orderBy('created_at', 'asc');
    }

    /**
     * Verificar si este mensaje es una respuesta
     */
    public function isReply(): bool
    {
        return $this->parent_message_id !== null;
    }

    /**
     * Obtener conteo de respuestas
     */
    public function getRepliesCountAttribute(): int
    {
        return $this->replies()->count();
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

    /**
     * Mensajes no leídos por un usuario específico
     */
    public function scopeUnreadBy(Builder $query, int $userId): Builder
    {
        return $query->whereDoesntHave('reads', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        });
    }

    /**
     * Mensajes que NO fueron creados por el usuario (no ver tus propios mensajes como no leídos)
     */
    public function scopeNotCreatedBy(Builder $query, int $userId): Builder
    {
        return $query->where('created_by', '!=', $userId);
    }

    /**
     * Mensajes visibles para un usuario según su rol
     */
    public function scopeVisibleToUser(Builder $query, $user): Builder
    {
        // Por ahora todos los usuarios autenticados ven admin+both
        // En el futuro se puede filtrar por rol
        return $query->whereIn('visibility', [self::VISIBILITY_ADMIN, self::VISIBILITY_BOTH]);
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

    // === MÉTODOS DE LECTURA ===

    /**
     * Verificar si un usuario ha leído este mensaje
     */
    public function isReadBy(int $userId): bool
    {
        return $this->reads()->where('user_id', $userId)->exists();
    }

    /**
     * Marcar como leído por un usuario
     */
    public function markAsReadBy(int $userId): void
    {
        if (!$this->isReadBy($userId)) {
            $this->reads()->create([
                'user_id' => $userId,
                'read_at' => now(),
            ]);
        }
    }

    /**
     * Obtener conteo de mensajes no leídos para un usuario
     * (excluye mensajes creados por el mismo usuario)
     */
    public static function unreadCountFor(int $userId): int
    {
        return static::visibleToUser(auth()->user())
            ->notCreatedBy($userId)
            ->unreadBy($userId)
            ->count();
    }

    /**
     * Obtener mensajes no leídos para un usuario
     * (excluye mensajes creados por el mismo usuario)
     */
    public static function getUnreadFor(int $userId, int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        return static::with(['order', 'creator'])
            ->visibleToUser(auth()->user())
            ->notCreatedBy($userId)
            ->unreadBy($userId)
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Obtener mensajes recientes (leídos y no leídos) para un usuario
     */
    public static function getRecentFor(int $userId, int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        return static::with(['order', 'creator'])
            ->visibleToUser(auth()->user())
            ->notCreatedBy($userId)
            ->latest()
            ->limit($limit)
            ->get();
    }

    // === FACTORY METHOD ===
    public static function log(
        Order $order,
        string $message,
        string $visibility = self::VISIBILITY_BOTH,
        ?int $userId = null,
        ?int $parentMessageId = null
    ): self {
        return self::create([
            'order_id' => $order->id,
            'parent_message_id' => $parentMessageId,
            'message' => $message,
            'visibility' => $visibility,
            'created_by' => $userId ?? auth()->id(),
        ]);
    }
}
