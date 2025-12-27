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

    // NUEVA RELACIÓN: Una variante tiene muchas exportaciones (archivos de producción)
    /**
     * Relación con las exportaciones de la variante.
     * Esta relación es CRÍTICA para la funcionalidad de producción.
     * Usa la columna 'design_variant_id' como clave foránea.
     */
    public function exports()
    {
        return $this->hasMany(DesignExport::class, 'design_variant_id');
    }

    // NUEVA RELACIÓN: Usuario que creó la variante (para auditoría)
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

    // NUEVO MÉTODO: Verifica si la variante tiene exportaciones
    public function hasExports()
    {
        return $this->exports()->exists();
    }

    // NUEVO MÉTODO: Verifica si la variante tiene exportaciones aprobadas
    public function hasApprovedExports()
    {
        return $this->exports()->where('status', 'aprobado')->exists();
    }
}
