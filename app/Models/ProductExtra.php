<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ProductExtra extends Model
{
    use SoftDeletes;

    protected $table = 'product_extras';

    protected $fillable = [
        'uuid',
        'name',
        'cost_addition',
        'price_addition',
        'minutes_addition',
    ];

    protected $casts = [
        'cost_addition' => 'decimal:4',
        'price_addition' => 'decimal:4',
        'minutes_addition' => 'integer',
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

    public function products()
    {
        return $this->belongsToMany(
            Product::class,
            'product_extra_assignment',
            'product_extra_id',
            'product_id'
        )->withTimestamps();
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

    /*
    |--------------------------------------------------------------------------
    | MÉTODOS
    |--------------------------------------------------------------------------
    */

    public function canDelete(): bool
    {
        return $this->products()->count() === 0;
    }
}
