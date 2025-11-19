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

