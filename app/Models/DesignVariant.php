<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DesignVariant extends Model
{
    use SoftDeletes;

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

    // Una variante pertenece a un diseño
    public function design()
    {
        return $this->belongsTo(Design::class);
    }

    // Valores de atributos (tallas, colores, etc.)
    public function attributeValues()
    {
        return $this->belongsToMany(AttributeValue::class, 'design_variant_attributes');
    }

    // 📸 Imágenes polimórficas
    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    // ⭐ Imagen principal
    public function primaryImage()
    {
        return $this->morphOne(Image::class, 'imageable')
            ->where('is_primary', true);
    }
}
