<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class Image extends Model
{
    // Configuración de thumbnails
    const THUMBNAIL_SMALL = 150;
    const THUMBNAIL_MEDIUM = 400;
    const WEBP_QUALITY = 85;

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
        'is_primary',
        'dominant_color',
        'color_palette',
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
     * Si no existe, lo genera automáticamente (LAZY LOADING).
     */
    public function getThumbnailSmallUrlAttribute(): ?string
    {
        if (!$this->file_path) {
            return null;
        }

        // Si ya existe el thumbnail, usarlo
        if ($this->thumbnail_small && Storage::disk('public')->exists($this->thumbnail_small)) {
            return asset('storage/' . $this->thumbnail_small);
        }

        // Lazy generation: intentar generar si no existe
        $thumbnailPath = $this->generateThumbnailIfNeeded('small');

        if ($thumbnailPath) {
            return asset('storage/' . $thumbnailPath);
        }

        // Fallback: imagen original
        return asset('storage/' . $this->file_path);
    }

    /**
     * Obtiene la URL del thumbnail mediano (para galerías).
     * Si no existe, lo genera automáticamente (LAZY LOADING).
     */
    public function getThumbnailMediumUrlAttribute(): ?string
    {
        if (!$this->file_path) {
            return null;
        }

        // Si ya existe el thumbnail, usarlo
        if ($this->thumbnail_medium && Storage::disk('public')->exists($this->thumbnail_medium)) {
            return asset('storage/' . $this->thumbnail_medium);
        }

        // Lazy generation: intentar generar si no existe
        $thumbnailPath = $this->generateThumbnailIfNeeded('medium');

        if ($thumbnailPath) {
            return asset('storage/' . $thumbnailPath);
        }

        // Fallback: imagen original
        return asset('storage/' . $this->file_path);
    }

    /**
     * Genera thumbnail si no existe (LAZY LOADING).
     * Solo genera una vez, luego lo cachea en BD.
     */
    protected function generateThumbnailIfNeeded(string $size): ?string
    {
        try {
            $fullPath = Storage::disk('public')->path($this->file_path);

            // Verificar que el archivo original existe
            if (!file_exists($fullPath)) {
                return null;
            }

            // Verificar que es una imagen válida
            $imageInfo = @getimagesize($fullPath);
            if ($imageInfo === false) {
                return null;
            }

            $manager = new ImageManager(new Driver());
            $img = $manager->read($fullPath);

            $directory = dirname($this->file_path);
            $baseName = pathinfo($this->file_name ?? $this->file_path, PATHINFO_FILENAME);
            $timestamp = time();
            $storagePath = Storage::disk('public')->path($directory);

            // Asegurar que el directorio existe
            if (!is_dir($storagePath)) {
                @mkdir($storagePath, 0755, true);
            }

            if ($size === 'small') {
                $fileName = "{$baseName}_{$timestamp}_thumb_sm.webp";
                $thumbPath = "{$directory}/{$fileName}";
                $thumbImage = clone $img;
                $thumbImage->scaleDown(width: self::THUMBNAIL_SMALL);
                $thumbImage->toWebp(self::WEBP_QUALITY)->save("{$storagePath}/{$fileName}");

                // Guardar en BD para no regenerar
                $this->update(['thumbnail_small' => $thumbPath, 'is_optimized' => true]);
                return $thumbPath;
            }

            if ($size === 'medium') {
                $fileName = "{$baseName}_{$timestamp}_thumb_md.webp";
                $thumbPath = "{$directory}/{$fileName}";
                $thumbImage = clone $img;
                $thumbImage->scaleDown(width: self::THUMBNAIL_MEDIUM);
                $thumbImage->toWebp(self::WEBP_QUALITY)->save("{$storagePath}/{$fileName}");

                // Guardar en BD para no regenerar
                $this->update(['thumbnail_medium' => $thumbPath, 'is_optimized' => true]);
                return $thumbPath;
            }

            return null;
        } catch (\Exception $e) {
            // Si falla, simplemente usar imagen original
            return null;
        }
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
        // Usar los accessors que hacen lazy loading
        return $this->thumbnail_medium_url ?? $this->thumbnail_small_url ?? $this->original_url;
    }
}
