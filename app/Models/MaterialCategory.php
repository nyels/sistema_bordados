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
        'base_unit_id',
        'has_color',
        'activo',
    ];

    protected $casts = [
        'has_color' => 'boolean',
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

    public function baseUnit()
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    public function materials()
    {
        return $this->hasMany(Material::class, 'material_category_id');
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
