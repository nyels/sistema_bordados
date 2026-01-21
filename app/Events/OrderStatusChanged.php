<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Order $order;
    public string $previousStatus;
    public string $newStatus;

    public function __construct(Order $order, string $previousStatus, string $newStatus)
    {
        $this->order = $order;
        $this->previousStatus = $previousStatus;
        $this->newStatus = $newStatus;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('orders.admin'),
            new PrivateChannel('orders.production'),
            new PrivateChannel('order.' . $this->order->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'status.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'previous_status' => $this->previousStatus,
            'new_status' => $this->newStatus,
            'status_label' => $this->order->status_label ?? $this->newStatus,
            'status_color' => $this->order->status_color ?? 'secondary',
            'updated_at' => now()->format('d/m/Y H:i'),
        ];
    }
}
