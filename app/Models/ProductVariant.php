<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProductVariant extends Model
{
    protected $fillable = ['uuid', 'product_id', 'sku_variant', 'price', 'stock_alert'];
    protected static function booted()
    {
        static::creating(fn($model) => $model->uuid = (string) Str::uuid());
    }

    /**
     * CALCULO DE PRECIO TOTAL
     * Suma el precio de la variante + los extras del producto padre
     */
    public function getTotalConExtras()
    {
        // Accedemos a los extras a través de la relación product()
        $sumaExtras = $this->product->extras->sum('price_addition');
        return $this->price + $sumaExtras;
    }
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Relación con Atributos (Color, Tela, etc.)
    public function attributes()
    {
        return $this->belongsToMany(AttributeValue::class, 'product_variant_attribute', 'product_variant_id', 'attribute_value_id')
            ->withPivot('attribute_id');
    }

    // LA RELACIÓN CLAVE: Conexión con los archivos de bordado (design_exports)
    public function designExports()
    {
        return $this->belongsToMany(DesignExport::class, 'product_variant_design')
            ->withPivot('application_type_id', 'notes')
            ->withTimestamps();
    }
}
