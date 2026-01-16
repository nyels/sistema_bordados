<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use App\Traits\HasActivityLog;
use App\Enums\UnitType;

/**
 * =============================================================================
 * MODELO: UNIDAD DE MEDIDA
 * =============================================================================
 *
 * Representa las unidades de medida del sistema.
 *
 * TIPOS (unit_type):
 * - canonical   : Unidad canónica de consumo (metro, litro, pieza)
 * - metric_pack : Presentación métrica derivada (rollo 25m, caja 100pz)
 * - logistic    : Unidad logística pura de compra (cono, saco, paquete)
 *
 * @property UnitType $unit_type Tipo semántico de la unidad
 * @property bool $is_base @deprecated Use unit_type instead
 */
class Unit extends Model
{
    use SoftDeletes, HasActivityLog;

    protected $table = 'units';

    protected $activityLogNameField = 'name';

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'symbol',
        'is_base',              // @deprecated - mantener por compatibilidad
        'unit_type',            // Nuevo: fuente de verdad semántica
        'compatible_base_unit_id',
        'activo',
    ];

    protected $casts = [
        'is_base' => 'boolean', // @deprecated
        'unit_type' => UnitType::class,
        'activo' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | BOOT
    |--------------------------------------------------------------------------
    */

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->slug) && !empty($model->name)) {
                $model->slug = Str::slug($model->name);
            }
        });

        static::updating(function (self $model): void {
            if ($model->isDirty('name')) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    public function compatibleBaseUnit()
    {
        return $this->belongsTo(Unit::class, 'compatible_base_unit_id');
    }

    public function purchaseUnits()
    {
        return $this->hasMany(Unit::class, 'compatible_base_unit_id')->where('activo', true);
    }

    public function categories()
    {
        return $this->belongsToMany(MaterialCategory::class, 'category_unit', 'unit_id', 'material_category_id')
            ->withTimestamps();
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES - SEMÁNTICOS (USAR ESTOS)
    |--------------------------------------------------------------------------
    */

    /**
     * Filtrar solo unidades activas.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    /**
     * Filtrar unidades canónicas (consumo): metro, litro, pieza.
     */
    public function scopeCanonical(Builder $query): Builder
    {
        return $query->where('unit_type', UnitType::CANONICAL);
    }

    /**
     * Filtrar presentaciones métricas: rollo 25m, caja 100pz.
     */
    public function scopeMetricPack(Builder $query): Builder
    {
        return $query->where('unit_type', UnitType::METRIC_PACK);
    }

    /**
     * Filtrar unidades logísticas puras: cono, saco, paquete.
     * ÚNICA permitida para: Categoría↔Unidad, Material.base_unit
     */
    public function scopeLogistic(Builder $query): Builder
    {
        return $query->where('unit_type', UnitType::LOGISTIC);
    }

    /**
     * Ordenar por nombre.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('name');
    }

    /**
     * Filtrar por compatibilidad con unidad base.
     */
    public function scopeCompatibleWith(Builder $query, int $baseUnitId): Builder
    {
        return $query->where('compatible_base_unit_id', $baseUnitId);
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES - LEGACY (@deprecated)
    |--------------------------------------------------------------------------
    */

    /**
     * @deprecated Use scopeCanonical() instead
     */
    public function scopeBase(Builder $query): Builder
    {
        return $query->where('is_base', true);
    }

    /**
     * @deprecated Use scopeLogistic() or scopeMetricPack() instead
     */
    public function scopePurchase(Builder $query): Builder
    {
        return $query->where('is_base', false);
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS - TIPO DE UNIDAD
    |--------------------------------------------------------------------------
    */

    /**
     * ¿Es unidad canónica de consumo?
     */
    public function isCanonical(): bool
    {
        return $this->unit_type === UnitType::CANONICAL;
    }

    /**
     * ¿Es presentación métrica?
     */
    public function isMetricPack(): bool
    {
        return $this->unit_type === UnitType::METRIC_PACK;
    }

    /**
     * ¿Es unidad logística de compra?
     */
    public function isLogistic(): bool
    {
        return $this->unit_type === UnitType::LOGISTIC;
    }

    /**
     * ¿Puede asignarse a categorías de materiales?
     */
    public function canAssignToCategory(): bool
    {
        return $this->unit_type?->canAssignToCategory() ?? false;
    }

    /**
     * ¿Puede ser unidad base de un material?
     */
    public function canBeBaseMaterialUnit(): bool
    {
        return $this->unit_type?->canBeBaseMaterialUnit() ?? false;
    }

    /**
     * ¿Puede ser origen de una conversión?
     */
    public function canBeConversionSource(): bool
    {
        return $this->unit_type?->canBeConversionSource() ?? false;
    }

    /**
     * ¿Puede ser destino de una conversión?
     */
    public function canBeConversionTarget(): bool
    {
        return $this->unit_type?->canBeConversionTarget() ?? false;
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTODOS ESTÁTICOS
    |--------------------------------------------------------------------------
    */

    /**
     * Obtener unidades logísticas para asignar a categorías.
     */
    public static function getLogisticUnits(): \Illuminate\Database\Eloquent\Collection
    {
        return static::active()
            ->logistic()
            ->ordered()
            ->get();
    }

    /**
     * Obtener unidades canónicas (consumo).
     */
    public static function getCanonicalUnits(): \Illuminate\Database\Eloquent\Collection
    {
        return static::active()
            ->canonical()
            ->ordered()
            ->get();
    }

    /**
     * Obtener presentaciones métricas derivadas de una unidad canónica.
     */
    public static function getMetricPacksFor(int $canonicalUnitId): \Illuminate\Database\Eloquent\Collection
    {
        return static::active()
            ->metricPack()
            ->where('compatible_base_unit_id', $canonicalUnitId)
            ->ordered()
            ->get();
    }

    /**
     * @deprecated Use getMetricPacksFor() instead
     */
    public static function getPurchaseUnitsFor(int $baseUnitId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('activo', true)
            ->where('is_base', false)
            ->where('compatible_base_unit_id', $baseUnitId)
            ->ordered()
            ->get();
    }

    /**
     * @deprecated Use getCanonicalUnits() instead
     */
    public static function getBaseUnits(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('activo', true)
            ->where('is_base', true)
            ->ordered()
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getDisplayNameAttribute(): string
    {
        return "{$this->name} ({$this->symbol})";
    }
}
