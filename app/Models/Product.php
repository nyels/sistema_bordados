<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class Product extends Model
{
    use SoftDeletes, HasFactory;

    protected $table = 'products';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'product_category_id',
        'product_type_id',
        'name',
        'sku',
        'description',
        'specifications',
        'status',
        // Pricing Fields
        'base_price',
        'production_cost',
        'materials_cost',
        'embroidery_cost',
        'embroidery_rate_per_thousand',
        'labor_cost',
        'extra_services_cost',
        'suggested_price',
        'profit_margin',
        'production_lead_time',
    ];

    protected $casts = [
        'specifications' => 'array',
        'tenant_id' => 'integer',
        'product_type_id' => 'integer',
        'base_price' => 'decimal:6',
        'production_cost' => 'decimal:6',
        'materials_cost' => 'decimal:6',
        'embroidery_cost' => 'decimal:6',
        'embroidery_rate_per_thousand' => 'decimal:4',
        'labor_cost' => 'decimal:6',
        'extra_services_cost' => 'decimal:6',
        'suggested_price' => 'decimal:6',
        'profit_margin' => 'decimal:2',
        'production_lead_time' => 'integer',
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
            if (empty($model->tenant_id)) {
                $model->tenant_id = 1;
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function productType()
    {
        return $this->belongsTo(ProductType::class, 'product_type_id');
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class, 'product_id');
    }

    public function activeVariants()
    {
        return $this->hasMany(ProductVariant::class, 'product_id')
            ->where('activo', true)
            ->orderBy('sku_variant');
    }

    public function extras()
    {
        return $this->belongsToMany(ProductExtra::class, 'product_extra_assignment', 'product_id', 'product_extra_id')
            ->withPivot(['id', 'snapshot_cost', 'snapshot_price', 'snapshot_time'])
            ->withTimestamps();
    }

    public function designs()
    {
        return $this->belongsToMany(Design::class, 'product_design', 'product_id', 'design_id')
            ->withPivot('application_type_id', 'design_export_id')
            ->withTimestamps();
    }

    public function materials()
    {
        return $this->belongsToMany(MaterialVariant::class, 'product_materials', 'product_id', 'material_variant_id')
            ->using(ProductMaterial::class)
            ->withPivot(['id', 'quantity', 'unit_cost', 'total_cost', 'is_primary', 'notes', 'active_for_variants'])
            ->withTimestamps();
    }

    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function primaryImage()
    {
        return $this->morphOne(Image::class, 'imageable')
            ->where('is_primary', true);
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    public function scopeDiscontinued(Builder $query): Builder
    {
        return $query->where('status', 'discontinued');
    }

    public function scopeByCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('product_category_id', $categoryId);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('sku', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%");
        });
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'active' => 'Activo',
            'draft' => 'Borrador',
            'discontinued' => 'Descontinuado',
            default => 'Desconocido',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'active' => 'success',
            'draft' => 'warning',
            'discontinued' => 'danger',
            default => 'secondary',
        };
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Total de puntadas del producto.
     * FUENTE: DesignExport específico asignado en product_design.design_export_id
     */
    public function getTotalStitchesAttribute(): int
    {
        $total = 0;
        foreach ($this->designs as $design) {
            // PRIORIDAD 1: Export específico asignado en el pivot
            if ($design->pivot->design_export_id) {
                $export = DesignExport::find($design->pivot->design_export_id);
                if ($export) {
                    $total += $export->stitches_count ?? 0;
                    continue;
                }
            }

            // FALLBACK: Si no hay export específico, usar exports aprobados del diseño
            $designTotal = 0;
            foreach ($design->exports as $export) {
                if ($export->status === 'aprobado') {
                    $designTotal += $export->stitches_count ?? 0;
                }
            }
            // Si no hay aprobados, usar el primero disponible
            if ($designTotal === 0 && $design->exports->count() > 0) {
                $designTotal = $design->exports->first()->stitches_count ?? 0;
            }
            $total += $designTotal;
        }
        return $total;
    }

    public function getLowestVariantPrice(): float
    {
        $variant = $this->variants()->orderBy('price', 'asc')->first();
        return $variant ? (float) $variant->price : 0;
    }

    // REMOVIDO: getBasePriceAttribute() sobreescribía el valor de BD
    // Ahora base_price se lee directamente de la columna en BD

    public function getFormattedBasePriceAttribute(): string
    {
        return '$' . number_format($this->attributes['base_price'] ?? 0, 2);
    }

    public function getExtrasTotal(): float
    {
        return (float) $this->extras->sum('price_addition');
    }

    public function getExtrasTotalAttribute(): float
    {
        return $this->getExtrasTotal();
    }

    public function getVariantsCountAttribute(): int
    {
        return $this->activeVariants()->count();
    }

    public function getPrimaryImageUrlAttribute(): ?string
    {
        $image = $this->primaryImage;
        if ($image) {
            return $image->display_url;
        }

        $firstImage = $this->images->first();
        return $firstImage ? $firstImage->display_url : null;
    }

    /**
     * Indica si este producto PUEDE requerir medidas (según su categoría).
     * NOTA: La decisión de usar medidas la toma el PEDIDO, no el producto.
     * Este accessor solo indica CAPACIDAD, no obligación.
     */
    public function getSupportsMeasurementsAttribute(): bool
    {
        return $this->category?->supports_measurements ?? false;
    }

    /**
     * Indica si el producto tiene tipo asignado (metadata histórica)
     */
    public function getHasProductTypeAttribute(): bool
    {
        return $this->product_type_id !== null;
    }

    /**
     * Nombre del tipo de producto para display
     */
    public function getProductTypeNameAttribute(): ?string
    {
        return $this->productType?->display_name;
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTODOS
    |--------------------------------------------------------------------------
    */

    public function canEdit(): bool
    {
        return in_array($this->status, ['draft', 'active']);
    }

    public function canDelete(): bool
    {
        return $this->status === 'draft' || $this->variants()->count() === 0;
    }

    public function activate(): void
    {
        $this->status = 'active';
        $this->save();
    }

    public function discontinue(): void
    {
        $this->status = 'discontinued';
        $this->save();
    }

    public function setAsDraft(): void
    {
        $this->status = 'draft';
        $this->save();
    }

    public static function generateSku(string $categoryPrefix, string $name): string
    {
        $nameSlug = Str::upper(Str::substr(Str::slug($name, ''), 0, 6));
        $random = Str::upper(Str::random(4));
        return "{$categoryPrefix}-{$nameSlug}-{$random}";
    }
}
