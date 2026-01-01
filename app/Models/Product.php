<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = ['product_category_id', 'name', 'sku', 'specifications', 'status', 'tenant_id'];

    protected $casts = [
        'specifications' => 'array',
    ];

    protected static function booted()
    {
        static::creating(fn($model) => $model->uuid = (string) Str::uuid());
    }

    // Un producto pertenece a una categoría
    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    // Un producto tiene muchas variantes (Tallas/Colores)
    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    // Extras permitidos para este producto (Alforzas, etc.)
    public function extras()
    {
        return $this->belongsToMany(ProductExtra::class, 'product_extra_assignment');
    }

    // En el modelo Product.php
    public function designs()
    {
        // Esto te permite obtener todos los diseños del producto 
        // saltando a través de las variantes
        return $this->hasManyThrough(
            DesignExport::class,
            ProductVariant::class,
            'product_id', // FK en product_variants
            'design_variant_id', // FK en design_exports (o tu tabla pivote)
            'id',
            'id'
        );
    }
}
