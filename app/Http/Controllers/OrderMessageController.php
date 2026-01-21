<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderMessage;
use App\Events\OrderMessageCreated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderMessageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Almacenar nuevo mensaje operativo
     */
    public function store(Request $request, Order $order)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
            'visibility' => 'required|in:admin,production,both',
        ]);

        $message = OrderMessage::create([
            'order_id' => $order->id,
            'message' => $validated['message'],
            'visibility' => $validated['visibility'],
            'created_by' => Auth::id(),
        ]);

        // Disparar evento para notificaciones en tiempo real
        if (class_exists(OrderMessageCreated::class)) {
            event(new OrderMessageCreated($message));
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Mensaje agregado correctamente.',
                'data' => [
                    'id' => $message->id,
                    'message' => $message->message,
                    'visibility' => $message->visibility,
                    'visibility_label' => $message->visibility_label,
                    'creator' => $message->creator?->name ?? 'Sistema',
                    'created_at' => $message->created_at->format('d/m/Y H:i'),
                ],
            ]);
        }

        return redirect()->back()->with('success', 'Nota operativa agregada.');
    }

    /**
     * Obtener mensajes de un pedido (API)
     */
    public function index(Order $order)
    {
        $messages = $order->messages()
            ->with('creator')
            ->latest()
            ->take(50)
            ->get()
            ->map(fn($m) => [
                'id' => $m->id,
                'message' => $m->message,
                'visibility' => $m->visibility,
                'visibility_label' => $m->visibility_label,
                'visibility_icon' => $m->visibility_icon,
                'creator' => $m->creator?->name ?? 'Sistema',
                'created_at' => $m->created_at->format('d/m/Y H:i'),
                'time_ago' => $m->created_at->diffForHumans(),
            ]);

        return response()->json([
            'success' => true,
            'data' => $messages,
        ]);
    }

    /**
     * Eliminar mensaje (solo creador o admin)
     */
    public function destroy(Order $order, OrderMessage $message)
    {
        // Verificar que el mensaje pertenece al pedido
        if ($message->order_id !== $order->id) {
            abort(404);
        }

        // Solo el creador o admin puede eliminar
        if ($message->created_by !== Auth::id()) {
            return redirect()->back()->with('error', 'Solo puede eliminar sus propios mensajes.');
        }

        $message->delete();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'Mensaje eliminado.');
    }
}
