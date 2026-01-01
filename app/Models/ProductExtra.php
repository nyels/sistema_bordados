<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProductExtra extends Model
{
    protected $fillable = ['name', 'cost_addition', 'price_addition', 'minutes_addition'];

    protected static function booted()
    {
        static::creating(fn($model) => $model->uuid = (string) Str::uuid());
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_extra_assignment');
    }
}
