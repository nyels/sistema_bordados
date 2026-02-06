<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ProductExtra extends Model
{
    use SoftDeletes;

    protected $table = 'product_extras';

    protected $fillable = [
        'uuid',
        'name',
        'extra_category_id',
        'cost_addition',
        'price_addition',
        'minutes_addition',
        'consumes_inventory',
    ];

    protected $casts = [
        'cost_addition' => 'decimal:4',
        'price_addition' => 'decimal:4',
        'minutes_addition' => 'integer',
        'consumes_inventory' => 'boolean',
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
        });
    }

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    /**
     * Categoría del extra.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ExtraCategory::class, 'extra_category_id');
    }

    public function products()
    {
        return $this->belongsToMany(
            Product::class,
            'product_extra_assignment',
            'product_extra_id',
            'product_id'
        )->withTimestamps();
    }

    /**
     * Materiales que consume este extra (solo si consumes_inventory = true).
     * Relación con variantes de material del inventario.
     */
    public function materials(): BelongsToMany
    {
        return $this->belongsToMany(
            MaterialVariant::class,
            'product_extra_materials',
            'product_extra_id',
            'material_variant_id'
        )->withPivot('quantity_required')->withTimestamps();
    }

    /**
     * Historial de uso de este extra en pedidos.
     * Útil para reportes y estadísticas de consumo.
     */
    public function orderItemExtras(): HasMany
    {
        return $this->hasMany(OrderItemExtra::class, 'product_extra_id');
    }

    /**
     * Obtiene el total de veces que se ha usado este extra.
     */
    public function getTotalUsageCountAttribute(): int
    {
        return $this->orderItemExtras()->sum('quantity');
    }

    /**
     * Obtiene el ingreso total generado por este extra.
     */
    public function getTotalRevenueAttribute(): float
    {
        return (float) $this->orderItemExtras()->sum('total_price');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('name', 'asc');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    /**
     * Convierte el nombre a mayúsculas correctamente (con soporte UTF-8).
     * Esto corrige casos donde caracteres especiales como ñ no se convirtieron bien.
     */
    public function getNameAttribute($value): string
    {
        return mb_strtoupper($value, 'UTF-8');
    }

    public function getFormattedCostAttribute(): string
    {
        return '$' . number_format($this->cost_addition, 2);
    }

    public function getFormattedPriceAttribute(): string
    {
        return '+$' . number_format($this->price_addition, 2);
    }

    public function getFormattedMinutesAttribute(): string
    {
        if ($this->minutes_addition <= 0) {
            return '-';
        }

        $hours = floor($this->minutes_addition / 60);
        $minutes = $this->minutes_addition % 60;

        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }

        return "{$minutes}m";
    }

    public function getProductsCountAttribute(): int
    {
        return $this->products()->count();
    }

    /**
     * Obtiene un resumen de los materiales con sus cantidades y unidades.
     * Formato: "1 m - Encaje (Blanco)" o "-" si no consume
     */
    public function getMaterialsSummaryAttribute(): string
    {
        if (!$this->consumes_inventory) {
            return '-';
        }

        $materials = $this->materials;

        if ($materials->isEmpty()) {
            return '<span class="text-warning"><i class="fas fa-exclamation-triangle"></i> Sin materiales</span>';
        }

        $items = [];
        foreach ($materials as $variant) {
            $qty = $variant->pivot->quantity_required;
            $unit = '';

            // Obtener la unidad de consumo del material
            if ($variant->material && $variant->material->consumptionUnit) {
                $unit = $variant->material->consumptionUnit->symbol;
            } elseif ($variant->material && $variant->material->baseUnit) {
                $unit = $variant->material->baseUnit->symbol;
            }

            $materialName = $variant->material ? $variant->material->name : 'Material';
            $colorName = $variant->color ? " ({$variant->color})" : '';

            $qtyFormatted = number_format((float) $qty, 2);
            $items[] = "{$qtyFormatted} {$unit} - {$materialName}{$colorName}";
        }

        return implode('<br>', $items);
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

    /*
    |--------------------------------------------------------------------------
    | MÉTODOS DE INVENTARIO
    |--------------------------------------------------------------------------
    */

    /**
     * Indica si este extra consume materiales del inventario.
     */
    public function consumesInventory(): bool
    {
        return $this->consumes_inventory === true;
    }

    /**
     * Indica si este extra tiene materiales asignados.
     */
    public function hasMaterials(): bool
    {
        return $this->materials()->count() > 0;
    }

    /**
     * Obtiene los requerimientos de materiales para una cantidad dada del extra.
     *
     * @param int $quantity Cantidad de veces que se aplica el extra
     * @return array [material_variant_id => quantity_required]
     */
    public function getMaterialRequirements(int $quantity = 1): array
    {
        if (!$this->consumesInventory()) {
            return [];
        }

        $requirements = [];

        foreach ($this->materials as $material) {
            $variantId = $material->id;
            $requiredQty = (float) $material->pivot->quantity_required * $quantity;

            if ($requiredQty > 0) {
                $requirements[$variantId] = ($requirements[$variantId] ?? 0) + $requiredQty;
            }
        }

        return $requirements;
    }

    /**
     * Sincroniza los materiales del extra.
     * Solo tiene efecto si consumes_inventory = true.
     *
     * @param array $materials Array de [material_variant_id => quantity_required]
     */
    public function syncMaterials(array $materials): void
    {
        if (!$this->consumesInventory()) {
            // Si no consume inventario, eliminar todos los materiales
            $this->materials()->detach();
            return;
        }

        $syncData = [];
        foreach ($materials as $variantId => $quantity) {
            if ($quantity > 0) {
                $syncData[$variantId] = ['quantity_required' => $quantity];
            }
        }

        $this->materials()->sync($syncData);
    }

    /**
     * Valida que el extra tenga configuración válida de inventario.
     *
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateInventoryConfig(): array
    {
        $errors = [];

        if ($this->consumesInventory() && !$this->hasMaterials()) {
            $errors[] = 'Este extra está marcado como consumidor de inventario pero no tiene materiales asignados.';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}
