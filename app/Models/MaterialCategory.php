<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use App\Traits\HasActivityLog;

class MaterialCategory extends Model
{
    use HasActivityLog;

    protected $table = 'material_categories';

    protected $activityLogNameField = 'name';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'default_inventory_unit_id',
        'allow_unit_override',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'allow_unit_override' => 'boolean',
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

    /**
     * Unidad de inventario por defecto para esta categoría.
     * Ejemplo: HILOS → METRO, BOTONES → PIEZA
     */
    public function defaultInventoryUnit()
    {
        return $this->belongsTo(Unit::class, 'default_inventory_unit_id');
    }

    public function materials()
    {
        return $this->hasMany(Material::class, 'material_category_id');
    }

    /**
     * Unidades de compra/presentación permitidas para esta categoría.
     */
    public function allowedUnits()
    {
        return $this->belongsToMany(Unit::class, 'category_unit', 'material_category_id', 'unit_id')
            ->withTimestamps();
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Verificar si permite que materiales usen una unidad diferente a la por defecto.
     */
    public function allowsUnitOverride(): bool
    {
        return $this->allow_unit_override ?? true;
    }

    /**
     * Obtener unidades de inventario disponibles para materiales de esta categoría.
     * Si allow_unit_override es false, solo devuelve la unidad por defecto.
     */
    public function getAvailableInventoryUnits(): \Illuminate\Database\Eloquent\Collection
    {
        if (!$this->allowsUnitOverride() && $this->default_inventory_unit_id) {
            return Unit::where('id', $this->default_inventory_unit_id)->get();
        }

        return Unit::active()->canonical()->ordered()->get();
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

    /*
    |--------------------------------------------------------------------------
    | MÉTODOS
    |--------------------------------------------------------------------------
    */

    public function canDelete(): array
    {
        if ($this->materials()->where('activo', true)->exists()) {
            return [
                'can_delete' => false,
                'message' => 'No se puede eliminar: tiene materiales activos asociados.',
            ];
        }

        return ['can_delete' => true, 'message' => ''];
    }
}
