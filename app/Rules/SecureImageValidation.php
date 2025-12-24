<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class SecureImageValidation implements Rule
{
    /**
     * Magic numbers para formatos de imagen
     */
    private $imageSignatures = [
        'jpeg' => [
            "\xFF\xD8\xFF\xE0",
            "\xFF\xD8\xFF\xE1",
            "\xFF\xD8\xFF\xE2",
            "\xFF\xD8\xFF\xE8"
        ],
        'png' => ["\x89\x50\x4E\x47\x0D\x0A\x1A\x0A"],
        'webp' => ["RIFF"],
        'gif' => ["GIF87a", "GIF89a"],
        'bmp' => ["BM"],
        'tiff' => ["II\x2A\x00", "MM\x00\x2A"],
    ];

    /**
     * Firmas de archivos peligrosos
     */
    private $dangerousSignatures = [
        'executable' => [
            "\x4D\x5A", // MZ (DOS/Windows)
            "\x7F\x45\x4C\x46", // ELF (Unix/Linux)
            "\x23\x21", // Shebang (#!)
        ],
        'archive' => [
            "\x50\x4B\x03\x04", // ZIP
            "\x52\x61\x72\x21", // RAR
            "\x37\x7A\xBC\xAF\x27\x1C", // 7z
        ],
        'script' => [
            "\x3C\x3F\x70\x68\x70", // <?php
            "\x3C\x73\x63\x72\x69\x70\x74", // <script
            "\x3C\x3F\x78\x6D\x6C", // <?xml
        ],
        'document' => [
            "\x25\x50\x44\x46", // %PDF
            "\xD0\xCF\x11\xE0", // Microsoft Office
        ]
    ];

    private $detectedDangerousType = null;

    /**
     * Determine if the validation rule passes.
     */
    public function passes($attribute, $value): bool
    {
        if (!$value instanceof UploadedFile) {
            return false;
        }

        try {
            $filePath = $value->getRealPath();

            // 1. Verificar que sea un archivo legítimo
            if (!is_uploaded_file($filePath)) {
                Log::warning('Intento de subida no válida', ['file' => $value->getClientOriginalName()]);
                return false;
            }

            // 2. Verificar firma binaria
            if (!$this->validateBinarySignature($filePath)) {
                Log::warning('Firma binaria inválida', ['file' => $value->getClientOriginalName()]);
                return false;
            }

            // 3. Verificar que no sea un archivo peligroso
            if ($this->isDangerousFile($filePath)) {
                Log::warning('Archivo peligroso detectado', [
                    'file' => $value->getClientOriginalName(),
                    'type' => $this->detectedDangerousType
                ]);
                return false;
            }

            // 4. Validar con GD o Imagick para asegurar que es imagen renderizable
            if (!$this->validateWithImageLibrary($filePath)) {
                Log::warning('No se puede renderizar como imagen', ['file' => $value->getClientOriginalName()]);
                return false;
            }

            // 5. Verificar dimensiones
            $dimensions = $this->getImageDimensions($filePath);
            if (!$dimensions || $dimensions['width'] < 50 || $dimensions['height'] < 50) {
                Log::warning('Dimensiones inválidas', [
                    'file' => $value->getClientOriginalName(),
                    'dimensions' => $dimensions
                ]);
                return false;
            }

            // 6. Verificar ratio de aspecto (opcional, para prevenir imágenes deformadas)
            $aspectRatio = $dimensions['width'] / $dimensions['height'];
            if ($aspectRatio < 0.1 || $aspectRatio > 10) {
                Log::warning('Ratio de aspecto extremo', [
                    'file' => $value->getClientOriginalName(),
                    'ratio' => $aspectRatio
                ]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Error en validación de imagen', [
                'file' => $value->getClientOriginalName(),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Validar firma binaria (magic numbers)
     */
    private function validateBinarySignature(string $filePath): bool
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return false;
        }

        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            return false;
        }

        // Leer primeros 16 bytes
        $header = fread($handle, 16);
        fclose($handle);

        if (strlen($header) < 4) {
            return false;
        }

        // Verificar si es una imagen válida
        foreach ($this->imageSignatures as $format => $signatures) {
            foreach ($signatures as $signature) {
                if (strpos($header, $signature) === 0) {
                    // Verificación especial para WebP
                    if ($format === 'webp' && strlen($header) >= 12) {
                        if (substr($header, 8, 4) !== 'WEBP') {
                            return false;
                        }
                    }
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Detectar archivos peligrosos
     */
    private function isDangerousFile(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            return false;
        }

        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            return false;
        }

        $header = fread($handle, 32);
        fclose($handle);

        foreach ($this->dangerousSignatures as $type => $signatures) {
            foreach ($signatures as $signature) {
                if (strpos($header, $signature) === 0) {
                    $this->detectedDangerousType = $type;
                    return true;
                }
            }
        }

        // Verificar por cadenas de texto peligrosas
        $content = file_get_contents($filePath, false, null, 0, 4096);
        $dangerousPatterns = [
            '/<\?php/i',
            '/<script/i',
            '/eval\s*\(/i',
            '/document\.cookie/i',
            '/onload\s*=/i',
            '/onerror\s*=/i',
            '/javascript:/i',
            '/vbscript:/i',
            '/data:/i',
            '/base64,/i'
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                $this->detectedDangerousType = 'malicious_script';
                return true;
            }
        }

        return false;
    }

    /**
     * Validar con librería de imágenes
     */
    private function validateWithImageLibrary(string $filePath): bool
    {
        try {
            // Intentar con GD
            if (function_exists('getimagesize')) {
                $imageInfo = @getimagesize($filePath);
                if (!$imageInfo) {
                    return false;
                }

                // Intentar cargar la imagen para verificar que es renderizable
                $imageType = $imageInfo[2];
                switch ($imageType) {
                    case IMAGETYPE_JPEG:
                        $image = @imagecreatefromjpeg($filePath);
                        break;
                    case IMAGETYPE_PNG:
                        $image = @imagecreatefrompng($filePath);
                        break;
                    case IMAGETYPE_GIF:
                        $image = @imagecreatefromgif($filePath);
                        break;
                    case IMAGETYPE_WEBP:
                        if (function_exists('imagecreatefromwebp')) {
                            $image = @imagecreatefromwebp($filePath);
                        } else {
                            return false;
                        }
                        break;
                    case IMAGETYPE_BMP:
                        if (function_exists('imagecreatefrombmp')) {
                            $image = @imagecreatefrombmp($filePath);
                        } else {
                            return false;
                        }
                        break;
                    default:
                        return false;
                }

                if ($image === false) {
                    return false;
                }

                imagedestroy($image);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Obtener dimensiones de la imagen
     */
    private function getImageDimensions(string $filePath): ?array
    {
        $imageInfo = @getimagesize($filePath);
        if (!$imageInfo) {
            return null;
        }

        return [
            'width' => $imageInfo[0],
            'height' => $imageInfo[1],
            'type' => $imageInfo[2],
            'mime' => $imageInfo['mime']
        ];
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        if ($this->detectedDangerousType) {
            return 'El archivo no es una imagen válida o contiene contenido peligroso (tipo detectado: ' . $this->detectedDangerousType . ').';
        }
        return 'El archivo no es una imagen válida o contiene contenido peligroso.';
    }
}
