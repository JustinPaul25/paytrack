<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Bell } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
// ScrollArea and Badge not available, using divs instead
// Using native date formatting instead of date-fns

interface Notification {
    id: number;
    type: string;
    title: string;
    message: string;
    action_url: string | null;
    read: boolean;
    read_at: string | null;
    created_at: string;
    data?: {
        order_id?: number;
        order_reference?: string;
        comment_id?: number;
        commenter_name?: string;
        customer_name?: string;
    };
}

const page = usePage();
const notifications = ref<Notification[]>([]);
const unreadCount = ref(0);
const isLoading = ref(false);
const isOpen = ref(false);

const fetchNotifications = async () => {
    if (isLoading.value) return;
    
    isLoading.value = true;
    try {
        const response = await fetch('/notifications', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        
        if (response.ok) {
            const data = await response.json();
            const previousUnreadCount = unreadCount.value;
            const previousNotificationIds = new Set(
                Array.isArray(notifications.value) ? notifications.value.map(n => n.id) : []
            );
            
            // Handle paginated response - extract data array from paginated object
            let notificationsArray = [];
            if (data.notifications) {
                if (Array.isArray(data.notifications)) {
                    notificationsArray = data.notifications;
                } else if (data.notifications.data && Array.isArray(data.notifications.data)) {
                    notificationsArray = data.notifications.data;
                }
            }
            
            // Filter out any null or invalid notifications
            notifications.value = notificationsArray.filter(n => n && n.id);
            unreadCount.value = data.unread_count || 0;
            
            // Check if there are new unread notifications (not from real-time events)
            const newNotifications = notifications.value.filter(
                n => !previousNotificationIds.has(n.id) && !n.read
            );
            
            // Play sound if unread count increased and we have new notifications
            if (unreadCount.value > previousUnreadCount && newNotifications.length > 0) {
                playNotificationSound();
            }
        }
    } catch (error) {
        console.error('Error fetching notifications:', error);
    } finally {
        isLoading.value = false;
    }
};

const markAsRead = async (notification: Notification) => {
    if (notification.read) return;
    
    // Optimistically update the UI first for immediate feedback
    const index = notifications.value.findIndex(n => n.id === notification.id);
    const wasUnread = index !== -1 && !notifications.value[index].read;
    
    if (wasUnread) {
        notifications.value[index].read = true;
        notifications.value[index].read_at = new Date().toISOString();
        // Decrement unread count immediately
        unreadCount.value = Math.max(0, unreadCount.value - 1);
    }
    
    try {
        const response = await fetch(`/notifications/${notification.id}/read`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });
        
        if (!response.ok) {
            // If server call failed, revert the optimistic update
            if (wasUnread && index !== -1) {
                notifications.value[index].read = false;
                notifications.value[index].read_at = null;
                unreadCount.value++;
            }
            console.error('Failed to mark notification as read:', response.statusText);
        }
        // If successful, the optimistic update remains - no need to do anything
    } catch (error) {
        // If error occurred, revert the optimistic update
        if (wasUnread && index !== -1) {
            notifications.value[index].read = false;
            notifications.value[index].read_at = null;
            unreadCount.value++;
        }
        console.error('Error marking notification as read:', error);
    }
};

const markAllAsRead = async () => {
    try {
        const response = await fetch('/notifications/read-all', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });
        
        if (response.ok) {
            notifications.value.forEach(n => {
                n.read = true;
                n.read_at = new Date().toISOString();
            });
            unreadCount.value = 0;
        }
    } catch (error) {
        console.error('Error marking all notifications as read:', error);
    }
};

const handleNotificationClick = async (event: Event, notification: Notification) => {
    event.preventDefault();
    event.stopPropagation();
    
    // Mark as read first
    await markAsRead(notification);
    
    // Then navigate if there's an action URL
    if (notification.action_url) {
        // Close dropdown before navigation
        isOpen.value = false;
        // Small delay to ensure dropdown closes smoothly
        await new Promise(resolve => setTimeout(resolve, 100));
        router.visit(notification.action_url);
    } else {
        // If no action URL, just mark as read (already done above)
        // You could add a visual feedback here if needed
    }
};


const formatTime = (dateString: string) => {
    try {
        const date = new Date(dateString);
        const now = new Date();
        const diffInSeconds = Math.floor((now.getTime() - date.getTime()) / 1000);
        
        if (diffInSeconds < 60) {
            return 'just now';
        } else if (diffInSeconds < 3600) {
            const minutes = Math.floor(diffInSeconds / 60);
            return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
        } else if (diffInSeconds < 86400) {
            const hours = Math.floor(diffInSeconds / 3600);
            return `${hours} hour${hours > 1 ? 's' : ''} ago`;
        } else {
            const days = Math.floor(diffInSeconds / 86400);
            return `${days} day${days > 1 ? 's' : ''} ago`;
        }
    } catch {
        return '';
    }
};

const getNotificationIcon = (type: string) => {
    if (type === 'order.created') {
        return '📦';
    } else if (type === 'order.comment') {
        return '💬';
    } else if (type === 'refund.request.created') {
        return '💰';
    }
    return '🔔';
};

// Play notification sound
const playNotificationSound = () => {
    try {
        const audio = new Audio('/audio/notification.mp3');
        audio.volume = 0.5; // Set volume to 50% to avoid being too loud
        audio.play().catch((error) => {
            // Some browsers may block autoplay, log but don't throw
            console.warn('Could not play notification sound:', error);
        });
    } catch (error) {
        console.warn('Error playing notification sound:', error);
    }
};

// Set up real-time listeners
let echoChannel: any = null;

let intervalId: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
    // Only fetch if user is authenticated
    const userId = (page.props.auth as any)?.user?.id;
    if (!userId) {
        return;
    }
    
    fetchNotifications();
    
    // Set up real-time listener
    try {
        if (import.meta.env.VITE_ABLY_KEY && (window as any).Echo) {
            // Echo.private() automatically adds "private-" prefix, so use "user.{id}" not "private-user.{id}"
            const channelName = `user.${userId}`;
            console.log('🔔 Setting up notification listener on channel:', channelName);
            
            echoChannel = (window as any).Echo.private(channelName);
            
            // Listen for notification.created events
            echoChannel.listen('.notification.created', (data: any) => {
                console.log('📬 Received notification.created event:', data);
                
                // If notification data is included, add it directly
                if (data.notification && data.notification.id) {
                    console.log('➕ Adding new notification:', data.notification);
                    // Ensure notifications is an array before unshifting
                    if (!Array.isArray(notifications.value)) {
                        notifications.value = [];
                    }
                    notifications.value.unshift(data.notification);
                    if (!data.notification.read) {
                        unreadCount.value++;
                        // Play notification sound for unread notifications
                        playNotificationSound();
                    }
                }
                
                // Also refresh to ensure we have the latest
                if (data.refresh_notifications) {
                    console.log('🔄 Refreshing notifications...');
                    fetchNotifications();
                }
            });
            
            // Also listen for comment.added events which also create notifications
            echoChannel.listen('.comment.added', (data: any) => {
                console.log('💬 Received comment.added event:', data);
                if (data.refresh_notifications) {
                    console.log('🔄 Refreshing notifications from comment...');
                    const unreadBeforeRefresh = unreadCount.value;
                    fetchNotifications().then(() => {
                        // If unread count increased after refresh, play sound
                        if (unreadCount.value > unreadBeforeRefresh) {
                            playNotificationSound();
                        }
                    });
                }
            });
            
            console.log('✅ Notification listener set up successfully');
        } else {
            console.warn('⚠️ Echo not available or ABLY_KEY not set');
        }
    } catch (error) {
        console.error('❌ Failed to set up real-time notifications:', error);
    }
    
    // Refresh notifications every 30 seconds
    intervalId = setInterval(fetchNotifications, 30000);
});

onUnmounted(() => {
    if (intervalId) {
        clearInterval(intervalId);
    }
    if (echoChannel && (window as any).Echo) {
        const userId = (page.props.auth as any)?.user?.id;
        if (userId) {
            // Echo.private() automatically adds "private-" prefix
            (window as any).Echo.leave(`private-user.${userId}`);
        }
    }
});
</script>

<template>
    <div class="fixed top-4 right-4 z-50">
        <DropdownMenu v-model:open="isOpen">
            <DropdownMenuTrigger as-child>
                <Button 
                    variant="default" 
                    size="icon" 
                    class="group relative h-11 w-11 cursor-pointer rounded-full shadow-2xl hover:shadow-2xl transition-all bg-primary hover:bg-primary/90 text-primary-foreground border-2 border-primary/20 hover:border-primary/40 ring-2 ring-primary/10 hover:ring-primary/20"
                >
                    <span class="sr-only">Notifications</span>
                    <Bell class="size-5 opacity-100 group-hover:scale-110 transition-transform" />
                    <span 
                        v-if="unreadCount > 0"
                        class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white border-2 border-white dark:border-gray-900 shadow-lg animate-pulse"
                    >
                        {{ unreadCount > 99 ? '99+' : unreadCount }}
                    </span>
                </Button>
            </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-80 p-0 mt-2">
            <div class="flex items-center justify-between border-b px-4 py-3">
                <h3 class="font-semibold">Notifications</h3>
                <Button
                    v-if="unreadCount > 0"
                    variant="ghost"
                    size="sm"
                    @click="markAllAsRead"
                    class="h-7 text-xs"
                >
                    Mark all as read
                </Button>
            </div>
            
            <div class="max-h-[400px] overflow-y-auto">
                <div v-if="isLoading && notifications.length === 0" class="p-4 text-center text-sm text-muted-foreground">
                    Loading notifications...
                </div>
                <div v-else-if="notifications.length === 0" class="p-4 text-center text-sm text-muted-foreground">
                    No notifications
                </div>
                <div v-else class="divide-y">
                    <template v-for="notification in notifications" :key="notification?.id">
                        <button
                            v-if="notification && notification.id"
                            @click="(event) => handleNotificationClick(event, notification)"
                            class="w-full text-left hover:bg-accent transition-colors"
                            :class="{ 'bg-accent/50': !notification.read }"
                        >
                        <div class="flex items-start gap-3 p-4">
                            <div class="text-2xl">{{ getNotificationIcon(notification.type) }}</div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="text-sm font-medium" :class="{ 'font-semibold': !notification.read }">
                                        {{ notification.title }}
                                    </p>
                                    <span v-if="!notification.read" class="h-2 w-2 rounded-full bg-primary" />
                                </div>
                                <p class="mt-1 text-sm text-muted-foreground line-clamp-2">
                                    {{ notification.message }}
                                </p>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{ formatTime(notification.created_at) }}
                                </p>
                            </div>
                        </div>
                        </button>
                    </template>
                </div>
            </div>
            
            <div v-if="notifications.length > 0" class="border-t p-2">
                <Link
                    :href="route('notifications.index')"
                    class="block w-full text-center text-sm text-muted-foreground hover:text-foreground"
                    @click="isOpen = false"
                >
                    View all notifications
                </Link>
            </div>
        </DropdownMenuContent>
        </DropdownMenu>
    </div>
</template>

