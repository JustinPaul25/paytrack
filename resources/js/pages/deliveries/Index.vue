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
    invoice?: { id: number; total_amount: number };
    delivery_address: string;
    contact_person: string;
    contact_phone: string;
    delivery_date: string;
    delivery_time: string;
    status: string;
    delivery_fee: number;
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
const filters = ref<{ search?: string }>(page.props.filters ? (page.props.filters as { search?: string }) : {});
const search = ref(typeof filters.value.search === 'string' ? filters.value.search : '');

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Deliveries', href: '/deliveries' },
];

watchEffect(() => {
    search.value = (page.props.filters && typeof (page.props.filters as { search?: string }).search === 'string')
        ? (page.props.filters as { search?: string }).search!
        : '';
});

let searchTimeout: ReturnType<typeof setTimeout> | null = null;
watch(search, (val) => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/deliveries', { search: val }, { preserveState: true, replace: true });
    }, 400);
});

function goToPage(pageNum: number) {
    router.get('/deliveries', { search: search.value, page: pageNum }, { preserveState: true, replace: true });
}

function getStatusBadgeClass(status: string) {
    switch (status) {
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
        
        <!-- Delivery Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <!-- Total Deliveries -->
            <Card class="relative overflow-hidden">
                <CardContent class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Total</p>
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

        <!-- Search and Actions -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex gap-2 items-center">
                <input 
                    v-model="search" 
                    type="text" 
                    placeholder="Search deliveries..." 
                    class="rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2" 
                />
            </div>
            <Link :href="route('deliveries.create')">
                <Button variant="default">
                    <span class="mr-2">+</span>
                    Add New Delivery
                </Button>
            </Link>
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
                            <th class="px-4 py-2 text-left">Delivery Fee</th>
                            <th class="px-4 py-2 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
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
                                    {{ delivery.status }}
                                </span>
                            </td>
                            <td class="px-4 py-2 font-medium">{{ formatCurrency(delivery.delivery_fee) }}</td>
                            <td class="px-4 py-2">
                                <div class="flex gap-2">
                                    <Link :href="route('deliveries.show', delivery.id)">
                                        <Button variant="ghost" size="sm">
                                            <Icon name="eye" class="h-4 w-4" />
                                        </Button>
                                    </Link>
                                    <Link :href="route('deliveries.edit', delivery.id)">
                                        <Button variant="ghost" size="sm">
                                            <Icon name="edit" class="h-4 w-4" />
                                        </Button>
                                    </Link>
                                    <Button variant="ghost" size="sm" @click="deleteDelivery(delivery.id)">
                                        <Icon name="trash" class="h-4 w-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="(page.props.deliveries as Paginated<Delivery>).last_page > 1" class="mt-4 flex items-center justify-between">
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
    </AppLayout>
</template> 