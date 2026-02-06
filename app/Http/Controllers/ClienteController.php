<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\ClientMeasurementHistory;
use App\Models\Recomendacion;
use App\Models\Estado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
            DB::transaction(function () use ($request) {
                $cliente = new Cliente();
                $cliente->nombre = mb_strtoupper(trim($request->nombre), 'UTF-8');
                $cliente->apellidos = mb_strtoupper(trim($request->apellidos), 'UTF-8');
                $cliente->telefono = $request->telefono;
                $cliente->email = $request->email;
                $cliente->direccion = mb_strtoupper(trim($request->direccion ?? ''), 'UTF-8');
                $cliente->ciudad = mb_strtoupper(trim($request->ciudad), 'UTF-8');
                $cliente->codigo_postal = $request->codigo_postal;
                $cliente->observaciones = mb_strtoupper(trim($request->observaciones ?? ''), 'UTF-8');
                $cliente->estado_id = $request->estado_id;
                $cliente->recomendacion_id = $request->recomendacion_id;
                // Campos legacy (se mantienen por compatibilidad)
                $cliente->busto = $request->busto;
                $cliente->alto_cintura = $request->alto_cintura;
                $cliente->cintura = $request->cintura;
                $cliente->cadera = $request->cadera;
                $cliente->largo = $request->largo;
                $cliente->largo_vestido = $request->largo_vestido;
                $cliente->save();

                // Guardar medidas en historial si hay al menos una medida
                $this->saveMeasurementsToHistory($cliente, $request, 'Registro inicial del cliente');
            });

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
            DB::transaction(function () use ($request, $id) {
                $cliente = Cliente::where('activo', true)->findOrFail($id);

                // Guardar medidas anteriores para comparar
                $oldMeasurements = [
                    'busto' => $cliente->busto,
                    'cintura' => $cliente->cintura,
                    'cadera' => $cliente->cadera,
                    'alto_cintura' => $cliente->alto_cintura,
                    'largo' => $cliente->largo,
                    'largo_vestido' => $cliente->largo_vestido,
                ];

                $cliente->nombre = mb_strtoupper(trim($request->nombre), 'UTF-8');
                $cliente->apellidos = mb_strtoupper(trim($request->apellidos), 'UTF-8');
                $cliente->telefono = $request->telefono;
                $cliente->email = $request->email;
                $cliente->direccion = mb_strtoupper(trim($request->direccion ?? ''), 'UTF-8');
                $cliente->ciudad = mb_strtoupper(trim($request->ciudad), 'UTF-8');
                $cliente->codigo_postal = $request->codigo_postal;
                $cliente->observaciones = mb_strtoupper(trim($request->observaciones ?? ''), 'UTF-8');
                $cliente->estado_id = $request->estado_id;
                $cliente->recomendacion_id = $request->recomendacion_id;
                // Campos legacy (se mantienen por compatibilidad)
                $cliente->busto = $request->busto;
                $cliente->alto_cintura = $request->alto_cintura;
                $cliente->cintura = $request->cintura;
                $cliente->cadera = $request->cadera;
                $cliente->largo = $request->largo;
                $cliente->largo_vestido = $request->largo_vestido;

                // Verificar si las medidas cambiaron
                $newMeasurements = [
                    'busto' => $request->busto,
                    'cintura' => $request->cintura,
                    'cadera' => $request->cadera,
                    'alto_cintura' => $request->alto_cintura,
                    'largo' => $request->largo,
                    'largo_vestido' => $request->largo_vestido,
                ];

                $measurementsChanged = $oldMeasurements != $newMeasurements;

                $cliente->save();

                // Guardar en historial solo si las medidas cambiaron y hay al menos una
                if ($measurementsChanged) {
                    $this->saveMeasurementsToHistory($cliente, $request, 'Actualización manual desde ficha de cliente');
                }
            });

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
            'apellidos' => ['required', 'string', 'max:255', 'regex:/^[A-Za-záéíóúÁÉÍÓÚñÑ\s]+$/u'],
            'telefono' => ['required', 'regex:/^[0-9]{10}$/', 'unique:clientes,telefono'],
            'email' => ['nullable', 'email', 'max:255'],
            'estado_id' => ['required', 'exists:estados,id'],
            'recomendacion_id' => ['required', 'exists:recomendacion,id'],
        ], [
            'nombre.required' => 'El nombre es obligatorio',
            'nombre.regex' => 'El nombre solo puede contener letras y espacios',
            'apellidos.required' => 'Los apellidos son obligatorios',
            'apellidos.regex' => 'Los apellidos solo pueden contener letras y espacios',
            'telefono.required' => 'El teléfono es obligatorio',
            'telefono.regex' => 'El teléfono debe tener 10 dígitos',
            'telefono.unique' => 'Este teléfono ya está registrado',
            'email.email' => 'El email no tiene un formato válido',
            'estado_id.required' => 'Selecciona un estado',
            'estado_id.exists' => 'El estado seleccionado no es válido',
            'recomendacion_id.required' => 'Selecciona una recomendación',
            'recomendacion_id.exists' => 'La recomendación seleccionada no es válida',
        ]);

        try {
            $cliente = new Cliente();
            $cliente->nombre = mb_strtoupper(trim($request->nombre), 'UTF-8');
            $cliente->apellidos = mb_strtoupper(trim($request->apellidos), 'UTF-8');
            $cliente->telefono = $request->telefono;
            $cliente->email = $request->email ? trim($request->email) : null;
            $cliente->estado_id = $request->estado_id;
            $cliente->recomendacion_id = $request->recomendacion_id;
            $cliente->ciudad = 'POR DEFINIR';
            $cliente->activo = true;

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

    /**
     * Guardar medidas en el historial del cliente
     * Source: 'manual' para ediciones desde sección clientes
     */
    protected function saveMeasurementsToHistory(Cliente $cliente, Request $request, string $notes = ''): ?ClientMeasurementHistory
    {
        $measurements = [
            'busto' => $request->busto,
            'cintura' => $request->cintura,
            'cadera' => $request->cadera,
            'alto_cintura' => $request->alto_cintura,
            'largo' => $request->largo,
            'largo_vestido' => $request->largo_vestido,
        ];

        // Filtrar valores vacíos
        $filteredMeasurements = collect($measurements)
            ->filter(fn($v) => !empty($v) && $v !== '0')
            ->toArray();

        // Solo guardar si hay al menos una medida
        if (empty($filteredMeasurements)) {
            return null;
        }

        return ClientMeasurementHistory::create([
            'cliente_id' => $cliente->id,
            'order_id' => null, // No viene de un pedido
            'order_item_id' => null,
            'product_id' => null,
            'measurements' => $filteredMeasurements,
            'source' => 'manual', // Indica que fue capturado manualmente en sección clientes
            'notes' => $notes,
            'created_by' => Auth::id(),
            'captured_at' => now(),
        ]);
    }
}
