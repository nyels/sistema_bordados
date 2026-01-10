<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use App\Traits\HasActivityLog;

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
        'is_base',
        'compatible_base_unit_id',
        'activo',
    ];

    protected $casts = [
        'is_base' => 'boolean',
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

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function scopeBase(Builder $query): Builder
    {
        return $query->where('is_base', true);
    }

    public function scopePurchase(Builder $query): Builder
    {
        return $query->where('is_base', false);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('name');
    }

    public function scopeCompatibleWith(Builder $query, int $baseUnitId): Builder
    {
        return $query->where('compatible_base_unit_id', $baseUnitId);
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTODOS ESTÁTICOS
    |--------------------------------------------------------------------------
    */

    public static function getPurchaseUnitsFor(int $baseUnitId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('activo', true)
            ->where('is_base', false)
            ->where('compatible_base_unit_id', $baseUnitId)
            ->ordered()
            ->get();
    }

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
