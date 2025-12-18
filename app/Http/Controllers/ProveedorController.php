<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use App\Models\Estado;
use App\Models\Giro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProveedorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $proveedores = Proveedor::where('activo', true)->with('estado', 'giro')->get();
        return view('admin.proveedores.index', compact('proveedores'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $estados = Estado::all();
        $giros = Giro::all();
        return view('admin.proveedores.create', compact('estados', 'giros'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'nombre_proveedor' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9\sñÑáéíóúÁÉÍÓÚ\.\,]+$/'],
            'giro_id' =>  ['required', 'exists:giros,id'],
            'direccion' =>  ['string', 'max:255', 'regex:/^[a-zA-Z0-9\sñÑáéíóúÁÉÍÓÚ\.\#]+$/', 'nullable'],
            'codigo_postal' => ['string', 'max:255', 'regex:/^[0-9]{5}$/', 'nullable'],
            'telefono' => ['required', 'string', 'max:255', 'regex:/^[0-9]{10}$/'],
            'email' => ['email', 'string', 'max:255', 'nullable'],
            'estado_id' => ['required', 'exists:estados,id'],
            'ciudad' => ['string', 'max:255', 'regex:/^[a-zA-Z0-9\sñÑáéíóúÁÉÍÓÚ\.]+$/', 'nullable'],
            'nombre_contacto' => ['string', 'max:255', 'regex:/^[a-zA-Z\sñÑáéíóúÁÉÍÓÚ\.]+$/', 'nullable'],
            'telefono_contacto' => ['string', 'max:255', 'regex:/^[0-9]{10}$/', 'nullable'],
            'email_contacto' => ['email', 'string', 'max:255', 'nullable'],
        ]);
        try {
            $proveedor = new Proveedor();
            $proveedor->nombre_proveedor = strtoupper(trim($request->nombre_proveedor));
            $proveedor->giro_id = $request->giro_id;
            $proveedor->direccion = strtoupper(trim($request->direccion));
            $proveedor->codigo_postal = $request->codigo_postal;
            $proveedor->telefono = $request->telefono;
            $proveedor->email = $request->email;
            $proveedor->estado_id = $request->estado_id;
            $proveedor->ciudad = strtoupper(trim($request->ciudad));
            $proveedor->nombre_contacto = strtoupper(trim($request->nombre_contacto));
            $proveedor->telefono_contacto = $request->telefono_contacto;
            $proveedor->email_contacto = $request->email_contacto;
            $proveedor->save();
            return redirect()->route('admin.proveedores.index')->with('success', 'Proveedor creado exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al crear el proveedor: ' . $e->getMessage());
            return redirect()->route('admin.proveedores.index')->with('error', 'Error al crear el proveedor');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Proveedor $proveedor)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $proveedor = Proveedor::where('activo', true)->with('estado', 'giro')->findOrFail($id);
        $estados = Estado::all();
        $giros = Giro::all();
        return view('admin.proveedores.edit', compact('proveedor', 'estados', 'giros'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre_proveedor' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9\sñÑáéíóúÁÉÍÓÚ\.\,]+$/'],
            'giro_id' =>  ['required', 'exists:giros,id'],
            'direccion' =>  ['string', 'max:255', 'regex:/^[a-zA-Z0-9\sñÑáéíóúÁÉÍÓÚ\.\#]+$/', 'nullable'],
            'codigo_postal' => ['string', 'max:255', 'regex:/^[0-9]{5}$/', 'nullable'],
            'telefono' => ['required', 'string', 'max:255', 'regex:/^[0-9]{10}$/'],
            'email' => ['email', 'string', 'max:255', 'nullable'],
            'estado_id' => ['required', 'exists:estados,id'],
            'ciudad' => ['string', 'max:255', 'regex:/^[a-zA-Z0-9\sñÑáéíóúÁÉÍÓÚ\.]+$/', 'nullable'],
            'nombre_contacto' => ['string', 'max:255', 'regex:/^[a-zA-Z\sñÑáéíóúÁÉÍÓÚ\.]+$/', 'nullable'],
            'telefono_contacto' => ['string', 'max:255', 'regex:/^[0-9]{10}$/', 'nullable'],
            'email_contacto' => ['email', 'string', 'max:255', 'nullable'],
        ]);

        $proveedor = Proveedor::where('activo', true)->with('estado', 'giro')->findOrFail($id);

        try {

            $proveedor->nombre_proveedor = strtoupper(trim($request->nombre_proveedor));
            $proveedor->giro_id = $request->giro_id;
            $proveedor->direccion = strtoupper(trim($request->direccion));
            $proveedor->codigo_postal = $request->codigo_postal;
            $proveedor->telefono = $request->telefono;
            $proveedor->email = $request->email;
            $proveedor->estado_id = $request->estado_id;
            $proveedor->ciudad = strtoupper(trim($request->ciudad));
            $proveedor->nombre_contacto = strtoupper(trim($request->nombre_contacto));
            $proveedor->telefono_contacto = $request->telefono_contacto;
            $proveedor->email_contacto = $request->email_contacto;

            if (!$proveedor->isDirty()) {
                return redirect()->route('admin.proveedores.index')->with('info', 'No se realizaron cambios');
            }

            $proveedor->save();
            return redirect()->route('admin.proveedores.index')->with('success', 'Proveedor actualizado exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al actualizar el proveedor: ' . $e->getMessage());
            return redirect()->route('admin.proveedores.index')->with('error', 'Error al actualizar el proveedor');
        }
    }

    public function confirm_delete($id)
    {
        $proveedor = Proveedor::where('activo', true)->with('estado', 'giro')->findOrFail($id);
        $estados = Estado::all();
        $giros = Giro::all();
        return view('admin.proveedores.delete', compact('proveedor', 'estados', 'giros'));
    }

    public function destroy($id)
    {
        $proveedor = Proveedor::where('activo', true)->with('estado', 'giro')->findOrFail($id);
        try {
            $proveedor->activo = false;
            $proveedor->save();
            return redirect()->route('admin.proveedores.index')->with('success', 'Proveedor eliminado exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al eliminar el proveedor: ' . $e->getMessage());
            return redirect()->route('admin.proveedores.index')->with('error', 'Error al eliminar el proveedor');
        }
    }
}
