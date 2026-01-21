<?php

namespace App\Events;

use App\Models\OrderMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderMessageCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public OrderMessage $message;

    public function __construct(OrderMessage $message)
    {
        $this->message = $message->load('creator');
    }

    /**
     * Canales de broadcast
     */
    public function broadcastOn(): array
    {
        $channels = [];

        // Canal general para admins
        if (in_array($this->message->visibility, ['admin', 'both'])) {
            $channels[] = new PrivateChannel('orders.admin');
        }

        // Canal de producción
        if (in_array($this->message->visibility, ['production', 'both'])) {
            $channels[] = new PrivateChannel('orders.production');
        }

        // Canal específico del pedido
        $channels[] = new PrivateChannel('order.' . $this->message->order_id);

        return $channels;
    }

    /**
     * Nombre del evento para el frontend
     */
    public function broadcastAs(): string
    {
        return 'message.created';
    }

    /**
     * Datos a enviar
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'order_id' => $this->message->order_id,
            'order_number' => $this->message->order->order_number ?? 'N/A',
            'message' => $this->message->message,
            'visibility' => $this->message->visibility,
            'visibility_label' => $this->message->visibility_label,
            'creator' => $this->message->creator?->name ?? 'Sistema',
            'created_at' => $this->message->created_at->format('d/m/Y H:i'),
            'time_ago' => $this->message->created_at->diffForHumans(),
        ];
    }
}
