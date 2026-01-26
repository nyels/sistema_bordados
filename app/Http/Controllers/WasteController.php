<?php

namespace App\Http\Controllers;

use App\Models\WasteEvent;
use App\Models\Order;
use App\Services\WasteService;
use Illuminate\Http\Request;

/**
 * CONTROLADOR PASIVO DE MERMA
 *
 * PROPÓSITO:
 * Exponer WasteService via HTTP sin agregar lógica.
 *
 * REGLAS:
 * - NO contiene lógica de negocio
 * - NO valida (WasteService valida)
 * - NO transforma datos
 * - SOLO delega a WasteService
 * - SOLO maneja try/catch para UX
 */
class WasteController extends Controller
{
    protected WasteService $wasteService;

    public function __construct(WasteService $wasteService)
    {
        $this->wasteService = $wasteService;
    }

    /**
     * Listado de eventos de merma.
     * GET admin/waste
     */
    public function index()
    {
        $wasteEvents = WasteEvent::with(['order', 'productVariant', 'creator', 'materialItems.materialVariant.material'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.waste.index', compact('wasteEvents'));
    }

    /**
     * Detalle de un evento de merma.
     * GET admin/waste/{wasteEvent}
     */
    public function show(WasteEvent $wasteEvent)
    {
        $wasteEvent->load(['order', 'productVariant.product', 'creator', 'materialItems.materialVariant.material']);

        return view('admin.waste.show', compact('wasteEvent'));
    }

    /**
     * Registrar merma de material.
     * POST admin/waste/material
     */
    public function storeMaterial(Request $request)
    {
        try {
            $this->wasteService->registerMaterialWaste(
                $request->input('materials', []),
                $request->input('reason', ''),
                $request->input('order_id'),
                $request->input('evidence_path')
            );

            return redirect()->route('admin.waste.index')
                ->with('success', 'Merma de material registrada correctamente.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al registrar la merma.');
        }
    }

    /**
     * Registrar merma en proceso (WIP).
     * POST admin/waste/wip/{order}
     */
    public function storeWip(Request $request, Order $order)
    {
        try {
            $this->wasteService->registerWipWaste(
                $order->id,
                $request->input('materials', []),
                $request->input('reason', ''),
                $request->input('evidence_path')
            );

            return redirect()->route('admin.orders.show', $order)
                ->with('success', 'Merma en proceso registrada correctamente.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al registrar la merma.');
        }
    }

    /**
     * Registrar merma de producto terminado.
     * POST admin/waste/finished-product
     */
    public function storeFinishedProduct(Request $request)
    {
        try {
            $this->wasteService->registerFinishedProductWaste(
                $request->input('product_variant_id'),
                $request->input('quantity'),
                $request->input('reason', ''),
                $request->input('order_id'),
                $request->input('evidence_path')
            );

            return redirect()->route('admin.waste.index')
                ->with('success', 'Merma de producto terminado registrada correctamente.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al registrar la merma.');
        }
    }
}
