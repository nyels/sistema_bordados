<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
