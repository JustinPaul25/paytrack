<?php

namespace App\Events;

use App\Events\NotificationCreated;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderComment;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderCommentAdded implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $comment;
    public $notifications = [];

    /**
     * Create a new event instance.
     */
    public function __construct(OrderComment $comment)
    {
        $this->comment = $comment->load('user', 'order.customer');
        
        $order = $comment->order;
        $commenter = $comment->user;
        
        // Create notifications for relevant users
        // If customer commented, notify staff. If staff commented, notify customer and other staff.
        if ($comment->is_staff_comment) {
            // Staff commented - notify customer and other staff
            $customer = $order->customer;
            $customerUser = User::where('email', $customer->email)->first();
            
            if ($customerUser) {
                $notification = Notification::create([
                    'user_id' => $customerUser->id,
                    'type' => 'order.comment',
                    'notifiable_id' => $order->id,
                    'notifiable_type' => Order::class,
                    'title' => 'New Comment on Order',
                    'message' => "{$commenter->name} commented on order {$order->reference_number}",
                    'action_url' => "/orders/{$order->id}",
                    'read' => false,
                    'data' => [
                        'order_id' => $order->id,
                        'order_reference' => $order->reference_number,
                        'comment_id' => $comment->id,
                        'commenter_name' => $commenter->name,
                    ],
                ]);
                $this->notifications[$customerUser->id] = $notification;
                // Broadcast notification.created event
                broadcast(new NotificationCreated($notification));
            }
            
            // Notify other staff members (excluding the commenter and Admin users)
            $allUsers = User::all();
            $staffUsers = $allUsers->filter(function ($user) use ($commenter) {
                return $user->id !== $commenter->id && $user->hasRole('Staff');
            });

            foreach ($staffUsers as $user) {
                $notification = Notification::create([
                    'user_id' => $user->id,
                    'type' => 'order.comment',
                    'notifiable_id' => $order->id,
                    'notifiable_type' => Order::class,
                    'title' => 'New Comment on Order',
                    'message' => "{$commenter->name} commented on order {$order->reference_number}",
                    'action_url' => "/orders/{$order->id}",
                    'read' => false,
                    'data' => [
                        'order_id' => $order->id,
                        'order_reference' => $order->reference_number,
                        'comment_id' => $comment->id,
                        'commenter_name' => $commenter->name,
                    ],
                ]);
                $this->notifications[$user->id] = $notification;
                // Broadcast notification.created event
                broadcast(new NotificationCreated($notification));
            }
        } else {
            // Customer commented - notify staff (excluding Admin)
            $allUsers = User::all();
            $staffUsers = $allUsers->filter(function ($user) {
                return $user->hasRole('Staff');
            });

            foreach ($staffUsers as $user) {
                $notification = Notification::create([
                    'user_id' => $user->id,
                    'type' => 'order.comment',
                    'notifiable_id' => $order->id,
                    'notifiable_type' => Order::class,
                    'title' => 'New Comment on Order',
                    'message' => "Customer commented on order {$order->reference_number}",
                    'action_url' => "/orders/{$order->id}",
                    'read' => false,
                    'data' => [
                        'order_id' => $order->id,
                        'order_reference' => $order->reference_number,
                        'comment_id' => $comment->id,
                        'commenter_name' => $commenter->name,
                    ],
                ]);
                $this->notifications[$user->id] = $notification;
                // Broadcast notification.created event
                broadcast(new NotificationCreated($notification));
            }
        }
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [
            new Channel('order.' . $this->comment->order_id),
        ];
        
        // Also broadcast to user-specific channels for notifications
        $order = $this->comment->order;
        
        if ($this->comment->is_staff_comment) {
            // Notify customer
            $customer = $order->customer;
            $customerUser = User::where('email', $customer->email)->first();
            if ($customerUser) {
                $channels[] = new PrivateChannel('user.' . $customerUser->id);
            }
            
            // Notify other staff (excluding Admin)
            $allUsers = User::all();
            $staffUsers = $allUsers->filter(function ($user) {
                return $user->id !== $this->comment->user_id && $user->hasRole('Staff');
            });
            
            foreach ($staffUsers as $user) {
                $channels[] = new PrivateChannel('user.' . $user->id);
            }
        } else {
            // Notify all staff (excluding Admin)
            $allUsers = User::all();
            $staffUsers = $allUsers->filter(function ($user) {
                return $user->hasRole('Staff');
            });
            
            foreach ($staffUsers as $user) {
                $channels[] = new PrivateChannel('user.' . $user->id);
            }
        }
        
        return $channels;
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'comment.added';
    }

    /**
     * Get the data to broadcast.
     * Since each user has their own notification, we'll just signal a refresh.
     */
    public function broadcastWith(): array
    {
        return [
            'type' => 'order.comment',
            'order_id' => $this->comment->order_id,
            'refresh_notifications' => true,
        ];
    }
    
    /**
     * Broadcast notification.created event to user channels as well
     */
    public function broadcastOnForNotifications(): array
    {
        $channels = [];
        $order = $this->comment->order;
        
        if ($this->comment->is_staff_comment) {
            // Notify customer
            $customer = $order->customer;
            $customerUser = User::where('email', $customer->email)->first();
            if ($customerUser) {
                $channels[] = new PrivateChannel('user.' . $customerUser->id);
            }
            
            // Notify other staff (excluding Admin)
            $allUsers = User::all();
            $staffUsers = $allUsers->filter(function ($user) {
                return $user->id !== $this->comment->user_id && $user->hasRole('Staff');
            });
            
            foreach ($staffUsers as $user) {
                $channels[] = new PrivateChannel('user.' . $user->id);
            }
        } else {
            // Notify all staff (excluding Admin)
            $allUsers = User::all();
            $staffUsers = $allUsers->filter(function ($user) {
                return $user->hasRole('Staff');
            });
            
            foreach ($staffUsers as $user) {
                $channels[] = new PrivateChannel('user.' . $user->id);
            }
        }
        
        return $channels;
    }
}
