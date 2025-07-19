<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import Avatar from '@/components/ui/avatar/Avatar.vue';
import AvatarImage from '@/components/ui/avatar/AvatarImage.vue';
import AvatarFallback from '@/components/ui/avatar/AvatarFallback.vue';
import { type BreadcrumbItem } from '@/types';
import Swal from 'sweetalert2';

interface Product {
    id: number;
    name: string;
    selling_price: number;
}

interface Customer {
    id: number;
    name: string;
    company_name?: string;
    email: string;
    phone?: string;
    address?: string;
}

interface User {
    id: number;
    name: string;
}

interface Invoice {
    id: number;
    customer: Customer;
    total_amount: number;
    status: string;
    payment_method: string;
    payment_reference?: string;
    created_at: string;
}

interface Refund {
    id: number;
    refund_number: string;
    invoice: Invoice;
    product: Product;
    user: User;
    quantity_refunded: number;
    refund_amount: number;
    status: string;
    refund_type: string;
    refund_method: string;
    reason?: string;
    notes?: string;
    reference_number?: string;
    created_at: string;
    updated_at: string;
}

const props = defineProps<{
    refund: Refund;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Refunds',
        href: '/refunds',
    },
    {
        title: `Refund ${props.refund.refund_number}`,
        href: `/refunds/${props.refund.id}`,
    }
];

function getStatusBadgeClass(status: string) {
    switch (status) {
        case 'pending': return 'bg-yellow-100 text-yellow-800';
        case 'approved': return 'bg-blue-100 text-blue-800';
        case 'processed': return 'bg-purple-100 text-purple-800';
        case 'completed': return 'bg-green-100 text-green-800';
        case 'cancelled': return 'bg-red-100 text-red-800';
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

function getCustomerInitials(customer: Customer) {
    return customer.name
        .split(' ')
        .map(word => word.charAt(0))
        .join('')
        .toUpperCase()
        .slice(0, 2);
}

async function approveRefund() {
    const result = await Swal.fire({
        title: 'Approve Refund?',
        text: 'Are you sure you want to approve this refund?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#8f5be8',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, approve',
    });
    if (result.isConfirmed) {
        router.post(`/refunds/${props.refund.id}/approve`, {}, {
            onSuccess: () => {
                Swal.fire('Refund approved', 'Refund has been approved successfully.', 'success');
            },
        });
    }
}

async function processRefund() {
    const result = await Swal.fire({
        title: 'Process Refund?',
        text: 'Are you sure you want to process this refund?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#8f5be8',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, process',
    });
    if (result.isConfirmed) {
        router.post(`/refunds/${props.refund.id}/process`, {}, {
            onSuccess: () => {
                Swal.fire('Refund processed', 'Refund has been processed successfully.', 'success');
            },
        });
    }
}

async function completeRefund() {
    const result = await Swal.fire({
        title: 'Complete Refund?',
        text: 'Are you sure you want to complete this refund? This will update product stock.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#8f5be8',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, complete',
    });
    if (result.isConfirmed) {
        router.post(`/refunds/${props.refund.id}/complete`, {}, {
            onSuccess: () => {
                Swal.fire('Refund completed', 'Refund has been completed successfully.', 'success');
            },
        });
    }
}

async function cancelRefund() {
    const result = await Swal.fire({
        title: 'Cancel Refund?',
        text: 'Are you sure you want to cancel this refund?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, cancel',
    });
    if (result.isConfirmed) {
        router.post(`/refunds/${props.refund.id}/cancel`, {}, {
            onSuccess: () => {
                Swal.fire('Refund cancelled', 'Refund has been cancelled successfully.', 'success');
            },
        });
    }
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Refund ${props.refund.refund_number}`" />
        
        <div class="flex items-center justify-between my-6">
            <h1 class="text-2xl font-bold">Refund {{ props.refund.refund_number }}</h1>
            <div class="flex gap-2">
                <Link v-if="props.refund.status === 'pending'" :href="route('refunds.edit', props.refund.id)">
                    <Button variant="default">Edit Refund</Button>
                </Link>
                <Link :href="route('refunds.index')">
                    <Button variant="ghost">Back to Refunds</Button>
                </Link>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Refund Details -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Refund Information -->
                <Card>
                    <CardHeader>
                        <CardTitle>Refund Information</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm font-medium text-gray-500">Refund Number</label>
                                <p class="text-lg font-semibold">{{ props.refund.refund_number }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Date Created</label>
                                <p>{{ formatDate(props.refund.created_at) }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Status</label>
                                <span :class="['px-2 py-1 rounded-full text-xs font-medium', getStatusBadgeClass(props.refund.status)]">
                                    {{ props.refund.status }}
                                </span>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Refund Type</label>
                                <p>{{ props.refund.refund_type.replace('_', ' ') }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Refund Method</label>
                                <p>{{ props.refund.refund_method.replace('_', ' ') }}</p>
                            </div>
                            <div v-if="props.refund.reference_number">
                                <label class="text-sm font-medium text-gray-500">Reference Number</label>
                                <p>{{ props.refund.reference_number }}</p>
                            </div>
                        </div>
                        <div v-if="props.refund.reason" class="mt-4">
                            <label class="text-sm font-medium text-gray-500">Reason</label>
                            <p class="mt-1">{{ props.refund.reason }}</p>
                        </div>
                        <div v-if="props.refund.notes" class="mt-4">
                            <label class="text-sm font-medium text-gray-500">Notes</label>
                            <p class="mt-1">{{ props.refund.notes }}</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Refund Details -->
                <Card>
                    <CardHeader>
                        <CardTitle>Refund Details</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-border">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-2 text-left">Product</th>
                                        <th class="px-4 py-2 text-left">Quantity Refunded</th>
                                        <th class="px-4 py-2 text-left">Refund Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="px-4 py-2">
                                            <div>
                                                <div class="font-medium">{{ props.refund.product.name }}</div>
                                                <div class="text-sm text-gray-500">Original Price: {{ formatCurrency(props.refund.product.selling_price) }}</div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-2">{{ props.refund.quantity_refunded }}</td>
                                        <td class="px-4 py-2 font-medium">{{ formatCurrency(props.refund.refund_amount / 100) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Customer Information -->
                <Card>
                    <CardHeader>
                        <CardTitle>Customer Information</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center space-x-3 mb-4">
                            <Avatar>
                                <AvatarFallback>{{ getCustomerInitials(props.refund.invoice.customer) }}</AvatarFallback>
                            </Avatar>
                            <div>
                                <h3 class="font-medium">{{ props.refund.invoice.customer.name }}</h3>
                                <p v-if="props.refund.invoice.customer.company_name" class="text-sm text-gray-500">
                                    {{ props.refund.invoice.customer.company_name }}
                                </p>
                            </div>
                        </div>
                        <div class="space-y-2 text-sm">
                            <div v-if="props.refund.invoice.customer.email">
                                <span class="text-gray-500">Email:</span>
                                <p>{{ props.refund.invoice.customer.email }}</p>
                            </div>
                            <div v-if="props.refund.invoice.customer.phone">
                                <span class="text-gray-500">Phone:</span>
                                <p>{{ props.refund.invoice.customer.phone }}</p>
                            </div>
                            <div v-if="props.refund.invoice.customer.address">
                                <span class="text-gray-500">Address:</span>
                                <p>{{ props.refund.invoice.customer.address }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Invoice Information -->
                <Card>
                    <CardHeader>
                        <CardTitle>Original Invoice</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div>
                                <label class="text-sm font-medium text-gray-500">Invoice Number</label>
                                <p class="font-medium">#{{ props.refund.invoice.id }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Total Amount</label>
                                <p class="font-medium">{{ formatCurrency(props.refund.invoice.total_amount / 100) }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Payment Method</label>
                                <p>{{ props.refund.invoice.payment_method.replace('_', ' ') }}</p>
                            </div>
                            <div v-if="props.refund.invoice.payment_reference">
                                <label class="text-sm font-medium text-gray-500">Payment Reference</label>
                                <p>{{ props.refund.invoice.payment_reference }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Date</label>
                                <p>{{ formatDate(props.refund.invoice.created_at) }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Status Actions -->
                <Card>
                    <CardHeader>
                        <CardTitle>Status Actions</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-2">
                            <Button 
                                v-if="props.refund.status === 'pending'" 
                                @click="approveRefund"
                                variant="default"
                                class="w-full"
                            >
                                Approve Refund
                            </Button>
                            <Button 
                                v-if="props.refund.status === 'approved'" 
                                @click="processRefund"
                                variant="default"
                                class="w-full"
                            >
                                Process Refund
                            </Button>
                            <Button 
                                v-if="props.refund.status === 'processed'" 
                                @click="completeRefund"
                                variant="default"
                                class="w-full"
                            >
                                Complete Refund
                            </Button>
                            <Button 
                                v-if="['pending', 'approved'].includes(props.refund.status)" 
                                @click="cancelRefund"
                                variant="destructive"
                                class="w-full"
                            >
                                Cancel Refund
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <!-- Created By -->
                <Card>
                    <CardHeader>
                        <CardTitle>Created By</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center space-x-3">
                            <Avatar>
                                <AvatarFallback>{{ props.refund.user.name.split(' ').map(n => n[0]).join('').toUpperCase() }}</AvatarFallback>
                            </Avatar>
                            <div>
                                <h3 class="font-medium">{{ props.refund.user.name }}</h3>
                                <p class="text-sm text-gray-500">{{ formatDate(props.refund.created_at) }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template> 