<?php

namespace App\Http\Controllers;

use App\Models\Design;
use App\Models\DesignVariant;
use App\Models\DesignExport;
use App\Models\Application_types;
use App\Services\EmbroideryAnalyzerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;


class DesignExportController extends Controller
{
    protected EmbroideryAnalyzerService $analyzer;

    public function __construct(EmbroideryAnalyzerService $analyzer)
    {
        $this->analyzer = $analyzer;
    }

    // =====================================================================
    // MÉTODOS CRUD ESTÁNDAR (MÓDULO INDEPENDIENTE)
    // =====================================================================

    public function index()
    {
        $exports = DesignExport::with(['design', 'variant', 'creator'])
            ->latest()
            ->paginate(20);
        $tipo_aplicacion = Application_types::activos()->orderBy('nombre_aplicacion')->get();

        return view('admin.production.index', compact('exports', 'tipo_aplicacion'));
    }

    public function create()
    {
        $designs = Design::where('is_active', true)->orderBy('name')->get();
        $applicationTypes = Application_types::activos()->orderBy('nombre_aplicacion')->get();
        return view('admin.production.create', compact('designs', 'applicationTypes'));
    }

    /**
     * Formulario de creación para un diseño específico
     */
    public function createForDesign(Design $design)
    {
        $applicationTypes = Application_types::activos()->orderBy('nombre_aplicacion')->get();
        return view('admin.designs.exports.create', compact('design', 'applicationTypes'));
    }

    /**
     * Formulario de creación para una variante específica
     */
    public function createForVariant(Design $design, DesignVariant $variant)
    {
        // Verificar que la variante pertenece al diseño
        if ($variant->design_id !== $design->id) {
            abort(404, 'Variante no encontrada para este diseño.');
        }

        $applicationTypes = Application_types::activos()->orderBy('nombre_aplicacion')->get();
        return view('admin.design-variants.exports.create', compact('design', 'variant', 'applicationTypes'));
    }

    /**
     * Guardar exportación para un diseño específico
     */
    public function storeForDesign(Request $request, Design $design)
    {
        $validated = $request->validate([
            'application_type' => 'required|string|max:50',
            'application_label' => 'required|string|max:100',
            'placement_description' => 'nullable|string|max:255',
            'file' => 'required|file|mimes:pes,dst,exp,jef,vip,vp3,xxx|max:10240',
            'notes' => 'nullable|string',
        ]);

        $file = $request->file('file');
        $fileName = $this->generateFileName(['design_id' => $design->id, 'application_label' => $validated['application_label']], $file);
        $filePath = $file->storeAs('exports/' . date('Y') . '/' . date('m'), $fileName, 'public');

        // Analizar archivo si es posible
        $analysisData = [];
        try {
            $result = $this->analyzer->analyzeFromUpload($file);
            if ($result['success']) {
                $analysisData = [
                    'stitches_count' => $result['total_stitches'] ?? null,
                    'width_mm' => $result['width_mm'] ?? null,
                    'height_mm' => $result['height_mm'] ?? null,
                    'colors_count' => $result['colors_count'] ?? null,
                    'colors_detected' => isset($result['colors']) ? json_encode($result['colors']) : null,
                    'auto_read_success' => true,
                ];
            }
        } catch (\Exception $e) {
            Log::warning('No se pudo analizar el archivo: ' . $e->getMessage());
        }

        DesignExport::create(array_merge([
            'design_id' => $design->id,
            'design_variant_id' => null,
            'application_type' => $validated['application_type'],
            'application_label' => $validated['application_label'],
            'placement_description' => $validated['placement_description'] ?? null,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'file_format' => strtoupper($file->getClientOriginalExtension()),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'notes' => $validated['notes'] ?? null,
            'status' => 'borrador',
            'created_by' => Auth::id(),
        ], $analysisData));

        return redirect()->route('admin.designs.index')
            ->with('success', 'Exportación creada correctamente para el diseño.');
    }

    /**
     * Guardar exportación para una variante específica
     */
    public function storeForVariant(Request $request, Design $design, DesignVariant $variant)
    {
        if ($variant->design_id !== $design->id) {
            abort(404, 'Variante no encontrada para este diseño.');
        }

        $validated = $request->validate([
            'application_type' => 'required|string|max:50',
            'application_label' => 'required|string|max:100',
            'placement_description' => 'nullable|string|max:255',
            'file' => 'required|file|mimes:pes,dst,exp,jef,vip,vp3,xxx|max:10240',
            'notes' => 'nullable|string',
        ]);

        $file = $request->file('file');
        $fileName = $this->generateFileName([
            'design_id' => $design->id,
            'design_variant_id' => $variant->id,
            'application_label' => $validated['application_label']
        ], $file);
        $filePath = $file->storeAs('exports/' . date('Y') . '/' . date('m'), $fileName, 'public');

        // Analizar archivo si es posible
        $analysisData = [];
        try {
            $result = $this->analyzer->analyzeFromUpload($file);
            if ($result['success']) {
                $analysisData = [
                    'stitches_count' => $result['total_stitches'] ?? null,
                    'width_mm' => $result['width_mm'] ?? null,
                    'height_mm' => $result['height_mm'] ?? null,
                    'colors_count' => $result['colors_count'] ?? null,
                    'colors_detected' => isset($result['colors']) ? json_encode($result['colors']) : null,
                    'auto_read_success' => true,
                ];
            }
        } catch (\Exception $e) {
            Log::warning('No se pudo analizar el archivo: ' . $e->getMessage());
        }

        DesignExport::create(array_merge([
            'design_id' => $design->id,
            'design_variant_id' => $variant->id,
            'application_type' => $validated['application_type'],
            'application_label' => $validated['application_label'],
            'placement_description' => $validated['placement_description'] ?? null,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'file_format' => strtoupper($file->getClientOriginalExtension()),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'notes' => $validated['notes'] ?? null,
            'status' => 'borrador',
            'created_by' => Auth::id(),
        ], $analysisData));

        return redirect()->route('admin.designs.index')
            ->with('success', 'Exportación creada correctamente para la variante.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'design_id' => 'required|exists:designs,id',
            'design_variant_id' => 'nullable|exists:design_variants,id',
            'application_type' => 'required|exists:application_types,id',
            'application_label' => 'required|string|max:100',
            'placement_description' => 'nullable|string|max:255',
            'file' => 'required|file|mimes:pes,dst,exp,jef,vip,vp3,xxx|max:10240',
            'stitches_count' => 'nullable|integer|min:0',
            'width_mm' => 'nullable|integer|min:0',
            'height_mm' => 'nullable|integer|min:0',
            'colors_count' => 'nullable|integer|min:0',
            'colors_detected' => 'nullable|json',
            'notes' => 'nullable|string',
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = $this->generateFileName($validated, $file);
            $filePath = $file->storeAs('exports/' . date('Y') . '/' . date('m'), $fileName, 'public');

            $validated['file_name'] = $file->getClientOriginalName();
            $validated['file_path'] = $filePath;
            $validated['file_format'] = strtoupper($file->getClientOriginalExtension());
            $validated['file_size'] = $file->getSize();
            $validated['mime_type'] = $file->getMimeType();
        }

        $validated['created_by'] = Auth::id();
        $validated['status'] = 'borrador';

        DesignExport::create($validated);

        return redirect()->route('admin.production.index')
            ->with('success', 'Exportación creada correctamente.');
    }

    public function show(DesignExport $export)
    {
        $export->load(['design', 'variant', 'creator', 'approver']);
        return view('admin.production.show', compact('export'));
    }

    public function edit(DesignExport $export)
    {
        $export->load(['design', 'variant']);
        $designs = Design::where('is_active', true)->orderBy('name')->get();
        $variants = $export->design ? $export->design->variants : collect();

        return view('admin.production.edit', compact('export', 'designs', 'variants'));
    }

    public function update(Request $request, DesignExport $export)
    {
        $validated = $request->validate([
            'design_id' => 'required|exists:designs,id',
            'design_variant_id' => 'nullable|exists:design_variants,id',
            'application_type' => 'required|string|max:50',
            'application_label' => 'required|string|max:100',
            'placement_description' => 'nullable|string|max:255',
            'file' => 'nullable|file|mimes:pes,dst,exp,jef,vip,vp3,xxx|max:10240',
            'stitches_count' => 'nullable|integer|min:0',
            'width_mm' => 'nullable|integer|min:0',
            'height_mm' => 'nullable|integer|min:0',
            'colors_count' => 'nullable|integer|min:0',
            'colors_detected' => 'nullable|json',
            'status' => 'required|in:borrador,pendiente,aprobado,archivado',
            'notes' => 'nullable|string',
        ]);

        if ($request->hasFile('file')) {
            if ($export->file_path && Storage::disk('public')->exists($export->file_path)) {
                Storage::disk('public')->delete($export->file_path);
            }

            $file = $request->file('file');
            $fileName = $this->generateFileName($validated, $file);
            $filePath = $file->storeAs('exports/' . date('Y') . '/' . date('m'), $fileName, 'public');

            $validated['file_name'] = $file->getClientOriginalName();
            $validated['file_path'] = $filePath;
            $validated['file_format'] = strtoupper($file->getClientOriginalExtension());
            $validated['file_size'] = $file->getSize();
            $validated['mime_type'] = $file->getMimeType();
            $validated['auto_read_success'] = false;
        }

        if ($validated['status'] === 'aprobado' && $export->status !== 'aprobado') {
            $validated['approved_by'] = Auth::id();
            $validated['approved_at'] = now();
        } elseif ($validated['status'] !== 'aprobado') {
            $validated['approved_by'] = null;
            $validated['approved_at'] = null;
        }

        $export->update($validated);

        return redirect()->route('admin.production.index')
            ->with('success', 'Exportación actualizada correctamente.');
    }

    public function destroy(DesignExport $export)
    {
        if ($export->file_path && Storage::disk('public')->exists($export->file_path)) {
            Storage::disk('public')->delete($export->file_path);
        }

        $export->delete();

        return redirect()->route('admin.production.index')
            ->with('success', 'Exportación eliminada correctamente.');
    }

    public function download(DesignExport $export)
    {
        if (!$export->file_path || !Storage::disk('public')->exists($export->file_path)) {
            return back()->with('error', 'Archivo no encontrado.');
        }

        return response()->download(Storage::disk('public')->path($export->file_path), $export->file_name);
    }

    // =====================================================================
    // MÉTODOS AJAX PARA EL MODAL
    // =====================================================================

    public function analyzeFile(Request $request)
    {
        try {
            $request->validate(['file' => 'required|file|max:10240']);

            $file = $request->file('file');
            $extension = strtolower($file->getClientOriginalExtension());

            if (!$this->analyzer->isValidExtension($extension)) {
                return response()->json([
                    'success' => false,
                    'error' => "Formato no soportado: .{$extension}. Formatos permitidos: " .
                        $this->analyzer->getAllowedExtensionsString()
                ], 400);
            }

            // Copiar archivo a temporal para análisis y SVG
            $originalPath = $file->getRealPath();
            $tempPath = tempnam(sys_get_temp_dir(), 'emb_') . '.' . $extension;

            if (!copy($originalPath, $tempPath)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Error al procesar el archivo.',
                    'allow_manual' => true
                ], 500);
            }

            try {
                $result = $this->analyzer->analyzeFromPath($tempPath);

                // Generar SVG para vista previa
                $svgContent = '';
                if ($result['success']) {
                    $svgContent = $this->analyzer->generateSvg($tempPath);
                }

                if ($result['success']) {
                    return response()->json([
                        'success' => true,
                        'data' => [
                            'file_name' => $file->getClientOriginalName(),
                            'file_format' => $result['file_format'] ?? strtoupper($extension),
                            'file_size' => filesize($tempPath),
                            'total_stitches' => $result['total_stitches'],
                            'stitches_count' => $result['total_stitches'],
                            'colors_count' => $result['colors_count'],
                            'width_mm' => $result['width_mm'],
                            'height_mm' => $result['height_mm'],
                            'colors' => $result['colors'],
                            'dimensions_formatted' => "{$result['width_mm']} x {$result['height_mm']} mm",
                            'stitches_formatted' => number_format($result['total_stitches'], 0, '.', ','),
                            'svg_content' => $svgContent,
                        ]
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'error' => $result['error'] ?? 'Error al analizar el archivo.',
                        'allow_manual' => true
                    ], 400);
                }
            } finally {
                // Cleanup temp file
                if (file_exists($tempPath)) {
                    @unlink($tempPath);
                }
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Archivo no válido o demasiado grande (máximo 10MB).'
            ], 400);
        } catch (\Exception $e) {
            Log::error('Error en analyzeFile: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error interno al procesar el archivo.',
                'allow_manual' => true
            ], 500);
        }
    }

    public function storeAjax(Request $request)
    {
        try {
            $validated = $request->validate([
                'design_id' => 'required|exists:designs,id',
                'design_variant_id' => 'nullable|exists:design_variants,id',
                'image_id' => 'nullable|exists:images,id',
                'application_type' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\-_]+$/'],
                'application_label' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\-_.,;:()\/#]+$/'],
                'placement_description' => ['nullable', 'string', 'max:255', 'regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\-_.,;:()\/#]*$/'],
                'file' => 'required|file|max:10240',
                'stitches_count' => 'nullable|integer|min:0',
                'width_mm' => 'nullable|numeric|min:0',
                'height_mm' => 'nullable|numeric|min:0',
                'colors_count' => 'nullable|integer|min:0',
                'colors_detected' => 'nullable',
                'notes' => ['nullable', 'string', 'max:1000', 'regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\-_.,;:()\/\n\r#@!?]*$/'],
                'auto_read_success' => 'nullable|boolean',
                'svg_content' => 'nullable|string', // Validar SVG content
            ], [
                'application_type.regex' => 'El tipo de aplicación contiene caracteres no permitidos.',
                'application_label.regex' => 'La etiqueta contiene caracteres no permitidos (evita: ´¨{}|<>\\`)',
                'placement_description.regex' => 'La descripción contiene caracteres no permitidos.',
                'notes.regex' => 'Las notas contienen caracteres no permitidos.',
            ]);

            $file = $request->file('file');
            $extension = strtolower($file->getClientOriginalExtension());

            if (!$this->analyzer->isValidExtension($extension)) {
                return response()->json([
                    'success' => false,
                    'error' => "Formato no soportado: .{$extension}"
                ], 400);
            }

            $fileName = $this->generateFileName($validated, $file);
            $filePath = $file->storeAs('exports/' . date('Y') . '/' . date('m'), $fileName, 'public');

            $exportData = [
                'design_id' => $validated['design_id'],
                'design_variant_id' => $validated['design_variant_id'] ?? null,
                'image_id' => $validated['image_id'] ?? null,
                'application_type' => $this->sanitizeText($validated['application_type']),
                'application_label' => $this->sanitizeText($validated['application_label']),
                'placement_description' => $this->sanitizeText($validated['placement_description'] ?? null),
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $filePath,
                'file_format' => strtoupper($extension),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'stitches_count' => $validated['stitches_count'] ?? null,
                'width_mm' => isset($validated['width_mm']) ? round($validated['width_mm']) : null,
                'height_mm' => isset($validated['height_mm']) ? round($validated['height_mm']) : null,
                'colors_count' => $validated['colors_count'] ?? null,
                'colors_detected' => isset($validated['colors_detected']) ?
                    (is_string($validated['colors_detected']) ? $validated['colors_detected'] : json_encode($validated['colors_detected'])) : null,
                'notes' => $this->sanitizeText($validated['notes'] ?? null),
                'status' => 'borrador',
                'created_by' => Auth::id(),
                'auto_read_success' => $request->boolean('auto_read_success', false),
                'svg_content' => $validated['svg_content'] ?? null, // Guardar SVG
            ];

            $export = DesignExport::create($exportData);
            $export->load(['creator:id,name']);

            return response()->json([
                'success' => true,
                'message' => 'Exportación creada correctamente.',
                'data' => $this->formatExportForJson($export)
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->errors()[array_key_first($e->errors())][0] ?? 'Datos inválidos.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error en storeAjax: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error al guardar: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateAjax(Request $request, DesignExport $export)
    {
        try {
            $validated = $request->validate([
                'application_type' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\-_]+$/'],
                'application_label' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\-_.,;:()\/#]+$/'],
                'placement_description' => ['nullable', 'string', 'max:255', 'regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\-_.,;:()\/#]*$/'],
                'notes' => ['nullable', 'string', 'max:1000', 'regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\-_.,;:()\/\n\r#@!?]*$/'],
                'stitches_count' => 'nullable|integer|min:0',
                'width_mm' => 'nullable|numeric|min:0',
                'height_mm' => 'nullable|numeric|min:0',
                'colors_count' => 'nullable|integer|min:0',
                'svg_content' => 'nullable|string', // Validar SVG content
            ], [
                'application_type.regex' => 'El tipo de aplicación contiene caracteres no permitidos.',
                'application_label.regex' => 'La etiqueta contiene caracteres no permitidos. Evita usar: ´ ¨ { } | < > \\ `',
                'placement_description.regex' => 'La descripción contiene caracteres no permitidos.',
                'notes.regex' => 'Las notas contienen caracteres no permitidos.',
            ]);

            $export->update([
                'application_type' => $this->sanitizeText($validated['application_type']),
                'application_label' => $this->sanitizeText($validated['application_label']),
                'placement_description' => $this->sanitizeText($validated['placement_description'] ?? null),
                'notes' => $this->sanitizeText($validated['notes'] ?? null),
                'stitches_count' => $validated['stitches_count'] ?? $export->stitches_count,
                'width_mm' => $validated['width_mm'] ?? $export->width_mm,
                'height_mm' => $validated['height_mm'] ?? $export->height_mm,
                'colors_count' => $validated['colors_count'] ?? $export->colors_count,
                'svg_content' => $validated['svg_content'] ?? $export->svg_content, // Actualizar SVG
            ]);

            $export->load(['creator:id,name', 'approver:id,name']);

            return response()->json([
                'success' => true,
                'message' => 'Exportación actualizada.',
                'data' => $this->formatExportForJson($export)
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->errors()[array_key_first($e->errors())][0] ?? 'Datos inválidos.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error en updateAjax: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error al actualizar.'
            ], 500);
        }
    }

    public function updateStatus(Request $request, DesignExport $export)
    {
        try {
            $validated = $request->validate([
                'status' => 'required|in:borrador,pendiente,aprobado,archivado',
            ]);

            $previousStatus = $export->status;
            $newStatus = $validated['status'];

            // Solo registrar si el estado realmente cambió
            if ($previousStatus !== $newStatus) {
                // Registrar en historial de cambios de estado
                \App\Models\DesignExportStatusHistory::create([
                    'design_export_id' => $export->id,
                    'previous_status' => $previousStatus,
                    'new_status' => $newStatus,
                    'changed_by' => Auth::id(),
                    'notes' => $request->input('notes'),
                ]);
            }

            $updateData = ['status' => $newStatus];

            if ($newStatus === 'aprobado' && $export->status !== 'aprobado') {
                $updateData['approved_by'] = Auth::id();
                $updateData['approved_at'] = now();
            } elseif ($newStatus !== 'aprobado' && $export->status === 'aprobado') {
                $updateData['approved_by'] = null;
                $updateData['approved_at'] = null;
            }

            $export->update($updateData);
            $export->load(['approver:id,name']);

            return response()->json([
                'success' => true,
                'message' => 'Estado actualizado.',
                'data' => [
                    'id' => $export->id,
                    'status' => $export->status,
                    'status_label' => $this->getStatusLabel($export->status),
                    'status_color' => $this->getStatusColor($export->status),
                    'approved_by' => $export->approver?->name,
                    'approved_at' => $export->approved_at?->format('d/m/Y H:i'),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => 'Error al actualizar estado.'], 500);
        }
    }

    public function getExportsCount(Design $design)
    {
        try {
            $count = DesignExport::where('design_id', $design->id)->count();
            return response()->json(['success' => true, 'count' => $count, 'design_id' => $design->id]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'count' => 0], 500);
        }
    }

    /**
     * Obtener contador de exportaciones del diseño que NO tienen image_id específico.
     * Esto es para mostrar en el badge de la imagen principal del diseño.
     */
    public function getExportsWithoutImageCount(Design $design)
    {
        try {
            // Contar producciones del diseño que no tienen image_id (diseño general)
            $count = DesignExport::where('design_id', $design->id)
                ->whereNull('image_id')
                ->whereNull('design_variant_id')
                ->count();

            return response()->json([
                'success' => true,
                'count' => $count,
                'design_id' => $design->id
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'count' => 0], 500);
        }
    }

    /**
     * Obtener contador de exportaciones para una imagen específica.
     */
    public function getImageExportsCount($imageId)
    {
        try {
            $count = DesignExport::where('image_id', $imageId)->count();
            return response()->json([
                'success' => true,
                'count' => $count,
                'image_id' => $imageId
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'count' => 0], 500);
        }
    }

    /**
     * Obtener contadores de exportaciones para múltiples imágenes (batch).
     * Reduce múltiples peticiones AJAX a una sola.
     */
    public function getImagesExportsCounts(Request $request)
    {
        try {
            $imageIds = $request->input('image_ids', []);

            if (empty($imageIds)) {
                return response()->json(['success' => true, 'counts' => []]);
            }

            // Una sola consulta para todos los contadores
            $counts = DesignExport::whereIn('image_id', $imageIds)
                ->selectRaw('image_id, COUNT(*) as count')
                ->groupBy('image_id')
                ->pluck('count', 'image_id')
                ->toArray();

            // Asegurar que todas las imágenes tengan un contador (0 si no hay)
            $result = [];
            foreach ($imageIds as $id) {
                $result[$id] = $counts[$id] ?? 0;
            }

            return response()->json([
                'success' => true,
                'counts' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'counts' => []], 500);
        }
    }

    /**
     * Obtener exportaciones vinculadas a una imagen específica.
     */
    public function getImageExports($imageId)
    {
        try {
            $exports = DesignExport::where('image_id', $imageId)
                ->with(['creator:id,name', 'design:id,name', 'variant:id,name'])
                ->orderByDesc('created_at')
                ->get()
                ->map(fn($e) => $this->formatExportForJson($e));

            return response()->json([
                'success' => true,
                'data' => $exports,
                'count' => $exports->count(),
                'image_id' => $imageId
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener exportaciones de imagen: ' . $e->getMessage());
            return response()->json(['success' => false, 'data' => [], 'count' => 0], 500);
        }
    }

    public function getExport(DesignExport $export)
    {
        try {
            $export->load(['creator:id,name', 'approver:id,name', 'design:id,name', 'variant:id,name']);
            return response()->json(['success' => true, 'data' => $this->formatExportForJson($export)]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => 'Error al cargar.'], 500);
        }
    }

    public function destroyAjax(DesignExport $export)
    {
        try {
            if ($export->file_path && Storage::disk('public')->exists($export->file_path)) {
                Storage::disk('public')->delete($export->file_path);
            }
            $export->delete();
            return response()->json(['success' => true, 'message' => 'Exportación eliminada.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => 'Error al eliminar.'], 500);
        }
    }

    public function getApplicationTypes()
    {
        try {
            $tipos = Application_types::activos()
                ->orderBy('nombre_aplicacion')
                ->get()
                ->map(function ($tipo) {
                    return [
                        'value' => $tipo->slug,
                        'label' => $tipo->nombre_aplicacion,
                        'id' => $tipo->id,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $tipos
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener tipos de aplicación: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error al cargar tipos de aplicación',
                'data' => []
            ], 500);
        }
    }

    public function getAllowedExtensions()
    {
        return response()->json([
            'success' => true,
            'data' => $this->analyzer->getAllowedExtensions(),
            'string' => $this->analyzer->getAllowedExtensionsString()
        ]);
    }

    /**
     * Obtener exportaciones de un diseño (sin variante específica)
     * Usado cuando se ve una variante específica
     */
    public function getDesignExports(Design $design)
    {
        try {
            $exports = DesignExport::where('design_id', $design->id)
                ->whereNull('design_variant_id')
                ->with(['creator:id,name', 'approver:id,name'])
                ->latest()
                ->get()
                ->map(fn($export) => $this->formatExportForJson($export));

            return response()->json(['success' => true, 'data' => $exports, 'count' => $exports->count()]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'data' => []], 500);
        }
    }

    /**
     * AJAX: Obtener contador de exportaciones de un diseño (sin incluir variantes)
     * Usado por el contador de producción en la pestaña del modal
     */
    public function getDesignExportsCount(Design $design)
    {
        try {
            // Contar exportaciones directas del diseño (sin variant_id)
            $count = DesignExport::where('design_id', $design->id)
                ->whereNull('design_variant_id')
                ->count();

            return response()->json([
                'success' => true,
                'count' => $count,
                'design_id' => $design->id
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'count' => 0], 500);
        }
    }

    /**
     * Obtener exportaciones de una variante específica
     */
    public function getVariantExports(Design $design, DesignVariant $variant)
    {
        try {
            if ($variant->design_id != $design->id) {
                return response()->json(['success' => false, 'message' => 'Variante inválida.', 'data' => []], 400);
            }

            $exports = DesignExport::where('design_variant_id', $variant->id)
                ->with(['creator:id,name', 'approver:id,name'])
                ->latest()
                ->get()
                ->map(fn($export) => $this->formatExportForJson($export));

            // Calcular resumen de estados
            $summary = [
                'borrador' => $exports->where('status', 'borrador')->count(),
                'pendiente' => $exports->where('status', 'pendiente')->count(),
                'aprobado' => $exports->where('status', 'aprobado')->count(),
                'archivado' => $exports->where('status', 'archivado')->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $exports,
                'count' => $exports->count(),
                'summary' => $summary,
                'context' => 'variant',
                'context_name' => $variant->name,
                'context_id' => $variant->id
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'data' => []], 500);
        }
    }

    /**
     * ⭐ NUEVO MÉTODO: Obtener TODAS las exportaciones de un diseño agrupadas por variante
     * Usado cuando se está en la imagen principal del diseño
     */
    public function getAllDesignExportsGrouped(Design $design)
    {
        try {
            $design->load(['variants' => function ($query) {
                $query->with(['images' => function ($q) {
                    $q->orderBy('order')->limit(1);
                }]);
            }]);

            // Obtener TODAS las exportaciones del diseño
            $allExports = DesignExport::where('design_id', $design->id)
                ->with([
                    'creator:id,name',
                    'approver:id,name',
                    'variant:id,name',
                    'variant.images',
                    'image',
                    'design.primaryImage'
                ])
                ->latest()
                ->get();

            // Calcular resumen global de estados
            $summary = [
                'borrador' => $allExports->where('status', 'borrador')->count(),
                'pendiente' => $allExports->where('status', 'pendiente')->count(),
                'aprobado' => $allExports->where('status', 'aprobado')->count(),
                'archivado' => $allExports->where('status', 'archivado')->count(),
            ];

            // Agrupar por variante
            $groups = [];

            // Grupo 1: Exportaciones del diseño principal (sin variante)
            $designExports = $allExports->whereNull('design_variant_id')->values();
            if ($designExports->count() > 0) {
                $groups[] = [
                    'type' => 'design',
                    'name' => 'Diseño Principal',
                    'variant_id' => null,
                    'thumbnail' => $design->primaryImage ? asset('storage/' . $design->primaryImage->file_path) : null,
                    'count' => $designExports->count(),
                    'exports' => $designExports->map(fn($export) => $this->formatExportForJson($export))->values()
                ];
            }

            // Grupos por cada variante
            foreach ($design->variants as $variant) {
                $variantExports = $allExports->where('design_variant_id', $variant->id)->values();
                if ($variantExports->count() > 0) {
                    $thumbnail = null;
                    if ($variant->images && $variant->images->count() > 0) {
                        $thumbnail = asset('storage/' . $variant->images->first()->file_path);
                    }

                    $groups[] = [
                        'type' => 'variant',
                        'name' => $variant->name,
                        'variant_id' => $variant->id,
                        'thumbnail' => $thumbnail,
                        'count' => $variantExports->count(),
                        'exports' => $variantExports->map(fn($export) => $this->formatExportForJson($export))->values()
                    ];
                }
            }

            // Lista de variantes para el filtro
            $variantsList = $design->variants->map(function ($v) use ($allExports) {
                return [
                    'id' => $v->id,
                    'name' => $v->name,
                    'count' => $allExports->where('design_variant_id', $v->id)->count()
                ];
            })->values();

            return response()->json([
                'success' => true,
                'context' => 'design',
                'design_id' => $design->id,
                'design_name' => $design->name,
                'total_count' => $allExports->count(),
                'summary' => $summary,
                'groups' => $groups,
                'variants_list' => $variantsList
            ]);
        } catch (\Exception $e) {
            Log::error('Error en getAllDesignExportsGrouped: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Error al cargar exportaciones.'], 500);
        }
    }

    // =====================================================================
    // MÉTODOS PRIVADOS
    // =====================================================================

    private function sanitizeText(?string $text): ?string
    {
        if ($text === null) return null;

        $text = strip_tags($text);
        $dangerousChars = ['<', '>', '"', "'", '&', '\\', '`', '{', '}', '|', '´', '¨', '^', '~'];
        $text = str_replace($dangerousChars, '', $text);
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    private function formatExportForJson(DesignExport $export): array
    {
        $colors = [];
        if ($export->colors_detected) {
            try {
                $colors = is_string($export->colors_detected)
                    ? json_decode($export->colors_detected, true)
                    : $export->colors_detected;
            } catch (\Exception $e) {
                $colors = [];
            }
        }

        // Determinar la imagen a mostrar (vinculada, de variante o del diseño)
        $imageUrl = null;
        if ($export->image_id && $export->image) {
            // Imagen vinculada directamente
            $imageUrl = asset('storage/' . $export->image->file_path);
        } elseif ($export->variant && $export->variant->images->count() > 0) {
            // Primera imagen de la variante
            $imageUrl = asset('storage/' . $export->variant->images->first()->file_path);
        } elseif ($export->design && $export->design->primaryImage) {
            // Imagen principal del diseño
            $imageUrl = asset('storage/' . $export->design->primaryImage->file_path);
        }

        return [
            'id' => $export->id,
            'design_id' => $export->design_id,
            'design_variant_id' => $export->design_variant_id,
            'image_id' => $export->image_id,
            'image_url' => $imageUrl,
            'variant_name' => $export->variant?->name ?? null,
            'application_label' => $export->application_label ?? 'Sin nombre',
            'application_type' => $export->application_type,
            'application_type_label' => $this->getApplicationTypeLabel($export->application_type),
            'placement_description' => $export->placement_description,
            'file_name' => $export->file_name,
            'file_format' => $export->file_format,
            'file_size' => $export->file_size,
            'file_size_formatted' => $this->formatFileSize($export->file_size),
            'stitches_count' => $export->stitches_count ?? 0,
            'stitches_formatted' => $export->stitches_count ? number_format($export->stitches_count, 0, '.', ',') : '--',
            'dimensions_formatted' => ($export->width_mm && $export->height_mm)
                ? "{$export->width_mm} x {$export->height_mm} mm" : '--',
            'width_mm' => $export->width_mm,
            'height_mm' => $export->height_mm,
            'colors_count' => $export->colors_count ?? 0,
            'colors_detected' => $colors,
            'status' => $export->status ?? 'borrador',
            'status_label' => $this->getStatusLabel($export->status),
            'status_color' => $this->getStatusColor($export->status),
            'auto_read_success' => (bool) $export->auto_read_success,
            'svg_content' => $export->svg_content, // Retornar SVG content
            'notes' => $export->notes,
            'created_at' => $export->created_at->format('d/m/Y H:i'),
            'updated_at' => $export->updated_at->format('d/m/Y H:i'),
            'creator_name' => $export->creator->name ?? 'Sistema',
            'approver_name' => $export->approver->name ?? null,
            'approved_at' => $export->approved_at?->format('d/m/Y H:i'),
            'download_url' => route('admin.production.download', $export),
            'edit_url' => route('admin.production.edit', $export),
        ];
    }

    private function generateFileName(array $data, $file): string
    {
        $designId = $data['design_id'] ?? '0';
        $variantId = $data['design_variant_id'] ?? '0';
        $application = Str::slug(substr($data['application_label'] ?? 'export', 0, 30));
        $timestamp = time();
        $extension = $file->getClientOriginalExtension();
        return "d{$designId}_v{$variantId}_{$application}_{$timestamp}.{$extension}";
    }

    private function getApplicationTypeLabel(?string $type): string
    {
        $labels = [
            'pecho'           => 'Pecho',
            'espalda'         => 'Espalda',
            'manga_izquierda' => 'Manga Izquierda',
            'manga_izq'       => 'Manga Izquierda', // Alias
            'manga_derecha'   => 'Manga Derecha',
            'manga_der'       => 'Manga Derecha',   // Alias
            'gorra'           => 'Gorra',
            'bolsillo'        => 'Bolsillo',
            'cuello'          => 'Cuello',
            'nuca'            => 'Nuca (Espalda)',
            'parche'          => 'Parche',
            'otro'            => 'Otro',
        ];
        return $labels[$type] ?? ucfirst($type ?? 'Desconocido');
    }

    private function getStatusLabel(?string $status): string
    {
        $labels = ['borrador' => 'Borrador', 'pendiente' => 'Pendiente', 'aprobado' => 'Aprobado', 'archivado' => 'Archivado'];
        return $labels[$status] ?? 'Desconocido';
    }

    private function getStatusColor(?string $status): string
    {
        $colors = ['borrador' => '#6b7280', 'pendiente' => '#f59e0b', 'aprobado' => '#10b981', 'archivado' => '#1f2937'];
        return $colors[$status] ?? '#6b7280';
    }

    private function formatFileSize(?int $bytes): string
    {
        if (!$bytes) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.1f %s", $bytes / pow(1024, $factor), $units[$factor]);
    }
}
