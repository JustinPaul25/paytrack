<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import { Button } from '@/components/ui/button';
import { type BreadcrumbItem } from '@/types';
import { CheckCircle2, XCircle, Package, MessageSquare, DollarSign, Bell, Loader2 } from 'lucide-vue-next';

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
        refund_request_id?: number;
        tracking_number?: string;
        invoice_reference?: string;
    };
}

interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}

const page = usePage();
const props = defineProps<{
    notifications: Paginated<Notification>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Notifications', href: '/notifications' },
];

// State for infinite scroll
const allNotifications = ref<Notification[]>([...props.notifications.data]);
const currentPage = ref(props.notifications.current_page);
const lastPage = ref(props.notifications.last_page);
const isLoading = ref(false);
const scrollContainer = ref<HTMLElement | null>(null);

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

const formatDate = (dateString: string) => {
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return dateString;
    }
};

const getNotificationIcon = (type: string) => {
    if (type.includes('order')) {
        return Package;
    } else if (type.includes('comment')) {
        return MessageSquare;
    } else if (type.includes('refund')) {
        return DollarSign;
    }
    return Bell;
};

const markAsRead = async (notification: Notification) => {
    if (notification.read) return;
    
    try {
        const response = await fetch(`/notifications/${notification.id}/read`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });
        
        if (response.ok) {
            // Find and update the notification in the array to ensure reactivity
            const index = allNotifications.value.findIndex(n => n.id === notification.id);
            if (index !== -1) {
                allNotifications.value[index].read = true;
                allNotifications.value[index].read_at = new Date().toISOString();
            } else {
                // Fallback: update the notification object directly
                notification.read = true;
                notification.read_at = new Date().toISOString();
            }
        }
    } catch (error) {
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
            // Update all notifications to read
            allNotifications.value.forEach(n => {
                n.read = true;
                n.read_at = new Date().toISOString();
            });
        }
    } catch (error) {
        console.error('Error marking all notifications as read:', error);
    }
};

const handleNotificationClick = async (notification: Notification) => {
    // Mark as read and wait for it to complete
    await markAsRead(notification);
    
    // Wait a tick to ensure UI updates before navigation
    await nextTick();
    
    // Then navigate if there's an action URL
    if (notification.action_url) {
        router.visit(notification.action_url);
    }
};

const unreadCount = computed(() => {
    return allNotifications.value.filter(n => !n.read).length;
});

const hasMore = computed(() => {
    return currentPage.value < lastPage.value;
});

const loadMore = async () => {
    if (isLoading.value || !hasMore.value) return;
    
    isLoading.value = true;
    try {
        const nextPage = currentPage.value + 1;
        const response = await fetch(`/notifications?page=${nextPage}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        
        if (response.ok) {
            const data = await response.json();
            if (data.notifications && Array.isArray(data.notifications.data)) {
                allNotifications.value.push(...data.notifications.data);
                currentPage.value = data.notifications.current_page;
                lastPage.value = data.notifications.last_page;
            }
        }
    } catch (error) {
        console.error('Error loading more notifications:', error);
    } finally {
        isLoading.value = false;
    }
};

const handleScroll = () => {
    if (isLoading.value || !hasMore.value) return;
    
    // Check if we're using a scrollable container or window scroll
    if (scrollContainer.value) {
        const container = scrollContainer.value;
        const scrollTop = container.scrollTop;
        const scrollHeight = container.scrollHeight;
        const clientHeight = container.clientHeight;
        
        // Load more when user scrolls to within 200px of the bottom
        if (scrollTop + clientHeight >= scrollHeight - 200) {
            loadMore();
        }
    } else {
        // Fallback to window scroll
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const scrollHeight = document.documentElement.scrollHeight;
        const clientHeight = window.innerHeight;
        
        // Load more when user scrolls to within 200px of the bottom
        if (scrollTop + clientHeight >= scrollHeight - 200) {
            loadMore();
        }
    }
};

onMounted(async () => {
    // Wait for DOM to be ready
    await nextTick();
    
    // Set up scroll listener on the scrollable container or window
    if (scrollContainer.value) {
        scrollContainer.value.addEventListener('scroll', handleScroll);
    } else {
        window.addEventListener('scroll', handleScroll);
    }
});

onUnmounted(() => {
    if (scrollContainer.value) {
        scrollContainer.value.removeEventListener('scroll', handleScroll);
    } else {
        window.removeEventListener('scroll', handleScroll);
    }
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Notifications" />
        
        <div class="flex items-center justify-between my-6">
            <div>
                <h1 class="text-2xl font-bold">Notifications</h1>
                <p class="text-sm text-muted-foreground mt-1">
                    {{ props.notifications.total }} total notification{{ props.notifications.total !== 1 ? 's' : '' }}
                </p>
            </div>
            <div class="flex gap-2">
                <Button
                    v-if="unreadCount > 0"
                    variant="outline"
                    @click="markAllAsRead"
                >
                    Mark all as read
                </Button>
            </div>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>All Notifications</CardTitle>
            </CardHeader>
            <CardContent>
                <div v-if="allNotifications.length === 0" class="py-12 text-center">
                    <Bell class="w-12 h-12 text-muted-foreground mx-auto mb-4" />
                    <p class="text-muted-foreground">No notifications yet</p>
                </div>
                
                <div
                    v-else
                    ref="scrollContainer"
                    class="divide-y max-h-[600px] overflow-y-auto"
                    style="scrollbar-width: thin;"
                >
                    <button
                        v-for="notification in allNotifications"
                        :key="notification.id"
                        @click="handleNotificationClick(notification)"
                        class="w-full text-left hover:bg-accent transition-colors p-4"
                        :class="{ 'bg-accent/50': !notification.read }"
                    >
                        <div class="flex items-start gap-4">
                            <div class="mt-1">
                                <component
                                    :is="getNotificationIcon(notification.type)"
                                    class="w-5 h-5 text-muted-foreground"
                                />
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="text-sm font-medium" :class="{ 'font-semibold': !notification.read }">
                                        {{ notification.title }}
                                    </p>
                                    <div class="flex items-center gap-2">
                                        <span v-if="!notification.read" class="h-2 w-2 rounded-full bg-primary flex-shrink-0" />
                                        <span v-if="notification.read" class="text-muted-foreground">
                                            <CheckCircle2 class="w-4 h-4" />
                                        </span>
                                    </div>
                                </div>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    {{ notification.message }}
                                </p>
                                <div class="flex items-center gap-2 mt-2">
                                    <p class="text-xs text-muted-foreground">
                                        {{ formatTime(notification.created_at) }}
                                    </p>
                                    <span class="text-xs text-muted-foreground">•</span>
                                    <p class="text-xs text-muted-foreground">
                                        {{ formatDate(notification.created_at) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </button>

                    <!-- Load More Button / Loading Indicator -->
                    <div v-if="hasMore || isLoading" class="flex justify-center items-center py-6">
                        <Button
                            v-if="hasMore && !isLoading"
                            variant="outline"
                            @click="loadMore"
                        >
                            Load More
                        </Button>
                        <div v-else-if="isLoading" class="flex items-center gap-2 text-muted-foreground">
                            <Loader2 class="w-4 h-4 animate-spin" />
                            <span class="text-sm">Loading more notifications...</span>
                        </div>
                    </div>

                    <!-- End of list indicator -->
                    <div v-if="!hasMore && allNotifications.length > 0" class="py-4 text-center text-sm text-muted-foreground">
                        <p>You've reached the end. Showing {{ allNotifications.length }} of {{ props.notifications.total }} notifications</p>
                    </div>
                </div>
            </CardContent>
        </Card>
    </AppLayout>
</template>

