<?php

namespace App\Http\Controllers;

use App\Models\Design;
use App\Models\DesignVariant;
use App\Models\Attribute;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class DesignVariantController extends Controller
{
    protected ImageService $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    public function create(Design $design)
    {
        try {
            $attributes = Attribute::with('values')
                ->orderBy('order')
                ->get();

            return view('admin.design-variants.create', compact('design', 'attributes'));
        } catch (\Exception $e) {
            Log::error('Error al cargar formulario de creación de variante: ' . $e->getMessage());
            return redirect()->route('admin.designs.index')
                ->with('error', 'Error al cargar formulario de creación de variante')
                ->with('icon', 'error');
            abort(500);
        }
    }

    /**
     * ============================================================
     * GUARDAR VARIANTE (STORE)
     * ============================================================
     */
    public function store(Request $request, Design $design)
    {
        Log::info('=== [PASO 0] INICIO MÉTODO STORE ===', [
            'design_id' => $design->id,
            'all_data' => $request->except(['_token', 'variant_images'])
        ]);

        // 1. Verificación inmediata de archivos
        if ($request->hasFile('variant_images')) {
            Log::info('=== [PASO 1] ARCHIVOS DETECTADOS ===', [
                'count' => count($request->file('variant_images')),
                'details' => array_map(fn($f) => $f->getClientOriginalName(), $request->file('variant_images'))
            ]);
        } else {
            Log::warning('=== [PASO 1] ADVERTENCIA: No se detectaron archivos en "variant_images" ===');
        }

        /**
         * 1. LIMPIEZA DE ATRIBUTOS (si se proporcionan)
         */
        if ($request->has('attribute_values')) {
            $request->merge([
                'attribute_values' => array_values(array_filter(
                    (array) $request->input('attribute_values'),
                    fn($value) => !is_null($value) && $value !== ''
                ))
            ]);
        }

        /**
         * 2. VALIDACIÓN (Simplificada - sin precio ni atributos obligatorios)
         */
        $rules = [
            'sku' => 'required|string|max:255|unique:design_variants,sku',
            'name' => 'required|string|max:255|unique:design_variants,name',
            'price' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'is_default' => 'nullable',
            'attribute_values' => 'nullable|array',
            'attribute_values.*' => 'exists:attribute_values,id',
            'variant_images' => 'nullable|array',
            'variant_images.*' => 'file|mimes:jpg,jpeg,png,webp,avif|max:10240',
            'primary_image_index' => 'nullable|integer|min:0',
        ];

        // Si hay imágenes, el índice de imagen principal es requerido
        if ($request->hasFile('variant_images')) {
            $rules['primary_image_index'] = 'required|integer|min:0';
        }

        $messages = [
            'primary_image_index.required' => 'Debes seleccionar una imagen principal para la variante.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            Log::error('=== [ERROR] FALLO DE VALIDACIÓN ===', [
                'errors' => $validator->errors()->toArray()
            ]);

            // Si es petición AJAX, devolver JSON
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()->toArray()
                ], 422);
            }

            return back()->with('error', 'Error al cargar formulario de creación de variante')
                ->with('icon', 'error')
                ->withInput();
        }

        Log::info('=== [PASO 2] VALIDACIÓN EXITOSA ===');
        $validated = $validator->validated();

        DB::beginTransaction();
        try {
            /**
             * 3. MANEJO DE VARIANTE POR DEFECTO
             */
            $isDefault = $request->input('is_default') == "1";
            if ($isDefault) {
                Log::info('=== [PASO 3] RESETEANDO OTRAS VARIANTES DEFAULT ===');
                $design->variants()->update(['is_default' => false]);
            }

            /**
             * 4. CREAR VARIANTE
             */
            $variant = $design->variants()->create([
                'sku' => $validated['sku'],
                'name' => $validated['name'],
                'price' => $validated['price'] ?? 0,
                'stock' => $validated['stock'] ?? 0,
                'is_active' => $request->input('is_active', "1") == "1",
                'is_default' => $isDefault,
                'activated_at' => now(),
            ]);

            Log::info('=== [PASO 4] VARIANTE CREADA EN BD ===', ['variant_id' => $variant->id]);

            /**
             * 5. SINCRONIZAR ATRIBUTOS (si se proporcionan)
             */
            if (!empty($validated['attribute_values'])) {
                $variant->attributeValues()->sync($validated['attribute_values']);
                Log::info('=== [PASO 5] ATRIBUTOS SINCRONIZADOS ===');
            } else {
                Log::info('=== [PASO 5] SIN ATRIBUTOS PARA SINCRONIZAR ===');
            }

            /**
             * 6. SUBIDA DE IMÁGENES
             */
            if ($request->hasFile('variant_images')) {
                Log::info('=== [PASO 6] INICIANDO SUBIDA DE IMÁGENES ===');

                // Obtener índice de imagen principal (0 por defecto)
                $primaryIndex = (int) $request->input('primary_image_index', 0);

                foreach ($request->file('variant_images') as $index => $image) {
                    $this->imageService->uploadImage(
                        $image,
                        DesignVariant::class,
                        $variant->id,
                        [
                            'design_name'   => $design->name,
                            'variant_name'  => $variant->name,
                            'variant_sku'   => $variant->sku,
                            'alt_text'      => $variant->name,
                            'is_primary'    => $index === $primaryIndex,
                            'image_context' => 'variant',
                            'order'         => $index,
                        ]
                    );
                }
                Log::info('=== [PASO 6] TODAS LAS IMÁGENES PROCESADAS EXITOSAMENTE ===', [
                    'primary_index' => $primaryIndex
                ]);
            }

            DB::commit();
            Log::info('=== [ÉXITO FINAL] TRANSACCIÓN COMPLETADA ===');

            // Si es petición AJAX, devolver JSON
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Variante guardada correctamente',
                    'variant_id' => $variant->id,
                    'redirect' => route('admin.designs.index')
                ]);
            }

            return redirect()
                ->route('admin.designs.index')
                ->with('success', 'Variante guardada correctamente')
                ->with('icon', 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('=== [ERROR CRÍTICO] FALLO EN EL PROCESO STORE ===', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Si es petición AJAX, devolver JSON de error
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al guardar la variante: ' . $e->getMessage(),
                    'errors' => []
                ], 500);
            }

            return back()
                ->with('error', 'Error al guardar la variante: ' . $e->getMessage())
                ->withInput()
                ->with('icon', 'error');
        }
    }

    public function edit(Design $design, DesignVariant $variant)
    {
        $variant->load('images');
        $attributes = Attribute::with('values')->orderBy('order')->get();
        $selectedAttributeValues = $variant->attributeValues->pluck('id')->toArray();

        return view('admin.design-variants.edit', compact('design', 'variant', 'attributes', 'selectedAttributeValues'));
    }

    /**
     * ============================================================
     * ACTUALIZAR VARIANTE (UPDATE)
     * ============================================================
     */
    public function update(Request $request, Design $design, DesignVariant $variant)
    {
        Log::info('=== INICIO ACTUALIZACIÓN DE VARIANTE ===', ['id' => $variant->id]);

        if ($request->has('attribute_values')) {
            $request->merge([
                'attribute_values' => array_values(array_filter(
                    (array) $request->input('attribute_values'),
                    fn($value) => !is_null($value) && $value !== ''
                ))
            ]);
        }

        $validator = Validator::make($request->all(), [
            'sku' => 'required|string|max:255|unique:design_variants,sku,' . $variant->id,
            'name' => 'required|string|max:255|unique:design_variants,name,' . $variant->id,
            'price' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'is_default' => 'nullable',
            'attribute_values' => 'nullable|array',
            'attribute_values.*' => 'exists:attribute_values,id',
            'variant_images' => 'nullable|array',
            'variant_images.*' => 'file|mimes:jpg,jpeg,png,webp,avif|max:10240',
            'primary_image_id' => 'nullable|integer|exists:images,id',
        ]);

        if ($validator->fails()) {
            Log::error('Error de validación en update:', $validator->errors()->toArray());
            return back()->with('error', 'Error al cargar formulario de actualización de variante')
                ->with('icon', 'error')
                ->withInput();
        }

        $validated = $validator->validated();
        DB::beginTransaction();

        try {
            $isDefault = $request->input('is_default') == "1";
            if ($isDefault) {
                $design->variants()->where('id', '!=', $variant->id)->update(['is_default' => false]);
            }

            $variant->update([
                'sku' => $validated['sku'],
                'name' => $validated['name'],
                'price' => $validated['price'] ?? $variant->price ?? 0,
                'stock' => $validated['stock'] ?? $variant->stock,
                'is_default' => $isDefault,
                'is_active' => $request->input('is_active', "1") == "1",
                'activated_at' => $variant->activated_at ?? now(),
            ]);

            // Sincronizar atributos solo si se proporcionan
            if (!empty($validated['attribute_values'])) {
                $variant->attributeValues()->sync($validated['attribute_values']);
            }

            // Actualizar imagen principal si se proporcionó
            if ($request->filled('primary_image_id')) {
                $primaryImageId = $request->input('primary_image_id');

                // Quitar is_primary de todas las imágenes de la variante
                $variant->images()->update(['is_primary' => false]);

                // Establecer la nueva imagen principal
                $variant->images()->where('id', $primaryImageId)->update(['is_primary' => true]);

                Log::info('Imagen principal actualizada', [
                    'variant_id' => $variant->id,
                    'primary_image_id' => $primaryImageId
                ]);
            }

            if ($request->hasFile('variant_images')) {
                Log::info('Subiendo nuevas imágenes en update...');
                // Buscamos el último orden para no sobrescribir posiciones
                $lastOrder = $variant->images()->max('order') ?? -1;

                foreach ($request->file('variant_images') as $index => $image) {
                    $this->imageService->uploadImage(
                        $image,
                        DesignVariant::class,
                        $variant->id,
                        [
                            'design_name'   => $design->name,
                            'variant_name'  => $variant->name,
                            'variant_sku'   => $variant->sku,
                            'alt_text'      => $variant->name,
                            'is_primary'    => false, // En update, las nuevas no suelen ser primarias por defecto
                            'image_context' => 'variant',
                            'order'         => $lastOrder + $index + 1,
                        ]
                    );
                }
            }

            DB::commit();
            return redirect()
                ->route('admin.designs.index')
                ->with('success', 'Variante actualizada correctamente');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en update variante: ' . $e->getMessage());
            return back()->with('error', 'Error al actualizar: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(Design $design, DesignVariant $variant)
    {
        try {
            DB::beginTransaction();
            foreach ($variant->images as $image) {
                $this->imageService->deleteImage($image);
            }
            $variant->delete();
            DB::commit();

            return redirect()
                ->route('admin.designs.index')
                ->with('success', 'Variante eliminada exitosamente')
                ->with('icon', 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar variante: ' . $e->getMessage());
            return back()->with('error', 'Error al eliminar variante')
                ->with('icon', 'error');
        }
    }

    public function destroyImage(Design $design, DesignVariant $variant, $imageId)
    {
        try {
            $image = $variant->images()->findOrFail($imageId);
            $this->imageService->deleteImage($image);
            return back()->with('success', 'Imagen eliminada correctamente')
                ->with('icon', 'success');
        } catch (\Exception $e) {
            Log::error('Error al eliminar imagen de variante: ' . $e->getMessage());
            return back()->with('error', 'Error al eliminar imagen')
                ->with('icon', 'error');
        }
    }
}
