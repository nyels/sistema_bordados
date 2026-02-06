<?php

namespace App\Http\Controllers;

use App\Models\Recomendacion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Throwable;

class RecomendacionController extends Controller
{
    public function index()
    {
        $recomendaciones = Recomendacion::where('activo', true)->orderBy('nombre_recomendacion')->get();
        return view('admin.recomendaciones.index', compact('recomendaciones'));
    }

    public function create()
    {
        return view('admin.recomendaciones.create');
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'nombre_recomendacion' => ['required', 'string', 'max:255', 'regex:/^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]+$/'],
        ]);

        try {
            $nombre = mb_strtoupper(trim($request->nombre_recomendacion), 'UTF-8');
            $existing = Recomendacion::where('nombre_recomendacion', $nombre)->first();

            if ($existing) {
                if ($existing->activo) {
                    $msg = 'El nombre de la recomendación ya ha sido registrado.';
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json(['success' => false, 'message' => $msg], 422);
                    }
                    return back()->withErrors(['nombre_recomendacion' => $msg])->withInput();
                }

                // Reactivar
                $existing->activo = true;
                $existing->fecha_baja = null;
                $existing->save();

                Log::info('[Recomendacion@store] Recomendación reactivada', ['id' => $existing->id, 'user_id' => Auth::id()]);

                $msg = 'Recomendación reactivada exitosamente';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => true, 'message' => $msg, 'data' => $existing]);
                }
                return redirect()->route('admin.recomendaciones.index')->with('success', $msg);
            }

            $recomendacion = Recomendacion::create(['nombre_recomendacion' => $nombre]);

            Log::info('[Recomendacion@store] Recomendación creada', ['id' => $recomendacion->id, 'user_id' => Auth::id()]);

            $msg = 'Recomendación guardada exitosamente';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $msg, 'data' => $recomendacion]);
            }
            return redirect()->route('admin.recomendaciones.index')->with('success', $msg);

        } catch (Throwable $e) {
            Log::error('[Recomendacion@store] Error', ['error' => $e->getMessage(), 'user_id' => Auth::id()]);

            $msg = 'Error al guardar la recomendación';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 500);
            }
            return redirect()->route('admin.recomendaciones.index')->with('error', $msg);
        }
    }

    public function edit($id)
    {
        $recomendacion = Recomendacion::where('activo', true)->findOrFail($id);
        return view('admin.recomendaciones.edit', compact('recomendacion'));
    }

    public function update(Request $request, $id): JsonResponse|RedirectResponse
    {
        $request->validate([
            'nombre_recomendacion' => ['required', 'string', 'max:255', 'regex:/^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]+$/', 'unique:recomendacion,nombre_recomendacion,' . $id],
        ]);

        try {
            $recomendacion = Recomendacion::where('activo', true)->findOrFail($id);
            $recomendacion->nombre_recomendacion = mb_strtoupper(trim($request->nombre_recomendacion), 'UTF-8');

            if (!$recomendacion->isDirty()) {
                $msg = 'No se realizaron cambios';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => true, 'message' => $msg, 'type' => 'info']);
                }
                return redirect()->route('admin.recomendaciones.index')->with('info', $msg);
            }

            $recomendacion->save();

            Log::info('[Recomendacion@update] Recomendación actualizada', ['id' => $recomendacion->id, 'user_id' => Auth::id()]);

            $msg = 'Recomendación actualizada exitosamente';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $msg, 'type' => 'success', 'data' => $recomendacion]);
            }
            return redirect()->route('admin.recomendaciones.index')->with('success', $msg);

        } catch (Throwable $e) {
            Log::error('[Recomendacion@update] Error', ['id' => $id, 'error' => $e->getMessage(), 'user_id' => Auth::id()]);

            $msg = 'Error al actualizar la recomendación';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 500);
            }
            return redirect()->route('admin.recomendaciones.index')->with('error', $msg);
        }
    }

    public function confirm_delete($id)
    {
        $recomendacion = Recomendacion::where('activo', true)->findOrFail($id);
        return view('admin.recomendaciones.delete', compact('recomendacion'));
    }

    public function destroy(Request $request, $id): JsonResponse|RedirectResponse
    {
        try {
            $recomendacion = Recomendacion::where('activo', true)->findOrFail($id);
            $recomendacion->activo = false;
            $recomendacion->fecha_baja = now();
            $recomendacion->save();

            Log::info('[Recomendacion@destroy] Recomendación eliminada', ['id' => $id, 'user_id' => Auth::id()]);

            $msg = 'Recomendación eliminada exitosamente';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $msg, 'type' => 'success']);
            }
            return redirect()->route('admin.recomendaciones.index')->with('success', $msg);

        } catch (Throwable $e) {
            Log::error('[Recomendacion@destroy] Error', ['id' => $id, 'error' => $e->getMessage(), 'user_id' => Auth::id()]);

            $msg = 'Error al eliminar la recomendación';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 500);
            }
            return redirect()->route('admin.recomendaciones.index')->with('error', $msg);
        }
    }
}
