<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Application_types extends Model
{
    protected $table = 'application_types';
    protected $fillable = [
        'slug',
        'nombre_aplicacion',
        'descripcion',
        'activo',
        'fecha_baja',
    ];

    /**
     * Boot del modelo para generar slug automáticamente
     */
    protected static function boot()
    {
        parent::boot();

        // Generar slug automáticamente al crear
        static::creating(function ($model) {
            if (empty($model->slug) && !empty($model->nombre_aplicacion)) {
                $model->slug = self::generateSlug($model->nombre_aplicacion);
            }
        });

        // Actualizar slug si cambia el nombre
        static::updating(function ($model) {
            if ($model->isDirty('nombre_aplicacion') && !empty($model->nombre_aplicacion)) {
                $model->slug = self::generateSlug($model->nombre_aplicacion);
            }
        });
    }

    /**
     * Genera un slug a partir del nombre
     * Ejemplo: "Brazo Izquierdo" → "brazo_izquierdo"
     */
    public static function generateSlug(string $nombre): string
    {
        // Convertir a minúsculas
        $slug = Str::lower($nombre);

        // Reemplazar caracteres especiales y acentos
        $slug = Str::ascii($slug);

        // Reemplazar espacios y guiones por guiones bajos
        $slug = preg_replace('/[\s\-]+/', '_', $slug);

        // Eliminar caracteres no alfanuméricos excepto guión bajo
        $slug = preg_replace('/[^a-z0-9_]/', '', $slug);

        // Eliminar guiones bajos múltiples
        $slug = preg_replace('/_+/', '_', $slug);

        // Eliminar guiones bajos al inicio y final
        return trim($slug, '_');
    }

    /**
     * Scope para obtener solo los activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true)->whereNull('fecha_baja');
    }

    /**
     * Relación con diseños
     */
    public function designs()
    {
        return $this->hasMany(Design::class);
    }

    /**
     * Relación con exportaciones de diseño
     */
    public function designExports()
    {
        return $this->hasMany(DesignExport::class, 'application_type', 'slug');
    }
}
