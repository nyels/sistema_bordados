<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class EmbroideryAnalyzerService
{
    protected string $scriptPath;
    protected string $pythonCommand;
    protected int $processTimeout = 60; // Increased for heavy files

    protected array $allowedExtensions = [
        'pes',
        'pec',
        'dst',
        'dsz',
        'jef',
        'jef+',
        'jmf',
        'pcs',
        'pcm',
        'vp3',
        'vip',
        'hus',
        'shv',
        'exp',
        'art',
        'art+',
        'xxx',
        'zhs',
        '10o',
        'tbf',
        'u01',
        'emd',
        'csd',
        'ksm',
        'zsk',
        'pmv',
        'toy',
        'sew',
        'tap',
        'stx',
        'mit',
        'stc',
        'phb',
        'phc',
        'max',
        'dat',
        'txt'
    ];

    protected array $config = [
        'max_file_size' => 10485760, // 10MB
        'svg_dpi' => 96,
    ];

    protected static array $cache = [];

    public function __construct(array $config = [])
    {
        $this->scriptPath = base_path('app/Scripts/embroidery_analyzer.py');
        $this->config = array_merge($this->config, $config);
        $this->pythonCommand = $this->detectPythonCommand();
    }

    protected function detectPythonCommand(): string
    {
        // Simple detection strategy
        $commands = ['python', 'python3', 'py'];
        foreach ($commands as $cmd) {
            $process = new Process([$cmd, '--version']);
            $process->run();
            if ($process->isSuccessful()) {
                return $cmd;
            }
        }
        return 'python'; // Fallback
    }

    public function analyzeFromPath(string $filePath, bool $forceRefresh = false): array
    {
        if (!file_exists($filePath)) {
            return $this->errorResponse("Archivo no encontrado");
        }

        $cacheKey = md5($filePath . filemtime($filePath));
        if (!$forceRefresh && isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        $result = $this->executeAnalysis($filePath, ['--json']);
        self::$cache[$cacheKey] = $result;
        return $result;
    }

    public function analyzeFromUpload(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $this->allowedExtensions)) {
            return $this->errorResponse("Formato .{$extension} no soportado");
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'emb_') . '.' . $extension;

        try {
            $file->move(dirname($tempPath), basename($tempPath));

            // Analyze
            $result = $this->analyzeFromPath($tempPath);

            // Add metadata
            $result['upload_metadata'] = [
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
            ];

            return $result;
        } finally {
            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    public function generateSvg(string $filePath): string
    {
        if (!file_exists($filePath)) {
            return "";
        }

        $args = [
            $this->pythonCommand,
            $this->scriptPath,
            $filePath,
            '--svg',
            '--dpi',
            (string)$this->config['svg_dpi']
        ];

        $process = new Process($args);
        $process->setTimeout($this->processTimeout);

        try {
            $process->mustRun();
            $output = $process->getOutput();

            // Try to parse JSON wrapper
            $jsonStart = strpos($output, '{');
            if ($jsonStart !== false) {
                $jsonStr = substr($output, $jsonStart);
                $data = json_decode($jsonStr, true);
                if (json_last_error() === JSON_ERROR_NONE && isset($data['svg'])) {
                    return $data['svg'];
                }
            }

            // Fallback: return raw output if it looks like SVG
            if (strpos($output, '<svg') !== false) {
                return $output;
            }

            return "";
        } catch (\Exception $e) {
            Log::error("SVG generation failed: " . $e->getMessage());
            return "";
        }
    }

    protected function executeAnalysis(string $filePath, array $flags = []): array
    {
        $args = array_merge(
            [$this->pythonCommand, $this->scriptPath, $filePath],
            $flags
        );

        $process = new Process($args);
        $process->setTimeout($this->processTimeout);

        try {
            $process->mustRun();
            $output = $process->getOutput();
            // Handle output that might contain non-JSON noise at start
            $jsonStart = strpos($output, '{');
            if ($jsonStart === false) {
                throw new \Exception("Invalid output format from Python script");
            }

            $jsonStr = substr($output, $jsonStart);
            $data = json_decode($jsonStr, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                // Try aggressive cleaning for UTF-8 issues
                $jsonStr = mb_convert_encoding($jsonStr, 'UTF-8', 'UTF-8');
                $data = json_decode($jsonStr, true);
            }

            if (!$data) {
                throw new \Exception("JSON parse error: " . json_last_error_msg());
            }

            return $this->normalizeAnalysisResult($data);
        } catch (ProcessFailedException $e) {
            Log::error("Analysis process failed: " . $e->getMessage());
            return $this->errorResponse("Error al procesar el archivo");
        } catch (\Exception $e) {
            Log::error("Analysis error: " . $e->getMessage());
            return $this->errorResponse("Error interno: " . $e->getMessage());
        }
    }

    protected function normalizeAnalysisResult(array $data): array
    {
        // Ensures the result has the structure expected by BOTH new and old fontend logic

        $result = $data; // Start with raw data (which now includes detailed stats)

        // -----------------------------------------------------------
        // LEGACY MAPPING (Backward Compatibility for Frontend)
        // -----------------------------------------------------------
        // The Python script (v2.1) returns flat keys like 'total_stitches', 
        // 'width_mm', etc., so we just ensure they exist.

        $defaults = [
            'success' => false,
            'total_stitches' => 0,
            'colors_count' => 0,
            'width_mm' => 0,
            'height_mm' => 0,
            'colors' => [],
            'machine_compatibility' => 'Desconocida'
        ];

        $result = array_merge($defaults, $result);

        // Enhance with 'technical' structure if we want to migrate specifically
        $result['technical'] = [
            'stitches' => [
                'total' => $result['total_stitches'],
                'jump' => $result['jumps'] ?? 0,
            ],
            'dimensions' => [
                'width_mm' => $result['width_mm'],
                'height_mm' => $result['height_mm'],
            ]
        ];

        return $result;
    }

    protected function errorResponse(string $message): array
    {
        return [
            'success' => false,
            'error' => $message,
            'total_stitches' => 0,
            'colors_count' => 0,
            'width_mm' => 0,
            'height_mm' => 0,
            'colors' => []
        ];
    }
}
