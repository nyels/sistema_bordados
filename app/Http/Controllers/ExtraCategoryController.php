<?php

namespace App\Http\Controllers;

use App\Models\ExtraCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ExtraCategoryController extends Controller
{
    public function index()
    {
        $categories = ExtraCategory::where('activo', true)
            ->withCount('extras')
            ->orderBy('nombre')
            ->get();

        return view('admin.extra_categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.extra_categories.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100|regex:/^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]+$/',
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.regex' => 'Solo se permiten letras y espacios.',
        ]);

        try {
            $nombre = mb_strtoupper(trim($request->nombre), 'UTF-8');

            // Verificar si existe (activa o inactiva)
            $existing = ExtraCategory::where('nombre', $nombre)->first();

            if ($existing) {
                if ($existing->activo) {
                    $msg = 'La categoría ya existe.';
                    return $request->expectsJson()
                        ? response()->json(['success' => false, 'message' => $msg], 422)
                        : back()->with('error', $msg)->withInput();
                }

                // Reactivar
                $existing->activo = true;
                $existing->save();

                $msg = 'Categoría reactivada correctamente.';
                return $request->expectsJson()
                    ? response()->json(['success' => true, 'message' => $msg, 'data' => $existing])
                    : redirect()->route('admin.extra_categories.index')->with('success', $msg);
            }

            $category = ExtraCategory::create(['nombre' => $nombre]);

            $msg = 'Categoría creada correctamente.';
            return $request->expectsJson()
                ? response()->json(['success' => true, 'message' => $msg, 'data' => $category])
                : redirect()->route('admin.extra_categories.index')->with('success', $msg);

        } catch (\Exception $e) {
            Log::error('[ExtraCategory@store] ' . $e->getMessage(), [
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            $msg = 'Error al guardar la categoría.';
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $msg], 500)
                : back()->with('error', $msg)->withInput();
        }
    }

    public function edit($id)
    {
        $category = ExtraCategory::where('activo', true)->findOrFail($id);
        return view('admin.extra_categories.edit', compact('category'));
    }

    public function update(Request $request, $id)
    {
        $category = ExtraCategory::where('activo', true)->findOrFail($id);

        $request->validate([
            'nombre' => 'required|string|max:100|regex:/^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]+$/|unique:extra_categories,nombre,' . $id,
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.regex' => 'Solo se permiten letras y espacios.',
            'nombre.unique' => 'Esta categoría ya existe.',
        ]);

        try {
            $category->nombre = mb_strtoupper(trim($request->nombre), 'UTF-8');

            if (!$category->isDirty()) {
                $msg = 'No se realizaron cambios.';
                return $request->expectsJson()
                    ? response()->json(['success' => true, 'message' => $msg, 'type' => 'info'])
                    : redirect()->route('admin.extra_categories.index')->with('info', $msg);
            }

            $category->save();

            $msg = 'Categoría actualizada correctamente.';
            return $request->expectsJson()
                ? response()->json(['success' => true, 'message' => $msg, 'data' => $category])
                : redirect()->route('admin.extra_categories.index')->with('success', $msg);

        } catch (\Exception $e) {
            Log::error('[ExtraCategory@update] ' . $e->getMessage(), [
                'id' => $id,
                'line' => $e->getLine(),
            ]);

            $msg = 'Error al actualizar la categoría.';
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $msg], 500)
                : back()->with('error', $msg)->withInput();
        }
    }

    public function confirmDelete($id)
    {
        $category = ExtraCategory::where('activo', true)->findOrFail($id);
        return view('admin.extra_categories.delete', compact('category'));
    }

    public function destroy(Request $request, $id)
    {
        $category = ExtraCategory::where('activo', true)->findOrFail($id);

        try {
            // Eliminación lógica
            $category->activo = false;
            $category->save();

            $msg = 'Categoría eliminada correctamente.';
            return $request->expectsJson()
                ? response()->json(['success' => true, 'message' => $msg])
                : redirect()->route('admin.extra_categories.index')->with('success', $msg);

        } catch (\Exception $e) {
            Log::error('[ExtraCategory@destroy] ' . $e->getMessage(), ['id' => $id]);

            $msg = 'Error al eliminar la categoría.';
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $msg], 500)
                : back()->with('error', $msg);
        }
    }
}
