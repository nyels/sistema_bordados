<?php

namespace App\Http\Controllers;

use App\Models\Giro;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Throwable;

class GiroController extends Controller
{
    public function index()
    {
        $giros = Giro::where('activo', true)->orderBy('nombre_giro')->get();
        return view('admin.giros.index', compact('giros'));
    }

    public function create()
    {
        return view('admin.giros.create');
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'nombre_giro' => ['required', 'string', 'max:255', 'regex:/^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]+$/'],
        ]);

        try {
            $nombre = mb_strtoupper(trim($request->nombre_giro), 'UTF-8');
            $existing = Giro::where('nombre_giro', $nombre)->first();

            if ($existing) {
                if ($existing->activo) {
                    $msg = 'El nombre del giro ya ha sido registrado.';
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json(['success' => false, 'message' => $msg], 422);
                    }
                    return back()->withErrors(['nombre_giro' => $msg])->withInput();
                }

                // Reactivar
                $existing->activo = true;
                $existing->save();

                Log::info('[Giro@store] Giro reactivado', ['id' => $existing->id, 'user_id' => Auth::id()]);

                $msg = 'Giro reactivado exitosamente';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => true, 'message' => $msg, 'data' => $existing]);
                }
                return redirect()->route('admin.giros.index')->with('success', $msg);
            }

            $giro = Giro::create(['nombre_giro' => $nombre]);

            Log::info('[Giro@store] Giro creado', ['id' => $giro->id, 'user_id' => Auth::id()]);

            $msg = 'Giro guardado exitosamente';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $msg, 'data' => $giro]);
            }
            return redirect()->route('admin.giros.index')->with('success', $msg);

        } catch (Throwable $e) {
            Log::error('[Giro@store] Error', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            $msg = 'Error al guardar el giro';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 500);
            }
            return redirect()->route('admin.giros.index')->with('error', $msg);
        }
    }

    public function edit($id)
    {
        $giro = Giro::where('activo', true)->findOrFail($id);
        return view('admin.giros.edit', compact('giro'));
    }

    public function update(Request $request, $id): JsonResponse|RedirectResponse
    {
        $request->validate([
            'nombre_giro' => ['required', 'string', 'max:255', 'regex:/^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]+$/', 'unique:giros,nombre_giro,' . $id],
        ]);

        try {
            $giro = Giro::where('activo', true)->findOrFail($id);
            $giro->nombre_giro = mb_strtoupper(trim($request->nombre_giro), 'UTF-8');

            if (!$giro->isDirty()) {
                $msg = 'No se realizaron cambios';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => true, 'message' => $msg, 'type' => 'info']);
                }
                return redirect()->route('admin.giros.index')->with('info', $msg);
            }

            $giro->save();

            Log::info('[Giro@update] Giro actualizado', ['id' => $giro->id, 'user_id' => Auth::id()]);

            $msg = 'Giro actualizado exitosamente';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $msg, 'type' => 'success', 'data' => $giro]);
            }
            return redirect()->route('admin.giros.index')->with('success', $msg);

        } catch (Throwable $e) {
            Log::error('[Giro@update] Error', ['id' => $id, 'error' => $e->getMessage(), 'user_id' => Auth::id()]);

            $msg = 'Error al actualizar el giro';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 500);
            }
            return redirect()->route('admin.giros.index')->with('error', $msg);
        }
    }

    public function confirm_delete($id)
    {
        $giro = Giro::where('activo', true)->findOrFail($id);
        return view('admin.giros.delete', compact('giro'));
    }

    public function destroy(Request $request, $id): JsonResponse|RedirectResponse
    {
        try {
            $giro = Giro::where('activo', true)->findOrFail($id);
            $giro->activo = false;
            $giro->save();

            Log::info('[Giro@destroy] Giro eliminado', ['id' => $id, 'user_id' => Auth::id()]);

            $msg = 'Giro eliminado exitosamente';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $msg, 'type' => 'success']);
            }
            return redirect()->route('admin.giros.index')->with('success', $msg);

        } catch (Throwable $e) {
            Log::error('[Giro@destroy] Error', ['id' => $id, 'error' => $e->getMessage(), 'user_id' => Auth::id()]);

            $msg = 'Error al eliminar el giro';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 500);
            }
            return redirect()->route('admin.giros.index')->with('error', $msg);
        }
    }
}
