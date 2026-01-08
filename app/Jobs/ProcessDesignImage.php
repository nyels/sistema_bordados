<?php

namespace App\Jobs;

use App\Models\Image;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use ColorThief\ColorThief;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ProcessDesignImage implements ShouldQueue
{
    use Queueable;

    // Configuración de thumbnails
    const THUMBNAIL_SMALL = 150;   // Para listados/grids
    const THUMBNAIL_MEDIUM = 400;  // Para galerías/modales
    const WEBP_QUALITY = 85;       // Calidad WebP

    /**
     * Número de intentos antes de fallar
     */
    public $tries = 3;

    /**
     * Tiempo máximo de ejecución (segundos)
     */
    public $timeout = 120;

    /**
     * ID de la imagen a procesar
     */
    protected int $imageId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $imageId)
    {
        $this->imageId = $imageId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $image = Image::find($this->imageId);

        if (!$image) {
            Log::warning("ProcessDesignImage: Image {$this->imageId} not found");
            return;
        }

        $fullPath = Storage::disk('public')->path($image->file_path);

        if (!file_exists($fullPath)) {
            Log::warning("ProcessDesignImage: File not found at {$fullPath}");
            return;
        }

        // 1. Extraer colores con ColorThief
        $this->extractColors($image, $fullPath);

        // 2. Generar thumbnails
        $this->generateThumbnails($image, $fullPath);

        Log::info("ProcessDesignImage: Completed processing for image {$this->imageId}");
    }

    /**
     * Extraer color dominante y paleta de colores
     */
    protected function extractColors(Image $image, string $fullPath): void
    {
        try {
            $dominantColorHex = null;
            $colorPalette = null;

            // Color dominante
            $rgb = ColorThief::getColor($fullPath);
            if ($rgb) {
                $dominantColorHex = sprintf("#%02x%02x%02x", $rgb[0], $rgb[1], $rgb[2]);
            }

            // Paleta de colores (8 colores)
            $palette = ColorThief::getPalette($fullPath, 8);
            if ($palette) {
                $hexPalette = array_map(function ($color) {
                    return sprintf("#%02x%02x%02x", $color[0], $color[1], $color[2]);
                }, $palette);
                $colorPalette = json_encode($hexPalette);
            }

            // Actualizar imagen
            $image->update([
                'dominant_color' => $dominantColorHex,
                'color_palette' => $colorPalette,
            ]);
        } catch (\Exception $e) {
            Log::warning("ProcessDesignImage: ColorThief error - " . $e->getMessage());
        }
    }

    /**
     * Generar thumbnails optimizados en WebP
     */
    protected function generateThumbnails(Image $image, string $fullPath): void
    {
        try {
            $manager = new ImageManager(new Driver());
            $img = $manager->read($fullPath);

            $directory = dirname($image->file_path);
            $baseName = pathinfo($image->file_name, PATHINFO_FILENAME);
            $timestamp = time();
            $storagePath = Storage::disk('public')->path($directory);

            // Asegurar que el directorio existe
            if (!is_dir($storagePath)) {
                mkdir($storagePath, 0755, true);
            }

            // Thumbnail pequeño (150px)
            $smallFileName = "{$baseName}_{$timestamp}_thumb_sm.webp";
            $smallPath = "{$directory}/{$smallFileName}";
            $smallImage = clone $img;
            $smallImage->scaleDown(width: self::THUMBNAIL_SMALL);
            $smallImage->toWebp(self::WEBP_QUALITY)->save("{$storagePath}/{$smallFileName}");

            // Thumbnail mediano (400px)
            $mediumFileName = "{$baseName}_{$timestamp}_thumb_md.webp";
            $mediumPath = "{$directory}/{$mediumFileName}";
            $mediumImage = clone $img;
            $mediumImage->scaleDown(width: self::THUMBNAIL_MEDIUM);
            $mediumImage->toWebp(self::WEBP_QUALITY)->save("{$storagePath}/{$mediumFileName}");

            // Actualizar modelo
            $image->update([
                'thumbnail_small' => $smallPath,
                'thumbnail_medium' => $mediumPath,
                'original_size' => filesize($fullPath),
                'is_optimized' => true,
            ]);
        } catch (\Exception $e) {
            Log::warning("ProcessDesignImage: Thumbnail error - " . $e->getMessage());
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessDesignImage: Job failed for image {$this->imageId} - " . $exception->getMessage());
    }
}
