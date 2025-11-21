<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationController extends Controller
{
    /**
     * Get all notifications for the authenticated user
     */
    public function index(Request $request)
    {
        // Ensure we only get notifications for the authenticated user
        $query = Notification::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc');

        // Paginate for both JSON and Inertia requests
        $perPage = $request->get('per_page', 20);
        $notifications = $query->paginate($perPage)->withQueryString();

        // Check if this is an Inertia request (page navigation)
        // Inertia requests have X-Inertia header
        if ($request->header('X-Inertia')) {
            return Inertia::render('Notifications/Index', [
                'notifications' => $notifications,
            ]);
        }

        // Otherwise, check if it's a pure AJAX/JSON request (for NotificationBell component)
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'notifications' => $notifications,
                'unread_count' => Notification::where('user_id', auth()->id())
                    ->where('read', false)
                    ->count(),
            ]);
        }

        // Default: return Inertia response for regular page loads
        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
        ]);
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead(Notification $notification)
    {
        if ($notification->user_id !== auth()->id()) {
            abort(403);
        }

        $notification->markAsRead();

        return response()->json(['success' => true]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        Notification::where('user_id', auth()->id())
            ->where('read', false)
            ->update([
                'read' => true,
                'read_at' => now(),
            ]);

        return response()->json(['success' => true]);
    }

    /**
     * Get unread count
     */
    public function unreadCount()
    {
        $count = Notification::where('user_id', auth()->id())
            ->where('read', false)
            ->count();

        return response()->json(['count' => $count]);
    }
}
