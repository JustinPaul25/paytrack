<?php

namespace App\Events;

use App\Events\NotificationCreated;
use App\Models\Customer;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CustomerRegistered implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $customer;
    public $notifications = [];

    /**
     * Create a new event instance.
     */
    public function __construct(Customer $customer)
    {
        $this->customer = $customer;
        
        // Create notifications for Admin users only
        $allUsers = User::all();
        $adminUsers = $allUsers->filter(function ($user) {
            return $user->hasRole('Admin');
        });

        foreach ($adminUsers as $user) {
            $notification = Notification::create([
                'user_id' => $user->id,
                'type' => 'customer.registered',
                'notifiable_id' => $customer->id,
                'notifiable_type' => Customer::class,
                'title' => 'New Customer Registration',
                'message' => "New customer {$customer->name} ({$customer->email}) needs approval",
                'action_url' => "/customers/{$customer->id}?from=users",
                'read' => false,
                'data' => [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'customer_email' => $customer->email,
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
        
        // Broadcast to each admin user's private channel
        $allUsers = User::all();
        $adminUsers = $allUsers->filter(function ($user) {
            return $user->hasRole('Admin');
        });

        foreach ($adminUsers as $user) {
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
     */
    public function broadcastWith(): array
    {
        return [
            'type' => 'customer.registered',
            'customer_id' => $this->customer->id,
            'refresh_notifications' => true,
        ];
    }
}

