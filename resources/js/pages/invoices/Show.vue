<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
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

interface Product {
    id: number;
    name: string;
    selling_price: number;
}

interface InvoiceItem {
    id: number;
    product_id: number;
    quantity: number;
    price: number;
    total: number;
    product: Product;
}

interface Media {
    id: number;
    file_name: string;
    mime_type: string;
    size: number;
    original_url: string;
    thumb_url?: string;
}

interface Customer {
    id: number;
    name: string;
    company_name?: string;
    email: string;
    phone?: string;
    address?: string;
    media?: Media[];
}

interface User {
    id: number;
    name: string;
}

interface Invoice {
    id: number;
    reference_number: string;
    customer: Customer;
    user: User;
    total_amount: number;
    subtotal_amount: number;
    vat_amount: number;
    vat_rate: number;
    status: string;
    payment_method: string;
    payment_reference?: string;
    notes?: string;
    created_at: string;
    updated_at: string;
    invoice_items: InvoiceItem[];
}

const props = defineProps<{
    invoice: Invoice;
}>();

const page = usePage();
const isCustomer = Array.isArray((page.props as any).auth?.userRoles) && (page.props as any).auth.userRoles.includes('Customer');

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Invoices',
        href: '/invoices',
    },
    {
        title: props.invoice.reference_number,
        href: `/invoices/${props.invoice.id}`,
    }
];

function getStatusBadgeClass(status: string) {
    switch (status) {
        case 'draft': return 'bg-gray-100 text-gray-800';
        case 'pending': return 'bg-yellow-100 text-yellow-800';
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

function getCustomerProfileImage(customer: Customer) {
    if (!customer.media || customer.media.length === 0) {
        return null;
    }
    
    // Find the profile image (first image in the collection)
    const profileImage = customer.media.find(media => 
        media.mime_type.startsWith('image/')
    );
    
    return profileImage ? profileImage.thumb_url || profileImage.original_url : null;
}

function getCustomerInitials(customer: Customer) {
    return customer.name
        .split(' ')
        .map(word => word.charAt(0))
        .join('')
        .toUpperCase()
        .slice(0, 2);
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="props.invoice.reference_number" />
        
        <div class="flex items-center justify-between my-6">
            <h1 class="text-2xl font-bold">{{ props.invoice.reference_number }}</h1>
            <div class="flex gap-2">
                <Button variant="outline" @click="window.print()">Download PDF</Button>
                <Button variant="outline" @click="window.print()">Print</Button>
                <Link v-if="isCustomer && props.invoice.status === 'completed'" :href="route('refundRequests.create', props.invoice.id)">
                    <Button variant="default">Request Refund</Button>
                </Link>
                <Link :href="route('invoices.index')">
                    <Button variant="ghost">Back</Button>
                </Link>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Invoice Details -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Invoice Information -->
                <Card>
                    <CardHeader>
                        <CardTitle>Invoice Information</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm font-medium text-gray-500">Reference Number</label>
                                <p class="text-lg font-semibold">{{ props.invoice.reference_number }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Date Created</label>
                                <p>{{ formatDate(props.invoice.created_at) }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Status</label>
                                <span :class="['px-2 py-1 rounded-full text-xs font-medium', getStatusBadgeClass(props.invoice.status)]">
                                    {{ props.invoice.status }}
                                </span>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Payment Method</label>
                                <p>{{ props.invoice.payment_method.replace('_', ' ') }}</p>
                            </div>
                            <div v-if="props.invoice.payment_reference">
                                <label class="text-sm font-medium text-gray-500">Payment Reference</label>
                                <p>{{ props.invoice.payment_reference }}</p>
                            </div>
                        </div>
                        <div v-if="props.invoice.notes" class="mt-4">
                            <label class="text-sm font-medium text-gray-500">Notes</label>
                            <p class="mt-1">{{ props.invoice.notes }}</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Invoice Items -->
                <Card>
                    <CardHeader>
                        <CardTitle>Invoice Items</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-border">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-2 text-left">Product</th>
                                        <th class="px-4 py-2 text-left">Quantity</th>
                                        <th class="px-4 py-2 text-left">Price</th>
                                        <th class="px-4 py-2 text-left">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in props.invoice.invoice_items" :key="item.id" class="hover:bg-muted">
                                        <td class="px-4 py-2">
                                            <div>
                                                <div class="font-medium">{{ item.product.name }}</div>
                                                <div class="text-sm text-gray-500">SKU: {{ item.product.id }}</div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-2">{{ item.quantity }}</td>
                                        <td class="px-4 py-2">{{ formatCurrency(item.price) }}</td>
                                        <td class="px-4 py-2 font-medium">{{ formatCurrency(item.total) }}</td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="border-t">
                                        <td colspan="3" class="px-4 py-2 text-right font-medium">Subtotal:</td>
                                        <td class="px-4 py-2 font-medium">{{ formatCurrency(props.invoice.subtotal_amount) }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="px-4 py-2 text-right font-medium">VAT ({{ props.invoice.vat_rate }}%):</td>
                                        <td class="px-4 py-2 font-medium">{{ formatCurrency(props.invoice.vat_amount) }}</td>
                                    </tr>
                                    <tr class="border-t">
                                        <td colspan="3" class="px-4 py-2 text-right font-bold text-lg">Total Amount:</td>
                                        <td class="px-4 py-2 font-bold text-lg">{{ formatCurrency(props.invoice.total_amount) }}</td>
                                    </tr>
                                </tfoot>
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
                        <div class="space-y-3">
                            <div class="flex items-center gap-3">
                                <Avatar class="size-12">
                                    <AvatarImage 
                                        v-if="getCustomerProfileImage(props.invoice.customer)"
                                        :src="getCustomerProfileImage(props.invoice.customer)!"
                                        :alt="props.invoice.customer.name"
                                    />
                                    <AvatarFallback v-else>
                                        {{ getCustomerInitials(props.invoice.customer) }}
                                    </AvatarFallback>
                                </Avatar>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Name</label>
                                    <p class="font-medium">{{ props.invoice.customer.name }}</p>
                                </div>
                            </div>
                            <div v-if="props.invoice.customer.company_name">
                                <label class="text-sm font-medium text-gray-500">Company</label>
                                <p>{{ props.invoice.customer.company_name }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Email</label>
                                <p>{{ props.invoice.customer.email }}</p>
                            </div>
                            <div v-if="props.invoice.customer.phone">
                                <label class="text-sm font-medium text-gray-500">Phone</label>
                                <p>{{ props.invoice.customer.phone }}</p>
                            </div>
                            <div v-if="props.invoice.customer.address">
                                <label class="text-sm font-medium text-gray-500">Address</label>
                                <p class="text-sm">{{ props.invoice.customer.address }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Created By -->
                <Card>
                    <CardHeader>
                        <CardTitle>Created By</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div>
                            <label class="text-sm font-medium text-gray-500">User</label>
                            <p>{{ props.invoice.user.name }}</p>
                        </div>
                        <div class="mt-3">
                            <label class="text-sm font-medium text-gray-500">Last Updated</label>
                            <p class="text-sm">{{ formatDate(props.invoice.updated_at) }}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template> 