<?php

namespace App\Http\Controllers;

use App\Models\Giro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GiroController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $giros = Giro::all()->where('activo', true);
        return view('admin.giros.index', compact('giros'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.giros.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre_giro' => ['required', 'string', 'max:255', 'regex:/^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]+$/'],
        ]);
        try {
            $nombre = strtoupper(trim($request->nombre_giro));

            // Buscar si ya existe (activo o inactivo)
            $existingGiro = Giro::where('nombre_giro', $nombre)->first();

            if ($existingGiro) {
                if ($existingGiro->activo) {
                    return back()->withErrors(['nombre_giro' => 'El nombre del giro ya ha sido registrado.'])->withInput();
                } else {
                    // Reactivar si existía pero estaba eliminado logicamente
                    $existingGiro->activo = true;
                    $existingGiro->save();
                    return redirect()->route('admin.giros.index')->with('success', 'Giro reactivado exitosamente');
                }
            }

            $giro = new Giro();
            $giro->nombre_giro = $nombre;
            $giro->save();
            return redirect()->route('admin.giros.index')->with('success', 'Giro guardado exitosamente');
        } catch (\Exception $e) {
            return redirect()->route('admin.giros.index')->with('error', 'Error al guardar el giro: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Giro $giro)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $giro = Giro::where('id', $id)
            ->where('activo', true)
            ->firstOrFail();
        if (!$giro) {
            return redirect()->route('admin.giros.index')->with('error', 'Giro no encontrado');
        }
        return view('admin.giros.edit', compact('giro'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre_giro' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]+$/',
                'unique:giros,nombre_giro,' . $id,
            ],
        ]);
        try {
            $giro = Giro::where('id', $id)
                ->where('activo', true)
                ->firstOrFail();
            $giro->nombre_giro = strtoupper(trim($request->nombre_giro));

            //validamos si hubo algun cambio o modificacion en el valor del campo nombre_estado, si no hubo cambios, no se actualiza
            if (! $giro->isDirty()) {
                // return back()->with('info', 'No se realizaron cambios');
                return redirect()->route('admin.giros.index')->with('info', 'No se realizaron cambios');
            }

            $giro->save();
            return redirect()->route('admin.giros.index')->with('success', 'Giro actualizado exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al actualizar el giro: ' . $e->getMessage());
            return redirect()->route('admin.giros.index')->with('error', 'Error al actualizar el giro');
        }
    }
    public function confirm_delete($id)
    {
        //validando que existe el giro
        $giro = Giro::where('id', $id)
            ->where('activo', true)
            ->firstOrFail();
        if (!$giro) {
            return redirect()->route('admin.giros.index')->with('error', 'Giro no encontrado');
        }
        return view('admin.giros.delete', compact('giro'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $giro = Giro::where('id', $id)
            ->where('activo', true)
            ->firstOrFail();
        if (!$giro) {
            return redirect()->route('admin.giros.confirm_delete', $id)->with('error', 'Giro no encontrado');
        }
        try {
            $giro->activo = false;
            $giro->save();
            return redirect()->route('admin.giros.index')->with('success', 'Giro eliminado exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al eliminar el giro: ' . $e->getMessage());
            return redirect()->route('admin.giros.index')->with('error', 'Error al eliminar el giro');
        }
    }
}
