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
     * Almacenar nuevo mensaje operativo (puede ser respuesta a otro mensaje)
     */
    public function store(Request $request, Order $order)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
            'visibility' => 'required|in:admin,production,both',
            'parent_message_id' => 'nullable|exists:order_messages,id',
        ]);

        $message = OrderMessage::create([
            'order_id' => $order->id,
            'parent_message_id' => $validated['parent_message_id'] ?? null,
            'message' => $validated['message'],
            'visibility' => $validated['visibility'],
            'created_by' => Auth::id(),
        ]);

        // Disparar evento para notificaciones en tiempo real
        // Usar broadcast()->toOthers() para excluir al remitente del mensaje
        if (class_exists(OrderMessageCreated::class)) {
            broadcast(new OrderMessageCreated($message))->toOthers();
        }

        if ($request->ajax() || $request->wantsJson()) {
            // Cargar relación con mensaje padre si existe
            $message->load('parent.creator');

            return response()->json([
                'success' => true,
                'message' => 'Mensaje agregado correctamente.',
                'data' => [
                    'id' => $message->id,
                    'message' => $message->message,
                    'visibility' => $message->visibility,
                    'visibility_label' => $message->visibility_label,
                    'creator' => $message->creator?->name ?? 'Sistema',
                    'created_by' => $message->created_by,
                    'is_own' => true,
                    'created_at' => $message->created_at->format('d/m/Y H:i'),
                    'time_ago' => $message->created_at->diffForHumans(),
                    'parent_message_id' => $message->parent_message_id,
                    'parent_preview' => $message->parent
                        ? mb_substr($message->parent->message, 0, 50) . (mb_strlen($message->parent->message) > 50 ? '...' : '')
                        : null,
                    'parent_creator' => $message->parent?->creator?->name ?? null,
                ],
            ]);
        }

        return redirect()->back()->with('success', 'Nota operativa agregada.');
    }

    /**
     * Obtener mensajes de un pedido (API) - lista plana estilo chat
     */
    public function index(Order $order)
    {
        // Obtener TODOS los mensajes ordenados por fecha (estilo chat)
        $messages = $order->messages()
            ->with(['creator', 'parent.creator'])
            ->orderBy('created_at', 'asc')
            ->take(100)
            ->get()
            ->map(fn($m) => [
                'id' => $m->id,
                'message' => $m->message,
                'visibility' => $m->visibility,
                'visibility_label' => $m->visibility_label,
                'visibility_icon' => $m->visibility_icon,
                'creator' => $m->creator?->name ?? 'Sistema',
                'created_by' => $m->created_by,
                'is_own' => $m->created_by === Auth::id(),
                'created_at' => $m->created_at->format('d/m/Y H:i'),
                'time_ago' => $m->created_at->diffForHumans(),
                // Info del mensaje padre si es respuesta
                'parent_message_id' => $m->parent_message_id,
                'parent_creator' => $m->parent?->creator?->name ?? null,
                'parent_preview' => $m->parent
                    ? mb_substr($m->parent->message, 0, 50) . (mb_strlen($m->parent->message) > 50 ? '...' : '')
                    : null,
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
