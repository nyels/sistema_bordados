<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DesignVariant extends Model
{
    protected $fillable = [
        'design_id',
        'sku',
        'name',
        'price',
        'stock',
        'is_active',
        'is_default'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
        'is_active' => 'boolean',
        'is_default' => 'boolean'
    ];

    // Relación: Una variante pertenece a un diseño
    public function design()
    {
        return $this->belongsTo(Design::class);
    }

    // Relación: Una variante tiene muchos valores de atributos
    public function attributeValues()
    {
        return $this->belongsToMany(AttributeValue::class, 'design_variant_attributes');
    }

    // Relación polimórfica: Una variante tiene muchas imágenes
    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    // Imagen principal de la variante
    public function primaryImage()
    {
        return $this->morphOne(Image::class, 'imageable')->where('is_primary', true);
    }
}
