<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ProductCategory extends Model
{
    use SoftDeletes;

    protected $table = 'product_categories';

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'description',
        'is_active',
        'supports_measurements',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'supports_measurements' => 'boolean',
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
            if (empty($model->slug)) {
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

    public function products()
    {
        return $this->hasMany(Product::class, 'product_category_id');
    }

    public function activeProducts()
    {
        return $this->hasMany(Product::class, 'product_category_id')
            ->where('status', 'active');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('name', 'asc');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getProductsCountAttribute(): int
    {
        return $this->products()->count();
    }

    public function getActiveProductsCountAttribute(): int
    {
        return $this->activeProducts()->count();
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->is_active ? 'Activa' : 'Inactiva';
    }

    public function getStatusColorAttribute(): string
    {
        return $this->is_active ? 'success' : 'danger';
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTODOS
    |--------------------------------------------------------------------------
    */

    public function canDelete(): bool
    {
        return $this->products()->count() === 0;
    }

    public function getSkuPrefix(): string
    {
        return Str::upper(Str::substr(Str::slug($this->name, ''), 0, 3));
    }

    /*
    |--------------------------------------------------------------------------
    | SOPORTE DE MEDIDAS (CANÓNICO)
    |--------------------------------------------------------------------------
    | Determina si los productos de esta categoría pueden requerir medidas
    | en pedidos. La decisión final la toma el PEDIDO, no el producto.
    */

    /**
     * Indica si esta categoría soporta productos con medidas personalizadas.
     *
     * REGLA CANÓNICA:
     * - supports_measurements = true → el checkbox "requiere medidas" APARECE en pedido
     * - supports_measurements = false → el checkbox "requiere medidas" NO aparece
     * - La decisión de usar medidas la toma el PEDIDO, no el producto
     */
    public function supportsMeasurements(): bool
    {
        return (bool) $this->supports_measurements;
    }

    /**
     * RESOLUCIÓN DE DOMINIO: Determina el ProductType basado en la categoría.
     *
     * REGLA ERP CANÓNICA:
     * - supports_measurements = true  → GARMENT_CUSTOM (prenda a medida)
     * - supports_measurements = false → GARMENT_STANDARD (prenda estándar)
     *
     * @throws \InvalidArgumentException Si no se puede resolver el tipo
     */
    public function resolveProductType(): ProductType
    {
        $code = $this->supports_measurements ? 'GARMENT_CUSTOM' : 'GARMENT_STANDARD';

        $productType = ProductType::where('code', $code)
            ->where('active', true)
            ->first();

        if (!$productType) {
            throw new \InvalidArgumentException(
                "No se puede determinar el tipo de producto para la categoría '{$this->name}'. " .
                "El tipo '{$code}' no existe o está inactivo. Contacte al administrador."
            );
        }

        return $productType;
    }
}
