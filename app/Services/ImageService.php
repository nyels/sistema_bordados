<?php

namespace App\Services;

use App\Models\Image;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use ColorThief\ColorThief; // <--- SE ADAPTA: Importación de la librería instalada

class ImageService
{
    /**
     * Mapeo de tipos MIME a extensiones correctas
     */
    private array $mimeToExtension = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/bmp' => 'bmp',
        'image/webp' => 'webp',
        'image/avif' => 'avif',
        'image/tiff' => 'tiff',
        'image/svg+xml' => 'svg',
        'application/octet-stream' => 'bin',
    ];

    /**
     * Sube una imagen, extrae colores y gestiona el registro en BD.
     */
    public function uploadImage(
        UploadedFile $file,
        string $imageableType,
        int $imageableId,
        array $options = []
    ): Image {
        try {
            Log::info('=== INICIO SUBIDA DE IMAGEN ===');
            Log::info('Tipo: ' . $imageableType);
            Log::info('ID: ' . $imageableId);

            // Validaciones iniciales
            if (!$file->isValid()) {
                throw new \Exception('El archivo subido no es válido');
            }

            if ($file->getSize() === 0) {
                throw new \Exception('El archivo está vacío');
            }

            if ($file->getSize() > 10 * 1024 * 1024) { // 10MB
                throw new \Exception('El archivo excede el tamaño máximo permitido (10MB)');
            }

            // Obtener información del archivo del request (si fue detectada por middleware)
            $request = request();
            $fieldName = 'image'; // Por defecto

            // Buscar el campo que coincide con este archivo
            foreach ($request->allFiles() as $key => $files) {
                if (is_array($files)) {
                    foreach ($files as $f) {
                        if ($f->getClientOriginalName() === $file->getClientOriginalName()) {
                            $fieldName = $key;
                            break 2;
                        }
                    }
                } elseif ($files->getClientOriginalName() === $file->getClientOriginalName()) {
                    $fieldName = $key;
                    break;
                }
            }

            // Usar el FORMATO detectado por el middleware (no la extensión del archivo)
            $detectedFormat = $request->input("_file_{$fieldName}_format");
            $correctExtension = $detectedFormat ? strtolower($detectedFormat) : $this->getCorrectExtension($file);

            Log::info('Detección de formato de imagen', [
                'campo' => $fieldName,
                'formato_detectado' => $detectedFormat ?? 'fallback',
                'extension_original' => $file->getClientOriginalExtension(),
                'extension_final' => $correctExtension,
                'mime_type' => $file->getMimeType()
            ]);

            // Generar nombre con nomenclatura: nombre_designs_principal_timestamp_hash.ext
            $designName  = $options['design_name']  ?? 'design';
            $variantName = $options['variant_name'] ?? null;
            $variantSku  = $options['variant_sku']  ?? null;

            $designSlug  = Str::slug($designName, '_');
            $variantSlug = $variantSku
                ? Str::slug($variantSku, '_')
                : ($variantName ? Str::slug($variantName, '_') : null);

            $isPrimary = $options['is_primary'] ?? false;
            $context   = $options['image_context'] ?? 'design';

            $timestamp = time();
            $hash = uniqid();

            // Formato: nombre_diseño_designs_principal_timestamp_hash.extensión
            if ($context === 'design') {
                $type = $isPrimary ? 'principal' : 'secondary';
                $fileName = "{$designSlug}_designs_{$type}_{$timestamp}_{$hash}.{$correctExtension}";
            } elseif ($context === 'variant') {
                $variantPart = $variantSlug ? "{$variantSlug}" : '';
                $type = $isPrimary ? 'variant_principal' : 'variant';
                $fileName = "{$designSlug}_{$variantPart}_designs_{$type}_{$timestamp}_{$hash}.{$correctExtension}";
            } else {
                $fileName = "{$designSlug}_{$timestamp}_{$hash}.{$correctExtension}";
            }

            Log::info('Nombre generado con extensión corregida: ' . $fileName);
            Log::info('Extensión original: ' . $file->getClientOriginalExtension());
            Log::info('Extensión corregida: ' . $correctExtension);

            // Definir ruta basada en tipo de archivo
            $year = date('Y');
            $month = date('m');
            $detectedType = $request->input("_file_{$fieldName}_type", 'image');

            switch ($detectedType) {
                case 'vector':
                    $path = "designs/vectors/{$year}/{$month}";
                    break;
                case 'embroidery':
                    $path = "designs/embroidery/{$year}/{$month}";
                    break;
                case 'image':
                default:
                    $path = "designs/images/{$year}/{$month}";
                    break;
            }

            Log::info('Ruta destino: ' . $path);

            // Guardar original
            $filePath = $file->storeAs($path, $fileName, 'public');

            if (!$filePath) {
                throw new \Exception('No se pudo guardar el archivo en storage');
            }

            Log::info('Archivo guardado en: ' . $filePath);

            if (!Storage::disk('public')->exists($filePath)) {
                throw new \Exception('El archivo no existe después de guardarlo');
            }

            // Obtener dimensiones (tolerante a errores)
            $fullPath = Storage::disk('public')->path($filePath);
            $imageSize = @getimagesize($fullPath);

            // --- SE ADAPTA: EXTRACCIÓN DE COLOR DOMINANTE Y PALETA COMPLETA ---
            $dominantColorHex = null;
            $colorPalette = null;

            // ACTUALIZACIÓN: Se añade validación de archivo existente antes de procesar con ColorThief
            if ($imageSize !== false && $detectedType === 'image' && file_exists($fullPath)) {
                try {
                    $rgb = ColorThief::getColor($fullPath);
                    if ($rgb) {
                        $dominantColorHex = sprintf("#%02x%02x%02x", $rgb[0], $rgb[1], $rgb[2]);
                        Log::info('Color dominante detectado: ' . $dominantColorHex);
                    }

                    $palette = ColorThief::getPalette($fullPath, 8);
                    if ($palette) {
                        $hexPalette = array_map(function ($color) {
                            return sprintf("#%02x%02x%02x", $color[0], $color[1], $color[2]);
                        }, $palette);
                        // ACTUALIZACIÓN: Se asegura que el JSON sea válido
                        $colorPalette = json_encode($hexPalette);
                        Log::info('Paleta de colores detectada:', $hexPalette);
                    }
                } catch (\Exception $e) {
                    Log::warning('No se pudo extraer el color o paleta: ' . $e->getMessage());
                }
            }

            if ($imageSize === false) {
                Log::warning('⚠️ No se pudieron obtener dimensiones con getimagesize', [
                    'file' => $file->getClientOriginalName()
                ]);
                $imageSize = [null, null];
            }

            // Si es imagen primaria, desactivar las demás
            if ($isPrimary) {
                Log::info('Desactivando otras imágenes primarias');
                Image::where('imageable_type', $imageableType)
                    ->where('imageable_id', $imageableId)
                    ->update(['is_primary' => false]);
            }

            // Obtener el siguiente orden
            $order = $options['order'] ?? $this->getNextOrder($imageableType, $imageableId);
            Log::info('Orden asignado: ' . $order);

            // Crear registro en la base de datos
            $imageData = [
                'imageable_type' => $imageableType,
                'imageable_id' => $imageableId,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $filePath,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'original_extension' => $file->getClientOriginalExtension(),
                'correct_extension' => $correctExtension,
                'width' => $imageSize[0] ?? null,
                'height' => $imageSize[1] ?? null,
                'dominant_color' => $dominantColorHex,
                'color_palette' => $colorPalette,
                'alt_text' => $options['alt_text'] ?? null,
                'is_primary' => $isPrimary,
                'order' => $order,
                'metadata' => json_encode([
                    'detected_type' => $detectedType,
                    'detected_format' => $correctExtension,
                    'upload_note' => $request->input("_file_{$fieldName}_note")
                ])
            ];

            Log::info('Datos de imagen a guardar:', $imageData);

            $image = Image::create($imageData);

            if (!$image) {
                throw new \Exception('No se pudo crear el registro de imagen en la base de datos');
            }

            Log::info('Imagen registrada con ID: ' . $image->id);
            Log::info('=== FIN SUBIDA DE IMAGEN EXITOSA ===');

            return $image;
        } catch (\Exception $e) {
            Log::error('=== ERROR AL SUBIR IMAGEN ===');
            Log::error('Mensaje: ' . $e->getMessage());

            if (isset($filePath) && Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
                Log::info('Archivo eliminado por error en BD');
            }

            throw $e;
        }
    }

    private function getCorrectExtension(UploadedFile $file): string
    {
        $mimeType = $file->getMimeType();
        $originalExtension = strtolower($file->getClientOriginalExtension());

        if (isset($this->mimeToExtension[$mimeType])) {
            $correctExtension = $this->mimeToExtension[$mimeType];
            if ($originalExtension !== $correctExtension) {
                Log::info('Extensión corregida', [
                    'original' => $originalExtension,
                    'correct' => $correctExtension,
                    'mime' => $mimeType
                ]);
            }
            return $correctExtension;
        }

        $embroideryExtensions = ['pes', 'dst', 'exp', 'xxx', 'jef', 'vp3', 'hus', 'pec', 'phc', 'sew', 'shv', 'csd', '10o', 'bro'];
        if (in_array($originalExtension, $embroideryExtensions)) {
            return $originalExtension;
        }

        return $originalExtension ?: 'bin';
    }

    public function deleteImage(Image $image): bool
    {
        try {
            Log::info('Eliminando imagen ID: ' . $image->id);
            if (Storage::disk('public')->exists($image->file_path)) {
                Storage::disk('public')->delete($image->file_path);
                Log::info('Archivo físico eliminado: ' . $image->file_path);
            }
            $this->deleteThumbnails($image);
            return $image->delete();
        } catch (\Exception $e) {
            Log::error('Error al eliminar imagen: ' . $e->getMessage());
            throw $e;
        }
    }

    public function reorderImages(array $images): bool
    {
        try {
            foreach ($images as $imageData) {
                Image::where('id', $imageData['id'])
                    ->update(['order' => $imageData['order']]);
            }
            Log::info('Imágenes reordenadas correctamente');
            return true;
        } catch (\Exception $e) {
            Log::error('Error al reordenar imágenes: ' . $e->getMessage());
            throw $e;
        }
    }

    public function setPrimaryImage(Image $image): Image
    {
        try {
            Image::where('imageable_type', $image->imageable_type)
                ->where('imageable_id', $image->imageable_id)
                ->where('id', '!=', $image->id)
                ->update(['is_primary' => false]);

            $image->update(['is_primary' => true]);
            return $image->fresh();
        } catch (\Exception $e) {
            Log::error('Error al establecer imagen primaria: ' . $e->getMessage());
            throw $e;
        }
    }

    private function getNextOrder(string $imageableType, int $imageableId): int
    {
        $maxOrder = Image::where('imageable_type', $imageableType)
            ->where('imageable_id', $imageableId)
            ->max('order');

        // ACTUALIZACIÓN: Se asegura el retorno de un entero base 0
        return ($maxOrder === null) ? 0 : (int)$maxOrder + 1;
    }

    private function deleteThumbnails(Image $image): void
    {
        try {
            $pathInfo = pathinfo($image->file_path);
            $sizes = ['small', 'medium', 'large'];

            foreach ($sizes as $size) {
                $thumbnailPath = "designs/thumbnails/{$size}/" . $pathInfo['basename'];
                if (Storage::disk('public')->exists($thumbnailPath)) {
                    Storage::disk('public')->delete($thumbnailPath);
                    Log::info('Thumbnail eliminado: ' . $thumbnailPath);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Error al eliminar thumbnails: ' . $e->getMessage());
        }
    }
}
