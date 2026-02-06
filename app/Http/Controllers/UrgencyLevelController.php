<?php

namespace App\Http\Controllers;

use App\Models\UrgencyLevel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Throwable;

class UrgencyLevelController extends Controller
{
    public function index()
    {
        $urgencyLevels = UrgencyLevel::where('activo', true)->orderBy('sort_order')->get();
        return view('admin.urgency-levels.index', compact('urgencyLevels'));
    }

    public function create()
    {
        return view('admin.urgency-levels.create');
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'time_percentage' => 'required|integer|min:1|max:200',
            'price_multiplier' => 'required|numeric|min:0.5|max:5',
            'color' => 'required|string|max:20',
            'icon' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        try {
            $name = mb_strtoupper(trim($validated['name']), 'UTF-8');
            $existing = UrgencyLevel::where('name', $name)->first();

            if ($existing) {
                if ($existing->activo) {
                    $msg = 'Ya existe un nivel de urgencia con ese nombre.';
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json(['success' => false, 'message' => $msg], 422);
                    }
                    return back()->withErrors(['name' => $msg])->withInput();
                }

                // Reactivar
                $existing->fill([
                    'time_percentage' => $validated['time_percentage'],
                    'price_multiplier' => $validated['price_multiplier'],
                    'color' => $validated['color'],
                    'icon' => $validated['icon'] ?? null,
                    'description' => $validated['description'] ?? null,
                    'sort_order' => $validated['sort_order'] ?? 0,
                    'activo' => true,
                ]);
                $existing->save();

                Log::info('[UrgencyLevel@store] Nivel reactivado', ['id' => $existing->id, 'user_id' => Auth::id()]);

                $msg = 'Nivel de urgencia reactivado correctamente.';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => true, 'message' => $msg, 'data' => $existing]);
                }
                return redirect()->route('admin.urgency-levels.index')->with('success', $msg);
            }

            // Generar slug único
            $slug = Str::slug($name);
            $originalSlug = $slug;
            $counter = 1;
            while (UrgencyLevel::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter++;
            }

            $level = UrgencyLevel::create([
                'name' => $name,
                'slug' => $slug,
                'time_percentage' => $validated['time_percentage'],
                'price_multiplier' => $validated['price_multiplier'],
                'color' => $validated['color'],
                'icon' => $validated['icon'] ?? null,
                'description' => $validated['description'] ?? null,
                'sort_order' => $validated['sort_order'] ?? 0,
                'activo' => true,
            ]);

            Log::info('[UrgencyLevel@store] Nivel creado', ['id' => $level->id, 'user_id' => Auth::id()]);

            $msg = 'Nivel de urgencia creado correctamente.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $msg, 'data' => $level]);
            }
            return redirect()->route('admin.urgency-levels.index')->with('success', $msg);

        } catch (Throwable $e) {
            Log::error('[UrgencyLevel@store] Error', ['error' => $e->getMessage(), 'user_id' => Auth::id()]);

            $msg = 'Error al crear el nivel de urgencia.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 500);
            }
            return back()->withInput()->with('error', $msg);
        }
    }

    public function edit($id)
    {
        $urgencyLevel = UrgencyLevel::where('activo', true)->findOrFail($id);
        return view('admin.urgency-levels.edit', compact('urgencyLevel'));
    }

    public function update(Request $request, $id): JsonResponse|RedirectResponse
    {
        $urgencyLevel = UrgencyLevel::where('activo', true)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'time_percentage' => 'required|integer|min:1|max:200',
            'price_multiplier' => 'required|numeric|min:0.5|max:5',
            'color' => 'required|string|max:20',
            'icon' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        try {
            $urgencyLevel->fill([
                'name' => mb_strtoupper(trim($validated['name']), 'UTF-8'),
                'time_percentage' => $validated['time_percentage'],
                'price_multiplier' => $validated['price_multiplier'],
                'color' => $validated['color'],
                'icon' => $validated['icon'] ?? null,
                'description' => $validated['description'] ?? null,
                'sort_order' => $validated['sort_order'] ?? 0,
            ]);

            if (!$urgencyLevel->isDirty()) {
                $msg = 'No se realizaron cambios.';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => true, 'message' => $msg, 'type' => 'info']);
                }
                return redirect()->route('admin.urgency-levels.index')->with('info', $msg);
            }

            $urgencyLevel->save();

            Log::info('[UrgencyLevel@update] Nivel actualizado', ['id' => $urgencyLevel->id, 'user_id' => Auth::id()]);

            $msg = 'Nivel de urgencia actualizado correctamente.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $msg, 'type' => 'success', 'data' => $urgencyLevel]);
            }
            return redirect()->route('admin.urgency-levels.index')->with('success', $msg);

        } catch (Throwable $e) {
            Log::error('[UrgencyLevel@update] Error', ['id' => $id, 'error' => $e->getMessage(), 'user_id' => Auth::id()]);

            $msg = 'Error al actualizar el nivel de urgencia.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 500);
            }
            return back()->withInput()->with('error', $msg);
        }
    }

    public function confirmDelete($id)
    {
        $urgencyLevel = UrgencyLevel::where('activo', true)->findOrFail($id);
        return view('admin.urgency-levels.delete', compact('urgencyLevel'));
    }

    public function destroy(Request $request, $id): JsonResponse|RedirectResponse
    {
        try {
            $urgencyLevel = UrgencyLevel::where('activo', true)->findOrFail($id);

            // Verificar que no sea el único nivel activo
            $activeCount = UrgencyLevel::where('activo', true)->count();
            if ($activeCount <= 1) {
                $msg = 'No se puede eliminar. Debe existir al menos un nivel de urgencia.';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }
                return redirect()->route('admin.urgency-levels.index')->with('error', $msg);
            }

            $urgencyLevel->activo = false;
            $urgencyLevel->save();

            Log::info('[UrgencyLevel@destroy] Nivel eliminado', ['id' => $id, 'user_id' => Auth::id()]);

            $msg = 'Nivel de urgencia eliminado correctamente.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $msg, 'type' => 'success']);
            }
            return redirect()->route('admin.urgency-levels.index')->with('success', $msg);

        } catch (Throwable $e) {
            Log::error('[UrgencyLevel@destroy] Error', ['id' => $id, 'error' => $e->getMessage(), 'user_id' => Auth::id()]);

            $msg = 'Error al eliminar el nivel de urgencia.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 500);
            }
            return redirect()->route('admin.urgency-levels.index')->with('error', $msg);
        }
    }
}
