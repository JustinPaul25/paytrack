<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
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

interface CustomerLog {
    id: number;
    customer_id: number;
    user_id: number | null;
    action: string;
    description: string;
    changes: Record<string, { old: any; new: any }> | null;
    ip_address: string | null;
    user_agent: string | null;
    created_at: string;
    customer?: {
        id: number;
        name: string;
        email: string;
    };
    user?: {
        id: number;
        name: string;
    } | null;
}

interface Customer {
    id: number;
    name: string;
    email: string;
}

const page = usePage();
const filters = ref<{ search?: string; customer_id?: number; action?: string }>(
    page.props.filters ? (page.props.filters as { search?: string; customer_id?: number; action?: string }) : {}
);
const search = ref(typeof filters.value.search === 'string' ? filters.value.search : '');
const selectedAction = ref(typeof filters.value.action === 'string' ? filters.value.action : '');
const customer = computed(() => (page.props.customer as Customer | undefined));
const actions = computed(() => (page.props.actions as string[]) || []);

const breadcrumbs: BreadcrumbItem[] = customer.value
    ? [
          { title: 'Customers', href: '/customers' },
          { title: customer.value.name, href: `/customers/${customer.value.id}` },
          { title: 'Logs', href: '#' },
      ]
    : [
          { title: 'Customers', href: '/customers' },
          { title: 'Logs', href: '/customers/logs' },
      ];

let searchTimeout: ReturnType<typeof setTimeout> | null = null;
watch(search, (val) => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        updateFilters();
    }, 400);
});

watch(selectedAction, () => {
    updateFilters();
});

function updateFilters() {
    const params: any = {};
    if (search.value) params.search = search.value;
    if (selectedAction.value) params.action = selectedAction.value;
    if (customer.value) {
        router.get(`/customers/${customer.value.id}/logs`, params, { preserveState: true, replace: true });
    } else {
        router.get('/customers/logs', params, { preserveState: true, replace: true });
    }
}

function goToPage(pageNum: number) {
    const params: any = { page: pageNum };
    if (search.value) params.search = search.value;
    if (selectedAction.value) params.action = selectedAction.value;
    
    if (customer.value) {
        router.get(`/customers/${customer.value.id}/logs`, params, { preserveState: true, replace: true });
    } else {
        router.get('/customers/logs', params, { preserveState: true, replace: true });
    }
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

function getActionBadgeClass(action: string): string {
    switch (action.toLowerCase()) {
        case 'created':
            return 'bg-green-100 text-green-800';
        case 'updated':
            return 'bg-blue-100 text-blue-800';
        case 'verified':
            return 'bg-purple-100 text-purple-800';
        case 'deleted':
            return 'bg-red-100 text-red-800';
        case 'registered':
            return 'bg-yellow-100 text-yellow-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}

function formatChanges(changes: Record<string, { old: any; new: any }> | null): string {
    if (!changes || Object.keys(changes).length === 0) return '';
    
    return Object.entries(changes)
        .map(([key, value]) => {
            const formattedKey = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
            return `${formattedKey}: "${value.old}" → "${value.new}"`;
        })
        .join(', ');
}

const logsPagination = computed(() => (page.props.logs as Paginated<CustomerLog>));
const currentPage = computed(() => logsPagination.value?.current_page || 1);
const lastPage = computed(() => logsPagination.value?.last_page || 1);

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
        <Head :title="customer ? `${customer.name} - Logs` : 'Customer Logs'" />
        
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold">
                    {{ customer ? `${customer.name} - Activity Logs` : 'Customer Activity Logs' }}
                </h1>
                <p class="text-sm text-muted-foreground mt-1">
                    Track all customer-related activities and changes
                </p>
            </div>
            <Link v-if="!customer" :href="route('customers.index')">
                <Button variant="outline">
                    <Icon name="arrow-left" class="h-4 w-4 mr-2" />
                    Back to Customers
                </Button>
            </Link>
            <Link v-else :href="route('customers.edit', customer.id)">
                <Button variant="outline">
                    <Icon name="arrow-left" class="h-4 w-4 mr-2" />
                    Back to Customer
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
                            placeholder="Search by description, customer name, or user..." 
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2" 
                        />
                    </div>
                    <div class="w-48">
                        <label class="block text-sm font-medium mb-1">Action</label>
                        <select 
                            v-model="selectedAction"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        >
                            <option value="">All Actions</option>
                            <option v-for="action in actions" :key="action" :value="action">
                                {{ action.charAt(0).toUpperCase() + action.slice(1) }}
                            </option>
                        </select>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Logs Table -->
        <Card>
            <CardContent class="p-0">
                <!-- Empty State -->
                <div v-if="logsPagination.total === 0" class="py-12 text-center">
                    <div class="mx-auto max-w-md">
                        <h3 class="text-lg font-semibold mb-2">No logs found</h3>
                        <p class="text-sm text-muted-foreground">
                            {{ search || selectedAction ? 'No logs match your filters.' : 'No customer activity logs have been recorded yet.' }}
                        </p>
                    </div>
                </div>

                <!-- Table -->
                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Date & Time</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Action</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Description</th>
                                <th v-if="!customer" class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Customer</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Performed By</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">IP Address</th>
                            </tr>
                        </thead>
                        <tbody class="bg-background divide-y divide-border">
                            <tr v-for="log in logsPagination.data" :key="log.id" class="hover:bg-muted/50">
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-muted-foreground">
                                    {{ formatDate(log.created_at) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span 
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                        :class="getActionBadgeClass(log.action)"
                                    >
                                        {{ log.action.charAt(0).toUpperCase() + log.action.slice(1) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <div>{{ log.description }}</div>
                                    <div v-if="log.changes && Object.keys(log.changes).length > 0" class="mt-1 text-xs text-muted-foreground">
                                        <strong>Changes:</strong> {{ formatChanges(log.changes) }}
                                    </div>
                                </td>
                                <td v-if="!customer" class="px-4 py-3 whitespace-nowrap text-sm">
                                    <Link 
                                        v-if="log.customer" 
                                        :href="route('customers.edit', log.customer.id)" 
                                        class="text-primary hover:underline"
                                    >
                                        {{ log.customer.name }}
                                    </Link>
                                    <span v-else class="text-muted-foreground">N/A</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm">
                                    <span v-if="log.user">{{ log.user.name }}</span>
                                    <span v-else class="text-muted-foreground italic">System / Self-registration</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-muted-foreground">
                                    {{ log.ip_address || 'N/A' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="logsPagination.last_page > 1" class="flex items-center justify-between px-4 py-4 border-t border-border">
                    <div class="text-sm text-muted-foreground">
                        Showing {{ logsPagination.from }} to {{ logsPagination.to }} of {{ logsPagination.total }} results
                    </div>
                    <div class="flex items-center gap-1">
                        <Button
                            v-if="logsPagination.prev_page_url"
                            variant="outline"
                            size="sm"
                            @click="goToPage(currentPage - 1)"
                        >
                            Previous
                        </Button>

                        <template v-for="n in pageNumbers" :key="'p-' + n">
                            <Button
                                v-if="typeof n === 'number'"
                                :variant="n === currentPage ? 'default' : 'outline'"
                                size="sm"
                                @click="n !== currentPage && goToPage(n)"
                            >
                                {{ n }}
                            </Button>
                            <span v-else class="px-2 text-sm text-muted-foreground">…</span>
                        </template>

                        <Button
                            v-if="logsPagination.next_page_url"
                            variant="outline"
                            size="sm"
                            @click="goToPage(currentPage + 1)"
                        >
                            Next
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>
    </AppLayout>
</template>
