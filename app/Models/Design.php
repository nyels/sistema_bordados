<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Design extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    // Relación: Un diseño tiene muchas variantes
    public function variants()
    {
        return $this->hasMany(DesignVariant::class);
    }

    // Relación: Un diseño pertenece a muchas categorías
    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    // Relación polimórfica: Un diseño tiene muchas imágenes
    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    // Imagen principal
    public function primaryImage()
    {
        return $this->morphOne(Image::class, 'imageable')->where('is_primary', true);
    }
}
