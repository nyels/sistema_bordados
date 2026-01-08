<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    /**
     * Mostrar listado de categorías
     */
    public function index()
    {
        // Obtener categorías con sus padres e hijos
        $categories = Category::with(['parent', 'children'])
            ->where('is_active', true)
            ->whereNull('parent_id') // Solo categorías raíz
            ->orderBy('order')
            ->orderBy('name')
            ->get();

        return view('admin.categorias.index', compact('categories'));
    }

    /**
     * Mostrar formulario para crear categoría
     */
    public function create()
    {
        // Obtener categorías para seleccionar como padre
        $parentCategories = Category::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.categorias.create', compact('parentCategories'));
    }

    /**
     * Guardar nueva categoría
     */
    public function store(Request $request)
    {

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean'
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        try {
            // Si no se proporciona orden, usar el siguiente disponible
            if (!isset($validated['order'])) {
                $validated['order'] = Category::max('order') + 1;
            }

            $category = Category::create($validated);

            return redirect()
                ->route('admin.categories.index')
                ->with('success', 'Categoría creada exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al crear la categoría: ' . $e->getMessage());
            return redirect()
                ->back()
                ->with('error', 'Error al crear la categoría: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar formulario para editar
     */
    public function edit(Category $category)
    {
        try {
            $parentCategories = Category::where('is_active', true)
                ->where('id', '!=', $category->id) // Evitar seleccionarse a sí misma
                ->orderBy('name')
                ->get();

            return view('admin.categorias.edit', compact('category', 'parentCategories'));
        } catch (\Exception $e) {
            Log::error('Error al editar la categoría: ' . $e->getMessage());
            return redirect()
                ->back()
                ->with('error', 'Error al editar la categoría: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar una categoría con sus diseños
     */
    public function show(Category $category)
    {
        $category->load([
            'designs.primaryImage',
            'children',
            'parent'
        ]);

        return view('admin.categorias.show', compact('category'));
    }



    /**
     * Actualizar categoría
     */
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean'
        ]);
        try {

            // Validar que no se seleccione a sí misma como padre
            if (isset($validated['parent_id']) && $validated['parent_id'] == $category->id) {
                return back()
                    ->with('error', 'Una categoría no puede ser su propia categoría padre')
                    ->withInput();
            }

            if ($category->name !== $validated['name']) {
                $validated['slug'] = Str::slug($validated['name']);
            }

            $category->update($validated);

            return redirect()
                ->route('admin.categories.index')
                ->with('success', 'Categoría actualizada exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al actualizar la categoría: ' . $e->getMessage());
            return redirect()
                ->back()
                ->with('error', 'Error al actualizar la categoría: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar categoría
     */
    public function confirm_delete(Category $category)
    {
        //validando que existe el giro
        $category = Category::where('id', $category->id)
            ->where('is_active', true)
            ->firstOrFail();
        if (!$category) {
            return redirect()->route('admin.categories.index')->with('error', 'Categoría no encontrada');
        }

        return view('admin.categorias.delete', compact('category'));
    }
    public function destroy(Category $category)
    {
        try {

            // Verificar si tiene diseños asociados
            if ($category->designs()->count() > 0) {
                return back()
                    ->with('error', 'No se puede eliminar la categoría porque tiene diseños asociados');
            }

            // Verificar si tiene subcategorías
            if ($category->children()->count() > 0) {
                return back()
                    ->with('error', 'No se puede eliminar la categoría porque tiene subcategorías');
            }

            $category->delete();

            return redirect()
                ->route('admin.categories.index')
                ->with('success', 'Categoría eliminada exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al eliminar la categoría: ' . $e->getMessage());
            return redirect()
                ->back()
                ->with('error', 'Error al eliminar la categoría: ' . $e->getMessage());
        }
    }
}
