<?php

namespace App\Http\Controllers;

use App\Models\Design;
use App\Models\Category;
use App\Models\Image;
use App\Services\DesignService;
use App\Services\ImageService;
use App\Services\Search\SearchService;
use App\Http\Requests\StoreDesignRequest;
use App\Http\Requests\UpdateDesignRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Session;

class DesignController extends Controller
{
    protected $designService;
    protected $imageService;
    protected $searchService;

    public function __construct(DesignService $designService, ImageService $imageService, SearchService $searchService)
    {
        $this->designService = $designService;
        $this->imageService = $imageService;
        $this->searchService = $searchService;
        $this->middleware('secure.file.upload')->only(['store', 'update']);
    }

    public function index(Request $request)
    {
        try {
            $baseQuery = Design::query()
                ->with(['categories', 'primaryImage', 'variants', 'variants.primaryImage'])
                ->where('is_active', true);

            // Variable para almacenar IDs de búsqueda (usada también en conteo de categorías)
            $matchingIds = [];

            // Búsqueda avanzada con normalización española
            if ($request->filled('search')) {
                $searchTerm = $request->search;

                // Obtener IDs que coinciden con búsqueda normalizada
                $matchingIds = $this->searchService->getMatchingIds(
                    $searchTerm,
                    Design::class,
                    ['limit' => 500]
                );

                if (!empty($matchingIds)) {
                    // Usar los IDs encontrados, mantener relevancia
                    $idsString = implode(',', $matchingIds);
                    $baseQuery->whereIn('id', $matchingIds)
                        ->orderByRaw("FIELD(id, {$idsString})");
                } else {
                    // Fallback: búsqueda LIKE tradicional si el índice no tiene resultados
                    $baseQuery->where(function ($q) use ($searchTerm) {
                        $q->where('name', 'like', "%{$searchTerm}%")
                            ->orWhere('description', 'like', "%{$searchTerm}%");
                    });
                }
            }

            $activeCategory = null;
            if ($request->filled('category')) {
                $activeCategory = Category::where('slug', $request->category)
                    ->where('is_active', true)
                    ->first();

                if ($activeCategory) {
                    $baseQuery->whereHas('categories', function ($q) use ($activeCategory) {
                        $q->where('categories.id', $activeCategory->id);
                    });
                }
            }

            $designs = (clone $baseQuery)
                ->orderByDesc('created_at')
                ->paginate(12)
                ->withQueryString();

            $categories = Category::where('is_active', true)
                ->orderBy('name')
                ->get();

            $categoryCounts = [];
            foreach ($categories as $category) {
                $countQuery = Design::query()
                    ->where('is_active', true);

                // Usar misma lógica de búsqueda avanzada para el conteo
                if ($request->filled('search') && !empty($matchingIds)) {
                    $countQuery->whereIn('id', $matchingIds);
                } elseif ($request->filled('search')) {
                    $countQuery->where(function ($q) use ($request) {
                        $q->where('name', 'like', '%' . $request->search . '%')
                            ->orWhere('description', 'like', '%' . $request->search . '%');
                    });
                }

                $categoryCounts[$category->id] = $countQuery
                    ->whereHas('categories', fn($q) => $q->where('categories.id', $category->id))
                    ->count();
            }

            return view('admin.designs.index', compact(
                'designs',
                'categories',
                'activeCategory',
                'categoryCounts'
            ));
        } catch (\Exception $e) {
            Log::error('Error al cargar listado de diseños: ' . $e->getMessage());
            return redirect()
                ->route('admin.designs.index')
                ->with('icon', 'error')
                ->with('error', 'Error al cargar los diseños: ' . $e->getMessage());
        }
    }

    /**
     * Obtener listado de diseños vía AJAX (para búsqueda sin reload).
     * Soporta filtro por categoría.
     */
    public function ajaxList(Request $request)
    {
        try {
            $query = Design::with(['primaryImage', 'categories', 'variants', 'exports'])
                ->where('is_active', true);

            // Filtro por categoría si se especifica
            if ($request->filled('category')) {
                $query->whereHas('categories', function ($q) use ($request) {
                    $q->where('slug', $request->category);
                });
            }

            $designs = $query->orderByDesc('created_at')
                ->limit(100)
                ->get();

            $formattedDesigns = $designs->map(function ($design) {
                return [
                    'id' => $design->id,
                    'name' => $design->name,
                    'slug' => $design->slug,
                    'description' => $design->description,
                    'image' => $design->primaryImage
                        ? asset('storage/' . $design->primaryImage->file_path)
                        : null,
                    'categories' => $design->categories->pluck('name')->toArray(),
                    'variants_count' => $design->variants->count(),
                    'exports_count' => $design->exports->count(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedDesigns,
                'total' => $formattedDesigns->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error en ajaxList: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar diseños',
                'data' => [],
            ], 500);
        }
    }

    public function show(Design $design)
    {
        try {
            $design->load([
                'categories',
                'primaryImage',
                'variants.images' => function ($q) {
                    $q->orderBy('order');
                },
                'variants.attributeValues'
            ]);

            // Cargar conteos de exports (todas las exportaciones del diseño)
            $design->loadCount('exports');

            $formattedDesign = [
                'id' => $design->id,
                'name' => $design->name,
                'slug' => $design->slug,
                'description' => $design->description,
                'is_active' => $design->is_active,
                'variants_count' => $design->variants->count(),
                'exports_count' => $design->exports_count ?? 0,
                'primaryImage' => $design->primaryImage ? [
                    'id' => $design->primaryImage->id,
                    'file_path' => $design->primaryImage->file_path,
                    'thumbnail_small' => $design->primaryImage->thumbnail_small,
                    'thumbnail_medium' => $design->primaryImage->thumbnail_medium,
                    'alt_text' => $design->primaryImage->alt_text,
                    'is_primary' => $design->primaryImage->is_primary,
                    'order' => $design->primaryImage->order
                ] : null,
                'variants' => $design->variants->map(function ($variant) {
                    return [
                        'id' => $variant->id,
                        'name' => $variant->name,
                        'sku' => $variant->sku,
                        'price' => $variant->price,
                        'stock' => $variant->stock,
                        'is_active' => $variant->is_active,
                        'is_default' => $variant->is_default,
                        'images' => $variant->images->map(function ($image) {
                            return [
                                'id' => $image->id,
                                'file_path' => $image->file_path,
                                'thumbnail_small' => $image->thumbnail_small,
                                'thumbnail_medium' => $image->thumbnail_medium,
                                'alt_text' => $image->alt_text,
                                'is_primary' => $image->is_primary,
                                'order' => $image->order
                            ];
                        })
                    ];
                })
            ];

            return response()->json([
                'success' => true,
                'design' => $formattedDesign
            ]);
        } catch (\Exception $e) {
            Log::error('Error al cargar detalles del diseño: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar detalles: ' . $e->getMessage()
            ], 500);
        }
    }

    public function create()
    {
        try {
            $categories = Category::where('is_active', true)
                ->orderBy('name')
                ->get();

            // Recuperar datos temporales si existen
            $tempImageData = Session::get('temp_image_data');

            return view('admin.designs.create', compact('categories', 'tempImageData'));
        } catch (\Exception $e) {
            Log::error('Error al cargar formulario de creación: ' . $e->getMessage());
            return redirect()
                ->route('admin.designs.create')
                ->with('icon', 'error')
                ->with('error', 'Error al cargar el formulario: ' . $e->getMessage());
        }
    }

    public function store(StoreDesignRequest $request)
    {
        DB::beginTransaction();

        try {
            Log::info('=== INICIO CREACIÓN DE DISEÑO ===');
            Log::info('Datos recibidos:', $request->only(['name', 'description', 'categories']));
            Log::info('¿Tiene archivo?', ['hasFile' => $request->hasFile('image')]);

            // Obtener información de validación del middleware
            $detectedType = $request->input('_file_image_type', 'image');
            $detectedFormat = $request->input('_file_image_format');
            $correctExtension = $request->input('_file_image_extension');
            $uploadNote = $request->input('_file_image_note');

            if ($uploadNote) {
                Log::info('Nota del middleware: ' . $uploadNote);
            }

            // Preparar datos del diseño
            $designData = $request->validated();

            // Crear diseño usando el servicio
            $design = $this->designService->createDesign($designData);
            Log::info('Diseño creado con ID: ' . $design->id);

            // Procesar archivo temporal si existe
            $tempImageData = Session::get('temp_image_data');
            if ($tempImageData && isset($tempImageData['temp_path'])) {
                $this->processTempImage($design, $tempImageData);
            } elseif ($request->hasFile('image')) {
                // Guardar archivo si fue subido
                $file = $request->file('image');
                $originalExtension = strtolower($file->getClientOriginalExtension());

                Log::info('Información del archivo:', [
                    'nombre' => $file->getClientOriginalName(),
                    'tamaño' => $file->getSize(),
                    'tipo_mime' => $file->getMimeType(),
                    'extensión_original' => $originalExtension,
                    'extensión_correcta' => $correctExtension,
                    'tipo_detectado' => $detectedType,
                    'formato_detectado' => $detectedFormat,
                    'nota' => $uploadNote,
                    'válido' => $file->isValid(),
                ]);

                // Determinar tipo de archivo basado en detección del middleware
                if ($detectedType === 'image' || $detectedType === 'vector') {
                    // Usar el ImageService existente para imágenes/vectores
                    // [ACTUALIZACIÓN]: Se pasa 'forced_extension' para que ImageService use la corrección del middleware
                    $image = $this->imageService->uploadImage(
                        $file,
                        'App\Models\Design',
                        $design->id,
                        [
                            'design_name' => $design->name,
                            'alt_text' => $request->name,
                            'is_primary' => true,
                            'order' => 0,
                            'forced_extension' => $correctExtension // [ACTUALIZACIÓN]
                        ]
                    );

                    Log::info('Archivo guardado con ID: ' . $image->id);
                    Log::info('Ruta del archivo: ' . $image->file_path);

                    // Actualizar información adicional del archivo en el diseño
                    $design->update([
                        'file_type' => $detectedType,
                        'file_extension' => $correctExtension ?? $originalExtension,
                        'file_size' => $file->getSize(),
                        'original_filename' => $file->getClientOriginalName(),
                        'detected_format' => $detectedFormat,
                    ]);

                    // Mostrar mensaje al usuario si la extensión fue corregida
                    if ($uploadNote && Str::contains($uploadNote, 'será corregida')) {
                        session()->flash('extension_warning', $uploadNote);
                    }
                } elseif ($detectedType === 'embroidery') {
                    // Para archivos de bordado, usar el proceso existente
                    $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    // [ACTUALIZACIÓN]: Usar correctExtension si está disponible
                    $finalExt = $correctExtension ?? $originalExtension;
                    $fileName = Str::slug($originalName) . '_' . time() . '_' . Str::random(8) . '.' . $finalExt;
                    $path = $file->storeAs('designs/embroidery', $fileName, 'public');

                    // Crear registro en tabla images para mantener consistencia
                    $image = Image::create([
                        'file_path' => $path,
                        'alt_text' => $request->name . ' (Archivo de bordado)',
                        'is_primary' => true,
                        'order' => 0,
                        'imageable_type' => 'App\Models\Design',
                        'imageable_id' => $design->id,
                        'original_extension' => $originalExtension,
                        'correct_extension' => $correctExtension,
                    ]);

                    // Actualizar diseño con información del archivo
                    $design->update([
                        'file_type' => 'embroidery',
                        'file_extension' => $correctExtension ?? $originalExtension,
                        'file_size' => $file->getSize(),
                        'original_filename' => $file->getClientOriginalName(),
                        'embroidery_file_path' => $path,
                        'detected_format' => $detectedFormat,
                    ]);

                    Log::info('Archivo de bordado guardado en: ' . $path);
                }
            } else {
                Log::warning('No se recibió ningún archivo en la petición');
            }

            DB::commit();
            Log::info('=== FIN CREACIÓN DE DISEÑO EXITOSA ===');

            // Limpiar datos temporales si existen
            Session::forget('temp_image_data');

            // Si es una solicitud AJAX, responder con JSON
            if ($request->ajax() || $request->wantsJson()) {
                $successMessage = 'Diseño ' . mb_strtoupper($design->name, 'UTF-8') . ' creado exitosamente';

                // Guardar mensaje en sesión para que aparezca el SweetAlert en el index
                Session::flash('success', $successMessage);

                return response()->json([
                    'success' => true,
                    'message' => $successMessage,
                    'redirect' => route('admin.designs.index'),
                    'design' => [
                        'id' => $design->id,
                        'name' => $design->name,
                    ]
                ]);
            }

            // Preparar respuesta con posible advertencia
            $response = redirect()
                ->route('admin.designs.index')
                ->with('success', 'Diseño ' . mb_strtoupper($request->name, 'UTF-8') . ' creado exitosamente')
                ->with('icon', 'success');

            if (session()->has('extension_warning')) {
                $response->with('warning', session('extension_warning'));
            }

            return $response;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('=== ERROR AL CREAR DISEÑO ===');
            Log::error('Mensaje: ' . $e->getMessage());
            Log::error('Archivo: ' . $e->getFile());
            Log::error('Línea: ' . $e->getLine());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            // Guardar temporalmente la información del archivo si fue subido
            if ($request->hasFile('image')) {
                $file = $request->file('image');

                // Almacenar archivo temporalmente
                $tempFileName = 'temp_' . time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
                $tempPath = $file->storeAs('temp', $tempFileName, 'public');

                // Guardar información del archivo en sesión
                Session::put('temp_image_data', [
                    'temp_path' => $tempPath,
                    'original_name' => $file->getClientOriginalName(),
                    'original_extension' => $file->getClientOriginalExtension(),
                    'detected_type' => $request->input('_file_image_type', 'image'),
                    'detected_format' => $request->input('_file_image_format'),
                    'correct_extension' => $request->input('_file_image_extension'),
                    'upload_note' => $request->input('_file_image_note'),
                    'file_size' => $file->getSize(),
                    'original_filename' => $file->getClientOriginalName(),
                ]);
            }

            // Si es una solicitud AJAX, responder con JSON
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al crear el diseño: ' . $e->getMessage(),
                    'errors' => []
                ], 500);
            }

            return redirect()
                ->route('admin.designs.create')
                ->with('icon', 'error')
                ->withInput()
                ->with('error', 'Error al crear el diseño: ' . $e->getMessage());
        }
    }

    public function edit(Design $design)
    {
        try {
            $categories = Category::where('is_active', true)
                ->orderBy('name')
                ->get();

            $selectedCategories = $design->categories->pluck('id')->toArray();

            $design->load(['images' => function ($query) {
                $query->orderBy('order', 'asc');
            }]);

            return view('admin.designs.edit', compact(
                'design',
                'categories',
                'selectedCategories'
            ));
        } catch (\Exception $e) {
            Log::error('Error al cargar formulario de edición: ' . $e->getMessage());
            return redirect()
                ->route('admin.designs.edit', $design)
                ->with('icon', 'error')
                ->withInput()
                ->with('error', 'Error al cargar el formulario de edición: ' . $e->getMessage());
        }
    }

    public function update(UpdateDesignRequest $request, Design $design)
    {
        DB::beginTransaction();

        try {
            Log::info('=== INICIO ACTUALIZACIÓN DE DISEÑO ===');
            Log::info('ID del diseño: ' . $design->id);

            $updatedDesign = $this->designService->updateDesign($design, $request->validated());
            Log::info('Diseño actualizado correctamente');

            // Procesar archivo temporal si existe
            $tempImageData = Session::get('temp_image_data');
            if ($tempImageData && isset($tempImageData['temp_path'])) {
                $this->processTempImage($design, $tempImageData);
            } elseif ($request->hasFile('image')) {
                // ✅ Actualizar o agregar nuevo archivo si fue subido
                Log::info('Nuevo archivo recibido');

                $file = $request->file('image');
                $extension = strtolower($file->getClientOriginalExtension());

                // [ACTUALIZACIÓN]: Capturamos todos los datos del middleware
                $correctExtension = $request->input('_file_image_extension', $extension);
                $detectedType = $request->input('_file_image_type');
                $detectedFormat = $request->input('_file_image_format');

                // Determinar tipo de archivo
                $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'avif', 'svg', 'svgz'];
                $embroideryExtensions = ['pes', 'dst', 'exp', 'xxx', 'jef', 'vp3', 'hus', 'pec', 'phc', 'sew', 'shv', 'csd', '10o', 'bro'];

                if (in_array($extension, $imageExtensions) || in_array($correctExtension, $imageExtensions) || $detectedType === 'image') {
                    // Para imágenes/vectores, usar el ImageService
                    $design->images()->update(['is_primary' => false]);

                    // [ACTUALIZACIÓN]: Se pasa forced_extension al service
                    $image = $this->imageService->uploadImage(
                        $file,
                        'App\Models\Design',
                        $design->id,
                        [
                            'design_name' => $design->name,
                            'alt_text' => $request->name,
                            'image_context' => 'design',
                            'is_primary' => true,
                            'order' => $design->images()->max('order') + 1,
                            'forced_extension' => $correctExtension // [ACTUALIZACIÓN]
                        ]
                    );

                    Log::info('Nueva imagen/vector guardado con ID: ' . $image->id);

                    // Actualizar información del archivo
                    $design->update([
                        'file_type' => in_array($correctExtension, ['svg', 'svgz']) ? 'vector' : 'image',
                        'file_extension' => $correctExtension,
                        'file_size' => $file->getSize(),
                        'original_filename' => $file->getClientOriginalName(),
                        'detected_format' => $detectedFormat, // [ACTUALIZACIÓN]
                    ]);
                } elseif (in_array($extension, $embroideryExtensions) || $detectedType === 'embroidery') {
                    // Para archivos de bordado
                    // Eliminar archivo anterior si existe
                    if ($design->embroidery_file_path && Storage::disk('public')->exists($design->embroidery_file_path)) {
                        Storage::disk('public')->delete($design->embroidery_file_path);
                    }

                    // Eliminar imágenes anteriores relacionadas
                    $design->images()->delete();

                    // Guardar nuevo archivo
                    $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $finalExt = $correctExtension ?? $extension;
                    $fileName = Str::slug($originalName) . '_' . time() . '_' . Str::random(8) . '.' . $finalExt;
                    $path = $file->storeAs('designs/embroidery', $fileName, 'public');

                    // Crear registro en tabla images
                    Image::create([
                        'file_path' => $path,
                        'alt_text' => $request->name . ' (Archivo de bordado)',
                        'is_primary' => true,
                        'order' => 0,
                        'imageable_type' => 'App\Models\Design',
                        'imageable_id' => $design->id,
                    ]);

                    // Actualizar información del archivo
                    $design->update([
                        'file_type' => 'embroidery',
                        'file_extension' => $finalExt,
                        'file_size' => $file->getSize(),
                        'original_filename' => $file->getClientOriginalName(),
                        'embroidery_file_path' => $path,
                        'detected_format' => $detectedFormat, // [ACTUALIZACIÓN]
                    ]);

                    Log::info('Nuevo archivo de bordado guardado en: ' . $path);
                }
            }

            DB::commit();
            Log::info('=== FIN ACTUALIZACIÓN EXITOSA ===');

            // Limpiar datos temporales
            Session::forget('temp_image_data');

            // Si es una solicitud AJAX, responder con JSON
            if ($request->ajax() || $request->wantsJson()) {
                $successMessage = 'Diseño ' . mb_strtoupper($updatedDesign->name, 'UTF-8') . ' actualizado exitosamente';

                // Guardar mensaje en sesión para que aparezca el SweetAlert en el index
                Session::flash('success', $successMessage);

                return response()->json([
                    'success' => true,
                    'message' => $successMessage,
                    'redirect' => route('admin.designs.index'),
                    'design' => [
                        'id' => $updatedDesign->id,
                        'name' => $updatedDesign->name,
                    ]
                ]);
            }

            return redirect()
                ->route('admin.designs.index')
                ->with('success', 'Diseño ' . mb_strtoupper($updatedDesign->name, 'UTF-8') . ' actualizado exitosamente')
                ->with('icon', 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('=== ERROR AL ACTUALIZAR DISEÑO ===');
            Log::error('Mensaje: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            // Si hay un nuevo archivo subido, guardarlo temporalmente
            if ($request->hasFile('image')) {
                $file = $request->file('image');

                // Almacenar archivo temporalmente
                $tempFileName = 'temp_' . time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
                $tempPath = $file->storeAs('temp', $tempFileName, 'public');

                // Guardar información del archivo en sesión
                Session::put('temp_image_data', [
                    'temp_path' => $tempPath,
                    'original_name' => $file->getClientOriginalName(),
                    'original_extension' => $file->getClientOriginalExtension(),
                    'detected_type' => $request->input('_file_image_type', 'image'),
                    'detected_format' => $request->input('_file_image_format'),
                    'correct_extension' => $request->input('_file_image_extension'),
                    'upload_note' => $request->input('_file_image_note'),
                    'file_size' => $file->getSize(),
                ]);
            }

            // Si es una solicitud AJAX, responder con JSON
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al actualizar el diseño: ' . $e->getMessage(),
                    'errors' => []
                ], 500);
            }

            return redirect()
                ->route('admin.designs.edit', $design)
                ->with('icon', 'error')
                ->withInput()
                ->with('error', 'Error al actualizar el diseño: ' . $e->getMessage());
        }
    }

    public function destroy(Design $design)
    {
        DB::beginTransaction();

        try {
            Log::info('=== INICIO ELIMINACIÓN DE DISEÑO ===', [
                'design_id' => $design->id,
                'design_name' => $design->name
            ]);

            if ($design->deleted_at !== null) {
                Log::warning('Intento de eliminar un diseño ya eliminado', [
                    'design_id' => $design->id
                ]);
                return redirect()
                    ->route('admin.designs.index')
                    ->with('icon', 'warning')
                    ->with('success', 'El diseño ya había sido eliminado previamente.');
            }

            if ($design->variants()->exists()) {
                Log::info('El diseño tiene variantes, desactivándolas');
                $design->variants()->update([
                    'is_active' => false,
                    'deleted_at' => now(),
                ]);
            }

            $design->update(['is_active' => false]);
            $this->designService->deleteDesign($design);

            DB::commit();
            Log::info('=== DISEÑO ELIMINADO CORRECTAMENTE ===', [
                'design_id' => $design->id
            ]);

            return redirect()
                ->route('admin.designs.index')
                ->with('success', 'Diseño ' . mb_strtoupper($design->name, 'UTF-8') . ' eliminado exitosamente')
                ->with('icon', 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('=== ERROR AL ELIMINAR DISEÑO ===', [
                'design_id' => $design->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return redirect()
                ->route('admin.designs.index')
                ->with('icon', 'error')
                ->with('error', 'Error al eliminar el diseño: ' . $e->getMessage());
        }
    }

    public function clearTempImage(Request $request)
    {
        try {
            $tempImageData = Session::get('temp_image_data');

            if ($tempImageData && isset($tempImageData['temp_path'])) {
                if (Storage::disk('public')->exists($tempImageData['temp_path'])) {
                    Storage::disk('public')->delete($tempImageData['temp_path']);
                }
                Session::forget('temp_image_data');
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Error al limpiar archivo temporal: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function processTempImage($design, $tempImageData)
    {
        try {
            if ($tempImageData && isset($tempImageData['temp_path'])) {
                $tempPath = $tempImageData['temp_path'];

                if (Storage::disk('public')->exists($tempPath)) {
                    $originalName = $tempImageData['original_name'];
                    $detectedType = $tempImageData['detected_type'];
                    $correctExtension = $tempImageData['correct_extension'] ?? $tempImageData['original_extension'];

                    if ($detectedType === 'image' || $detectedType === 'vector') {
                        // Mover archivo a ubicación permanente
                        $fileName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '_' . time() . '_' . Str::random(8) . '.' . $correctExtension;
                        $newPath = 'designs/images/' . $fileName;

                        Storage::disk('public')->move($tempPath, $newPath);

                        // Crear registro de imagen
                        Image::create([
                            'file_path' => $newPath,
                            'alt_text' => $design->name,
                            'is_primary' => true,
                            'order' => 0,
                            'imageable_type' => 'App\Models\Design',
                            'imageable_id' => $design->id,
                        ]);

                        // Actualizar diseño
                        $design->update([
                            'file_type' => $detectedType,
                            'file_extension' => $correctExtension,
                            'original_filename' => $originalName,
                        ]);

                        Log::info('Archivo temporal procesado y guardado en: ' . $newPath);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error al procesar imagen temporal: ' . $e->getMessage());
        }
    }

    public function downloadFile(Design $design)
    {
        Log::info('📥 Intento de descarga de archivo principal de diseño', [
            'design_id' => $design->id,
            'name' => $design->name,
        ]);

        $filePath = null;
        $fileName = null;

        // 1. Prioridad: Archivo de bordado específico
        if ($design->embroidery_file_path && Storage::disk('public')->exists($design->embroidery_file_path)) {
            $filePath = $design->embroidery_file_path;
            $ext = pathinfo($filePath, PATHINFO_EXTENSION);
            $fileName = Str::slug($design->name) . '.' . $ext;
        }
        // 2. Fallback: Imagen principal
        elseif ($design->primaryImage && $design->primaryImage->file_path && Storage::disk('public')->exists($design->primaryImage->file_path)) {
            $filePath = $design->primaryImage->file_path;
            $ext = pathinfo($filePath, PATHINFO_EXTENSION);
            $fileName = Str::slug($design->name) . '.' . $ext;
        }

        if ($filePath) {
            return Storage::disk('public')->download($filePath, $fileName);
        }

        return back()->with('error', 'No hay archivo disponible para descargar.');
    }

    public function downloadImage(Image $image)
    {
        if ($image->file_path && Storage::disk('public')->exists($image->file_path)) {
            $fileName = $image->original_filename ?? basename($image->file_path);
            return Storage::disk('public')->download($image->file_path, $fileName);
        }
        return back()->with('error', 'Imagen no encontrada.');
    }
}
