<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import Icon from '@/components/Icon.vue';
import { type BreadcrumbItem } from '@/types';

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

interface StockMovement {
    id: number;
    product_id: number;
    refund_id: number | null;
    invoice_id: number | null;
    user_id: number | null;
    type: 'sale' | 'refund' | 'writeoff' | 'adjustment';
    quantity: number;
    quantity_before: number | null;
    quantity_after: number | null;
    notes: string | null;
    created_at: string;
    user?: {
        id: number;
        name: string;
    } | null;
    invoice?: {
        id: number;
        reference_number: string;
    } | null;
    refund?: {
        id: number;
    } | null;
}

interface Product {
    id: number;
    name: string;
    SKU: string;
    stock: number;
    initial_stock: number;
    category?: {
        id: number;
        name: string;
    };
}

const page = usePage();
const product = computed(() => (page.props.product as Product));
const filters = ref<{ type?: string; search?: string }>(
    page.props.filters ? (page.props.filters as { type?: string; search?: string }) : {}
);
const search = ref(typeof filters.value.search === 'string' ? filters.value.search : '');
const selectedType = ref(typeof filters.value.type === 'string' ? filters.value.type : '');
const types = computed(() => (page.props.types as string[]) || []);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Products', href: '/products' },
    { title: product.value.name, href: '#' },
    { title: 'Stock History', href: '#' },
];

let searchTimeout: ReturnType<typeof setTimeout> | null = null;
watch(search, (val) => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        updateFilters();
    }, 400);
});

watch(selectedType, () => {
    updateFilters();
});

function updateFilters() {
    const params: any = {};
    if (search.value) params.search = search.value;
    if (selectedType.value) params.type = selectedType.value;
    
    router.get(route('products.stockHistory', product.value.id), params, { preserveState: true, replace: true });
}

function goToPage(pageNum: number) {
    const params: any = { page: pageNum };
    if (search.value) params.search = search.value;
    if (selectedType.value) params.type = selectedType.value;
    
    router.get(route('products.stockHistory', product.value.id), params, { preserveState: true, replace: true });
}

function formatDate(dateString: string): string {
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function getTypeBadgeClass(type: string): string {
    switch (type.toLowerCase()) {
        case 'adjustment':
            return 'bg-blue-100 text-blue-800';
        case 'sale':
            return 'bg-red-100 text-red-800';
        case 'refund':
            return 'bg-green-100 text-green-800';
        case 'writeoff':
            return 'bg-orange-100 text-orange-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}

function getTypeLabel(type: string): string {
    switch (type.toLowerCase()) {
        case 'adjustment':
            return 'Stock Added';
        case 'sale':
            return 'Sale';
        case 'refund':
            return 'Refund';
        case 'writeoff':
            return 'Write-off';
        default:
            return type;
    }
}

function formatQuantity(quantity: number): string {
    return quantity > 0 ? `+${quantity}` : `${quantity}`;
}

const movementsPagination = computed(() => (page.props.movements as Paginated<StockMovement>));
const currentPage = computed(() => movementsPagination.value?.current_page || 1);
const lastPage = computed(() => movementsPagination.value?.last_page || 1);

const pageNumbers = computed<(number | string)[]>(() => {
    const total = lastPage.value || 1;
    const current = currentPage.value || 1;
    const delta = 2;
    const range: (number | string)[] = [];
    const start = Math.max(1, current - delta);
    const end = Math.min(total, current + delta);

    for (let i = start; i <= end; i++) range.push(i);

    if (start > 1) {
        if (start > 2) {
            range.unshift('...');
        }
        range.unshift(1);
    }
    if (end < total) {
        if (end < total - 1) {
            range.push('...');
        }
        range.push(total);
    }
    return range;
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`${product.name} - Stock History`" />
        
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold">
                    Stock History - {{ product.name }}
                </h1>
                <p class="text-sm text-muted-foreground mt-1">
                    Track all stock movements for this product
                </p>
                <div class="mt-2 flex gap-4 text-sm">
                    <span class="text-muted-foreground">
                        <strong>Stock:</strong> {{ product.initial_stock }}
                    </span>
                    <span class="text-muted-foreground">
                        <strong>Remaining Stock:</strong> {{ product.stock }}
                    </span>
                    <span class="text-muted-foreground">
                        <strong>SKU:</strong> {{ product.SKU }}
                    </span>
                </div>
            </div>
            <Link :href="route('products.index')">
                <Button variant="outline">
                    <Icon name="arrow-left" class="h-4 w-4 mr-2" />
                    Back to Products
                </Button>
            </Link>
        </div>

        <!-- Filters -->
        <Card class="mb-6">
            <CardContent class="p-4">
                <div class="flex gap-4 items-end">
                    <div class="flex-1">
                        <label class="block text-sm font-medium mb-1">Search</label>
                        <input 
                            v-model="search" 
                            type="text" 
                            placeholder="Search by notes or user name..." 
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2" 
                        />
                    </div>
                    <div class="w-48">
                        <label class="block text-sm font-medium mb-1">Type</label>
                        <select 
                            v-model="selectedType"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        >
                            <option value="">All Types</option>
                            <option v-for="type in types" :key="type" :value="type">
                                {{ getTypeLabel(type) }}
                            </option>
                        </select>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Movements Table -->
        <Card>
            <CardContent class="p-0">
                <!-- Empty State -->
                <div v-if="movementsPagination.total === 0" class="py-12 text-center">
                    <div class="mx-auto max-w-md">
                        <h3 class="text-lg font-semibold mb-2">No stock movements found</h3>
                        <p class="text-sm text-muted-foreground">
                            {{ search || selectedType ? 'No movements match your filters.' : 'No stock movements have been recorded yet.' }}
                        </p>
                    </div>
                </div>

                <!-- Table -->
                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Date & Time</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Quantity</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Before</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">After</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">User</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Related</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Notes</th>
                            </tr>
                        </thead>
                        <tbody class="bg-background divide-y divide-border">
                            <tr v-for="movement in movementsPagination.data" :key="movement.id" class="hover:bg-muted/50">
                                <td class="px-4 py-3 whitespace-nowrap text-sm">
                                    {{ formatDate(movement.created_at) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span :class="['px-2 py-1 rounded-full text-xs font-medium', getTypeBadgeClass(movement.type)]">
                                        {{ getTypeLabel(movement.type) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium" :class="movement.quantity > 0 ? 'text-green-600' : 'text-red-600'">
                                    {{ formatQuantity(movement.quantity) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-muted-foreground">
                                    {{ movement.quantity_before ?? 'N/A' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-muted-foreground">
                                    {{ movement.quantity_after ?? 'N/A' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-muted-foreground">
                                    {{ movement.user?.name ?? 'System' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm">
                                    <Link v-if="movement.invoice" :href="route('invoices.show', movement.invoice.id)" class="text-blue-600 hover:underline">
                                        Invoice #{{ movement.invoice.reference_number }}
                                    </Link>
                                    <span v-else-if="movement.refund" class="text-muted-foreground">
                                        Refund #{{ movement.refund.id }}
                                    </span>
                                    <span v-else class="text-muted-foreground">—</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">
                                    {{ movement.notes || '—' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="movementsPagination.last_page > 1" class="px-4 py-4 border-t border-border">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-muted-foreground">
                            Showing {{ movementsPagination.from }} to {{ movementsPagination.to }} of {{ movementsPagination.total }} results
                        </div>
                        <div class="flex gap-2">
                            <Button 
                                v-if="movementsPagination.prev_page_url" 
                                variant="outline" 
                                size="sm"
                                @click="goToPage(currentPage - 1)"
                            >
                                Previous
                            </Button>
                            <template v-for="(pageNum, index) in pageNumbers" :key="index">
                                <Button
                                    v-if="pageNum !== '...'"
                                    :variant="pageNum === currentPage ? 'default' : 'outline'"
                                    size="sm"
                                    @click="goToPage(pageNum as number)"
                                >
                                    {{ pageNum }}
                                </Button>
                                <span v-else class="px-2 py-1 text-sm text-muted-foreground">...</span>
                            </template>
                            <Button 
                                v-if="movementsPagination.next_page_url" 
                                variant="outline" 
                                size="sm"
                                @click="goToPage(currentPage + 1)"
                            >
                                Next
                            </Button>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </AppLayout>
</template>
