<?php

namespace App\Http\Controllers;

use App\Models\Estado;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Throwable;

class EstadoController extends Controller
{
    public function index()
    {
        $estados = Estado::where('activo', true)->orderBy('nombre_estado')->get();
        return view('admin.estados.index', compact('estados'));
    }

    public function create()
    {
        return view('admin.estados.create');
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'nombre_estado' => ['required', 'string', 'max:255', 'regex:/^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]+$/'],
        ]);

        try {
            $nombre = mb_strtoupper(trim($request->nombre_estado), 'UTF-8');
            $existing = Estado::where('nombre_estado', $nombre)->first();

            if ($existing) {
                if ($existing->activo) {
                    $msg = 'El nombre del estado ya ha sido registrado.';
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json(['success' => false, 'message' => $msg], 422);
                    }
                    return back()->withErrors(['nombre_estado' => $msg])->withInput();
                }

                // Reactivar
                $existing->activo = true;
                $existing->save();

                Log::info('[Estado@store] Estado reactivado', ['id' => $existing->id, 'user_id' => Auth::id()]);

                $msg = 'Estado reactivado exitosamente';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => true, 'message' => $msg, 'data' => $existing]);
                }
                return redirect()->route('admin.estados.index')->with('success', $msg);
            }

            $estado = Estado::create(['nombre_estado' => $nombre]);

            Log::info('[Estado@store] Estado creado', ['id' => $estado->id, 'user_id' => Auth::id()]);

            $msg = 'Estado creado exitosamente';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $msg, 'data' => $estado]);
            }
            return redirect()->route('admin.estados.index')->with('success', $msg);

        } catch (Throwable $e) {
            Log::error('[Estado@store] Error', ['error' => $e->getMessage(), 'user_id' => Auth::id()]);

            $msg = 'Error al crear el estado';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 500);
            }
            return redirect()->route('admin.estados.index')->with('error', $msg);
        }
    }

    public function edit($id)
    {
        $estado = Estado::where('activo', true)->findOrFail($id);
        return view('admin.estados.edit', compact('estado'));
    }

    public function update(Request $request, $id): JsonResponse|RedirectResponse
    {
        $request->validate([
            'nombre_estado' => ['required', 'string', 'max:255', 'regex:/^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]+$/', 'unique:estados,nombre_estado,' . $id],
        ]);

        try {
            $estado = Estado::where('activo', true)->findOrFail($id);
            $estado->nombre_estado = mb_strtoupper(trim($request->nombre_estado), 'UTF-8');

            if (!$estado->isDirty()) {
                $msg = 'No se realizaron cambios';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => true, 'message' => $msg, 'type' => 'info']);
                }
                return redirect()->route('admin.estados.index')->with('info', $msg);
            }

            $estado->save();

            Log::info('[Estado@update] Estado actualizado', ['id' => $estado->id, 'user_id' => Auth::id()]);

            $msg = 'Estado actualizado exitosamente';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $msg, 'type' => 'success', 'data' => $estado]);
            }
            return redirect()->route('admin.estados.index')->with('success', $msg);

        } catch (Throwable $e) {
            Log::error('[Estado@update] Error', ['id' => $id, 'error' => $e->getMessage(), 'user_id' => Auth::id()]);

            $msg = 'Error al actualizar el estado';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 500);
            }
            return redirect()->route('admin.estados.index')->with('error', $msg);
        }
    }

    public function confirm_delete($id)
    {
        $estado = Estado::where('activo', true)->findOrFail($id);
        return view('admin.estados.delete', compact('estado'));
    }

    public function destroy(Request $request, $id): JsonResponse|RedirectResponse
    {
        try {
            $estado = Estado::where('activo', true)->findOrFail($id);
            $estado->activo = false;
            $estado->save();

            Log::info('[Estado@destroy] Estado eliminado', ['id' => $id, 'user_id' => Auth::id()]);

            $msg = 'Estado eliminado exitosamente';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $msg, 'type' => 'success']);
            }
            return redirect()->route('admin.estados.index')->with('success', $msg);

        } catch (Throwable $e) {
            Log::error('[Estado@destroy] Error', ['id' => $id, 'error' => $e->getMessage(), 'user_id' => Auth::id()]);

            $msg = 'Error al eliminar el estado';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 500);
            }
            return redirect()->route('admin.estados.index')->with('error', $msg);
        }
    }
}
