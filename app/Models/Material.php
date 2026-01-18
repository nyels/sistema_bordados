<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use App\Traits\HasActivityLog;
use App\Models\MaterialCategory;
use App\Models\MaterialVariant;
use App\Models\MaterialUnitConversion;
use App\Models\Unit;

class Material extends Model
{
    use HasActivityLog;

    protected $table = 'materials';

    protected $activityLogNameField = 'name';

    protected $fillable = [
        'uuid',
        'material_category_id',
        'base_unit_id',
        'consumption_unit_id',
        'name',
        'slug',
        'composition',
        'description',
        'has_color',
        'conversion_factor',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'has_color' => 'boolean',
        'conversion_factor' => 'decimal:4',
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
                $model->slug = static::generateUniqueSlug($model->name);
            }
        });

        static::updating(function (self $model): void {
            if ($model->isDirty('name')) {
                $model->slug = static::generateUniqueSlug($model->name, $model->id);
            }
        });
    }

    /**
     * Genera un slug único para el material.
     * Si el slug base ya existe, agrega un sufijo numérico (-1, -2, etc.)
     *
     * @param string $name Nombre del material
     * @param int|null $excludeId ID del material a excluir (para edición)
     * @return string Slug único
     */
    public static function generateUniqueSlug(string $name, ?int $excludeId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        // Buscar si el slug ya existe (excluyendo el material actual si es edición)
        while (true) {
            $query = static::where('slug', $slug);

            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            if (!$query->exists()) {
                break;
            }

            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    public function category()
    {
        return $this->belongsTo(MaterialCategory::class, 'material_category_id');
    }

    public function baseUnit()
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    public function consumptionUnit()
    {
        return $this->belongsTo(Unit::class, 'consumption_unit_id');
    }

    public function variants()
    {
        return $this->hasMany(MaterialVariant::class, 'material_id');
    }

    public function activeVariants()
    {
        return $this->hasMany(MaterialVariant::class, 'material_id')->where('activo', true);
    }

    public function unitConversions()
    {
        return $this->hasMany(MaterialUnitConversion::class, 'material_id');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('name');
    }

    public function scopeByCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('material_category_id', $categoryId);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getFullNameAttribute(): string
    {
        $name = $this->name;
        if ($this->composition) {
            $name .= " ({$this->composition})";
        }
        return $name;
    }

    public function getTotalStockAttribute(): float
    {
        return $this->activeVariants()->sum('current_stock');
    }

    public function getTotalValueAttribute(): float
    {
        return $this->activeVariants()->sum('current_value');
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTODOS
    |--------------------------------------------------------------------------
    */

    public function canDelete(): array
    {
        if ($this->variants()->where('activo', true)->exists()) {
            return [
                'can_delete' => false,
                'message' => 'No se puede eliminar: tiene variantes activas asociadas.',
            ];
        }

        return ['can_delete' => true, 'message' => ''];
    }

    public function hasColor(): bool
    {
        return $this->has_color;
    }

    public function getBaseUnit(): ?Unit
    {
        return $this->baseUnit;
    }
}
