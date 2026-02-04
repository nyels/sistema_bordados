<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class UrgencyLevel extends Model
{
    protected $table = 'urgency_levels';

    protected $fillable = [
        'name',
        'slug',
        'time_percentage',
        'price_multiplier',
        'color',
        'icon',
        'description',
        'sort_order',
        'activo',
    ];

    protected $casts = [
        'time_percentage' => 'integer',
        'price_multiplier' => 'decimal:2',
        'sort_order' => 'integer',
        'activo' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeActivo(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    /**
     * Retorna el label formateado para mostrar en selects
     * Ej: "Urgente (50% tiempo)"
     */
    public function getDisplayLabelAttribute(): string
    {
        return "{$this->name} ({$this->time_percentage}% tiempo)";
    }

    /**
     * Retorna el badge HTML con color
     */
    public function getBadgeHtmlAttribute(): string
    {
        $icon = $this->icon ? "<i class='fas {$this->icon} mr-1'></i>" : '';
        return "<span class='badge' style='background-color: {$this->color}; color: white;'>{$icon}{$this->name}</span>";
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTODOS ESTÁTICOS
    |--------------------------------------------------------------------------
    */

    /**
     * Obtiene todos los niveles activos para llenar selects
     */
    public static function getForSelect(): \Illuminate\Support\Collection
    {
        return static::activo()
            ->ordered()
            ->get()
            ->mapWithKeys(fn($level) => [$level->slug => $level->display_label]);
    }

    /**
     * Busca un nivel por slug
     */
    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }

    /**
     * Obtiene el nivel normal (por defecto)
     */
    public static function getDefault(): ?self
    {
        return static::where('slug', 'normal')->first();
    }
}
