<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted, watch } from 'vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import Avatar from '@/components/ui/avatar/Avatar.vue';
import AvatarImage from '@/components/ui/avatar/AvatarImage.vue';
import AvatarFallback from '@/components/ui/avatar/AvatarFallback.vue';
import Label from '@/components/ui/label/Label.vue';
import Swal from 'sweetalert2';
import { type BreadcrumbItem } from '@/types';

interface Product {
    id: number;
    name: string;
    selling_price: number;
    stock: number;
}

interface OrderItem {
    id: number;
    product_id: number;
    quantity: number;
    price: number;
    total: number;
    product: Product;
}

interface Customer {
    id: number;
    name: string;
    company_name?: string;
    email: string;
    phone?: string;
    address?: string;
    purok?: string;
    barangay?: string;
    city_municipality?: string;
    province?: string;
    media?: any[];
}

interface User {
    id: number;
    name: string;
}

interface Invoice {
    id: number;
    reference_number: string;
}

interface Order {
    id: number;
    reference_number: string;
    customer: Customer;
    approved_by?: User;
    invoice?: Invoice;
    total_amount: number;
    subtotal_amount: number;
    vat_amount: number;
    vat_rate: number;
    status: string;
    delivery_type: string;
    notes?: string;
    rejection_reason?: string;
    created_at: string;
    updated_at: string;
    approved_at?: string;
    order_items: OrderItem[];
    comments?: OrderComment[];
}

interface OrderComment {
    id: number;
    comment: string;
    is_staff_comment: boolean;
    created_at: string;
    user: {
        id: number;
        name: string;
    };
}

const props = defineProps<{
    order: Order;
    hasStockIssues?: boolean;
}>();

const page = usePage();
const isCustomer = Array.isArray((page.props as any).auth?.userRoles) && (page.props as any).auth.userRoles.includes('Customer');
const isStaff = Array.isArray((page.props as any).auth?.userRoles) && 
    ((page.props as any).auth.userRoles.includes('Admin') || (page.props as any).auth.userRoles.includes('Staff'));

const showRejectDialog = ref(false);
const rejectionForm = useForm({
    rejection_reason: '',
});

const commentForm = useForm({
    comment: '',
});

// Create a reactive ref for comments that can be updated in real-time
const comments = ref<OrderComment[]>([...(props.order.comments || [])]);

// Create a reactive ref for order that can be updated in real-time
const order = ref<Order>({ ...props.order });

// Watch for prop changes (e.g., when page reloads after comment submission)
watch(() => props.order.comments, (newComments) => {
    if (newComments && Array.isArray(newComments)) {
        // Merge new comments, avoiding duplicates
        newComments.forEach((newComment: OrderComment) => {
            if (!comments.value.find(c => c.id === newComment.id)) {
                comments.value.push(newComment);
            }
        });
        // Sort by created_at to maintain order
        comments.value.sort((a, b) => 
            new Date(a.created_at).getTime() - new Date(b.created_at).getTime()
        );
    }
}, { deep: true });

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Orders',
        href: '/orders',
    },
    {
        title: props.order.reference_number,
        href: `/orders/${props.order.id}`,
    }
];

function getStatusBadgeClass(status: string) {
    switch (status) {
        case 'pending': return 'bg-yellow-100 text-yellow-800';
        case 'approved': return 'bg-green-100 text-green-800';
        case 'rejected': return 'bg-red-100 text-red-800';
        case 'cancelled': return 'bg-gray-100 text-gray-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

function formatCurrency(amount: number) {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2
    }).format(amount);
}

function formatDate(dateString: string) {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function getCustomerProfileImage(customer: Customer) {
    if (!customer.media || customer.media.length === 0) {
        return null;
    }
    
    const profileImage = customer.media.find((media: any) => 
        media.mime_type && media.mime_type.startsWith('image/')
    );
    
    return profileImage ? (profileImage.thumb_url || profileImage.original_url) : null;
}

function getCustomerInitials(customer: Customer) {
    return customer.name
        .split(' ')
        .map(word => word.charAt(0))
        .join('')
        .toUpperCase()
        .slice(0, 2);
}

function formatAddress(customer: Customer): string {
    const parts: string[] = [];
    
    if (customer.address) {
        parts.push(customer.address);
    }
    if (customer.purok) {
        parts.push(`Purok ${customer.purok}`);
    }
    if (customer.barangay) {
        parts.push(`Barangay ${customer.barangay}`);
    }
    if (customer.city_municipality) {
        parts.push(customer.city_municipality);
    }
    if (customer.province) {
        parts.push(customer.province);
    }
    
    return parts.length > 0 ? parts.join(', ') : '';
}

function approveOrder() {
    Swal.fire({
        title: 'Approve this order?',
        text: 'This will check product availability, deduct stock, and create an invoice.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, approve',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('orders.approve', props.order.id), {}, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Order approved',
                        text: 'Invoice has been created successfully.',
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true,
                    });
                },
                onError: (errors: any) => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Approval failed',
                        text: errors.stock || 'Unable to approve order. Please check product availability.',
                    });
                }
            });
        }
    });
}

function rejectOrder() {
    if (!rejectionForm.rejection_reason.trim()) {
        Swal.fire({
            icon: 'warning',
            title: 'Rejection reason required',
            text: 'Please provide a reason for rejecting this order.',
        });
        return;
    }

    Swal.fire({
        title: 'Reject this order?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, reject',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            rejectionForm.post(route('orders.reject', props.order.id), {
                preserveScroll: true,
                onSuccess: () => {
                    showRejectDialog.value = false;
                    rejectionForm.reset();
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Order rejected',
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true,
                    });
                },
            });
        }
    });
}

function cancelOrder() {
    Swal.fire({
        title: 'Cancel this order?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, cancel',
        cancelButtonText: 'No'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('orders.cancel', props.order.id), {}, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Order cancelled',
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true,
                    });
                },
            });
        }
    });
}

// Check if there are stock issues
const hasStockIssues = computed(() => {
    if (props.hasStockIssues !== undefined) {
        return props.hasStockIssues;
    }
    // Fallback check on frontend
    return order.value.order_items.some((item: OrderItem) => item.quantity > item.product.stock);
});

const canApprove = computed(() => {
    return isStaff && order.value.status === 'pending' && !hasStockIssues.value;
});

const canReject = computed(() => {
    return isStaff && order.value.status === 'pending';
});

const canCancel = computed(() => {
    return (isCustomer || isStaff) && order.value.status === 'pending';
});

function addComment() {
    if (!commentForm.comment.trim()) {
        Swal.fire({
            icon: 'warning',
            title: 'Comment required',
            text: 'Please enter a comment.',
        });
        return;
    }

    const currentUser = (page.props as any).auth?.user;
    const userRoles = (page.props as any).auth?.userRoles || [];
    const isStaffUser = userRoles.includes('Admin') || userRoles.includes('Staff');

    const commentText = commentForm.comment; // Store before reset

    commentForm.post(route('orders.comments.store', props.order.id), {
        preserveScroll: true,
        onSuccess: (page) => {
            // Try to get the updated order from the response
            const updatedOrder = page.props.order;
            
            if (updatedOrder?.comments && Array.isArray(updatedOrder.comments)) {
                // Find the new comment (should be the last one or match the text)
                const newComment = updatedOrder.comments.find((c: OrderComment) => 
                    c.comment === commentText && 
                    !comments.value.find(existing => existing.id === c.id)
                );
                
                if (newComment) {
                    comments.value.push(newComment);
                    
                    // Scroll to bottom
                    setTimeout(() => {
                        const commentsContainer = document.querySelector('[data-comments-container]');
                        if (commentsContainer) {
                            commentsContainer.scrollTop = commentsContainer.scrollHeight;
                        }
                    }, 100);
                } else {
                    // If we can't find it, refresh comments from server
                    // The real-time update should handle it, but this ensures it shows
                    if (updatedOrder.comments.length > comments.value.length) {
                        comments.value = [...updatedOrder.comments];
                    }
                }
            }

            commentForm.reset();
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Comment added',
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true,
            });
        },
        onError: (errors) => {
            console.error('Error adding comment:', errors);
        },
    });
}

// Set up real-time comment listening
let echoChannel: any = null;

onMounted(() => {
    if (!import.meta.env.VITE_ABLY_KEY) {
        console.warn('Ably key not found. Real-time updates will not work.');
        return;
    }

    if (!window.Echo) {
        console.warn('Laravel Echo not initialized. Real-time updates will not work.');
        return;
    }

    try {
        // Listen for new comments on this order's channel
        const channelName = `order.${props.order.id}`;
        console.log('📡 Subscribing to channel:', channelName);
        
        // Use regular channel (public channel)
        // The backend still handles authorization for who can receive broadcasts
        echoChannel = window.Echo.channel(channelName);
        
        // Log channel subscription status
        if (echoChannel) {
            console.log('✅ Channel object created:', channelName);
        } else {
            console.error('❌ Failed to create channel:', channelName);
        }

        // Listen for order updates (status changes, approval, etc.)
        echoChannel.listen('.order.updated', (data: { order: any }) => {
            console.log('🔄 Received order.updated event:', data);
            
            if (data.order) {
                // Store old status to check if it changed
                const oldStatus = order.value.status;
                
                // Update order status and related fields
                order.value.status = data.order.status;
                order.value.rejection_reason = data.order.rejection_reason;
                order.value.approved_at = data.order.approved_at;
                order.value.updated_at = data.order.updated_at;
                
                // Update approved_by if provided
                if (data.order.approved_by) {
                    order.value.approved_by = {
                        id: data.order.approved_by.id,
                        name: data.order.approved_by.name,
                    };
                }
                
                // Update invoice if provided
                if (data.order.invoice) {
                    order.value.invoice = {
                        id: data.order.invoice.id,
                        reference_number: data.order.invoice.reference_number,
                    };
                }
                
                // Show notification for status changes
                if (data.order.status !== oldStatus) {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'info',
                        title: `Order ${data.order.status}`,
                        text: `Order ${order.value.reference_number} has been ${data.order.status}`,
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true,
                    });
                }
            }
        });

        // Listen for the custom event name (with dot prefix for custom events)
        echoChannel.listen('.comment.added', (data: { comment: OrderComment }) => {
            console.log('🔔 Received comment.added event:', data);
            
            // Check if comment doesn't already exist (prevent duplicates)
            if (data.comment && !comments.value.find(c => c.id === data.comment.id)) {
                console.log('Adding new comment to list:', data.comment);
                comments.value.push(data.comment);
                
                // Sort by created_at to maintain order
                comments.value.sort((a, b) => 
                    new Date(a.created_at).getTime() - new Date(b.created_at).getTime()
                );
                
                // Scroll to bottom of comments section to show new comment
                setTimeout(() => {
                    const commentsContainer = document.querySelector('[data-comments-container]');
                    if (commentsContainer) {
                        commentsContainer.scrollTop = commentsContainer.scrollHeight;
                    }
                }, 100);
            } else {
                console.log('Comment already exists, skipping:', data.comment?.id);
            }
        });

        // Also listen for the default event name (fallback - no dot prefix)
        echoChannel.listen('OrderCommentAdded', (data: { comment: OrderComment }) => {
            console.log('🔔 Received OrderCommentAdded event:', data);
            
            if (data.comment && !comments.value.find(c => c.id === data.comment.id)) {
                console.log('Adding new comment to list:', data.comment);
                comments.value.push(data.comment);
                
                // Sort by created_at to maintain order
                comments.value.sort((a, b) => 
                    new Date(a.created_at).getTime() - new Date(b.created_at).getTime()
                );
                
                setTimeout(() => {
                    const commentsContainer = document.querySelector('[data-comments-container]');
                    if (commentsContainer) {
                        commentsContainer.scrollTop = commentsContainer.scrollHeight;
                    }
                }, 100);
            }
        });
    } catch (error) {
        console.error('Error setting up Echo channel:', error);
    }
});

onUnmounted(() => {
    // Clean up: leave the channel when component is destroyed
    if (echoChannel && window.Echo) {
        window.Echo.leave(`order.${props.order.id}`);
    }
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Order ${order.reference_number}`" />
        
        <!-- Header Actions -->
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">Order {{ order.reference_number }}</h1>
            <div class="flex gap-2">
                <Link :href="route('orders.index')">
                    <Button variant="ghost">Back to Orders</Button>
                </Link>
                <Button v-if="canCancel" variant="outline" @click="cancelOrder">
                    Cancel Order
                </Button>
                <Button v-if="canReject" variant="outline" @click="showRejectDialog = true">
                    Reject Order
                </Button>
                <Button 
                    v-if="isStaff && order.status === 'pending'" 
                    variant="default" 
                    :disabled="hasStockIssues"
                    @click="approveOrder"
                    :title="hasStockIssues ? 'Cannot approve order due to insufficient stock. Please communicate with customer or adjust quantities.' : ''"
                >
                    Approve Order
                </Button>
            </div>
        </div>

        <!-- Status Badge and Stock Warning -->
        <div class="mb-6 flex items-center gap-4">
            <span :class="['px-3 py-1 rounded-full text-sm font-medium', getStatusBadgeClass(order.status)]">
                {{ order.status.charAt(0).toUpperCase() + order.status.slice(1) }}
            </span>
            <div v-if="isStaff && hasStockIssues && order.status === 'pending'" class="bg-red-50 border border-red-200 rounded-md px-4 py-2">
                <p class="text-sm text-red-800">
                    <strong>⚠️ Warning:</strong> This order has insufficient stock. Please communicate with the customer using the comments section below before approving.
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Order Details -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Customer Information -->
                <Card>
                    <CardHeader>
                        <CardTitle>Customer Information</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-start gap-4">
                            <Avatar class="h-16 w-16">
                                <AvatarImage v-if="getCustomerProfileImage(order.customer)" :src="getCustomerProfileImage(order.customer)" :alt="order.customer.name" />
                                <AvatarFallback v-else>{{ getCustomerInitials(order.customer) }}</AvatarFallback>
                            </Avatar>
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold">{{ order.customer.name }}</h3>
                                <p v-if="order.customer.company_name" class="text-sm text-gray-600">{{ order.customer.company_name }}</p>
                                <p class="text-sm text-gray-600 mt-1">{{ order.customer.email }}</p>
                                <p v-if="order.customer.phone" class="text-sm text-gray-600">{{ order.customer.phone }}</p>
                                <p v-if="formatAddress(order.customer)" class="text-sm text-gray-600 mt-2">{{ formatAddress(order.customer) }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Notes -->
                <Card v-if="order.notes">
                    <CardHeader>
                        <CardTitle>Notes</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-sm whitespace-pre-wrap">{{ order.notes }}</p>
                    </CardContent>
                </Card>

                <!-- Order Items -->
                <Card>
                    <CardHeader>
                        <CardTitle>Order Items</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div v-for="item in order.order_items" :key="item.id" class="border rounded-md p-4">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <h4 class="font-medium">{{ item.product.name }}</h4>
                                        <p class="text-sm text-gray-600">Quantity: {{ item.quantity }}</p>
                                        <p v-if="isStaff" class="text-sm text-gray-600">Stock Available: {{ item.product.stock }}</p>
                                        <p v-if="isStaff && item.quantity > item.product.stock" class="text-sm text-red-600 font-medium mt-1">
                                            ⚠️ Insufficient stock! (Requested: {{ item.quantity }}, Available: {{ item.product.stock }})
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-medium">{{ formatCurrency(item.price) }} each</p>
                                        <p class="text-sm text-gray-600">Total: {{ formatCurrency(item.total) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 pt-4 border-t">
                            <div class="flex justify-end">
                                <div class="text-right space-y-1">
                                    <div class="text-sm text-gray-600">Subtotal: {{ formatCurrency(order.subtotal_amount) }}</div>
                                    <div class="text-sm text-gray-600">VAT ({{ order.vat_rate }}%): {{ formatCurrency(order.vat_amount) }}</div>
                                    <div class="text-lg font-semibold">Total: {{ formatCurrency(order.total_amount) }}</div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Rejection Reason -->
                <Card v-if="order.status === 'rejected' && order.rejection_reason">
                    <CardHeader>
                        <CardTitle class="text-red-600">Rejection Reason</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-sm text-red-600 whitespace-pre-wrap">{{ order.rejection_reason }}</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Order Information -->
                <Card>
                    <CardHeader>
                        <CardTitle>Order Information</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div>
                            <p class="text-sm text-gray-600">Reference Number</p>
                            <p class="font-medium">{{ order.reference_number }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Delivery Type</p>
                            <p class="font-medium capitalize">{{ order.delivery_type }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Created At</p>
                            <p class="font-medium">{{ formatDate(order.created_at) }}</p>
                        </div>
                        <div v-if="order.approved_at">
                            <p class="text-sm text-gray-600">Approved At</p>
                            <p class="font-medium">{{ formatDate(order.approved_at) }}</p>
                        </div>
                        <div v-if="order.approved_by">
                            <p class="text-sm text-gray-600">Approved By</p>
                            <p class="font-medium">{{ order.approved_by.name }}</p>
                        </div>
                        <div v-if="order.invoice">
                            <p class="text-sm text-gray-600">Invoice</p>
                            <Link :href="route('invoices.show', order.invoice.id)" class="font-medium text-blue-600 hover:underline">
                                {{ order.invoice.reference_number }}
                            </Link>
                        </div>
                    </CardContent>
                </Card>

                <!-- Comments Section -->
                <Card>
                    <CardHeader>
                        <CardTitle>Comments</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <!-- Comments List -->
                        <div 
                            v-if="comments.length > 0" 
                            class="space-y-4 mb-6 max-h-[400px] overflow-y-auto"
                            data-comments-container
                        >
                            <div 
                                v-for="comment in comments" 
                                :key="comment.id" 
                                class="border rounded-md p-3"
                                :class="comment.is_staff_comment ? 'bg-blue-50 border-blue-200' : 'bg-gray-50 border-gray-200'"
                            >
                                <div class="flex items-start justify-between mb-2">
                                    <div>
                                        <p class="font-medium text-sm">
                                            {{ comment.user.name }}
                                            <span v-if="comment.is_staff_comment" class="text-xs text-blue-600 ml-2">(Staff)</span>
                                            <span v-else class="text-xs text-gray-600 ml-2">(Customer)</span>
                                        </p>
                                        <p class="text-xs text-gray-500">{{ formatDate(comment.created_at) }}</p>
                                    </div>
                                </div>
                                <p class="text-sm whitespace-pre-wrap mt-2">{{ comment.comment }}</p>
                            </div>
                        </div>
                        <div v-else class="text-sm text-gray-500 mb-6 text-center py-4">
                            No comments yet. Start the conversation below.
                        </div>

                        <!-- Add Comment Form -->
                        <form @submit.prevent="addComment" class="space-y-3">
                            <div>
                                <Label for="comment" class="text-sm">Add a comment</Label>
                                <textarea
                                    id="comment"
                                    v-model="commentForm.comment"
                                    class="w-full rounded-md border border-input bg-transparent px-3 py-2 mt-1 text-sm text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none"
                                    rows="3"
                                    placeholder="Type your message here..."
                                    required
                                />
                            </div>
                            <div class="flex justify-end">
                                <Button type="submit" variant="default" size="sm" :disabled="commentForm.processing">
                                    {{ commentForm.processing ? 'Posting...' : 'Post Comment' }}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Reject Dialog -->
        <div v-if="showRejectDialog" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <Card class="w-full max-w-md mx-4">
                <CardHeader>
                    <CardTitle>Reject Order</CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="rejectOrder" class="space-y-4">
                        <div>
                            <Label for="rejection_reason">Rejection Reason *</Label>
                            <textarea
                                id="rejection_reason"
                                v-model="rejectionForm.rejection_reason"
                                class="w-full rounded-md border border-input bg-transparent px-3 py-2 mt-1 text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none"
                                rows="4"
                                placeholder="Please provide a reason for rejecting this order..."
                                required
                            />
                        </div>
                        <div class="flex gap-2 justify-end">
                            <Button type="button" variant="ghost" @click="showRejectDialog = false; rejectionForm.reset()">
                                Cancel
                            </Button>
                            <Button type="submit" variant="default" :disabled="rejectionForm.processing">
                                Reject Order
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

