<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class PersonalizationTimeMultiplier extends Model
{
    protected $table = 'personalization_time_multipliers';

    protected $fillable = [
        'type',
        'multiplier',
        'description',
        'priority',
        'is_active',
        'updated_by',
    ];

    protected $casts = [
        'multiplier' => 'decimal:2',
        'priority' => 'integer',
        'is_active' => 'boolean',
    ];

    // === CONSTANTES DE TIPO ===
    public const TYPE_NONE = 'none';
    public const TYPE_TEXT = 'text';
    public const TYPE_MEASUREMENTS = 'measurements';
    public const TYPE_DESIGN = 'design';

    // Cache key
    private const CACHE_KEY = 'personalization_multipliers';
    private const CACHE_TTL = 3600; // 1 hora

    // === BOOT ===
    protected static function boot(): void
    {
        parent::boot();

        // Limpiar cache al modificar
        static::saved(function () {
            Cache::forget(self::CACHE_KEY);
        });

        static::deleted(function () {
            Cache::forget(self::CACHE_KEY);
        });
    }

    // === RELACIONES ===

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // === MÉTODOS ESTÁTICOS ===

    /**
     * Obtener todos los multiplicadores activos (cacheado).
     */
    public static function getAllActive(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return self::where('is_active', true)
                ->orderBy('priority', 'desc')
                ->pluck('multiplier', 'type')
                ->toArray();
        });
    }

    /**
     * Obtener multiplicador por tipo.
     * Si no existe, retorna 1.0 (sin modificación).
     */
    public static function getMultiplier(string $type): float
    {
        $multipliers = self::getAllActive();
        return (float) ($multipliers[$type] ?? 1.00);
    }

    /**
     * Determinar tipo de personalización basado en datos del item.
     * Prioridad: design > measurements > text > none
     */
    public static function determineType(array $itemData): string
    {
        // Si tiene diseño personalizado (requiere aprobación)
        $hasDesign = !empty($itemData['customization_notes']) &&
            (str_contains(strtolower($itemData['customization_notes']), 'diseño') ||
             str_contains(strtolower($itemData['customization_notes']), 'design'));

        if ($hasDesign) {
            return self::TYPE_DESIGN;
        }

        // Si tiene medidas
        $hasMeasurements = !empty($itemData['measurements']) &&
            is_array($itemData['measurements']) &&
            count(array_filter($itemData['measurements'], fn($v) => !empty($v) && $v !== '0')) > 0;

        if ($hasMeasurements) {
            return self::TYPE_MEASUREMENTS;
        }

        // Si tiene texto de bordado
        if (!empty($itemData['embroidery_text'])) {
            return self::TYPE_TEXT;
        }

        return self::TYPE_NONE;
    }

    /**
     * Calcular tiempo estimado para un item.
     * tiempo = base_lead_time × multiplier × urgency_multiplier
     */
    public static function calculateItemLeadTime(
        int $baseLeadTime,
        string $personalizationType,
        float $urgencyMultiplier = 1.0
    ): int {
        $multiplier = self::getMultiplier($personalizationType);
        return (int) ceil($baseLeadTime * $multiplier * $urgencyMultiplier);
    }

    // === SCOPES ===

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // === ACCESSORS ===

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            self::TYPE_NONE => 'Sin personalización',
            self::TYPE_TEXT => 'Texto bordado',
            self::TYPE_MEASUREMENTS => 'Medidas personalizadas',
            self::TYPE_DESIGN => 'Diseño personalizado',
            default => $this->type,
        };
    }
}
