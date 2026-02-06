<?php

namespace App\Http\Controllers;

use App\Models\MotivoDescuento;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Throwable;

class MotivoDescuentoController extends Controller
{
    public function index()
    {
        $motivosDescuento = MotivoDescuento::where('activo', true)->orderBy('nombre')->get();
        return view('admin.motivos-descuento.index', compact('motivosDescuento'));
    }

    public function create()
    {
        return view('admin.motivos-descuento.create');
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
        ]);

        try {
            $nombre = mb_strtoupper(trim($request->nombre), 'UTF-8');
            $existing = MotivoDescuento::where('nombre', $nombre)->first();

            if ($existing) {
                if ($existing->activo) {
                    $msg = 'El motivo de descuento ya ha sido registrado.';
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json(['success' => false, 'message' => $msg], 422);
                    }
                    return back()->withErrors(['nombre' => $msg])->withInput();
                }

                // Reactivar
                $existing->activo = true;
                $existing->fecha_baja = null;
                $existing->save();

                Log::info('[MotivoDescuento@store] Motivo reactivado', ['id' => $existing->id, 'user_id' => Auth::id()]);

                $msg = 'Motivo de descuento reactivado exitosamente';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => true, 'message' => $msg, 'data' => $existing]);
                }
                return redirect()->route('admin.motivos-descuento.index')->with('success', $msg);
            }

            $motivoDescuento = MotivoDescuento::create(['nombre' => $nombre]);

            Log::info('[MotivoDescuento@store] Motivo creado', ['id' => $motivoDescuento->id, 'user_id' => Auth::id()]);

            $msg = 'Motivo de descuento guardado exitosamente';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $msg, 'data' => $motivoDescuento]);
            }
            return redirect()->route('admin.motivos-descuento.index')->with('success', $msg);

        } catch (Throwable $e) {
            Log::error('[MotivoDescuento@store] Error', ['error' => $e->getMessage(), 'user_id' => Auth::id()]);

            $msg = 'Error al guardar el motivo de descuento';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 500);
            }
            return redirect()->route('admin.motivos-descuento.index')->with('error', $msg);
        }
    }

    public function edit($id)
    {
        $motivoDescuento = MotivoDescuento::where('activo', true)->findOrFail($id);
        return view('admin.motivos-descuento.edit', compact('motivoDescuento'));
    }

    public function update(Request $request, $id): JsonResponse|RedirectResponse
    {
        $request->validate([
            'nombre' => ['required', 'string', 'max:255', 'unique:motivos_descuento,nombre,' . $id],
        ]);

        try {
            $motivoDescuento = MotivoDescuento::where('activo', true)->findOrFail($id);
            $motivoDescuento->nombre = mb_strtoupper(trim($request->nombre), 'UTF-8');

            if (!$motivoDescuento->isDirty()) {
                $msg = 'No se realizaron cambios';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => true, 'message' => $msg, 'type' => 'info']);
                }
                return redirect()->route('admin.motivos-descuento.index')->with('info', $msg);
            }

            $motivoDescuento->save();

            Log::info('[MotivoDescuento@update] Motivo actualizado', ['id' => $motivoDescuento->id, 'user_id' => Auth::id()]);

            $msg = 'Motivo de descuento actualizado exitosamente';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $msg, 'type' => 'success', 'data' => $motivoDescuento]);
            }
            return redirect()->route('admin.motivos-descuento.index')->with('success', $msg);

        } catch (Throwable $e) {
            Log::error('[MotivoDescuento@update] Error', ['id' => $id, 'error' => $e->getMessage(), 'user_id' => Auth::id()]);

            $msg = 'Error al actualizar el motivo de descuento';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 500);
            }
            return redirect()->route('admin.motivos-descuento.index')->with('error', $msg);
        }
    }

    public function confirm_delete($id)
    {
        $motivoDescuento = MotivoDescuento::where('activo', true)->findOrFail($id);
        return view('admin.motivos-descuento.delete', compact('motivoDescuento'));
    }

    public function destroy(Request $request, $id): JsonResponse|RedirectResponse
    {
        try {
            $motivoDescuento = MotivoDescuento::where('activo', true)->findOrFail($id);
            $motivoDescuento->activo = false;
            $motivoDescuento->fecha_baja = now();
            $motivoDescuento->save();

            Log::info('[MotivoDescuento@destroy] Motivo eliminado', ['id' => $id, 'user_id' => Auth::id()]);

            $msg = 'Motivo de descuento eliminado exitosamente';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $msg, 'type' => 'success']);
            }
            return redirect()->route('admin.motivos-descuento.index')->with('success', $msg);

        } catch (Throwable $e) {
            Log::error('[MotivoDescuento@destroy] Error', ['id' => $id, 'error' => $e->getMessage(), 'user_id' => Auth::id()]);

            $msg = 'Error al eliminar el motivo de descuento';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 500);
            }
            return redirect()->route('admin.motivos-descuento.index')->with('error', $msg);
        }
    }
}
