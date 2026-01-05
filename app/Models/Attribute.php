<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // 1. Importar para borrado lógico
use Illuminate\Support\Str;

class Attribute extends Model
{
    use SoftDeletes; // 2. Usar el trait para borrado lógico

    protected $fillable = [
        'name',
        'slug',
        'type',
        'is_required',
        'order'
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'order' => 'integer',
        'deleted_at' => 'datetime' // 3. Cast de fecha para Laravel 10/11/12
    ];
    /**
     * Boot del modelo para asegurar que el slug sea siempre correcto
     */
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($attribute) {
            if (empty($attribute->slug)) {
                $attribute->slug = Str::slug($attribute->name);
            }
        });
    }

    /**
     * Relación: Un atributo tiene muchos valores 
     */
    public function values()
    {
        // Añadimos el orden por defecto para que los colores/tallas salgan siempre organizados
        return $this->hasMany(AttributeValue::class)->orderBy('order', 'asc');
    }
}
