<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class AttributeValue extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'attribute_id',
        'value',
        'hex_color',
        'order'
    ];
    protected $dates = ['deleted_at'];

    protected $casts = [
        'order' => 'integer'
    ];



    public const TALLAS_ORDENADAS = [
        'XXS' => 1,
        'XS' => 2,
        'S' => 3,
        'CH' => 3, // CH y S son lo mismo
        'M' => 4,
        'L' => 5,
        'G' => 5,
        'XL' => 6,
        'XG' => 6,
        'XXL' => 7
    ];
    /**
     * GLOBAL SCOPE DE ORDENAMIENTO
     * Esto hace que CUALQUIER consulta a AttributeValue salga ordenada por 'order'.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $valor = strtoupper($model->value);

            // Si el valor existe en nuestro diccionario, le asigna su peso
            if (isset(self::TALLAS_ORDENADAS[$valor])) {
                $model->order = self::TALLAS_ORDENADAS[$valor];
            } else {
                // Si es una talla nueva/extraña, la manda al final
                $model->order = static::where('attribute_id', $model->attribute_id)->max('order') + 1;
            }
        });
    }

    // Relación: Un valor pertenece a un atributo
    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }

    // Relación: Un valor está en muchas variantes
    public function designVariants()
    {
        return $this->belongsToMany(DesignVariant::class, 'design_variant_attributes');
    }
}
