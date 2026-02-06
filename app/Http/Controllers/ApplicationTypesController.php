<?php

namespace App\Http\Controllers;

use App\Models\Application_types;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Throwable;

class ApplicationTypesController extends Controller
{
    public function index()
    {
        $aplication_types = Application_types::where('activo', true)->orderBy('nombre_aplicacion')->get();
        return view('admin.tipos_aplicacion.index', compact('aplication_types'));
    }

    public function create()
    {
        return view('admin.tipos_aplicacion.create');
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'nombre_aplicacion' => ['required', 'string', 'max:255', 'regex:/^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]+$/'],
        ]);

        try {
            $nombre = mb_strtoupper(trim($request->nombre_aplicacion), 'UTF-8');
            $existing = Application_types::where('nombre_aplicacion', $nombre)->first();

            if ($existing) {
                if ($existing->activo) {
                    $msg = 'El tipo de aplicación ya ha sido registrado.';
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json(['success' => false, 'message' => $msg], 422);
                    }
                    return back()->withErrors(['nombre_aplicacion' => $msg])->withInput();
                }

                // Reactivar
                $existing->activo = true;
                $existing->save();

                Log::info('[ApplicationTypes@store] Tipo reactivado', ['id' => $existing->id, 'user_id' => Auth::id()]);

                $msg = 'Tipo de aplicación reactivado exitosamente';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => true, 'message' => $msg, 'data' => $existing]);
                }
                return redirect()->route('admin.tipos_aplicacion.index')->with('success', $msg);
            }

            $aplication_types = Application_types::create([
                'nombre_aplicacion' => $nombre,
                'slug' => Str::slug($request->nombre_aplicacion),
            ]);

            Log::info('[ApplicationTypes@store] Tipo creado', ['id' => $aplication_types->id, 'user_id' => Auth::id()]);

            $msg = 'Tipo de aplicación guardado exitosamente';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $msg, 'data' => $aplication_types]);
            }
            return redirect()->route('admin.tipos_aplicacion.index')->with('success', $msg);

        } catch (Throwable $e) {
            Log::error('[ApplicationTypes@store] Error', ['error' => $e->getMessage(), 'user_id' => Auth::id()]);

            $msg = 'Error al guardar el tipo de aplicación';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 500);
            }
            return redirect()->route('admin.tipos_aplicacion.index')->with('error', $msg);
        }
    }

    public function edit($id)
    {
        $aplication_types = Application_types::where('activo', true)->findOrFail($id);
        return view('admin.tipos_aplicacion.edit', compact('aplication_types'));
    }

    public function update(Request $request, $id): JsonResponse|RedirectResponse
    {
        $request->validate([
            'nombre_aplicacion' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]+$/',
                'unique:application_types,nombre_aplicacion,' . $id,
            ],
        ]);

        try {
            $aplication_types = Application_types::where('activo', true)->findOrFail($id);
            $aplication_types->nombre_aplicacion = mb_strtoupper(trim($request->nombre_aplicacion), 'UTF-8');

            if (!$aplication_types->isDirty()) {
                $msg = 'No se realizaron cambios';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => true, 'message' => $msg, 'type' => 'info']);
                }
                return redirect()->route('admin.tipos_aplicacion.index')->with('info', $msg);
            }

            $aplication_types->save();

            Log::info('[ApplicationTypes@update] Tipo actualizado', ['id' => $aplication_types->id, 'user_id' => Auth::id()]);

            $msg = 'Tipo de aplicación actualizado exitosamente';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $msg, 'type' => 'success', 'data' => $aplication_types]);
            }
            return redirect()->route('admin.tipos_aplicacion.index')->with('success', $msg);

        } catch (Throwable $e) {
            Log::error('[ApplicationTypes@update] Error', ['id' => $id, 'error' => $e->getMessage(), 'user_id' => Auth::id()]);

            $msg = 'Error al actualizar el tipo de aplicación';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 500);
            }
            return redirect()->route('admin.tipos_aplicacion.index')->with('error', $msg);
        }
    }

    public function confirm_delete($id)
    {
        $aplication_types = Application_types::where('activo', true)->findOrFail($id);
        return view('admin.tipos_aplicacion.delete', compact('aplication_types'));
    }

    public function destroy(Request $request, $id): JsonResponse|RedirectResponse
    {
        try {
            $aplication_types = Application_types::where('activo', true)->findOrFail($id);
            $aplication_types->activo = false;
            $aplication_types->save();

            Log::info('[ApplicationTypes@destroy] Tipo eliminado', ['id' => $id, 'user_id' => Auth::id()]);

            $msg = 'Tipo de aplicación eliminado exitosamente';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $msg, 'type' => 'success']);
            }
            return redirect()->route('admin.tipos_aplicacion.index')->with('success', $msg);

        } catch (Throwable $e) {
            Log::error('[ApplicationTypes@destroy] Error', ['id' => $id, 'error' => $e->getMessage(), 'user_id' => Auth::id()]);

            $msg = 'Error al eliminar el tipo de aplicación';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 500);
            }
            return redirect()->route('admin.tipos_aplicacion.index')->with('error', $msg);
        }
    }
}
