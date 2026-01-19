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

interface Delivery {
    id: number;
    customer: { id: number; name: string; company_name?: string };
    invoice?: { id: number; total_amount: number; reference_number?: string };
    delivery_address: string;
    contact_person: string;
    contact_phone: string;
    delivery_date: string;
    delivery_time: string;
    status: string;
    delivery_fee: number;
    type?: string;
    notes?: string;
    created_at: string;
    updated_at: string;
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

interface DeliveryStats {
    totalDeliveries: number;
    pendingDeliveries: number;
    completedDeliveries: number;
    cancelledDeliveries: number;
}

const page = usePage();
const filters = ref<{ search?: string; type?: string; classification?: string; date_from?: string; date_to?: string }>(page.props.filters ? (page.props.filters as { search?: string; type?: string; classification?: string; date_from?: string; date_to?: string }) : {});
const search = ref(typeof filters.value.search === 'string' ? filters.value.search : '');
const typeFilter = ref(typeof filters.value.type === 'string' ? filters.value.type : '');
const classificationFilter = ref(typeof filters.value.classification === 'string' ? filters.value.classification : '');
const dateFrom = ref(typeof filters.value.date_from === 'string' ? filters.value.date_from : '');
const dateTo = ref(typeof filters.value.date_to === 'string' ? filters.value.date_to : '');
const showStats = ref(false);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Deliveries', href: '/deliveries' },
];

watchEffect(() => {
    search.value = (page.props.filters && typeof (page.props.filters as { search?: string }).search === 'string')
        ? (page.props.filters as { search?: string }).search!
        : '';
    typeFilter.value = (page.props.filters && typeof (page.props.filters as { type?: string }).type === 'string')
        ? (page.props.filters as { type?: string }).type!
        : '';
    classificationFilter.value = (page.props.filters && typeof (page.props.filters as { classification?: string }).classification === 'string')
        ? (page.props.filters as { classification?: string }).classification!
        : '';
    dateFrom.value = (page.props.filters && typeof (page.props.filters as { date_from?: string }).date_from === 'string')
        ? (page.props.filters as { date_from?: string }).date_from!
        : '';
    dateTo.value = (page.props.filters && typeof (page.props.filters as { date_to?: string }).date_to === 'string')
        ? (page.props.filters as { date_to?: string }).date_to!
        : '';
});

let searchTimeout: ReturnType<typeof setTimeout> | null = null;
watch(search, (val) => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 400);
});

watch(typeFilter, () => {
    applyFilters();
});

watch(classificationFilter, () => {
    applyFilters();
});

watch([dateFrom, dateTo], () => {
    applyFilters();
});

function applyFilters() {
    const params: Record<string, string | undefined> = {
        search: search.value || undefined,
        type: typeFilter.value || undefined,
        classification: classificationFilter.value || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
    };
    router.get('/deliveries', params, { preserveState: true, replace: true });
}

function goToPage(pageNum: number) {
    const params: Record<string, string | undefined> = {
        search: search.value || undefined,
        type: typeFilter.value || undefined,
        classification: classificationFilter.value || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
        page: pageNum.toString(),
    };
    router.get('/deliveries', params, { preserveState: true, replace: true });
}

function getStatusBadgeClass(status: string) {
    switch (status) {
        case 'pending': return 'bg-yellow-100 text-yellow-800';
        case 'completed': return 'bg-green-100 text-green-800';
        case 'cancelled': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

function getStatusLabel(status: string): string {
    switch (status) {
        case 'pending': return 'Out for Delivery';
        case 'completed': return 'Delivered';
        case 'cancelled': return 'Cancelled';
        default: return status.charAt(0).toUpperCase() + status.slice(1);
    }
}

function getTypeBadgeClass(type?: string) {
    switch (type) {
        case 'return': return 'bg-orange-100 text-orange-800';
        case 'order': return 'bg-blue-100 text-blue-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

function getTypeLabel(type?: string): string {
    switch (type) {
        case 'return': return 'Return Delivery';
        case 'order': return 'Order Delivery';
        default: return 'Order Delivery'; // Default to order for backward compatibility
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
    return new Date(dateString).toLocaleDateString();
}

async function deleteDelivery(id: number) {
    const result = await Swal.fire({
        title: 'Delete Delivery?',
        text: 'Are you sure you want to delete this delivery? This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#8f5be8',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete delivery',
    });
    if (result.isConfirmed) {
        router.delete(`/deliveries/${id}`, {
            onSuccess: () => {
                Swal.fire('Delivery deleted', 'Delivery deleted successfully.', 'success');
            },
        });
    }
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Deliveries" />
        
        <!-- Enhanced Delivery Stats Widget -->
        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 transform -translate-y-4"
            enter-to-class="opacity-100 transform translate-y-0"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 transform translate-y-0"
            leave-to-class="opacity-0 transform -translate-y-4"
        >
            <div v-show="showStats" class="grid grid-cols-1 lg:grid-cols-4 gap-6 mt-8 mb-6">
                <!-- Total Deliveries -->
                <Card class="relative overflow-hidden">
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Total Deliveries</p>
                                <p class="text-xl font-bold text-blue-600">{{ (page.props.stats as DeliveryStats).totalDeliveries }}</p>
                            </div>
                            <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center">
                                <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Pending Deliveries -->
                <Card class="relative overflow-hidden">
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Pending</p>
                                <p class="text-xl font-bold text-yellow-600">{{ (page.props.stats as DeliveryStats).pendingDeliveries }}</p>
                            </div>
                            <div class="h-12 w-12 rounded-full bg-yellow-100 flex items-center justify-center">
                                <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Completed Deliveries -->
                <Card class="relative overflow-hidden">
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Completed</p>
                                <p class="text-xl font-bold text-green-600">{{ (page.props.stats as DeliveryStats).completedDeliveries }}</p>
                            </div>
                            <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center">
                                <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Cancelled Deliveries -->
                <Card class="relative overflow-hidden">
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Cancelled</p>
                                <p class="text-xl font-bold text-red-600">{{ (page.props.stats as DeliveryStats).cancelledDeliveries }}</p>
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

        <!-- Search and Filters -->
        <div class="flex items-center gap-2 flex-wrap mt-4 mb-2">
            <input 
                v-model="search" 
                type="text" 
                placeholder="Search deliveries by customer name..." 
                class="flex-1 min-w-[200px] rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2" 
            />
            <select 
                v-model="typeFilter" 
                class="rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
            >
                <option value="">All Types</option>
                <option value="order">Order Delivery</option>
                <option value="return">Return Delivery</option>
            </select>
            <select 
                v-model="classificationFilter" 
                class="rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
            >
                <option value="">All Deliveries</option>
                <option value="complete_no_issues">Complete Deliveries (No Issues)</option>
                <option value="refunded_orders">Deliveries from Refunded Orders</option>
            </select>
            
            <label class="text-sm text-muted-foreground flex items-center gap-2">
                <span>From:</span>
                <input 
                    v-model="dateFrom" 
                    type="date" 
                    class="rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2" 
                />
            </label>
            
            <label class="text-sm text-muted-foreground flex items-center gap-2">
                <span>To:</span>
                <input 
                    v-model="dateTo" 
                    type="date" 
                    class="rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2" 
                />
            </label>
        </div>

        <Card>
            <CardContent>
                <table class="min-w-full divide-y divide-border">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left">Customer</th>
                            <th class="px-4 py-2 text-left">Contact Person</th>
                            <th class="px-4 py-2 text-left">Delivery Date</th>
                            <th class="px-4 py-2 text-left">Status</th>
                            <th class="px-4 py-2 text-left">Type</th>
                            <th class="px-4 py-2 text-left">Delivery Fee</th>
                            <th class="px-4 py-2 text-left">Invoice</th>
                            <th class="px-4 py-2 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody v-if="(page.props.deliveries as Paginated<Delivery>).data.length">
                        <tr v-for="delivery in (page.props.deliveries as Paginated<Delivery>).data" :key="delivery.id" class="hover:bg-muted">
                            <td class="px-4 py-2">
                                <div>
                                    <div class="font-medium">{{ delivery.customer.name }}</div>
                                    <div v-if="delivery.customer.company_name" class="text-sm text-gray-500">{{ delivery.customer.company_name }}</div>
                                </div>
                            </td>
                            <td class="px-4 py-2">
                                <div>
                                    <div class="font-medium">{{ delivery.contact_person }}</div>
                                    <div class="text-sm text-gray-500">{{ delivery.contact_phone }}</div>
                                </div>
                            </td>
                            <td class="px-4 py-2">
                                <div>
                                    <div class="font-medium">{{ formatDate(delivery.delivery_date) }}</div>
                                    <div class="text-sm text-gray-500">{{ delivery.delivery_time }}</div>
                                </div>
                            </td>
                            <td class="px-4 py-2">
                                <span :class="['px-2 py-1 rounded-full text-xs font-medium', getStatusBadgeClass(delivery.status)]">
                                    {{ getStatusLabel(delivery.status) }}
                                </span>
                            </td>
                            <td class="px-4 py-2">
                                <span :class="['px-2 py-1 rounded-full text-xs font-medium', getTypeBadgeClass(delivery.type)]">
                                    {{ getTypeLabel(delivery.type) }}
                                </span>
                            </td>
                            <td class="px-4 py-2 font-medium">{{ formatCurrency(delivery.delivery_fee) }}</td>
                            <td class="px-4 py-2">
                                <Link v-if="delivery.invoice" :href="route('invoices.show', delivery.invoice.id)" class="text-blue-500 hover:underline font-medium">
                                    {{ delivery.invoice.reference_number || `#${delivery.invoice.id}` }}
                                </Link>
                                <span v-else class="text-gray-400">N/A</span>
                            </td>
                            <td class="px-4 py-2">
                                <div class="flex gap-2">
                                    <Button variant="ghost" size="sm" as-child>
                                        <Link :href="route('deliveries.show', delivery.id)">
                                            <Icon name="eye" class="h-4 w-4" />
                                        </Link>
                                    </Button>
                                    <Button variant="ghost" size="sm" as-child>
                                        <Link :href="route('deliveries.edit', delivery.id)">
                                            <Icon name="edit" class="h-4 w-4" />
                                        </Link>
                                    </Button>
                                    <Button variant="ghost" size="sm" @click="deleteDelivery(delivery.id)">
                                        <Icon name="trash" class="h-4 w-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                    <tbody v-else>
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-sm text-gray-500">
                                No deliveries found.
                                <button 
                                    v-if="(search && search.toString().trim().length) || typeFilter || classificationFilter || dateFrom || dateTo" 
                                    type="button" 
                                    class="underline underline-offset-4 ml-1" 
                                    @click="search = ''; typeFilter = ''; classificationFilter = ''; dateFrom = ''; dateTo = '';"
                                >
                                    Clear filters
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="(page.props.deliveries as Paginated<Delivery>).last_page > 1" class="flex items-center justify-between mt-6">
                    <div class="text-sm text-gray-700">
                        Showing {{ (page.props.deliveries as Paginated<Delivery>).from }} to {{ (page.props.deliveries as Paginated<Delivery>).to }} of {{ (page.props.deliveries as Paginated<Delivery>).total }} results
                    </div>
                    <div class="flex gap-2">
                        <Button 
                            v-if="(page.props.deliveries as Paginated<Delivery>).prev_page_url" 
                            variant="outline" 
                            size="sm"
                            @click="goToPage((page.props.deliveries as Paginated<Delivery>).current_page - 1)"
                        >
                            Previous
                        </Button>
                        <Button 
                            v-if="(page.props.deliveries as Paginated<Delivery>).next_page_url" 
                            variant="outline" 
                            size="sm"
                            @click="goToPage((page.props.deliveries as Paginated<Delivery>).current_page + 1)"
                        >
                            Next
                        </Button>
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