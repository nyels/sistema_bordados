<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttributeValue extends Model
{
    protected $fillable = [
        'attribute_id',
        'value',
        'hex_color',
        'order'
    ];

    protected $casts = [
        'order' => 'integer'
    ];

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
