<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    protected $fillable = [
        'imageable_type',
        'imageable_id',
        'file_name',
        'file_path',
        'thumbnail_small',
        'thumbnail_medium',
        'is_optimized',
        'original_size',
        'file_size',
        'mime_type',
        'width',
        'height',
        'alt_text',
        'order',
        'is_primary'
    ];

    protected $casts = [
        'file_size' => 'integer',
        'original_size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'order' => 'integer',
        'is_primary' => 'boolean',
        'is_optimized' => 'boolean'
    ];

    // Relación polimórfica inversa
    public function imageable()
    {
        return $this->morphTo();
    }

    /**
     * Relación con las producciones/exportaciones vinculadas a esta imagen.
     */
    public function exports()
    {
        return $this->hasMany(DesignExport::class);
    }

    /**
     * Contador de producciones para esta imagen.
     */
    public function getExportsCountAttribute()
    {
        return $this->exports()->count();
    }

    /**
     * Obtiene la URL del thumbnail pequeño (para listados).
     * Si no existe, devuelve la imagen original.
     */
    public function getThumbnailSmallUrlAttribute(): ?string
    {
        if ($this->thumbnail_small) {
            return asset('storage/' . $this->thumbnail_small);
        }
        return $this->file_path ? asset('storage/' . $this->file_path) : null;
    }

    /**
     * Obtiene la URL del thumbnail mediano (para galerías).
     * Si no existe, devuelve la imagen original.
     */
    public function getThumbnailMediumUrlAttribute(): ?string
    {
        if ($this->thumbnail_medium) {
            return asset('storage/' . $this->thumbnail_medium);
        }
        return $this->file_path ? asset('storage/' . $this->file_path) : null;
    }

    /**
     * Obtiene la URL de la imagen original (para descargas).
     */
    public function getOriginalUrlAttribute(): ?string
    {
        return $this->file_path ? asset('storage/' . $this->file_path) : null;
    }

    /**
     * Obtiene la mejor imagen disponible para visualización.
     * Prioridad: thumbnail_medium > thumbnail_small > original
     */
    public function getDisplayUrlAttribute(): ?string
    {
        if ($this->thumbnail_medium) {
            return asset('storage/' . $this->thumbnail_medium);
        }
        if ($this->thumbnail_small) {
            return asset('storage/' . $this->thumbnail_small);
        }
        return $this->file_path ? asset('storage/' . $this->file_path) : null;
    }
}
