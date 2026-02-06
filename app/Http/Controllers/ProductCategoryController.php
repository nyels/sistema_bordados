<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProductCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $categories = ProductCategory::withCount('products')
                ->orderBy('name')
                ->get();

            return view('admin.product_categories.index', compact('categories'));
        } catch (\Exception $e) {
            Log::error('[ProductCategory@index] Error al cargar categorías: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            return redirect()->back()->with('error', 'Error al cargar las categorías de productos.');
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        try {
            return view('admin.product_categories.create');
        } catch (\Exception $e) {
            Log::error('[ProductCategory@create] Error al cargar formulario: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            return redirect()->route('admin.product_categories.index')
                ->with('error', 'Error al cargar el formulario de creación.');
        }
    }

    /**
     * Store a newly created resource in storage.
     * Incluye validación robusta contra SQL injection
     */
    public function store(Request $request)
    {
        // Validación robusta con regex para prevenir SQL injection
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'min:2',
                'max:100',
                'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ0-9\s\-\_\.\,]+$/',
                'unique:product_categories,name'
            ],
            'description' => [
                'nullable',
                'string',
                'max:500',
                'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ0-9\s\-\_\.\,\!\?\(\)\:\;]+$/'
            ],
            'is_active' => ['boolean'],
            'supports_measurements' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'El nombre de la categoría es obligatorio.',
            'name.min' => 'El nombre debe tener al menos 2 caracteres.',
            'name.max' => 'El nombre no puede exceder 100 caracteres.',
            'name.regex' => 'El nombre contiene caracteres no permitidos.',
            'name.unique' => 'Ya existe una categoría con este nombre.',
            'description.max' => 'La descripción no puede exceder 500 caracteres.',
            'description.regex' => 'La descripción contiene caracteres no permitidos.'
        ]);

        try {
            $category = new ProductCategory();
            $category->name = mb_strtoupper(trim($validated['name']), 'UTF-8');
            $category->description = isset($validated['description']) ? trim($validated['description']) : null;
            $category->is_active = $request->has('is_active') ? true : false;
            $category->supports_measurements = $request->boolean('supports_measurements');
            $category->save();

            Log::info('[ProductCategory@store] Categoría creada exitosamente', [
                'user_id' => Auth::id(),
                'category_id' => $category->id,
                'category_name' => $category->name
            ]);

            $msg = 'Categoría "' . $category->name . '" creada exitosamente.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $msg, 'data' => $category]);
            }
            return redirect()->route('admin.product_categories.index')->with('success', $msg);
        } catch (\Exception $e) {
            Log::error('[ProductCategory@store] Error al crear categoría: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'request_data' => $request->except(['_token']),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ]);
            $msg = 'Error al crear la categoría. Por favor, intente nuevamente.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 500);
            }
            return redirect()->back()->withInput()->with('error', $msg);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $category = ProductCategory::with('products')->findOrFail($id);
            return view('admin.product_categories.show', compact('category'));
        } catch (\Exception $e) {
            Log::error('[ProductCategory@show] Error al mostrar categoría: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'category_id' => $id,
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            return redirect()->route('admin.product_categories.index')
                ->with('error', 'Categoría no encontrada.');
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        try {
            $category = ProductCategory::findOrFail($id);
            return view('admin.product_categories.edit', compact('category'));
        } catch (\Exception $e) {
            Log::error('[ProductCategory@edit] Error al cargar formulario de edición: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'category_id' => $id,
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            return redirect()->route('admin.product_categories.index')
                ->with('error', 'Categoría no encontrada.');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $category = ProductCategory::findOrFail($id);

        // Validación robusta con regex para prevenir SQL injection
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'min:2',
                'max:100',
                'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ0-9\s\-\_\.\,]+$/',
                Rule::unique('product_categories', 'name')->ignore($category->id)
            ],
            'description' => [
                'nullable',
                'string',
                'max:500',
                'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ0-9\s\-\_\.\,\!\?\(\)\:\;]+$/'
            ],
            'is_active' => ['boolean'],
            'supports_measurements' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'El nombre de la categoría es obligatorio.',
            'name.min' => 'El nombre debe tener al menos 2 caracteres.',
            'name.max' => 'El nombre no puede exceder 100 caracteres.',
            'name.regex' => 'El nombre contiene caracteres no permitidos.',
            'name.unique' => 'Ya existe una categoría con este nombre.',
            'description.max' => 'La descripción no puede exceder 500 caracteres.',
            'description.regex' => 'La descripción contiene caracteres no permitidos.'
        ]);

        try {
            $category->name = mb_strtoupper(trim($validated['name']), 'UTF-8');
            $category->description = isset($validated['description']) ? trim($validated['description']) : null;
            $category->is_active = $request->has('is_active') ? true : false;
            $category->supports_measurements = $request->boolean('supports_measurements');

            if (!$category->isDirty()) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => true, 'message' => 'No se realizaron cambios en la categoría.', 'type' => 'info']);
                }
                return redirect()->route('admin.product_categories.index')
                    ->with('info', 'No se realizaron cambios en la categoría.');
            }

            $category->save();

            Log::info('[ProductCategory@update] Categoría actualizada exitosamente', [
                'user_id' => Auth::id(),
                'category_id' => $category->id,
                'category_name' => $category->name
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Categoría "' . $category->name . '" actualizada exitosamente.',
                    'type' => 'success',
                    'data' => $category
                ]);
            }
            return redirect()->route('admin.product_categories.index')
                ->with('success', 'Categoría "' . $category->name . '" actualizada exitosamente.');
        } catch (\Exception $e) {
            Log::error('[ProductCategory@update] Error al actualizar categoría: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'category_id' => $id,
                'request_data' => $request->except(['_token']),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ]);
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Error al actualizar la categoría.'], 500);
            }
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al actualizar la categoría. Por favor, intente nuevamente.');
        }
    }

    /**
     * Confirm delete page (soft delete)
     */
    public function confirmDelete($id)
    {
        try {
            $category = ProductCategory::withCount('products')->findOrFail($id);
            return view('admin.product_categories.delete', compact('category'));
        } catch (\Exception $e) {
            Log::error('[ProductCategory@confirmDelete] Error al cargar confirmación: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'category_id' => $id,
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            return redirect()->route('admin.product_categories.index')
                ->with('error', 'Categoría no encontrada.');
        }
    }

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy(Request $request, $id)
    {
        try {
            $category = ProductCategory::findOrFail($id);

            // Verificar si tiene productos asociados
            if ($category->products()->count() > 0) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No se puede eliminar la categoría porque tiene productos asociados.'
                    ], 422);
                }
                return redirect()->route('admin.product_categories.index')
                    ->with('error', 'No se puede eliminar la categoría porque tiene productos asociados.');
            }

            $categoryName = $category->name;
            $category->delete(); // SoftDelete

            Log::info('[ProductCategory@destroy] Categoría eliminada exitosamente', [
                'user_id' => Auth::id(),
                'category_id' => $id,
                'category_name' => $categoryName
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Categoría "' . $categoryName . '" eliminada exitosamente.', 'type' => 'success']);
            }
            return redirect()->route('admin.product_categories.index')
                ->with('success', 'Categoría "' . $categoryName . '" eliminada exitosamente.');
        } catch (\Exception $e) {
            Log::error('[ProductCategory@destroy] Error al eliminar categoría: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'category_id' => $id,
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ]);
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Error al eliminar la categoría.'], 500);
            }
            return redirect()->route('admin.product_categories.index')
                ->with('error', 'Error al eliminar la categoría. Por favor, intente nuevamente.');
        }
    }
}
