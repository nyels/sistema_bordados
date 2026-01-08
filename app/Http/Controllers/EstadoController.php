<?php

namespace App\Http\Controllers;

use App\Models\Estado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EstadoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $estados = Estado::all()->where('activo', true);
        return view('admin.estados.index', compact('estados'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.estados.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre_estado' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]+$/',
            ],
        ]);

        try {
            $nombre = strtoupper(trim($request->nombre_estado));

            // Buscar si ya existe (activo o inactivo)
            $existingEstado = Estado::where('nombre_estado', $nombre)->first();

            if ($existingEstado) {
                if ($existingEstado->activo) {
                    return back()->withErrors(['nombre_estado' => 'El nombre del estado ya ha sido registrado.'])->withInput();
                } else {
                    // Reactivar si existía pero estaba eliminado (soft delete lógico)
                    $existingEstado->activo = true;
                    $existingEstado->save();
                    return redirect()->route('admin.estados.index')->with('success', 'Estado reactivado exitosamente');
                }
            }

            $estado = new Estado();
            $estado->nombre_estado = $nombre;
            $estado->save();

            return redirect()->route('admin.estados.index')->with('success', 'Estado creado exitosamente');
        } catch (\Exception $e) {
            return redirect()->route('admin.estados.index')->with('error', 'Error al crear el estado');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Estado $estado)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $estado = Estado::where('id', $id)
            ->where('activo', true)
            ->firstOrFail();
        if (!$estado) {
            return redirect()->route('admin.estados.index')->with('error', 'Estado no encontrado');
        }
        return view('admin.estados.edit', compact('estado'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre_estado' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]+$/',
                'unique:estados,nombre_estado,' . $id,
            ],
        ]);
        try {
            $estado = Estado::where('id', $id)
                ->where('activo', true)
                ->firstOrFail();
            $estado->nombre_estado = strtoupper(trim($request->nombre_estado));

            //validamos si hubo algun cambio o modificacion en el valor del campo nombre_estado, si no hubo cambios, no se actualiza
            if (! $estado->isDirty()) {
                // return back()->with('info', 'No se realizaron cambios');
                return redirect()->route('admin.estados.index')->with('info', 'No se realizaron cambios');
            }

            $estado->save();
            return redirect()->route('admin.estados.index')->with('success', 'Estado actualizado exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al actualizar el estado: ' . $e->getMessage());
            return redirect()->route('admin.estados.index')->with('error', 'Error al actualizar el estado');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function confirm_delete($id)
    {
        //validando que existe el estado
        $estado = Estado::where('id', $id)
            ->where('activo', true)
            ->firstOrFail();
        if (!$estado) {
            return redirect()->route('admin.estados.index')->with('error', 'Estado no encontrado');
        }
        return view('admin.estados.delete', compact('estado'));
    }

    public function destroy($id)
    {
        $estado = Estado::where('id', $id)
            ->where('activo', true)
            ->firstOrFail();
        if (!$estado) {
            return redirect()->route('admin.estados.confirm_delete', $id)->with('error', 'Estado no encontrado');
        }
        try {
            $estado->activo = false;
            $estado->save();
            return redirect()->route('admin.estados.index')->with('success', 'Estado eliminado exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al eliminar el estado: ' . $e->getMessage());
            return redirect()->route('admin.estados.index')->with('error', 'Error al eliminar el estado');
        }
    }
}
