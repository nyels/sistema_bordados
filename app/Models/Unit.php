<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use App\Traits\HasActivityLog;
use App\Enums\UnitType;
use App\Enums\MeasurementFamily;

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
        'is_base',                      // @deprecated - mantener por compatibilidad
        'unit_type',                    // Fuente de verdad semántica
        'measurement_family',           // Familia de medición para filtrado semántico
        'compatible_base_unit_id',      // FK → unidad canonical compatible
        'default_conversion_factor',    // Factor predeterminado para metric_pack
        'sort_order',
        'activo',
    ];

    protected $casts = [
        'is_base' => 'boolean',                     // @deprecated
        'unit_type' => UnitType::class,
        'measurement_family' => MeasurementFamily::class,
        'default_conversion_factor' => 'decimal:4',
        'activo' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | BOOT
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | CONSTANTES DE SISTEMA
    |--------------------------------------------------------------------------
    */
    public const SYSTEM_UNITS = ['metro', 'pieza', 'litro', 'mililitro', 'gramo', 'kilogramo', 'minuto'];

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

            // PROTECCIÓN DE SISTEMA: Impedir modificación de unidades base
            if ($model->isSystemUnit()) {
                // Permitir solo cambiar el estado 'activo' si fuera absolutamente necesario (opcional),
                // pero el requerimiento dice "no puedan ser cambiadas". Bloqueamos todo cambio estructural.
                // Si se intenta cambiar nombre, simbolo, tipo, o factores clave:
                if ($model->isDirty(['name', 'symbol', 'is_base', 'unit_type', 'compatible_base_unit_id', 'default_conversion_factor'])) {
                    throw new \Exception("ACCIÓN BLOQUEADA: La unidad '{$model->name}' es fundamental para el sistema y no puede ser modificada.");
                }
            }
        });

        static::deleting(function (self $model): void {
            // PROTECCIÓN DE SISTEMA: Impedir eliminación de unidades base
            if ($model->isSystemUnit()) {
                throw new \Exception("ACCIÓN BLOQUEADA: La unidad '{$model->name}' es fundamental para el sistema y no puede ser eliminada.");
            }
        });

        static::saving(function (self $model): void {
            // Regla de negocio para determinar el tipo semántico
            if ($model->is_base) {
                // Si es base (marcada como consumo en la UI) -> Canonical
                $model->unit_type = UnitType::CANONICAL;
                $model->compatible_base_unit_id = null;
            } else {
                // Si no es base, solo aplicamos la lógica automática si el unit_type es nulo
                // Esto permite forzar 'logistic' vía controlador o tinker aunque tenga compatibilidad
                if (empty($model->unit_type)) {
                    if (!empty($model->compatible_base_unit_id)) {
                        $model->unit_type = UnitType::METRIC_PACK;
                    } else {
                        $model->unit_type = UnitType::LOGISTIC;
                    }
                }
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

    /**
     * Filtrar por familia de medición compatible.
     * Incluye siempre las unidades "universal".
     *
     * @param MeasurementFamily|string $family
     */
    public function scopeCompatibleWithFamily(Builder $query, MeasurementFamily|string $family): Builder
    {
        if (is_string($family)) {
            $family = MeasurementFamily::tryFrom($family);
        }

        if (!$family) {
            return $query;
        }

        $compatibleFamilies = MeasurementFamily::getCompatibleFamilies($family);
        $familyValues = array_map(fn($f) => $f->value, $compatibleFamilies);

        return $query->whereIn('measurement_family', $familyValues);
    }

    /**
     * Filtrar por familia de medición exacta.
     */
    public function scopeOfFamily(Builder $query, MeasurementFamily|string $family): Builder
    {
        if (is_string($family)) {
            $family = MeasurementFamily::tryFrom($family);
        }

        return $query->where('measurement_family', $family?->value);
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

    /**
     * Verificar si es una unidad protegida del sistema (Metro, Pieza, etc).
     */
    public function isSystemUnit(): bool
    {
        // Se normaliza el slug para comparación segura
        return in_array(Str::slug($this->name), self::SYSTEM_UNITS);
    }

    /**
     * Verificar si la unidad está en uso por materiales o conversiones.
     * Usado para bloqueo por uso real (no por tipo).
     *
     * @return array{in_use: bool, materials_count: int, conversions_count: int, categories_count: int}
     */
    public function getUsageInfo(): array
    {
        $materialsAsBase = Material::where('base_unit_id', $this->id)->where('activo', true)->count();
        $materialsAsConsumption = Material::where('consumption_unit_id', $this->id)->where('activo', true)->count();
        $conversionsFrom = MaterialUnitConversion::where('from_unit_id', $this->id)->count();
        $conversionsTo = MaterialUnitConversion::where('to_unit_id', $this->id)->count();
        $categoriesDefault = MaterialCategory::where('default_inventory_unit_id', $this->id)->count();
        $categoriesAllowed = $this->categories()->count();

        $totalMaterials = $materialsAsBase + $materialsAsConsumption;
        $totalConversions = $conversionsFrom + $conversionsTo;

        return [
            'in_use' => ($totalMaterials + $totalConversions + $categoriesDefault) > 0,
            'materials_count' => $totalMaterials,
            'conversions_count' => $totalConversions,
            'categories_count' => $categoriesAllowed,
            'categories_default_count' => $categoriesDefault,
        ];
    }

    /**
     * ¿Está la unidad en uso?
     */
    public function isInUse(): bool
    {
        return $this->getUsageInfo()['in_use'];
    }

    /**
     * ¿Puede eliminarse esta unidad?
     * Solo se puede eliminar si: no es de sistema Y no está en uso
     */
    public function canBeDeleted(): bool
    {
        return !$this->isSystemUnit() && !$this->isInUse();
    }

    /**
     * ¿Puede editarse esta unidad?
     * Las unidades de sistema no pueden editarse.
     * Las unidades en uso pueden editarse parcialmente (nombre, símbolo) pero no su tipo.
     */
    public function canBeEdited(): bool
    {
        return !$this->isSystemUnit();
    }

    /**
     * ¿Puede cambiarse el tipo de esta unidad?
     */
    public function canChangeType(): bool
    {
        return !$this->isSystemUnit() && !$this->isInUse();
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
