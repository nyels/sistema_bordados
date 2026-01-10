<?php

namespace App\Http\Controllers;

use App\Services\EmbroideryAnalyzerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VisualizerController extends Controller
{
    protected EmbroideryAnalyzerService $analyzer;

    public function __construct(EmbroideryAnalyzerService $analyzer)
    {
        $this->analyzer = $analyzer;
    }

    /**
     * Muestra la vista principal del visualizador.
     */
    public function index()
    {
        return view('admin.visualizer.index');
    }

    /**
     * Analiza el archivo subido y devuelve los resultados JSON.
     */
    public function analyze(Request $request)
    {
        $tempPath = null;
        try {
            // Validar archivo (10MB max)
            $request->validate([
                'file' => 'required|file|max:10240'
            ]);

            $file = $request->file('file');
            $extension = $file->getClientOriginalExtension();

            // Crear una ruta temporal con la extensión correcta (IMPORTANTE para pyembroidery)
            $tempPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'viz_' . uniqid() . '.' . $extension;

            // Mover/Copiar el archivo a la ruta temporal
            file_put_contents($tempPath, file_get_contents($file->getRealPath()));

            // 1. Analizar Datos Técnicos
            $result = $this->analyzer->analyzeFromPath($tempPath);

            // Agregar metadatos originales que analyzeFromPath no tiene (porque lee del disco)
            $result['original_name'] = $file->getClientOriginalName();
            $result['mime_type'] = $file->getMimeType();

            if ($result['success']) {
                // 2. Generar SVG (Usando la misma ruta temporal válida)
                $svg = $this->analyzer->generateSvg($tempPath);

                return response()->json([
                    'success' => true,
                    'data' => $this->cleanUtf8(array_merge($result, ['svg_content' => $svg]))
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error']
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Error en VisualizerController@analyze: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error interno al procesar el archivo: ' . $e->getMessage()
            ], 500);
        } finally {
            // Limpieza siempre
            if ($tempPath && file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }
    }
    /**
     * Limpia recursivamente los datos para asegurar UTF-8 válido.
     */
    private function cleanUtf8($data)
    {
        if (is_string($data)) {
            // mb_convert_encoding con 'UTF-8' como input y output reemplaza bytes inválidos
            return mb_convert_encoding($data, 'UTF-8', 'UTF-8');
        }
        if (is_array($data)) {
            $ret = [];
            foreach ($data as $i => $d) {
                $ret[$i] = $this->cleanUtf8($d);
            }
            return $ret;
        }
        return $data;
    }
}
