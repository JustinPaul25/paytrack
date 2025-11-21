<?php

namespace App\Events;

use App\Events\NotificationCreated;
use App\Models\RefundRequest;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RefundRequestCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $refundRequest;
    public $notifications = [];

    /**
     * Create a new event instance.
     */
    public function __construct(RefundRequest $refundRequest)
    {
        $this->refundRequest = $refundRequest->load(['invoice', 'product']);
        
        // Create notifications for Staff users (excluding Admin)
        $allUsers = User::all();
        $staffUsers = $allUsers->filter(function ($user) {
            return $user->hasRole('Staff');
        });

        foreach ($staffUsers as $user) {
            $notification = Notification::create([
                'user_id' => $user->id,
                'type' => 'refund.request.created',
                'notifiable_id' => $refundRequest->id,
                'notifiable_type' => RefundRequest::class,
                'title' => 'New Refund Request',
                'message' => "New refund request {$refundRequest->tracking_number} for invoice {$refundRequest->invoice_reference}",
                'action_url' => "/refunds?status=pending",
                'read' => false,
                'data' => [
                    'refund_request_id' => $refundRequest->id,
                    'tracking_number' => $refundRequest->tracking_number,
                    'invoice_reference' => $refundRequest->invoice_reference,
                    'customer_name' => $refundRequest->customer_name,
                    'product_id' => $refundRequest->product_id,
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
            'type' => 'refund.request.created',
            'refund_request_id' => $this->refundRequest->id,
            'refresh_notifications' => true,
        ];
    }
}


