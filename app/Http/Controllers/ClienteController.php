<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Recomendacion;
use App\Models\Estado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ClienteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $clientes = Cliente::where('activo', true)->with('estado', 'recomendacion')->get();
        return view('admin.clientes.index', compact('clientes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $recomendaciones = Recomendacion::all();
        $estados = Estado::all();
        return view('admin.clientes.create', compact('recomendaciones', 'estados'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'apellidos' => ['required', 'string', 'max:255'],
            'telefono' => ['required', 'regex:/^[0-9]{10}$/', 'unique:clientes,telefono'],
            'email' => ['nullable', 'email', 'unique:clientes,email'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'ciudad' => ['required', 'string', 'max:255'],
            'codigo_postal' => ['nullable', 'max:5', 'regex:/^[0-9]{5}$/'],
            'fecha_baja' => 'nullable',
            'motivo_baja' => ['nullable', 'string', 'max:255'],
            'observaciones' => ['nullable', 'string'],
            'estado_id' => ['required', 'exists:estados,id'],
            'recomendacion_id' => ['required', 'exists:recomendacion,id'],
            'busto' => [
                'nullable',
                'regex:/^[1-9]\d{1,2}(\.(?:[1-9]|\d[1-9]))?$/'
            ],
            'alto_cintura' => [
                'nullable',
                'regex:/^[1-9]\d{1,2}(\.(?:[1-9]|\d[1-9]))?$/'
            ],
            'cintura' => [
                'nullable',
                'regex:/^[1-9]\d{1,2}(\.(?:[1-9]|\d[1-9]))?$/'
            ],
            'cadera' => [
                'nullable',
                'regex:/^[1-9]\d{1,2}(\.(?:[1-9]|\d[1-9]))?$/'
            ],
            'largo' => [
                'nullable',
                'regex:/^[1-9]\d{1,2}(\.(?:[1-9]|\d[1-9]))?$/'
            ],
            'largo_vestido' => [
                'nullable',
                'regex:/^[1-9]\d{1,2}(\.(?:[1-9]|\d[1-9]))?$/'
            ],
        ]);

        try {
            $cliente = new Cliente();
            $cliente->nombre = mb_strtoupper(trim($request->nombre), 'UTF-8');
            $cliente->apellidos = mb_strtoupper(trim($request->apellidos), 'UTF-8');
            $cliente->telefono = $request->telefono;
            $cliente->email = $request->email;
            $cliente->direccion = mb_strtoupper(trim($request->direccion), 'UTF-8');
            $cliente->ciudad = mb_strtoupper(trim($request->ciudad), 'UTF-8');
            $cliente->codigo_postal = $request->codigo_postal;
            $cliente->observaciones = mb_strtoupper(trim($request->observaciones), 'UTF-8');
            $cliente->estado_id = $request->estado_id;
            $cliente->recomendacion_id = $request->recomendacion_id;
            $cliente->busto = $request->busto;
            $cliente->alto_cintura = $request->alto_cintura;
            $cliente->cintura = $request->cintura;
            $cliente->cadera = $request->cadera;
            $cliente->largo = $request->largo;
            $cliente->largo_vestido = $request->largo_vestido;
            $cliente->save();
            return redirect()->route('admin.clientes.index')->with('success', 'Cliente creado correctamente');
        } catch (\Exception $e) {
            Log::error('Error al crear el cliente: ' . $e->getMessage());
            return redirect()->route('admin.clientes.index')->with('error', 'Error al crear el cliente: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Cliente $cliente)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $cliente = Cliente::where('activo', true)->with('estado', 'recomendacion')->findOrFail($id);
        $recomendaciones = Recomendacion::all();
        $estados = Estado::all();
        return view('admin.clientes.edit', compact('cliente', 'recomendaciones', 'estados'));
    }

    public function getMeasures($id)
    {
        $cliente = Cliente::findOrFail($id);
        return view('admin.clientes.partials.measures', compact('cliente'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {


        $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'apellidos' => ['required', 'string', 'max:255'],
            'telefono' => ['required', 'regex:/^[0-9]{10}$/', 'unique:clientes,telefono,' . $id],
            'email' => ['nullable', 'email', 'unique:clientes,email,' . $id],
            'direccion' => ['nullable', 'string', 'max:255'],
            'codigo_postal' => ['nullable', 'max:5', 'regex:/^[0-9]{5}$/'],
            'ciudad' => ['required', 'string', 'max:255'],
            'fecha_baja' => 'nullable',
            'motivo_baja' => ['nullable', 'string', 'max:255'],
            'observaciones' => ['nullable', 'string'],
            'estado_id' => ['required', 'exists:estados,id'],
            'recomendacion_id' => ['required', 'exists:recomendacion,id'],
            'busto' => [
                'nullable',
                'regex:/^[1-9]\d{1,2}(\.(?:[1-9]|\d[1-9]))?$/'
            ],
            'alto_cintura' => [
                'nullable',
                'regex:/^[1-9]\d{1,2}(\.(?:[1-9]|\d[1-9]))?$/'
            ],
            'cintura' => [
                'nullable',
                'regex:/^[1-9]\d{1,2}(\.(?:[1-9]|\d[1-9]))?$/'
            ],
            'cadera' => [
                'nullable',
                'regex:/^[1-9]\d{1,2}(\.(?:[1-9]|\d[1-9]))?$/'
            ],
            'largo' => [
                'nullable',
                'regex:/^[1-9]\d{1,2}(\.(?:[1-9]|\d[1-9]))?$/'
            ],
            'largo_vestido' => [
                'nullable',
                'regex:/^[1-9]\d{1,2}(\.(?:[1-9]|\d[1-9]))?$/'
            ],
        ]);

        try {
            $cliente = Cliente::where('activo', true)->with('estado', 'recomendacion')->findOrFail($id);
            $cliente->nombre = mb_strtoupper(trim($request->nombre), 'UTF-8');
            $cliente->apellidos = mb_strtoupper(trim($request->apellidos), 'UTF-8');
            $cliente->telefono = $request->telefono;
            $cliente->email = $request->email;
            $cliente->direccion = mb_strtoupper(trim($request->direccion), 'UTF-8');
            $cliente->ciudad = mb_strtoupper(trim($request->ciudad), 'UTF-8');
            $cliente->codigo_postal = $request->codigo_postal;
            $cliente->observaciones = mb_strtoupper(trim($request->observaciones ?? ''), 'UTF-8');
            $cliente->estado_id = $request->estado_id;
            $cliente->recomendacion_id = $request->recomendacion_id;
            $cliente->busto = $request->busto;
            $cliente->alto_cintura = $request->alto_cintura;
            $cliente->cintura = $request->cintura;
            $cliente->cadera = $request->cadera;
            $cliente->largo = $request->largo;
            $cliente->largo_vestido = $request->largo_vestido;

            if (!$cliente->isDirty()) {
                return redirect()->route('admin.clientes.index')->with('warning', 'No se realizaron cambios');
            }

            $cliente->save();
            return redirect()->route('admin.clientes.index')->with('success', 'Cliente actualizado correctamente');
        } catch (\Exception $e) {
            Log::error('Error al actualizar el cliente: ' . $e->getMessage());
            return redirect()->route('admin.clientes.index')->with('error', 'Error al actualizar el cliente: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */

    public function confirm_delete($id)
    {
        $cliente = Cliente::where('activo', true)->with('estado', 'recomendacion')->findOrFail($id);
        $estados = Estado::all();
        $recomendaciones = Recomendacion::all();
        return view('admin.clientes.delete', compact('cliente', 'estados', 'recomendaciones'));
    }

    public function destroy($id)
    {
        try {
            $cliente = Cliente::where('activo', true)->with('estado', 'recomendacion')->findOrFail($id);
            $cliente->activo = false;
            $cliente->fecha_baja = now();
            $cliente->save();
            return redirect()->route('admin.clientes.index')->with('success', 'Cliente eliminado correctamente');
        } catch (\Exception $e) {
            Log::error('Error al eliminar el cliente: ' . $e->getMessage());
            return redirect()->route('admin.clientes.index')->with('error', 'Error al eliminar el cliente: ' . $e->getMessage());
        }
    }

    // === AJAX: CREAR CLIENTE RÁPIDO (desde modal de pedidos) ===
    public function quickStore(Request $request)
    {
        $request->validate([
            'nombre' => ['required', 'string', 'max:255', 'regex:/^[A-Za-záéíóúÁÉÍÓÚñÑ\s]+$/u'],
            'apellidos' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-záéíóúÁÉÍÓÚñÑ\s]*$/u'],
            'telefono' => ['required', 'regex:/^[0-9]{10}$/', 'unique:clientes,telefono'],
        ]);

        try {
            $cliente = new Cliente();
            $cliente->nombre = mb_strtoupper(trim($request->nombre), 'UTF-8');
            $cliente->apellidos = mb_strtoupper(trim($request->apellidos ?? ''), 'UTF-8');
            $cliente->telefono = $request->telefono;
            $cliente->activo = true;

            // Valores por defecto para campos requeridos
            $defaultEstado = Estado::first();
            $defaultRecomendacion = Recomendacion::first();
            $cliente->estado_id = $defaultEstado?->id;
            $cliente->recomendacion_id = $defaultRecomendacion?->id;
            $cliente->ciudad = 'POR DEFINIR';

            $cliente->save();

            // Respuesta JSON para Select2
            return response()->json([
                'success' => true,
                'id' => $cliente->id,
                'text' => "{$cliente->nombre} {$cliente->apellidos} - {$cliente->telefono}",
            ]);
        } catch (\Exception $e) {
            Log::error('Error al crear cliente rápido: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al crear cliente: ' . $e->getMessage(),
            ], 422);
        }
    }
}
