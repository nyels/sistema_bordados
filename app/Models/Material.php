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
        'name',
        'slug',
        'composition',
        'description',
        'activo',
    ];

    protected $casts = [
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

    public function category()
    {
        return $this->belongsTo(MaterialCategory::class, 'material_category_id');
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
        return $this->category?->has_color ?? false;
    }

    public function getBaseUnit(): ?Unit
    {
        return $this->category?->baseUnit;
    }

    public function conversions()
    {
        return $this->hasMany(MaterialUnitConversion::class, 'material_id');
    }
}
