<?php

namespace App\Http\Controllers;

use App\Models\Recomendacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RecomendacionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $recomendaciones = Recomendacion::where('activo', true)->get();
        return view('admin.recomendaciones.index', compact('recomendaciones'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.recomendaciones.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre_recomendacion' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]+$/',
            ],
        ]);
        try {
            $nombre = strtoupper(trim($request->nombre_recomendacion));

            // Buscar si ya existe (activo o inactivo)
            $existingRecomendacion = Recomendacion::where('nombre_recomendacion', $nombre)->first();

            if ($existingRecomendacion) {
                if ($existingRecomendacion->activo) {
                    return back()->withErrors(['nombre_recomendacion' => 'El nombre de la recomendación ya ha sido registrado.'])->withInput();
                } else {
                    // Reactivar si existía pero estaba eliminado logicamente
                    $existingRecomendacion->activo = true;
                    $existingRecomendacion->fecha_baja = null; // Limpiar fecha de baja si existe
                    $existingRecomendacion->save();
                    return redirect()->route('admin.recomendaciones.index')->with('success', 'Recomendación reactivada exitosamente');
                }
            }

            $recomendacion = new Recomendacion();
            $recomendacion->nombre_recomendacion = $nombre;
            $recomendacion->save();
            return redirect()->route('admin.recomendaciones.index')->with('success', 'Recomendación guardada exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al guardar la recomendación: ' . $e->getMessage());
            return redirect()->route('admin.recomendaciones.index')->with('error', 'Error al guardar la recomendación: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Recomendacion $recomendacion)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $recomendacion = Recomendacion::where('activo', true)->findOrFail($id);
        return view('admin.recomendaciones.edit', compact('recomendacion'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre_recomendacion' => ['required', 'string', 'max:255', 'regex:/^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]+$/', 'unique:recomendacion,nombre_recomendacion,' . $id],
        ]);
        try {
            $recomendacion = Recomendacion::where('activo', true)->findOrFail($id);
            $recomendacion->nombre_recomendacion = strtoupper(trim($request->nombre_recomendacion));
            if (!$recomendacion->isDirty()) {
                return redirect()->route('admin.recomendaciones.index')->with('info', 'No se realizaron cambios');
            }
            $recomendacion->save();
            return redirect()->route('admin.recomendaciones.index')->with('success', 'Recomendación guardada exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al guardar la recomendación: ' . $e->getMessage());
            return redirect()->route('admin.recomendaciones.index')->with('error', 'Error al guardar la recomendación: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */

    public function confirm_delete($id)
    {
        $recomendacion = Recomendacion::where('activo', true)->findOrFail($id);
        return view('admin.recomendaciones.delete', compact('recomendacion'));
    }

    public function destroy($id)
    {
        try {
            $recomendacion = Recomendacion::where('activo', true)->findOrFail($id);
            $recomendacion->activo = false;
            $recomendacion->fecha_baja = now();
            $recomendacion->save();
            return redirect()->route('admin.recomendaciones.index')->with('success', 'Recomendación eliminada exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al eliminar la recomendación: ' . $e->getMessage());
            return redirect()->route('admin.recomendaciones.index')->with('error', 'Error al eliminar la recomendación: ' . $e->getMessage());
        }
    }
}
