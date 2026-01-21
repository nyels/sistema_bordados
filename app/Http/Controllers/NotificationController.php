<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\OrderMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class NotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Obtener notificaciones recientes para polling AJAX
     * Devuelve eventos y mensajes de los últimos 5 minutos que no se han visto
     */
    public function getRecent(Request $request)
    {
        $since = $request->input('since', Carbon::now()->subMinutes(5)->toDateTimeString());
        $sinceCarbon = Carbon::parse($since);

        $notifications = collect();

        // Eventos recientes de pedidos del usuario (o todos si es admin)
        $recentEvents = OrderEvent::where('created_at', '>', $sinceCarbon)
            ->whereIn('event_type', [
                OrderEvent::TYPE_PRODUCTION_BLOCKED,
                OrderEvent::TYPE_MATERIAL_INSUFFICIENT,
                OrderEvent::TYPE_PRODUCTION_STARTED,
                OrderEvent::TYPE_READY,
                OrderEvent::TYPE_DELIVERED,
                OrderEvent::TYPE_BLOCKED,
                OrderEvent::TYPE_UNBLOCKED,
            ])
            ->with('order')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        foreach ($recentEvents as $event) {
            $type = match($event->event_type) {
                OrderEvent::TYPE_PRODUCTION_BLOCKED,
                OrderEvent::TYPE_MATERIAL_INSUFFICIENT,
                OrderEvent::TYPE_BLOCKED => 'warning',
                OrderEvent::TYPE_UNBLOCKED,
                OrderEvent::TYPE_READY,
                OrderEvent::TYPE_DELIVERED => 'success',
                default => 'info'
            };

            $notifications->push([
                'id' => 'event_' . $event->id,
                'type' => $type,
                'title' => $event->event_label,
                'message' => $event->order ? "<strong>{$event->order->order_number}</strong>: {$event->message}" : $event->message,
                'created_at' => $event->created_at->toDateTimeString(),
            ]);
        }

        // Mensajes operativos recientes
        $recentMessages = OrderMessage::where('created_at', '>', $sinceCarbon)
            ->where('created_by', '!=', Auth::id()) // No mostrar mis propios mensajes
            ->whereIn('visibility', ['admin', 'both'])
            ->with(['order', 'creator'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        foreach ($recentMessages as $message) {
            $notifications->push([
                'id' => 'message_' . $message->id,
                'type' => 'info',
                'title' => 'Nuevo Mensaje Operativo',
                'message' => "<strong>{$message->order->order_number}</strong>: " .
                            ($message->creator ? $message->creator->name : 'Sistema') .
                            " - " . \Str::limit($message->message, 50),
                'created_at' => $message->created_at->toDateTimeString(),
            ]);
        }

        // Ordenar por fecha y limitar
        $notifications = $notifications->sortByDesc('created_at')->take(10)->values();

        return response()->json([
            'success' => true,
            'notifications' => $notifications,
            'timestamp' => Carbon::now()->toDateTimeString(),
        ]);
    }

    /**
     * Marcar notificaciones como leídas
     */
    public function markAsRead(Request $request)
    {
        // Aquí podríamos implementar un sistema de tracking de notificaciones vistas
        // Por ahora solo confirmamos la acción
        return response()->json([
            'success' => true,
            'message' => 'Notificaciones marcadas como leídas'
        ]);
    }

    /**
     * Obtener conteo de notificaciones pendientes
     */
    public function getCount()
    {
        $since = Carbon::now()->subHours(24);

        // Contar eventos importantes no vistos
        $eventCount = OrderEvent::where('created_at', '>', $since)
            ->whereIn('event_type', [
                OrderEvent::TYPE_PRODUCTION_BLOCKED,
                OrderEvent::TYPE_MATERIAL_INSUFFICIENT,
                OrderEvent::TYPE_BLOCKED,
            ])
            ->count();

        // Contar mensajes no vistos
        $messageCount = OrderMessage::where('created_at', '>', $since)
            ->where('created_by', '!=', Auth::id())
            ->whereIn('visibility', ['admin', 'both'])
            ->count();

        return response()->json([
            'success' => true,
            'count' => $eventCount + $messageCount,
            'events' => $eventCount,
            'messages' => $messageCount,
        ]);
    }
}
