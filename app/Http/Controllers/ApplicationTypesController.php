<?php

namespace App\Http\Controllers;

use App\Models\Application_types;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ApplicationTypesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $aplication_types = Application_types::all()->where('activo', true);
        return view('admin.tipos_aplicacion.index', compact('aplication_types'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.tipos_aplicacion.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre_aplicacion' => ['required', 'string', 'max:255', 'regex:/^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]+$/'],
        ]);
        try {
            $aplication_types = new Application_types();
            $aplication_types->nombre_aplicacion = strtoupper(trim($request->nombre_aplicacion));
            $aplication_types->slug = Str::slug($request->nombre_aplicacion);
            $aplication_types->save();
            return redirect()->route('admin.tipos_aplicacion.index')->with('success', 'Tipo de aplicación guardado exitosamente');
        } catch (\Exception $e) {
            return redirect()->route('admin.tipos_aplicacion.index')->with('error', 'Error al guardar el tipo de aplicación: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Application_types $application_types)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $aplication_types = Application_types::where('id', $id)
            ->where('activo', true)
            ->firstOrFail();
        if (!$aplication_types) {
            return redirect()->route('admin.tipos_aplicacion.index')->with('error', 'Tipo de aplicación no encontrado');
        }
        return view('admin.tipos_aplicacion.edit', compact('aplication_types'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
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
            $aplication_types = Application_types::where('id', $id)
                ->where('activo', true)
                ->firstOrFail();
            $aplication_types->nombre_aplicacion = strtoupper(trim($request->nombre_aplicacion));

            //validamos si hubo algun cambio o modificacion en el valor del campo nombre_estado, si no hubo cambios, no se actualiza
            if (! $aplication_types->isDirty()) {
                // return back()->with('info', 'No se realizaron cambios');
                return redirect()->route('admin.tipos_aplicacion.index')->with('info', 'No se realizaron cambios');
            }

            $aplication_types->save();
            return redirect()->route('admin.tipos_aplicacion.index')->with('success', 'Tipo de aplicación actualizado exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al actualizar el tipo de aplicación: ' . $e->getMessage());
            return redirect()->route('admin.tipos_aplicacion.index')->with('error', 'Error al actualizar el tipo de aplicación');
        }
    }

    public function confirm_delete($id)
    {
        //validando que existe el giro
        $aplication_types = Application_types::where('id', $id)
            ->where('activo', true)
            ->firstOrFail();
        if (!$aplication_types) {
            return redirect()->route('admin.tipos_aplicacion.index')->with('error', 'Tipo de aplicación no encontrado');
        }
        return view('admin.tipos_aplicacion.delete', compact('aplication_types'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $aplication_types = Application_types::where('id', $id)
            ->where('activo', true)
            ->firstOrFail();
        if (!$aplication_types) {
            return redirect()->route('admin.tipos_aplicacion.confirm_delete', $id)->with('error', 'Tipo de aplicación no encontrado');
        }
        try {
            $aplication_types->activo = false;
            $aplication_types->save();
            return redirect()->route('admin.tipos_aplicacion.index')->with('success', 'Tipo de aplicación eliminado exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al eliminar el tipo de aplicación: ' . $e->getMessage());
            return redirect()->route('admin.tipos_aplicacion.index')->with('error', 'Error al eliminar el tipo de aplicación');
        }
    }
}
