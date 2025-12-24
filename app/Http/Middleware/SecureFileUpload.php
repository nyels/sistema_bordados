<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SecureFileUpload
{
    /**
     * Magic numbers para formatos de imagen válidos
     */
    private array $validImageSignatures = [
        'jpeg' => [
            'ffd8ffe0',
            'ffd8ffe1',
            'ffd8ffe2',
            'ffd8ffe3',
            'ffd8ffe8',
            'ffd8ffdb',
            'ffd8ffee'
        ],
        'png' => ['89504e470d0a1a0a'],
        'webp' => ['52494646'], // RIFF + WEBP
        'gif' => ['474946383761', '474946383961'],
        'bmp' => ['424d'],
        'tiff' => ['49492a00', '4d4d002a'],
        'avif' => ['6674797061766966', '000000186674797061766966'],
    ];

    /**
     * Magic numbers para formatos vectoriales
     */
    private array $validVectorSignatures = [
        'svg' => ['3c737667', '3c3f786d6c', '3c21444f4354595045'],
    ];

    /**
     * Magic numbers para archivos de bordado
     */
    private array $validEmbroiderySignatures = [
        'pes' => ['23504553', '50455330', '43455031'],
        'dst' => ['4154414a494d41', '4c414a494d41'],
        'exp' => ['45787031', '45787032'],
        'xxx' => ['585858', '53494e474552'],
        'jef' => ['4a4546', '4a454631'],
        'vp3' => ['567033', '4856534d'],
        'hus' => ['4846475631', '4846475632'],
        'pec' => ['504543', '50454330', '43455031'],
        'phc' => ['504843', '43455031'],
        'sew' => ['534557', '53455730'],
        'shv' => ['534856', '53485630'],
        'csd' => ['436865727279'],
        '10o' => ['31306f'],
        'bro' => ['42524f'],
    ];

    /**
     * Firmas de archivos peligrosos (NO PERMITIDOS)
     */
    private array $dangerousSignatures = [
        'exe' => ['4d5a', '5a4d'],
        'elf' => ['7f454c46'],
        'pdf' => ['25504446'],
        'zip' => ['504b0304', '504b0506', '504b0708'],
        'rar' => ['526172211a0700'],
        'php' => ['3c3f706870'],
        'html' => ['3c68746d6c', '3c21444f4354595045'],
        'javascript' => ['3c736372697074'],
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        Log::info('🛡️ MIDDLEWARE SecureFileUpload INICIADO');

        // Solo validar si hay archivos en la petición
        if ($this->hasUploadedFiles($request)) {
            $this->validateAllUploadedFiles($request);
        }

        return $next($request);
    }

    /**
     * Verifica si la petición tiene archivos subidos
     */
    private function hasUploadedFiles(Request $request): bool
    {
        $hasFiles = false;

        foreach ($request->allFiles() as $files) {
            if (is_array($files)) {
                foreach ($files as $file) {
                    if ($file && $file->isValid()) {
                        $hasFiles = true;
                        break 2;
                    }
                }
            } elseif ($files && $files->isValid()) {
                $hasFiles = true;
                break;
            }
        }

        return $hasFiles;
    }

    /**
     * Valida todos los archivos subidos
     */
    private function validateAllUploadedFiles(Request $request): void
    {
        foreach ($request->allFiles() as $fieldName => $files) {
            if (is_array($files)) {
                foreach ($files as $file) {
                    if ($file && $file->isValid()) {
                        $this->validateSingleFile($request, $file, $fieldName);
                    }
                }
            } elseif ($files && $files->isValid()) {
                $this->validateSingleFile($request, $files, $fieldName);
            }
        }
    }

    /**
     * Valida un archivo individual
     */
    private function validateSingleFile(Request $request, $file, string $fieldName): void
    {
        $fileName = $file->getClientOriginalName();
        $fileSize = $file->getSize();
        $fileMime = $file->getMimeType();
        $extension = strtolower($file->getClientOriginalExtension());
        $ip = request()->ip();

        Log::info('🔍 Validando archivo subido', [
            'ip' => $ip,
            'campo' => $fieldName,
            'file_name' => $fileName,
            'file_size' => $this->formatBytes($fileSize),
            'file_mime' => $fileMime,
            'extension' => $extension,
        ]);

        // 1. Validar tamaño máximo (50MB)
        if ($fileSize > 50 * 1024 * 1024) {
            Log::warning('❌ Archivo excede tamaño máximo', [
                'file' => $fileName,
                'tamaño' => $this->formatBytes($fileSize),
                'límite' => '50MB'
            ]);
            abort(422, "El archivo '{$fileName}' excede el tamaño máximo permitido de 50MB.");
        }

        // 2. Obtener y validar firma del archivo
        $signature = $this->getFileSignature($file);

        if (empty($signature)) {
            Log::warning('❌ No se pudo leer la firma del archivo', [
                'file' => $fileName
            ]);
            abort(422, "No se pudo validar el archivo '{$fileName}'. Puede estar corrupto.");
        }

        // 3. Detectar archivos peligrosos
        if ($this->isDangerousFile($signature)) {
            Log::warning('❌ Archivo peligroso detectado', [
                'file' => $fileName,
                'signature' => $signature
            ]);
            abort(422, "El archivo '{$fileName}' no es permitido por razones de seguridad.");
        }

        // 4. Verificar si es un formato permitido
        $detectedType = $this->detectFileType($signature, $file, $extension);

        if (!$detectedType['valid']) {
            Log::warning('❌ Formato no permitido', [
                'file' => $fileName,
                'signature' => $signature,
                'extension' => $extension,
                'detected_type' => $detectedType['type'] ?? 'desconocido'
            ]);

            $message = $detectedType['message'] ?? "El formato del archivo '{$fileName}' no es compatible.";
            abort(422, $message);
        }

        // 5. Log de éxito con información detallada
        Log::info('✅ Archivo validado correctamente', [
            'file' => $fileName,
            'detected_type' => $detectedType['type'],
            'detected_format' => $detectedType['format'] ?? null,
            'extension' => $extension,
            'note' => $detectedType['note'] ?? null
        ]);

        // 6. Agregar información de tipo detectado al request para uso posterior
        $request->merge([
            "_file_{$fieldName}_type" => $detectedType['type'],
            "_file_{$fieldName}_format" => $detectedType['format'] ?? null,
            "_file_{$fieldName}_extension" => $detectedType['correct_extension'] ?? $extension,
            "_file_{$fieldName}_note" => $detectedType['note'] ?? null,
        ]);
    }

    /**
     * Obtiene la firma hexadecimal de un archivo
     */
    private function getFileSignature($file, int $bytes = 64): string
    {
        try {
            $handle = fopen($file->getRealPath(), 'rb');
            if (!$handle) {
                Log::warning('No se pudo abrir el archivo para leer firma', [
                    'file' => $file->getClientOriginalName()
                ]);
                return '';
            }

            $header = fread($handle, $bytes);
            fclose($handle);

            $hexHeader = '';
            for ($i = 0; $i < strlen($header); $i++) {
                $hexHeader .= str_pad(dechex(ord($header[$i])), 2, '0', STR_PAD_LEFT);
            }

            Log::debug('Firma hexadecimal leída', [
                'file' => $file->getClientOriginalName(),
                'primeros_32_bytes' => substr($hexHeader, 0, 64)
            ]);

            return strtolower($hexHeader);
        } catch (\Exception $e) {
            Log::error('Error al leer firma del archivo', [
                'file' => $file->getClientOriginalName(),
                'error' => $e->getMessage()
            ]);
            return '';
        }
    }

    /**
     * Detecta el tipo de archivo basado en la firma
     */
    private function detectFileType(string $signature, $file, string $extension): array
    {
        // VALIDACIÓN ESPECIAL AVIF PRIMERO (antes de verificar otras firmas)
        // AVIF puede empezar con varios tamaños de bloque
        if (preg_match('/^0000001[0-9a-f]66747970/', $signature) ||
            preg_match('/^0000002[0-9a-f]66747970/', $signature)) {
            $avifValid = $this->validateAvif($file);
            if ($avifValid) {
                $note = $extension !== 'avif'
                    ? "El archivo tiene extensión .{$extension} pero es un formato AVIF. Se guardará como .avif"
                    : null;

                return [
                    'valid' => true,
                    'type' => 'image',
                    'format' => 'avif',
                    'correct_extension' => 'avif',
                    'note' => $note
                ];
            }
        }

        // 1. Verificar archivos de imagen
        foreach ($this->validImageSignatures as $type => $signatures) {
            foreach ($signatures as $sig) {
                if (strpos($signature, $sig) === 0) {
                    // Verificación especial para WebP
                    if ($type === 'webp') {
                        $webpValid = $this->validateWebP($file);
                        if (!$webpValid) {
                            return [
                                'valid' => false,
                                'type' => 'webp',
                                'message' => 'El archivo WebP no es válido o está corrupto.'
                            ];
                        }
                    }

                    // Skip AVIF aquí porque ya se validó arriba
                    if ($type === 'avif') {
                        continue;
                    }

                    $correctExtension = $type === 'jpeg' ? 'jpg' : $type;
                    $note = $extension !== $correctExtension
                        ? "El archivo tiene extensión .{$extension} pero es formato {$correctExtension}. Se guardará como .{$correctExtension}"
                        : null;

                    return [
                        'valid' => true,
                        'type' => 'image',
                        'format' => $type,
                        'correct_extension' => $correctExtension,
                        'note' => $note
                    ];
                }
            }
        }

        // 2. Verificar archivos vectoriales (SVG)
        foreach ($this->validVectorSignatures as $type => $signatures) {
            foreach ($signatures as $sig) {
                if (strpos($signature, $sig) === 0) {
                    $svgValid = $this->validateSvg($file);
                    if (!$svgValid) {
                        return [
                            'valid' => false,
                            'type' => 'svg',
                            'message' => 'El archivo SVG no es válido o está mal formado.'
                        ];
                    }

                    $note = $extension !== 'svg'
                        ? "La extensión .{$extension} será corregida a .svg"
                        : null;

                    return [
                        'valid' => true,
                        'type' => 'vector',
                        'format' => 'svg',
                        'correct_extension' => 'svg',
                        'note' => $note
                    ];
                }
            }
        }

        // 3. Verificar archivos de bordado
        foreach ($this->validEmbroiderySignatures as $type => $signatures) {
            foreach ($signatures as $sig) {
                if (strpos($signature, $sig) === 0) {
                    $note = $extension !== $type
                        ? "La extensión .{$extension} será corregida a .{$type}"
                        : null;

                    return [
                        'valid' => true,
                        'type' => 'embroidery',
                        'format' => $type,
                        'correct_extension' => $type,
                        'note' => $note
                    ];
                }
            }
        }

        // 4. Si no coincide con ninguna firma, verificar por extensión (como fallback)
        $allowedExtensions = [
            'image' => ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'avif', 'tiff'],
            'vector' => ['svg', 'svgz'],
            'embroidery' => ['pes', 'dst', 'exp', 'xxx', 'jef', 'vp3', 'hus', 'pec', 'phc', 'sew', 'shv', 'csd', '10o', 'bro']
        ];

        foreach ($allowedExtensions as $type => $exts) {
            if (in_array($extension, $exts)) {
                return [
                    'valid' => true,
                    'type' => $type,
                    'format' => $extension,
                    'correct_extension' => $extension,
                    'note' => 'Validado por extensión (no se pudo verificar la firma)'
                ];
            }
        }

        return [
            'valid' => false,
            'type' => 'desconocido',
            'message' => 'Formato de archivo no compatible. Solo se permiten imágenes, SVG y archivos de bordado.'
        ];
    }

    /**
     * Verifica si es un archivo WebP válido
     */
    private function validateWebP($file): bool
    {
        try {
            $handle = fopen($file->getRealPath(), 'rb');
            fseek($handle, 8);
            $webpHeader = fread($handle, 4);
            fclose($handle);

            return $webpHeader === 'WEBP';
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Verifica si es un archivo AVIF válido
     */
    private function validateAvif($file): bool
    {
        try {
            $handle = fopen($file->getRealPath(), 'rb');
            fseek($handle, 8);
            $brand = fread($handle, 4);
            fclose($handle);

            return $brand === 'avif' || $brand === 'avis' || $brand === 'mif1';
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Verifica si es un archivo SVG válido
     */
    private function validateSvg($file): bool
    {
        try {
            $content = file_get_contents($file->getRealPath(), false, null, 0, 2048);
            return Str::contains($content, ['<svg', '<SVG']);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Detecta archivos peligrosos
     */
    private function isDangerousFile(string $signature): bool
    {
        foreach ($this->dangerousSignatures as $type => $signatures) {
            foreach ($signatures as $sig) {
                if (strpos($signature, $sig) === 0) {
                    Log::warning("Archivo peligroso detectado: {$type}", [
                        'signature' => $signature,
                        'dangerous_type' => $type
                    ]);
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Formatea bytes a formato legible
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
