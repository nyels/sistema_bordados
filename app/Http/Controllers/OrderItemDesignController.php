<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderEvent;
use App\Models\OrderMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class OrderItemDesignController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Subir archivo de diseño a un item
     */
    public function upload(Request $request, Order $order, OrderItem $item)
    {
        // Validar que el item pertenece al pedido
        if ($item->order_id !== $order->id) {
            abort(404);
        }

        // Solo permitir subida en estados editables
        if (!in_array($order->status, [Order::STATUS_DRAFT, Order::STATUS_CONFIRMED])) {
            return back()->with('error', 'No se puede modificar el diseño en este estado del pedido.');
        }

        $request->validate([
            'design_file' => 'required|file|max:10240|mimes:ai,dst,png,jpg,jpeg,pdf,svg',
            'design_notes' => 'nullable|string|max:1000',
            'custom_text' => 'nullable|string|max:255',
        ]);

        // Eliminar archivo anterior si existe
        if ($item->design_file && Storage::disk('public')->exists($item->design_file)) {
            Storage::disk('public')->delete($item->design_file);
        }

        // Guardar nuevo archivo
        $file = $request->file('design_file');
        $path = $file->store('designs/' . $order->id, 'public');

        $item->update([
            'design_file' => $path,
            'design_original_name' => $file->getClientOriginalName(),
            'design_status' => OrderItem::DESIGN_STATUS_PENDING,
            'design_notes' => $request->design_notes,
            'custom_text' => $request->custom_text,
            'design_approved' => false,
            'design_approved_at' => null,
            'design_approved_by' => null,
        ]);

        // Registrar evento
        OrderEvent::log(
            $order,
            'design_uploaded',
            "Diseño subido para: {$item->product_name}",
            [
                'item_id' => $item->id,
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
            ]
        );

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Diseño subido correctamente.',
                'item' => [
                    'id' => $item->id,
                    'design_file' => $item->design_file,
                    'design_original_name' => $item->design_original_name,
                    'design_status' => $item->design_status,
                ]
            ]);
        }

        return back()->with('success', 'Diseño subido correctamente. Pendiente de revisión.');
    }

    /**
     * Enviar diseño a revisión
     */
    public function sendToReview(Order $order, OrderItem $item)
    {
        if ($item->order_id !== $order->id) {
            abort(404);
        }

        if (!$item->design_file) {
            return back()->with('error', 'Debe subir un archivo de diseño primero.');
        }

        $item->update([
            'design_status' => OrderItem::DESIGN_STATUS_IN_REVIEW,
        ]);

        // Crear mensaje operativo
        OrderMessage::create([
            'order_id' => $order->id,
            'message' => "Diseño de '{$item->product_name}' enviado para revisión del cliente.",
            'visibility' => 'both',
            'created_by' => Auth::id(),
        ]);

        OrderEvent::log(
            $order,
            'design_sent_review',
            "Diseño de '{$item->product_name}' enviado a revisión",
            ['item_id' => $item->id]
        );

        return back()->with('success', 'Diseño enviado a revisión del cliente.');
    }

    /**
     * Aprobar diseño (cliente o admin)
     */
    public function approve(Order $order, OrderItem $item)
    {
        if ($item->order_id !== $order->id) {
            abort(404);
        }

        if ($item->design_status !== OrderItem::DESIGN_STATUS_IN_REVIEW) {
            return back()->with('error', 'El diseño debe estar en revisión para aprobarlo.');
        }

        $item->update([
            'design_status' => OrderItem::DESIGN_STATUS_APPROVED,
            'design_approved' => true,
            'design_approved_at' => now(),
            'design_approved_by' => Auth::id(),
            'measurements_hash_at_approval' => $item->getCurrentMeasurementsHash(),
        ]);

        // Crear mensaje operativo
        OrderMessage::create([
            'order_id' => $order->id,
            'message' => "DISEÑO APROBADO: '{$item->product_name}' listo para producción.",
            'visibility' => 'both',
            'created_by' => Auth::id(),
        ]);

        OrderEvent::log(
            $order,
            'design_approved',
            "Diseño aprobado: {$item->product_name}",
            ['item_id' => $item->id, 'approved_by' => Auth::user()->name]
        );

        return back()->with('success', 'Diseño aprobado. El item puede continuar a producción.');
    }

    /**
     * Rechazar diseño
     */
    public function reject(Request $request, Order $order, OrderItem $item)
    {
        if ($item->order_id !== $order->id) {
            abort(404);
        }

        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $item->update([
            'design_status' => OrderItem::DESIGN_STATUS_REJECTED,
            'design_approved' => false,
        ]);

        // Crear mensaje con motivo
        OrderMessage::create([
            'order_id' => $order->id,
            'message' => "DISEÑO RECHAZADO: '{$item->product_name}'\nMotivo: {$request->rejection_reason}",
            'visibility' => 'both',
            'created_by' => Auth::id(),
        ]);

        OrderEvent::log(
            $order,
            'design_rejected',
            "Diseño rechazado: {$item->product_name}",
            [
                'item_id' => $item->id,
                'reason' => $request->rejection_reason,
            ]
        );

        return back()->with('warning', 'Diseño rechazado. Se notificó al equipo para corrección.');
    }

    /**
     * Descargar archivo de diseño
     */
    public function download(Order $order, OrderItem $item)
    {
        if ($item->order_id !== $order->id) {
            abort(404);
        }

        if (!$item->design_file || !Storage::disk('public')->exists($item->design_file)) {
            abort(404, 'Archivo de diseño no encontrado.');
        }

        return Storage::disk('public')->download(
            $item->design_file,
            $item->design_original_name ?? 'design.' . pathinfo($item->design_file, PATHINFO_EXTENSION)
        );
    }

    /**
     * Ver estado de diseños de un pedido (API)
     */
    public function status(Order $order)
    {
        $items = $order->items()
            ->whereIn('personalization_type', [OrderItem::PERSONALIZATION_DESIGN])
            ->select('id', 'product_name', 'design_file', 'design_original_name', 'design_status', 'design_approved', 'design_notes', 'custom_text')
            ->get();

        return response()->json([
            'success' => true,
            'items' => $items,
            'summary' => [
                'total' => $items->count(),
                'pending' => $items->where('design_status', 'pending')->count(),
                'in_review' => $items->where('design_status', 'in_review')->count(),
                'approved' => $items->where('design_status', 'approved')->count(),
                'rejected' => $items->where('design_status', 'rejected')->count(),
            ]
        ]);
    }
}
