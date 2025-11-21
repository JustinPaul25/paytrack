<?php

use App\Models\Order;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('order.{orderId}', function ($user, $orderId) {
    $order = Order::find($orderId);
    
    if (!$order) {
        return false;
    }

    // Staff and Admin can access all orders
    if ($user->hasRole('Admin') || $user->hasRole('Staff')) {
        return ['id' => $user->id, 'name' => $user->name];
    }

    // Customers can only access their own orders
    if ($user->hasRole('Customer')) {
        $customer = \App\Models\Customer::where('email', $user->email)->first();
        if ($customer && $order->customer_id === $customer->id) {
            return ['id' => $user->id, 'name' => $user->name];
        }
    }

    return false;
});

Broadcast::channel('user.{userId}', function ($user, $userId) {
    // Log for debugging
    \Log::info('Broadcast channel authorization', [
        'user_id' => $user?->id,
        'requested_user_id' => $userId,
        'channel' => 'user.' . $userId,
    ]);
    
    if (!$user) {
        \Log::warning('Broadcast channel authorization failed: No user');
        return false;
    }
    
    // Return user data if authorized, false otherwise
    if ((int) $user->id === (int) $userId) {
        \Log::info('Broadcast channel authorized', [
            'user_id' => $user->id,
            'channel' => 'user.' . $userId,
        ]);
        return [
            'id' => $user->id,
            'name' => $user->name,
        ];
    }
    
    \Log::warning('Broadcast channel authorization failed: User ID mismatch', [
        'user_id' => $user->id,
        'requested_user_id' => $userId,
    ]);
    return false;
});

