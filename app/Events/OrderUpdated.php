<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;

    /**
     * Create a new event instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order->load([
            'customer',
            'approvedBy',
            'invoice',
            'orderItems.product',
        ]);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('order.' . $this->order->id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'order.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'order' => [
                'id' => $this->order->id,
                'reference_number' => $this->order->reference_number,
                'status' => $this->order->status,
                'approved_by' => $this->order->approvedBy ? [
                    'id' => $this->order->approvedBy->id,
                    'name' => $this->order->approvedBy->name,
                ] : null,
                'approved_at' => $this->order->approved_at?->toISOString(),
                'rejection_reason' => $this->order->rejection_reason,
                'invoice' => $this->order->invoice ? [
                    'id' => $this->order->invoice->id,
                    'reference_number' => $this->order->invoice->reference_number,
                ] : null,
                'updated_at' => $this->order->updated_at->toISOString(),
            ],
        ];
    }
}
