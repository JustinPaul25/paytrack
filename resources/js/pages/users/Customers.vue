<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Select } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import { type BreadcrumbItem } from '@/types';
import { MapPin, ShoppingBag, Package } from 'lucide-vue-next';

const page = usePage();

interface TopProduct {
    name: string;
    quantity: number;
    amount: number;
}

interface Customer {
    id: number;
    name: string;
    company_name?: string;
    email: string;
    phone?: string;
    address?: string;
    location?: { lat: number; lng: number };
    total_purchases: number;
    total_invoices: number;
    top_products: TopProduct[];
    updated_at: string;
}

const filters = ref<{ search?: string; duration?: string }>(page.props.filters ? (page.props.filters as any) : {});
const search = ref(filters.value.search || '');
const duration = ref(filters.value.duration || 'all');

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Users', href: '/users' },
    { title: 'Customers', href: '/users/customers' },
];

const durationOptions = [
    { value: 'all', label: 'All Time' },
    { value: 'month', label: 'This Month' },
    { value: 'year', label: 'This Year' },
];

let searchTimeout: ReturnType<typeof setTimeout> | null = null;
watch(search, (val) => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        updateFilters();
    }, 400);
});

watch(duration, () => {
    updateFilters();
});

function updateFilters() {
    router.get('/users/customers', {
        search: search.value || undefined,
        duration: duration.value !== 'all' ? duration.value : undefined,
    }, { preserveState: true, replace: true });
}

function formatCurrency(amount: number) {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2
    }).format(amount);
}

function goToPage(pageNum: number) {
    router.get('/users/customers', {
        search: search.value || undefined,
        duration: duration.value !== 'all' ? duration.value : undefined,
        page: pageNum,
    }, { preserveState: true, replace: true });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Customers" />
        
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">Customer Management</h1>
        </div>

        <!-- Filters -->
        <div class="flex flex-col md:flex-row gap-4 items-start md:items-center justify-between mt-4 mb-2">
            <div class="flex flex-wrap gap-2 items-center w-full md:w-auto">
                <input 
                    v-model="search" 
                    type="text" 
                    placeholder="Search customers..." 
                    class="rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 flex-1 md:flex-initial min-w-[200px]" 
                />
                <Select
                    v-model="duration"
                    :options="durationOptions"
                    placeholder="Duration"
                    class="w-full md:w-[150px]"
                />
            </div>
        </div>

        <div v-if="(page.props.customers as Paginated<Customer>).data.length === 0" class="py-12 text-center">
            <div class="text-xl font-semibold mb-2">No customers found</div>
            <p class="text-sm text-gray-600 mb-6">
                <span v-if="search || duration !== 'all'">
                    No customers match your current filters.
                </span>
                <span v-else>
                    No customers in the system yet.
                </span>
            </p>
            <Button v-if="search || duration !== 'all'" variant="outline" @click="search = ''; duration = 'all'">
                Clear Filters
            </Button>
        </div>

        <div v-else class="space-y-6">
            <Card
                v-for="customer in (page.props.customers as Paginated<Customer>).data"
                :key="customer.id"
                class="hover:shadow-lg transition-shadow"
            >
                <CardHeader>
                    <div class="flex items-start justify-between">
                        <div>
                            <CardTitle class="text-xl">{{ customer.name }}</CardTitle>
                            <p v-if="customer.company_name" class="text-sm text-gray-500 mt-1">
                                {{ customer.company_name }}
                            </p>
                        </div>
                        <Link :href="route('customers.edit', customer.id)">
                            <Button variant="outline" size="sm">
                                View Details
                            </Button>
                        </Link>
                    </div>
                </CardHeader>
                <CardContent class="space-y-4">
                    <!-- Contact Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-700 mb-2">Contact Information</h3>
                            <div class="space-y-1 text-sm">
                                <div class="flex items-center gap-2">
                                    <span class="text-gray-500">Email:</span>
                                    <span>{{ customer.email }}</span>
                                </div>
                                <div v-if="customer.phone" class="flex items-center gap-2">
                                    <span class="text-gray-500">Phone:</span>
                                    <span>{{ customer.phone }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <h3 class="text-sm font-semibold text-gray-700 mb-2 flex items-center gap-1">
                                <MapPin class="h-4 w-4" />
                                Address
                            </h3>
                            <p v-if="customer.address" class="text-sm text-gray-600">
                                {{ customer.address }}
                            </p>
                            <p v-else class="text-sm text-gray-400 italic">No address provided</p>
                        </div>
                    </div>

                    <!-- Purchase Statistics -->
                    <div class="border-t pt-4">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-1">
                            <ShoppingBag class="h-4 w-4" />
                            Purchase Statistics
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-blue-50 p-3 rounded-lg">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-blue-600 font-semibold">₱</span>
                                    <span class="text-xs text-gray-600">Total Purchases</span>
                                </div>
                                <p class="text-lg font-bold text-blue-600">{{ formatCurrency(customer.total_purchases) }}</p>
                            </div>
                            <div class="bg-purple-50 p-3 rounded-lg">
                                <div class="flex items-center gap-2 mb-1">
                                    <Package class="h-4 w-4 text-purple-600" />
                                    <span class="text-xs text-gray-600">Total Invoices</span>
                                </div>
                                <p class="text-lg font-bold text-purple-600">{{ customer.total_invoices }}</p>
                            </div>
                            <div class="bg-green-50 p-3 rounded-lg">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-green-600 font-semibold">₱</span>
                                    <span class="text-xs text-gray-600">Average Order</span>
                                </div>
                                <p class="text-lg font-bold text-green-600">
                                    {{ customer.total_invoices > 0 ? formatCurrency(customer.total_purchases / customer.total_invoices) : formatCurrency(0) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Top Purchases -->
                    <div v-if="customer.top_products && customer.top_products.length > 0" class="border-t pt-4">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Top Purchases</h3>
                        <div class="space-y-2">
                            <div
                                v-for="(product, index) in customer.top_products"
                                :key="index"
                                class="flex items-center justify-between p-2 bg-gray-50 rounded"
                            >
                                <div class="flex items-center gap-3">
                                    <span class="text-xs font-semibold text-gray-500 w-6">{{ index + 1 }}.</span>
                                    <span class="text-sm font-medium">{{ product.name }}</span>
                                </div>
                                <div class="flex items-center gap-4 text-sm">
                                    <span class="text-gray-600">Qty: <span class="font-semibold">{{ product.quantity }}</span></span>
                                    <span class="text-gray-600">{{ formatCurrency(product.amount) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="border-t pt-4">
                        <p class="text-sm text-gray-400 italic">No purchase history available</p>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Pagination -->
        <div v-if="(page.props.customers as Paginated<Customer>).last_page > 1" class="flex items-center justify-between mt-6">
            <div class="text-sm text-gray-700">
                Showing {{ (page.props.customers as Paginated<Customer>).from }} to {{ (page.props.customers as Paginated<Customer>).to }} of {{ (page.props.customers as Paginated<Customer>).total }} results
            </div>
            <div class="flex gap-2">
                <Button 
                    v-if="(page.props.customers as Paginated<Customer>).prev_page_url" 
                    variant="outline" 
                    size="sm"
                    @click="goToPage((page.props.customers as Paginated<Customer>).current_page - 1)"
                >
                    Previous
                </Button>
                <Button 
                    v-if="(page.props.customers as Paginated<Customer>).next_page_url" 
                    variant="outline" 
                    size="sm"
                    @click="goToPage((page.props.customers as Paginated<Customer>).current_page + 1)"
                >
                    Next
                </Button>
            </div>
        </div>
    </AppLayout>
</template>

