<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\OrderMessage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class NotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Obtener mensajes no leídos para el usuario actual (NUEVA API)
     */
    public function unreadMessages(): JsonResponse
    {
        $userId = Auth::id();

        $messages = OrderMessage::with(['order', 'creator', 'parent.creator'])
            ->visibleToUser(auth()->user())
            ->notCreatedBy($userId)
            ->unreadBy($userId)
            ->latest()
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'count' => $messages->count(),
            'total_unread' => OrderMessage::unreadCountFor($userId),
            'messages' => $messages->map(fn($m) => [
                'id' => $m->id,
                'order_id' => $m->order_id,
                'order_number' => $m->order?->order_number ?? 'N/A',
                'message' => $m->message,
                'visibility' => $m->visibility,
                'visibility_label' => $m->visibility_label,
                'creator' => $m->creator?->name ?? 'Sistema',
                'created_at' => $m->created_at->format('d/m/Y H:i'),
                'time_ago' => $m->created_at->diffForHumans(),
                // Info del mensaje padre (si es respuesta)
                'is_reply' => $m->parent_message_id !== null,
                'parent_message_id' => $m->parent_message_id,
                'parent_preview' => $m->parent
                    ? mb_substr($m->parent->message, 0, 40) . (mb_strlen($m->parent->message) > 40 ? '...' : '')
                    : null,
                'parent_creator' => $m->parent?->creator?->name ?? null,
            ]),
        ]);
    }

    /**
     * Obtener conteo de mensajes no leídos (NUEVA API)
     */
    public function unreadCount(): JsonResponse
    {
        $userId = Auth::id();

        return response()->json([
            'success' => true,
            'count' => OrderMessage::unreadCountFor($userId),
        ]);
    }

    /**
     * Marcar un mensaje específico como leído (NUEVA API)
     */
    public function markMessageAsRead(OrderMessage $message): JsonResponse
    {
        $userId = Auth::id();

        $message->markAsReadBy($userId);

        return response()->json([
            'success' => true,
            'message' => 'Mensaje marcado como leído.',
            'remaining_unread' => OrderMessage::unreadCountFor($userId),
        ]);
    }

    /**
     * Marcar todos los mensajes como leídos (NUEVA API)
     */
    public function markAllMessagesAsRead(): JsonResponse
    {
        $userId = Auth::id();

        $unreadMessages = OrderMessage::visibleToUser(auth()->user())
            ->notCreatedBy($userId)
            ->unreadBy($userId)
            ->get();

        foreach ($unreadMessages as $message) {
            $message->markAsReadBy($userId);
        }

        return response()->json([
            'success' => true,
            'message' => 'Todos los mensajes marcados como leídos.',
            'marked_count' => $unreadMessages->count(),
        ]);
    }

    /**
     * Obtener mensajes recientes con estado de lectura (NUEVA API)
     */
    public function recentMessages(): JsonResponse
    {
        $userId = Auth::id();

        $messages = OrderMessage::getRecentFor($userId, 50);

        return response()->json([
            'success' => true,
            'messages' => $messages->map(function ($m) use ($userId) {
                return [
                    'id' => $m->id,
                    'order_id' => $m->order_id,
                    'order_number' => $m->order?->order_number ?? 'N/A',
                    'message' => $m->message,
                    'visibility' => $m->visibility,
                    'visibility_label' => $m->visibility_label,
                    'creator' => $m->creator?->name ?? 'Sistema',
                    'created_at' => $m->created_at->format('d/m/Y H:i'),
                    'time_ago' => $m->created_at->diffForHumans(),
                    'is_read' => $m->isReadBy($userId),
                ];
            }),
        ]);
    }

    // ========================================
    // MÉTODOS LEGACY (mantener compatibilidad)
    // ========================================

    /**
     * Obtener notificaciones recientes para polling AJAX (LEGACY)
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
            ->where('created_by', '!=', Auth::id())
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
     * Marcar notificaciones como leídas (LEGACY)
     */
    public function markAsRead(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Notificaciones marcadas como leídas'
        ]);
    }

    /**
     * Obtener conteo de notificaciones pendientes (LEGACY)
     */
    public function getCount()
    {
        $userId = Auth::id();

        // Usar el nuevo sistema de lectura persistida
        $messageCount = OrderMessage::unreadCountFor($userId);

        // Eventos importantes de las últimas 24 horas (legacy)
        $since = Carbon::now()->subHours(24);
        $eventCount = OrderEvent::where('created_at', '>', $since)
            ->whereIn('event_type', [
                OrderEvent::TYPE_PRODUCTION_BLOCKED,
                OrderEvent::TYPE_MATERIAL_INSUFFICIENT,
                OrderEvent::TYPE_BLOCKED,
            ])
            ->count();

        return response()->json([
            'success' => true,
            'count' => $eventCount + $messageCount,
            'events' => $eventCount,
            'messages' => $messageCount,
        ]);
    }
}
