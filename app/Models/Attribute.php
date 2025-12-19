<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attribute extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'type',
        'is_required',
        'order'
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'order' => 'integer'
    ];

    // Relación: Un atributo tiene muchos valores
    public function values()
    {
        return $this->hasMany(AttributeValue::class);
    }
}
