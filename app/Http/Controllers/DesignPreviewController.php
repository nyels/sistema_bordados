<?php

namespace App\Http\Controllers;

use App\Models\DesignExport;
use App\Services\EmbroideryAnalyzerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DesignPreviewController extends Controller
{
    protected EmbroideryAnalyzerService $analyzer;

    public function __construct(EmbroideryAnalyzerService $analyzer)
    {
        $this->analyzer = $analyzer;
    }

    /**
     * Retorna la vista previa SVG de una exportación de diseño.
     * Utiliza cache para evitar ejecuciones repetidas de Python.
     */
    public function preview(Request $request, DesignExport $export)
    {
        // El cache se basa en el ID de la exportación y la fecha de actualización
        // para asegurar que si el archivo cambia, la vista previa se regenere.
        // v2: Agregado viewBox ajustado y padding.
        $cacheKey = "embroidery_preview_svg_v2_{$export->id}_{$export->updated_at->timestamp}";

        try {
            $svgContent = Cache::remember($cacheKey, now()->addDays(7), function () use ($export) {
                if (!$export->file_path || !Storage::disk('public')->exists($export->file_path)) {
                    return null;
                }

                $fullPath = Storage::disk('public')->path($export->file_path);

                Log::info("Generando preview SVG para exportación #{$export->id}");
                return $this->analyzer->generateSvg($fullPath);
            });

            if (!$svgContent) {
                return response()->json(['error' => 'No se pudo generar la vista previa'], 404);
            }

            // --- Soporte Especial para Explorador Interactivo ---

            // 1. Si se solicita descarga
            if ($request->has('download')) {
                return response($svgContent, 200)
                    ->header('Content-Type', 'image/svg+xml')
                    ->header('Content-Disposition', 'attachment; filename="diseno_' . $export->id . '.svg"');
            }

            // 2. DetecciÃ³n de Explorador (PÃ¡gina con herramientas)
            // Se activa si tiene ?explorer=1 o si es una navegaciÃ³n directa del navegador (Sec-Fetch-Dest: document)
            $fetchDest = $request->header('Sec-Fetch-Dest');
            $isDirectVisit = ($fetchDest === 'document') || ($request->has('explorer'));

            if ($isDirectVisit && !$request->ajax()) {
                return view('admin.produccion.preview_explorer', [
                    'export' => $export,
                    'svgContent' => $svgContent
                ]);
            }

            // 3. Respuesta estÃ¡ndar: Imagen SVG pura (para tags <img>)
            return response($svgContent, 200)
                ->header('Content-Type', 'image/svg+xml')
                ->header('Cache-Control', 'public, max-age=604800, immutable'); // Cache del navegador por 7 dÃ­as

        } catch (\Exception $e) {
            Log::error("Error en DesignPreviewController: " . $e->getMessage());
            return response()->json(['error' => 'Error interno al procesar la vista previa'], 500);
        }
    }
}
