<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch, watchEffect } from 'vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import Icon from '@/components/Icon.vue';
import Swal from 'sweetalert2';
import { type BreadcrumbItem } from '@/types';

interface Order {
    id: number;
    reference_number: string;
    customer: { id: number; name: string; company_name?: string };
    approved_by?: { id: number; name: string };
    invoice?: { id: number; reference_number: string };
    total_amount: number;
    status: string;
    notes?: string;
    rejection_reason?: string;
    created_at: string;
    updated_at: string;
    approved_at?: string;
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

interface OrderStats {
    totalOrders: number;
    pendingOrders: number;
    approvedOrders: number;
    rejectedOrders: number;
}

const page = usePage();
const isCustomer = Array.isArray((page.props as any).auth?.userRoles) && (page.props as any).auth.userRoles.includes('Customer');
const isStaff = Array.isArray((page.props as any).auth?.userRoles) && 
    ((page.props as any).auth.userRoles.includes('Admin') || (page.props as any).auth.userRoles.includes('Staff'));
const filters = ref<{ search?: string; status?: string }>(
    page.props.filters ? (page.props.filters as { search?: string; status?: string }) : {}
);
const search = ref(typeof filters.value.search === 'string' ? filters.value.search : '');
const status = ref(typeof filters.value.status === 'string' ? filters.value.status : '');
const showStats = ref(false);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Orders', href: '/orders' },
];

watchEffect(() => {
    search.value = (page.props.filters && typeof (page.props.filters as { search?: string }).search === 'string')
        ? (page.props.filters as { search?: string }).search!
        : '';
    status.value = (page.props.filters && typeof (page.props.filters as { status?: string }).status === 'string')
        ? (page.props.filters as { status?: string }).status!
        : '';
});

let searchTimeout: ReturnType<typeof setTimeout> | null = null;
watch(search, (val) => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/orders', {
            search: val || undefined,
            status: status.value || undefined,
        }, { preserveState: true, replace: true });
    }, 400);
});

function goToPage(pageNum: number) {
    router.get('/orders', {
        search: search.value || undefined,
        status: status.value || undefined,
        page: pageNum,
    }, { preserveState: true, replace: true });
}

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

function togglePendingFilter() {
    status.value = status.value === 'pending' ? '' : 'pending';
    router.get('/orders', {
        search: search.value || undefined,
        status: status.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}

function formatDateFriendly(dateString: string) {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: '2-digit'
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Orders" />
        
        <!-- Enhanced Order Stats Widget -->
        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 transform -translate-y-4"
            enter-to-class="opacity-100 transform translate-y-0"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 transform translate-y-0"
            leave-to-class="opacity-0 transform -translate-y-4"
        >
            <div v-show="showStats" class="grid grid-cols-1 lg:grid-cols-4 gap-6 mt-8 mb-6">
            <!-- Total Orders -->
            <Card class="relative overflow-hidden">
                <CardContent class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Total Orders</p>
                            <p class="text-xl font-bold text-blue-600">{{ (page.props.stats as OrderStats).totalOrders }}</p>
                        </div>
                        <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center">
                            <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Pending Orders -->
            <Card class="relative overflow-hidden">
                <CardContent class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Pending</p>
                            <p class="text-xl font-bold text-yellow-600">{{ (page.props.stats as OrderStats).pendingOrders }}</p>
                        </div>
                        <div class="h-12 w-12 rounded-full bg-yellow-100 flex items-center justify-center">
                            <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Approved Orders -->
            <Card class="relative overflow-hidden">
                <CardContent class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Approved</p>
                            <p class="text-xl font-bold text-green-600">{{ (page.props.stats as OrderStats).approvedOrders }}</p>
                        </div>
                        <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center">
                            <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Rejected Orders -->
            <Card class="relative overflow-hidden">
                <CardContent class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Rejected</p>
                            <p class="text-xl font-bold text-red-600">{{ (page.props.stats as OrderStats).rejectedOrders }}</p>
                        </div>
                        <div class="h-12 w-12 rounded-full bg-red-100 flex items-center justify-center">
                            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
        </Transition>

        <!-- Search and Actions -->
        <div class="flex items-center justify-between mt-4 mb-2">
            <div class="flex gap-2 items-center">
                <input 
                    v-model="search" 
                    type="text" 
                    placeholder="Search by reference number or customer name" 
                    class="rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2" 
                />
            </div>
            <div class="flex items-center gap-2">
                <Button
                    variant="outline"
                    :class="status === 'pending' ? 'border-yellow-400 text-yellow-600 bg-yellow-50' : ''"
                    @click="togglePendingFilter"
                >
                    Pending
                </Button>
            <Link v-if="isCustomer" :href="route('orders.create')">
                <Button variant="default">
                    <span class="mr-2">+</span>
                    Create Order
                </Button>
            </Link>
            </div>
        </div>


        <Card>
            <CardContent>
                <div v-if="(page.props.orders as Paginated<Order>).data.length === 0">
                    <!-- If filters are applied, show filtered empty state -->
                    <div v-if="(search && search.trim().length) || (status && status.trim().length)" class="py-12 text-center">
                        <div class="text-xl font-semibold mb-2">No results found</div>
                        <p class="text-sm text-gray-600 mb-6">
                            We couldn't find any orders matching your search/filter.
                        </p>
                        <div class="flex items-center justify-center gap-2">
                            <Button variant="outline" @click="search = ''; status = ''">Clear search</Button>
                            <Link v-if="isCustomer" :href="route('orders.create')">
                                <Button variant="default">
                                    <span class="mr-2">+</span>
                                    Create Order
                                </Button>
                            </Link>
                        </div>
                    </div>
                    <!-- Otherwise, show empty state for first use -->
                    <div v-else class="py-12 text-center">
                        <div class="text-xl font-semibold mb-2">No orders yet</div>
                        <p class="text-sm text-gray-600 mb-6" v-if="isCustomer">Create your first order to get started.</p>
                        <Link v-if="isCustomer" :href="route('orders.create')">
                            <Button variant="default">
                                <span class="mr-2">+</span>
                                Create Order
                            </Button>
                        </Link>
                    </div>
                </div>
                <div v-else>
                    <table class="min-w-full divide-y divide-border">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 text-left">Reference #</th>
                                <th v-if="!isCustomer" class="px-4 py-2 text-left">Customer</th>
                                <th class="px-4 py-2 text-left">Total</th>
                                <th class="px-4 py-2 text-left">Status</th>
                                <th class="px-4 py-2 text-left">Date</th>
                                <th v-if="isStaff" class="px-4 py-2 text-left">Approved By</th>
                                <th class="px-4 py-2 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="order in (page.props.orders as Paginated<Order>).data" :key="order.id" class="hover:bg-muted">
                                <td class="px-4 py-2 font-medium">
                                    <Link :href="route('orders.show', order.id)" class="underline underline-offset-4">
                                        {{ order.reference_number }}
                                    </Link>
                                </td>
                                <td v-if="!isCustomer" class="px-4 py-2">
                                    <div>
                                        <div class="font-medium">{{ order.customer.name }}</div>
                                        <div v-if="order.customer.company_name" class="text-sm text-gray-500">{{ order.customer.company_name }}</div>
                                    </div>
                                </td>
                                <td class="px-4 py-2 font-medium">{{ formatCurrency(order.total_amount) }}</td>
                                <td class="px-4 py-2">
                                    <span :class="['px-2 py-1 rounded-full text-xs font-medium', getStatusBadgeClass(order.status)]">
                                        {{ order.status.charAt(0).toUpperCase() + order.status.slice(1) }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ formatDateFriendly(order.created_at) }}</td>
                                <td v-if="isStaff" class="px-4 py-2 text-sm text-gray-500">
                                    <span v-if="order.approved_by">{{ order.approved_by.name }}</span>
                                    <span v-else class="text-gray-400">-</span>
                                </td>
                                <td class="px-4 py-2">
                                    <div class="flex gap-2">
                                        <Link :href="route('orders.show', order.id)">
                                            <Button variant="ghost" size="sm">
                                                <Icon name="eye" class="h-4 w-4" />
                                                <span class="ml-1">View</span>
                                            </Button>
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div v-if="(page.props.orders as Paginated<Order>).last_page > 1" class="flex items-center justify-between mt-6">
                        <div class="text-sm text-gray-700">
                            Showing {{ (page.props.orders as Paginated<Order>).from }} to {{ (page.props.orders as Paginated<Order>).to }} of {{ (page.props.orders as Paginated<Order>).total }} results
                        </div>
                        <div class="flex gap-2">
                            <Button 
                                v-if="(page.props.orders as Paginated<Order>).prev_page_url" 
                                variant="outline" 
                                size="sm"
                                @click="goToPage((page.props.orders as Paginated<Order>).current_page - 1)"
                            >
                                Previous
                            </Button>
                            <Button 
                                v-if="(page.props.orders as Paginated<Order>).next_page_url" 
                                variant="outline" 
                                size="sm"
                                @click="goToPage((page.props.orders as Paginated<Order>).current_page + 1)"
                            >
                                Next
                            </Button>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Floating Toggle Button -->
        <div class="fixed bottom-6 right-6 z-50">
            <button 
                @click="showStats = !showStats" 
                class="h-12 w-12 rounded-full shadow-lg hover:shadow-xl transition-all duration-200 flex items-center justify-center"
                :class="showStats ? 'bg-red-500 hover:bg-red-600 text-white' : 'bg-blue-500 hover:bg-blue-600 text-white'"
            >
                <svg v-if="!showStats" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                <svg v-else class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    </AppLayout>
</template>

