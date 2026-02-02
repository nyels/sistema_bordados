<?php

namespace App\Http\Controllers;

use App\Models\MotivoDescuento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MotivoDescuentoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $motivosDescuento = MotivoDescuento::where('activo', true)->orderBy('nombre', 'asc')->get();
        return view('admin.motivos-descuento.index', compact('motivosDescuento'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.motivos-descuento.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:255',
            ],
        ]);
        try {
            $nombre = strtoupper(trim($request->nombre));

            // Buscar si ya existe (activo o inactivo)
            $existing = MotivoDescuento::where('nombre', $nombre)->first();

            if ($existing) {
                if ($existing->activo) {
                    return back()->withErrors(['nombre' => 'El motivo de descuento ya ha sido registrado.'])->withInput();
                } else {
                    // Reactivar si existía pero estaba eliminado logicamente
                    $existing->activo = true;
                    $existing->fecha_baja = null;
                    $existing->save();
                    return redirect()->route('admin.motivos-descuento.index')->with('success', 'Motivo de descuento reactivado exitosamente');
                }
            }

            $motivoDescuento = new MotivoDescuento();
            $motivoDescuento->nombre = $nombre;
            $motivoDescuento->save();
            return redirect()->route('admin.motivos-descuento.index')->with('success', 'Motivo de descuento guardado exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al guardar el motivo de descuento: ' . $e->getMessage());
            return redirect()->route('admin.motivos-descuento.index')->with('error', 'Error al guardar el motivo de descuento: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $motivoDescuento = MotivoDescuento::where('activo', true)->findOrFail($id);
        return view('admin.motivos-descuento.edit', compact('motivoDescuento'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => ['required', 'string', 'max:255', 'unique:motivos_descuento,nombre,' . $id],
        ]);
        try {
            $motivoDescuento = MotivoDescuento::where('activo', true)->findOrFail($id);
            $motivoDescuento->nombre = strtoupper(trim($request->nombre));
            if (!$motivoDescuento->isDirty()) {
                return redirect()->route('admin.motivos-descuento.index')->with('info', 'No se realizaron cambios');
            }
            $motivoDescuento->save();
            return redirect()->route('admin.motivos-descuento.index')->with('success', 'Motivo de descuento actualizado exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al actualizar el motivo de descuento: ' . $e->getMessage());
            return redirect()->route('admin.motivos-descuento.index')->with('error', 'Error al actualizar el motivo de descuento: ' . $e->getMessage());
        }
    }

    /**
     * Show confirmation before deleting
     */
    public function confirm_delete($id)
    {
        $motivoDescuento = MotivoDescuento::where('activo', true)->findOrFail($id);
        return view('admin.motivos-descuento.delete', compact('motivoDescuento'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $motivoDescuento = MotivoDescuento::where('activo', true)->findOrFail($id);
            $motivoDescuento->activo = false;
            $motivoDescuento->fecha_baja = now();
            $motivoDescuento->save();
            return redirect()->route('admin.motivos-descuento.index')->with('success', 'Motivo de descuento eliminado exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al eliminar el motivo de descuento: ' . $e->getMessage());
            return redirect()->route('admin.motivos-descuento.index')->with('error', 'Error al eliminar el motivo de descuento: ' . $e->getMessage());
        }
    }
}
