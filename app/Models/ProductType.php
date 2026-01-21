<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * ProductType - Tipos de producto para lógica de negocio
 *
 * ARQUITECTURA:
 * - 'code': Inmutable, usado para lógica de negocio
 * - 'display_name': Editable por usuario sin romper lógica
 * - 'requires_measurements': Define si productos de este tipo requieren medidas
 *
 * CÓDIGOS RECOMENDADOS:
 * - GARMENT_CUSTOM: Prenda a medida (requiere medidas)
 * - GARMENT_STANDARD: Prenda estándar/tallas (no requiere medidas)
 * - ACCESSORY: Accesorios (no requiere medidas)
 * - SERVICE: Servicios (no requiere medidas)
 */
class ProductType extends Model
{
    protected $table = 'product_types';

    protected $fillable = [
        'uuid',
        'code',
        'display_name',
        'description',
        'requires_measurements',
        'sort_order',
        'active',
    ];

    protected $casts = [
        'requires_measurements' => 'boolean',
        'active' => 'boolean',
        'sort_order' => 'integer',
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
            // Normalizar código a mayúsculas
            if ($model->code) {
                $model->code = strtoupper(trim($model->code));
            }
        });

        // Proteger código de cambios después de creación
        static::updating(function (self $model): void {
            if ($model->isDirty('code')) {
                throw new \RuntimeException(
                    'El código del tipo de producto no puede ser modificado después de su creación. ' .
                    'Utilice display_name para cambiar el nombre visible.'
                );
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'product_type_id');
    }

    public function activeProducts(): HasMany
    {
        return $this->hasMany(Product::class, 'product_type_id')
            ->where('status', 'active');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeRequiresMeasurements(Builder $query): Builder
    {
        return $query->where('requires_measurements', true);
    }

    public function scopeDoesNotRequireMeasurements(Builder $query): Builder
    {
        return $query->where('requires_measurements', false);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('display_name');
    }

    public function scopeByCode(Builder $query, string $code): Builder
    {
        return $query->where('code', strtoupper(trim($code)));
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTODOS ESTÁTICOS
    |--------------------------------------------------------------------------
    */

    /**
     * Buscar tipo por código
     */
    public static function findByCode(string $code): ?self
    {
        return self::byCode($code)->first();
    }

    /**
     * Obtener todos los tipos activos ordenados
     */
    public static function getActiveOrdered(): \Illuminate\Database\Eloquent\Collection
    {
        return self::active()->ordered()->get();
    }

    /**
     * Obtener solo tipos que requieren medidas
     */
    public static function getRequiringMeasurements(): \Illuminate\Database\Eloquent\Collection
    {
        return self::active()->requiresMeasurements()->ordered()->get();
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getMeasurementStatusLabelAttribute(): string
    {
        return $this->requires_measurements ? 'Requiere Medidas' : 'Sin Medidas';
    }

    public function getMeasurementStatusColorAttribute(): string
    {
        return $this->requires_measurements ? 'warning' : 'secondary';
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->active ? 'Activo' : 'Inactivo';
    }

    public function getStatusColorAttribute(): string
    {
        return $this->active ? 'success' : 'danger';
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTODOS DE NEGOCIO
    |--------------------------------------------------------------------------
    */

    /**
     * Verifica si este tipo puede ser eliminado
     */
    public function canDelete(): bool
    {
        return $this->products()->count() === 0;
    }

    /**
     * Cuenta productos asociados
     */
    public function getProductsCountAttribute(): int
    {
        return $this->products()->count();
    }

    /**
     * Cuenta productos activos asociados
     */
    public function getActiveProductsCountAttribute(): int
    {
        return $this->activeProducts()->count();
    }
}
