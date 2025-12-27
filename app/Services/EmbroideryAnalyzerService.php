<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;

/**
 * EmbroideryAnalyzerService
 * 
 * Servicio para analizar archivos de bordado utilizando PyEmbroidery.
 * Extrae información técnica: puntadas, colores, dimensiones.
 * 
 * Formatos soportados: PES, DST, EXP, JEF, VP3, VIP, XXX, y 40+ más.
 * 
 * @author Sistema de Gestión de Diseños
 * @version 1.0.0
 */
class EmbroideryAnalyzerService
{
    /**
     * Ruta al script Python de análisis.
     */
    protected string $scriptPath;

    /**
     * Comando Python a utilizar.
     */
    protected string $pythonCommand;

    /**
     * Formatos de archivo permitidos.
     */
    protected array $allowedExtensions = [
        'pes',
        'dst',
        'exp',
        'jef',
        'vp3',
        'vip',
        'xxx',
        'hus',
        'pec',
        'sew',
        'shv',
        'tap',
        'tbf',
        'u01'
    ];

    /**
     * Constructor del servicio.
     */
    public function __construct()
    {
        // Ruta al script Python (relativa a la raíz del proyecto)
        $this->scriptPath = base_path('app/scripts/embroidery_analyzer.py');

        // Detectar comando Python disponible
        $this->pythonCommand = $this->detectPythonCommand();
    }

    /**
     * Detecta el comando Python disponible en el sistema.
     * 
     * @return string Comando Python (python3, python, o ruta completa)
     */
    protected function detectPythonCommand(): string
    {
        // En Windows, intentar python primero
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Verificar si python está disponible
            exec('where python 2>&1', $output, $returnCode);
            if ($returnCode === 0) {
                return 'python';
            }

            // Intentar con python3
            exec('where python3 2>&1', $output, $returnCode);
            if ($returnCode === 0) {
                return 'python3';
            }

            // Ruta por defecto de Python en Windows
            return 'python';
        }

        // En Linux/Mac, preferir python3
        exec('which python3 2>&1', $output, $returnCode);
        if ($returnCode === 0) {
            return 'python3';
        }

        return 'python';
    }

    /**
     * Analiza un archivo de bordado desde una ruta.
     * 
     * @param string $filePath Ruta completa al archivo
     * @return array Datos del análisis
     */
    public function analyzeFromPath(string $filePath): array
    {
        // Verificar que el archivo existe
        if (!file_exists($filePath)) {
            return $this->errorResponse("Archivo no encontrado: {$filePath}");
        }

        // Verificar extensión
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions)) {
            return $this->errorResponse("Formato no soportado: .{$extension}");
        }

        // Verificar que el script existe
        if (!file_exists($this->scriptPath)) {
            Log::error("EmbroideryAnalyzer: Script no encontrado en {$this->scriptPath}");
            return $this->errorResponse("Error de configuración del servidor. Script no encontrado.");
        }

        // Ejecutar el script Python
        return $this->executeAnalysis($filePath);
    }

    /**
     * Analiza un archivo de bordado desde un UploadedFile.
     * 
     * @param UploadedFile $file Archivo subido
     * @return array Datos del análisis
     */
    public function analyzeFromUpload(UploadedFile $file): array
    {
        // Verificar extensión
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $this->allowedExtensions)) {
            return $this->errorResponse("Formato no soportado: .{$extension}");
        }

        // Guardar temporalmente el archivo para análisis
        $tempPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'emb_' . uniqid() . '.' . $extension;

        try {
            // Mover archivo a ubicación temporal
            file_put_contents($tempPath, file_get_contents($file->getRealPath()));

            // Analizar
            $result = $this->executeAnalysis($tempPath);

            // Agregar información del archivo original
            $result['original_name'] = $file->getClientOriginalName();
            $result['mime_type'] = $file->getMimeType();

            return $result;
        } finally {
            // Limpiar archivo temporal
            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    /**
     * Ejecuta el script Python de análisis.
     * 
     * @param string $filePath Ruta al archivo a analizar
     * @return array Resultado del análisis
     */
    protected function executeAnalysis(string $filePath): array
    {
        // Escapar argumentos para seguridad
        $escapedPath = escapeshellarg($filePath);
        $escapedScript = escapeshellarg($this->scriptPath);

        // Construir comando
        $command = "{$this->pythonCommand} {$escapedScript} {$escapedPath} 2>&1";

        // Log del comando (solo en debug)
        if (config('app.debug')) {
            Log::debug("EmbroideryAnalyzer: Ejecutando comando", ['command' => $command]);
        }

        // Ejecutar comando
        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        // Unir salida
        $jsonOutput = implode("\n", $output);

        // Log de salida (solo en debug)
        if (config('app.debug')) {
            Log::debug("EmbroideryAnalyzer: Salida", [
                'output' => $jsonOutput,
                'return_code' => $returnCode
            ]);
        }

        // Intentar decodificar JSON
        $result = json_decode($jsonOutput, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error("EmbroideryAnalyzer: Error al decodificar JSON", [
                'output' => $jsonOutput,
                'json_error' => json_last_error_msg()
            ]);
            return $this->errorResponse("Error al procesar el archivo. Salida inválida del analizador.");
        }

        return $result;
    }

    /**
     * Genera una respuesta de error estandarizada.
     * 
     * @param string $message Mensaje de error
     * @return array
     */
    protected function errorResponse(string $message): array
    {
        return [
            'success' => false,
            'error' => $message,
            'file_name' => null,
            'file_format' => null,
            'file_size' => 0,
            'total_stitches' => 0,
            'colors_count' => 0,
            'width_mm' => 0,
            'height_mm' => 0,
            'colors' => [],
        ];
    }

    /**
     * Obtiene los formatos de archivo permitidos.
     * 
     * @return array
     */
    public function getAllowedExtensions(): array
    {
        return $this->allowedExtensions;
    }

    /**
     * Obtiene las extensiones como string para validación de Laravel.
     * 
     * @return string Ejemplo: "pes,dst,exp,jef,vp3,vip,xxx"
     */
    public function getAllowedExtensionsString(): string
    {
        return implode(',', $this->allowedExtensions);
    }

    /**
     * Verifica si una extensión es válida.
     * 
     * @param string $extension
     * @return bool
     */
    public function isValidExtension(string $extension): bool
    {
        return in_array(strtolower($extension), $this->allowedExtensions);
    }

    /**
     * Formatea las dimensiones para mostrar.
     * 
     * @param float $width
     * @param float $height
     * @return string Ejemplo: "73.8 x 98.8 mm"
     */
    public static function formatDimensions(float $width, float $height): string
    {
        return "{$width} x {$height} mm";
    }

    /**
     * Formatea el conteo de puntadas con separador de miles.
     * 
     * @param int $stitches
     * @return string Ejemplo: "12,462"
     */
    public static function formatStitchCount(int $stitches): string
    {
        return number_format($stitches, 0, '.', ',');
    }
}
