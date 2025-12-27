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

    /**
     * Relación con las exportaciones del diseño.
     */
    public function exports()
    {
        return $this->hasMany(DesignExport::class);
    }

    /**
     * Relación con las exportaciones sin variante (generales).
     * Esta relación es CRÍTICA para la funcionalidad de producción.
     * Filtra solo las exportaciones que no están asociadas a una variante específica.
     */
    public function generalExports()
    {
        return $this->hasMany(DesignExport::class)->whereNull('design_variant_id');
    }

    // NUEVA RELACIÓN: Usuario que creó el diseño (para auditoría)
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // NUEVO MÉTODO: Contador de exportaciones (para optimización en vistas)
    public function getExportsCountAttribute()
    {
        // Si ya existe el campo exports_count en la tabla (optimización), usarlo
        if (isset($this->attributes['exports_count'])) {
            return $this->attributes['exports_count'];
        }
        // Si no, contar sobre la relación
        return $this->exports()->count();
    }

    // NUEVO MÉTODO: Verifica si el diseño tiene exportaciones
    public function hasExports()
    {
        return $this->exports()->exists();
    }

    // NUEVO MÉTODO: Verifica si el diseño tiene exportaciones aprobadas
    public function hasApprovedExports()
    {
        return $this->exports()->where('status', 'aprobado')->exists();
    }
}
