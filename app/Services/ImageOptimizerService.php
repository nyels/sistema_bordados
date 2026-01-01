<?php

namespace App\Services;

use App\Models\Image;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageOptimizerService
{
    protected ImageManager $manager;

    // Configuración de tamaños de thumbnail
    const THUMBNAIL_SMALL = 150;   // Para listados/grids
    const THUMBNAIL_MEDIUM = 400;  // Para galerías/modales
    const JPEG_QUALITY = 85;       // Calidad JPEG (80-90 es óptimo)

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
    }

    /**
     * Procesa una imagen: guarda original y genera thumbnails.
     *
     * @param UploadedFile $file Archivo subido
     * @param string $directory Directorio destino (ej: 'designs', 'variants')
     * @return array Datos de la imagen procesada
     */
    public function processUploadedImage(UploadedFile $file, string $directory = 'images'): array
    {
        try {
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $baseName = pathinfo($originalName, PATHINFO_FILENAME);
            $timestamp = time();
            $uniqueName = "{$baseName}_{$timestamp}";

            // Crear directorio si no existe
            $storagePath = "public/{$directory}/" . date('Y/m');
            Storage::makeDirectory($storagePath);

            // 1. Guardar imagen ORIGINAL (sin modificar)
            $originalFileName = "{$uniqueName}.{$extension}";
            $originalPath = "{$directory}/" . date('Y/m') . "/{$originalFileName}";
            $file->storeAs("public/{$directory}/" . date('Y/m'), $originalFileName);

            // Obtener tamaño original
            $originalSize = $file->getSize();

            // 2. Generar thumbnails
            $thumbnails = $this->generateThumbnails($file, $directory, $uniqueName);

            // 3. Obtener dimensiones de la imagen original
            $dimensions = $this->getImageDimensions($file);

            return [
                'success' => true,
                'file_name' => $originalFileName,
                'file_path' => $originalPath,
                'thumbnail_small' => $thumbnails['small'],
                'thumbnail_medium' => $thumbnails['medium'],
                'original_size' => $originalSize,
                'file_size' => $originalSize,
                'mime_type' => $file->getMimeType(),
                'width' => $dimensions['width'],
                'height' => $dimensions['height'],
                'is_optimized' => true,
            ];
        } catch (\Exception $e) {
            Log::error('Error procesando imagen: ' . $e->getMessage());

            // Si falla la optimización, al menos guardar el original
            return $this->fallbackSave($file, $directory);
        }
    }

    /**
     * Genera thumbnails para una imagen.
     */
    protected function generateThumbnails(UploadedFile $file, string $directory, string $baseName): array
    {
        $thumbnails = ['small' => null, 'medium' => null];
        $storagePath = storage_path("app/public/{$directory}/" . date('Y/m'));

        try {
            // Crear imagen desde archivo
            $image = $this->manager->read($file->getPathname());

            // Thumbnail pequeño (150px)
            $smallPath = "{$directory}/" . date('Y/m') . "/{$baseName}_thumb_sm.webp";
            $smallImage = clone $image;
            $smallImage->scaleDown(width: self::THUMBNAIL_SMALL);
            $smallImage->toWebp(self::JPEG_QUALITY)->save("{$storagePath}/{$baseName}_thumb_sm.webp");
            $thumbnails['small'] = $smallPath;

            // Thumbnail mediano (400px)
            $mediumPath = "{$directory}/" . date('Y/m') . "/{$baseName}_thumb_md.webp";
            $mediumImage = clone $image;
            $mediumImage->scaleDown(width: self::THUMBNAIL_MEDIUM);
            $mediumImage->toWebp(self::JPEG_QUALITY)->save("{$storagePath}/{$baseName}_thumb_md.webp");
            $thumbnails['medium'] = $mediumPath;

        } catch (\Exception $e) {
            Log::warning('Error generando thumbnails: ' . $e->getMessage());
        }

        return $thumbnails;
    }

    /**
     * Obtiene las dimensiones de una imagen.
     */
    protected function getImageDimensions(UploadedFile $file): array
    {
        try {
            $image = $this->manager->read($file->getPathname());
            return [
                'width' => $image->width(),
                'height' => $image->height(),
            ];
        } catch (\Exception $e) {
            return ['width' => null, 'height' => null];
        }
    }

    /**
     * Fallback: guardar solo el original si falla la optimización.
     */
    protected function fallbackSave(UploadedFile $file, string $directory): array
    {
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $timestamp = time();
        $uniqueName = "{$baseName}_{$timestamp}.{$extension}";

        $path = $file->storeAs("public/{$directory}/" . date('Y/m'), $uniqueName);
        $relativePath = str_replace('public/', '', $path);

        return [
            'success' => true,
            'file_name' => $uniqueName,
            'file_path' => $relativePath,
            'thumbnail_small' => null,
            'thumbnail_medium' => null,
            'original_size' => $file->getSize(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'width' => null,
            'height' => null,
            'is_optimized' => false,
        ];
    }

    /**
     * Optimiza una imagen existente (genera thumbnails).
     * Útil para procesar imágenes ya subidas.
     */
    public function optimizeExistingImage(Image $image): bool
    {
        try {
            if ($image->is_optimized) {
                return true; // Ya está optimizada
            }

            $originalPath = storage_path("app/public/{$image->file_path}");

            if (!file_exists($originalPath)) {
                Log::warning("Imagen no encontrada: {$originalPath}");
                return false;
            }

            $directory = dirname($image->file_path);
            // Usar el basename del file_path (no file_name) para consistencia
            $baseName = pathinfo($image->file_path, PATHINFO_FILENAME);
            $storagePath = storage_path("app/public/{$directory}");

            // Crear imagen desde archivo existente
            $img = $this->manager->read($originalPath);

            // Thumbnail pequeño
            $smallPath = "{$directory}/{$baseName}_thumb_sm.webp";
            $smallImage = clone $img;
            $smallImage->scaleDown(width: self::THUMBNAIL_SMALL);
            $smallImage->toWebp(self::JPEG_QUALITY)->save("{$storagePath}/{$baseName}_thumb_sm.webp");

            // Thumbnail mediano
            $mediumPath = "{$directory}/{$baseName}_thumb_md.webp";
            $mediumImage = clone $img;
            $mediumImage->scaleDown(width: self::THUMBNAIL_MEDIUM);
            $mediumImage->toWebp(self::JPEG_QUALITY)->save("{$storagePath}/{$baseName}_thumb_md.webp");

            // Actualizar modelo
            $image->update([
                'thumbnail_small' => $smallPath,
                'thumbnail_medium' => $mediumPath,
                'original_size' => filesize($originalPath),
                'width' => $img->width(),
                'height' => $img->height(),
                'is_optimized' => true,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Error optimizando imagen {$image->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Elimina una imagen y sus thumbnails.
     */
    public function deleteImage(Image $image): bool
    {
        try {
            // Eliminar original
            if ($image->file_path) {
                Storage::disk('public')->delete($image->file_path);
            }

            // Eliminar thumbnails
            if ($image->thumbnail_small) {
                Storage::disk('public')->delete($image->thumbnail_small);
            }
            if ($image->thumbnail_medium) {
                Storage::disk('public')->delete($image->thumbnail_medium);
            }

            return true;
        } catch (\Exception $e) {
            Log::error("Error eliminando imagen {$image->id}: " . $e->getMessage());
            return false;
        }
    }
}
