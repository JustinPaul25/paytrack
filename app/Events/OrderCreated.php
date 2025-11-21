<?php

namespace App\Events;

use App\Events\NotificationCreated;
use App\Models\Order;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;
    public $notifications = [];

    /**
     * Create a new event instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order->load('customer');
        
        // Create notifications for staff users (excluding Admin)
        $allUsers = User::all();
        $staffUsers = $allUsers->filter(function ($user) {
            return $user->hasRole('Staff');
        });

        foreach ($staffUsers as $user) {
            $notification = Notification::create([
                'user_id' => $user->id,
                'type' => 'order.created',
                'notifiable_id' => $order->id,
                'notifiable_type' => Order::class,
                'title' => 'New Order Created',
                'message' => "New order {$order->reference_number} from {$order->customer->name}",
                'action_url' => "/orders/{$order->id}",
                'read' => false,
                'data' => [
                    'order_id' => $order->id,
                    'order_reference' => $order->reference_number,
                    'customer_name' => $order->customer->name,
                ],
            ]);

            // Store notification for this user
            $this->notifications[$user->id] = $notification;
            
            // Broadcast notification.created event for real-time updates
            broadcast(new NotificationCreated($notification));
        }
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [];
        
        // Broadcast to each staff user's private channel (excluding Admin)
        $allUsers = User::all();
        $staffUsers = $allUsers->filter(function ($user) {
            return $user->hasRole('Staff');
        });

        foreach ($staffUsers as $user) {
            $channels[] = new PrivateChannel('user.' . $user->id);
        }

        return $channels;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'notification.created';
    }

    /**
     * Get the data to broadcast.
     * Since each user has their own notification, we'll just signal a refresh.
     */
    public function broadcastWith(): array
    {
        return [
            'type' => 'order.created',
            'order_id' => $this->order->id,
            'refresh_notifications' => true,
        ];
    }
}
