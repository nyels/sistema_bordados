<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'parent_id',
        'order',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer'
    ];

    // Relación: Una categoría puede tener muchas subcategorías
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    // Relación: Una categoría pertenece a una categoría padre
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    // Relación: Una categoría tiene muchos diseños
    public function designs()
    {
        return $this->belongsToMany(Design::class);
    }
    /*
    fillable: Campos que se pueden asignar masivamente
    casts: Convierte automáticamente tipos de datos
    Las relaciones permiten acceder a datos relacionados fácilmente */
}
