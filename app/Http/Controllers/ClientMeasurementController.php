<?php

namespace App\Http\Controllers;

use App\Models\ClientMeasurement;
use App\Models\Cliente;
use App\Http\Requests\StoreClientMeasurementRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * ClientMeasurementController
 *
 * Maneja medidas de clientes con flujo ERP:
 * - Un cliente puede tener múltiples registros de medidas
 * - Solo UNA medida puede ser "primary" por cliente
 * - El pedido referencia client_measurement_id
 */
class ClientMeasurementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // =========================================
    // LISTAR MEDIDAS DE UN CLIENTE (AJAX)
    // =========================================
    public function index(Cliente $cliente)
    {
        $measurements = ClientMeasurement::where('cliente_id', $cliente->id)
            ->orderBy('is_primary', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($m) {
                return [
                    'id' => $m->id,
                    'uuid' => $m->uuid,
                    'label' => $m->label,
                    'is_primary' => (bool) $m->is_primary,
                    'busto' => $m->busto,
                    'cintura' => $m->cintura,
                    'cadera' => $m->cadera,
                    'alto_cintura' => $m->alto_cintura,
                    'largo' => $m->largo,
                    'largo_vestido' => $m->largo_vestido,
                    'hombro' => $m->hombro,
                    'espalda' => $m->espalda,
                    'largo_manga' => $m->largo_manga,
                    'notes' => $m->notes,
                    'summary' => $m->summary,
                    'created_at' => $m->created_at->format('d/m/Y H:i'),
                    'created_at_short' => $m->created_at->format('d/m/Y'),
                ];
            });

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'measurements' => $measurements,
                'has_measurements' => $measurements->isNotEmpty(),
                'primary_id' => $measurements->firstWhere('is_primary', true)['id'] ?? null,
            ]);
        }

        return view('admin.clientes.measurements', compact('cliente', 'measurements'));
    }

    // =========================================
    // GUARDAR NUEVAS MEDIDAS (AJAX)
    // =========================================
    public function store(StoreClientMeasurementRequest $request)
    {
        $validated = $request->validated();

        // Si es primary, desmarcar las anteriores
        if (!empty($validated['is_primary'])) {
            ClientMeasurement::where('cliente_id', $validated['cliente_id'])
                ->update(['is_primary' => false]);
        }

        $measurement = ClientMeasurement::create([
            ...$validated,
            'created_by' => Auth::id(),
        ]);

        // Si es AJAX, retornar JSON con datos completos
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'measurement' => [
                    'id' => $measurement->id,
                    'uuid' => $measurement->uuid,
                    'label' => $measurement->label,
                    'is_primary' => (bool) $measurement->is_primary,
                    'busto' => $measurement->busto,
                    'cintura' => $measurement->cintura,
                    'cadera' => $measurement->cadera,
                    'alto_cintura' => $measurement->alto_cintura,
                    'largo' => $measurement->largo,
                    'largo_vestido' => $measurement->largo_vestido,
                    'notes' => $measurement->notes,
                    'summary' => $measurement->summary,
                    'created_at' => $measurement->created_at->format('d/m/Y H:i'),
                ],
                'message' => 'Medidas guardadas exitosamente.',
            ]);
        }

        return redirect()->back()
            ->with('success', 'Medidas guardadas exitosamente.');
    }

    // =========================================
    // ACTUALIZAR MEDIDAS EXISTENTES (AJAX)
    // =========================================
    public function update(Request $request, ClientMeasurement $measurement)
    {
        $validated = $request->validate([
            'busto' => 'nullable|numeric|min:30|max:200',
            'cintura' => 'nullable|numeric|min:30|max:200',
            'cadera' => 'nullable|numeric|min:30|max:200',
            'alto_cintura' => 'nullable|numeric|min:10|max:100',
            'largo' => 'nullable|numeric|min:30|max:200',
            'largo_vestido' => 'nullable|numeric|min:30|max:200',
            'hombro' => 'nullable|numeric|min:10|max:100',
            'espalda' => 'nullable|numeric|min:20|max:100',
            'largo_manga' => 'nullable|numeric|min:20|max:100',
            'label' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:500',
        ]);

        $measurement->update($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'measurement' => [
                    'id' => $measurement->id,
                    'uuid' => $measurement->uuid,
                    'label' => $measurement->label,
                    'is_primary' => (bool) $measurement->is_primary,
                    'busto' => $measurement->busto,
                    'cintura' => $measurement->cintura,
                    'cadera' => $measurement->cadera,
                    'alto_cintura' => $measurement->alto_cintura,
                    'largo' => $measurement->largo,
                    'largo_vestido' => $measurement->largo_vestido,
                    'notes' => $measurement->notes,
                    'summary' => $measurement->summary,
                    'created_at' => $measurement->created_at->format('d/m/Y H:i'),
                ],
                'message' => 'Medidas actualizadas correctamente.',
            ]);
        }

        return redirect()->back()->with('success', 'Medidas actualizadas.');
    }

    // =========================================
    // MARCAR COMO PRIMARIA (AJAX)
    // =========================================
    public function setPrimary(ClientMeasurement $measurement)
    {
        // Desmarcar otras del mismo cliente
        ClientMeasurement::where('cliente_id', $measurement->cliente_id)
            ->update(['is_primary' => false]);

        // Marcar esta como principal
        $measurement->update(['is_primary' => true]);

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'measurement_id' => $measurement->id,
                'message' => 'Medidas marcadas como principales.',
            ]);
        }

        return redirect()->back()
            ->with('success', 'Medidas marcadas como principales.');
    }

    // =========================================
    // OBTENER UNA MEDIDA ESPECÍFICA (AJAX)
    // =========================================
    public function show(ClientMeasurement $measurement)
    {
        return response()->json([
            'success' => true,
            'measurement' => [
                'id' => $measurement->id,
                'uuid' => $measurement->uuid,
                'cliente_id' => $measurement->cliente_id,
                'label' => $measurement->label,
                'is_primary' => (bool) $measurement->is_primary,
                'busto' => $measurement->busto,
                'cintura' => $measurement->cintura,
                'cadera' => $measurement->cadera,
                'alto_cintura' => $measurement->alto_cintura,
                'largo' => $measurement->largo,
                'largo_vestido' => $measurement->largo_vestido,
                'hombro' => $measurement->hombro,
                'espalda' => $measurement->espalda,
                'largo_manga' => $measurement->largo_manga,
                'notes' => $measurement->notes,
                'summary' => $measurement->summary,
                'created_at' => $measurement->created_at->format('d/m/Y H:i'),
            ],
        ]);
    }

    // =========================================
    // ELIMINAR MEDIDAS (AJAX)
    // =========================================
    public function destroy(ClientMeasurement $measurement)
    {
        $clienteId = $measurement->cliente_id;
        $wasPrimary = $measurement->is_primary;

        $measurement->delete();

        // Si era primary, marcar la más reciente como primary
        if ($wasPrimary) {
            $nextPrimary = ClientMeasurement::where('cliente_id', $clienteId)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($nextPrimary) {
                $nextPrimary->update(['is_primary' => true]);
            }
        }

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Medidas eliminadas.',
            ]);
        }

        return redirect()->back()
            ->with('success', 'Medidas eliminadas.');
    }
}
