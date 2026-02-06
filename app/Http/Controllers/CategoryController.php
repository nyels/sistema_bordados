<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Throwable;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::with(['parent', 'children'])
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('order')
            ->orderBy('name')
            ->get();

        return view('admin.categorias.index', compact('categories'));
    }

    public function create()
    {
        $parentCategories = Category::where('is_active', true)->orderBy('name')->get();
        return view('admin.categorias.create', compact('parentCategories'));
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean'
        ]);

        try {
            $validated['slug'] = Str::slug($validated['name']);

            if (!isset($validated['order'])) {
                $validated['order'] = Category::max('order') + 1;
            }

            $category = Category::create($validated);

            Log::info('[Category@store] Categoría creada', ['id' => $category->id, 'user_id' => Auth::id()]);

            $msg = 'Categoría creada exitosamente';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $msg, 'data' => $category]);
            }
            return redirect()->route('admin.categories.index')->with('success', $msg);

        } catch (Throwable $e) {
            Log::error('[Category@store] Error', ['error' => $e->getMessage(), 'user_id' => Auth::id()]);

            $msg = 'Error al crear la categoría';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 500);
            }
            return redirect()->back()->with('error', $msg);
        }
    }

    public function edit(Category $category)
    {
        $parentCategories = Category::where('is_active', true)
            ->where('id', '!=', $category->id)
            ->orderBy('name')
            ->get();

        return view('admin.categorias.edit', compact('category', 'parentCategories'));
    }

    public function show(Category $category)
    {
        $category->load(['designs.primaryImage', 'children', 'parent']);
        return view('admin.categorias.show', compact('category'));
    }

    public function update(Request $request, Category $category): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean'
        ]);

        try {
            if (isset($validated['parent_id']) && $validated['parent_id'] == $category->id) {
                $msg = 'Una categoría no puede ser su propia categoría padre';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }
                return back()->with('error', $msg)->withInput();
            }

            if ($category->name !== $validated['name']) {
                $validated['slug'] = Str::slug($validated['name']);
            }

            $category->fill($validated);

            if (!$category->isDirty()) {
                $msg = 'No se realizaron cambios';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => true, 'message' => $msg, 'type' => 'info']);
                }
                return redirect()->route('admin.categories.index')->with('info', $msg);
            }

            $category->save();

            Log::info('[Category@update] Categoría actualizada', ['id' => $category->id, 'user_id' => Auth::id()]);

            $msg = 'Categoría actualizada exitosamente';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $msg, 'type' => 'success', 'data' => $category]);
            }
            return redirect()->route('admin.categories.index')->with('success', $msg);

        } catch (Throwable $e) {
            Log::error('[Category@update] Error', ['id' => $category->id, 'error' => $e->getMessage(), 'user_id' => Auth::id()]);

            $msg = 'Error al actualizar la categoría';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 500);
            }
            return redirect()->back()->with('error', $msg);
        }
    }

    public function confirm_delete(Category $category)
    {
        $category = Category::where('id', $category->id)->where('is_active', true)->firstOrFail();
        return view('admin.categorias.delete', compact('category'));
    }

    public function destroy(Request $request, Category $category): JsonResponse|RedirectResponse
    {
        try {
            if ($category->designs()->count() > 0) {
                $msg = 'No se puede eliminar la categoría porque tiene diseños asociados';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }
                return back()->with('error', $msg);
            }

            if ($category->children()->count() > 0) {
                $msg = 'No se puede eliminar la categoría porque tiene subcategorías';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }
                return back()->with('error', $msg);
            }

            $categoryId = $category->id;
            $category->delete();

            Log::info('[Category@destroy] Categoría eliminada', ['id' => $categoryId, 'user_id' => Auth::id()]);

            $msg = 'Categoría eliminada exitosamente';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $msg, 'type' => 'success']);
            }
            return redirect()->route('admin.categories.index')->with('success', $msg);

        } catch (Throwable $e) {
            Log::error('[Category@destroy] Error', ['id' => $category->id, 'error' => $e->getMessage(), 'user_id' => Auth::id()]);

            $msg = 'Error al eliminar la categoría';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 500);
            }
            return redirect()->back()->with('error', $msg);
        }
    }
}
