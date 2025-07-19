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

interface Refund {
    id: number;
    refund_number: string;
    invoice: { id: number; customer: { id: number; name: string; company_name?: string } };
    product: { id: number; name: string };
    user: { id: number; name: string };
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

interface RefundStats {
    totalRefunds: number;
    totalAmount: number;
    pendingRefunds: number;
    completedRefunds: number;
}

const page = usePage();
const filters = ref<{ search?: string }>(page.props.filters ? (page.props.filters as { search?: string }) : {});
const search = ref(typeof filters.value.search === 'string' ? filters.value.search : '');

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Refunds', href: '/refunds' },
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
        router.get('/refunds', { search: val }, { preserveState: true, replace: true });
    }, 400);
});

function goToPage(pageNum: number) {
    router.get('/refunds', { search: search.value, page: pageNum }, { preserveState: true, replace: true });
}

async function deleteRefund(id: number) {
    const result = await Swal.fire({
        title: 'Delete Refund?',
        text: 'Are you sure you want to delete this refund? This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#8f5be8',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete refund',
    });
    if (result.isConfirmed) {
        router.delete(`/refunds/${id}`, {
            onSuccess: () => {
                Swal.fire('Refund deleted', 'Refund deleted successfully.', 'success');
            },
        });
    }
}

function getStatusBadgeClass(status: string) {
    switch (status) {
        case 'pending': return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400';
        case 'approved': return 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400';
        case 'processed': return 'bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-400';
        case 'completed': return 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400';
        case 'cancelled': return 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400';
        default: return 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400';
    }
}

function formatCurrency(amount: number) {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2
    }).format(amount);
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Refunds" />
        
        <!-- Search and Actions Bar -->
        <Card class="my-6">
            <CardContent class="p-4">
                <div class="flex flex-col sm:flex-row gap-4 items-center justify-between">
                    <div class="flex-1 max-w-md">
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            <input 
                                v-model="search" 
                                type="text" 
                                placeholder="Search refunds by refund number, customer name..." 
                                class="w-full pl-10 pr-4 py-2 rounded-lg border border-input bg-background text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2" 
                            />
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <Link :href="route('refunds.create')">
                            <Button variant="default" class="flex items-center gap-2">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Create Refund
                            </Button>
                        </Link>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Enhanced Refund Stats Widget -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Refunds -->
            <Card class="relative overflow-hidden">
                <CardContent class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Total Refunds</p>
                            <p class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ (page.props.stats as RefundStats).totalRefunds }}</p>
                        </div>
                        <div class="h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900/20 flex items-center justify-center">
                            <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                            </svg>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Total Amount -->
            <Card class="relative overflow-hidden">
                <CardContent class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Total Amount</p>
                            <p class="text-xl font-bold text-green-600 dark:text-green-400">{{ formatCurrency((page.props.stats as RefundStats).totalAmount) }}</p>
                        </div>
                        <div class="h-12 w-12 rounded-full bg-green-100 dark:bg-green-900/20 flex items-center justify-center">
                            <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Pending Refunds -->
            <Card class="relative overflow-hidden">
                <CardContent class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Pending</p>
                            <p class="text-xl font-bold text-yellow-600 dark:text-yellow-400">{{ (page.props.stats as RefundStats).pendingRefunds }}</p>
                        </div>
                        <div class="h-12 w-12 rounded-full bg-yellow-100 dark:bg-yellow-900/20 flex items-center justify-center">
                            <svg class="h-6 w-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Completed Refunds -->
            <Card class="relative overflow-hidden">
                <CardContent class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Completed</p>
                            <p class="text-xl font-bold text-emerald-600 dark:text-emerald-400">{{ (page.props.stats as RefundStats).completedRefunds }}</p>
                        </div>
                        <div class="h-12 w-12 rounded-full bg-emerald-100 dark:bg-emerald-900/20 flex items-center justify-center">
                            <svg class="h-6 w-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardContent>
                <table class="min-w-full divide-y divide-border">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left">Refund #</th>
                            <th class="px-4 py-2 text-left">Customer</th>
                            <th class="px-4 py-2 text-left">Product</th>
                            <th class="px-4 py-2 text-left">Amount</th>
                            <th class="px-4 py-2 text-left">Status</th>
                            <th class="px-4 py-2 text-left">Type</th>
                            <th class="px-4 py-2 text-left">Method</th>
                            <th class="px-4 py-2 text-left">Created</th>
                            <th class="px-4 py-2 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="refund in (page.props.refunds as Paginated<Refund>).data" :key="refund.id" class="hover:bg-muted">
                            <td class="px-4 py-2 font-medium">{{ refund.refund_number }}</td>
                            <td class="px-4 py-2">
                                <div>
                                    <div class="font-medium">{{ refund.invoice.customer.name }}</div>
                                    <div v-if="refund.invoice.customer.company_name" class="text-sm text-muted-foreground">{{ refund.invoice.customer.company_name }}</div>
                                </div>
                            </td>
                            <td class="px-4 py-2">{{ refund.product.name }}</td>
                            <td class="px-4 py-2 font-medium">{{ formatCurrency(refund.refund_amount / 100) }}</td>
                            <td class="px-4 py-2">
                                <span :class="['px-2 py-1 rounded-full text-xs font-medium', getStatusBadgeClass(refund.status)]">
                                    {{ refund.status }}
                                </span>
                            </td>
                            <td class="px-4 py-2">{{ refund.refund_type.replace('_', ' ') }}</td>
                            <td class="px-4 py-2">{{ refund.refund_method.replace('_', ' ') }}</td>
                            <td class="px-4 py-2 text-sm text-muted-foreground">{{ new Date(refund.created_at).toLocaleDateString() }}</td>
                            <td class="px-4 py-2">
                                <div class="flex gap-2">
                                    <Link :href="route('refunds.show', refund.id)">
                                        <Button variant="ghost" size="sm">
                                            <Icon name="eye" class="h-4 w-4" />
                                        </Button>
                                    </Link>
                                    <Link v-if="refund.status === 'pending'" :href="route('refunds.edit', refund.id)">
                                        <Button variant="ghost" size="sm">
                                            <Icon name="edit" class="h-4 w-4" />
                                        </Button>
                                    </Link>
                                    <Button variant="ghost" size="sm" @click="deleteRefund(refund.id)">
                                        <Icon name="trash" class="h-4 w-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="(page.props.refunds as Paginated<Refund>).last_page > 1" class="flex items-center justify-between mt-6">
                    <div class="text-sm text-muted-foreground">
                        Showing {{ (page.props.refunds as Paginated<Refund>).from }} to {{ (page.props.refunds as Paginated<Refund>).to }} of {{ (page.props.refunds as Paginated<Refund>).total }} results
                    </div>
                    <div class="flex gap-2">
                        <Button 
                            v-if="(page.props.refunds as Paginated<Refund>).prev_page_url" 
                            variant="outline" 
                            size="sm"
                            @click="goToPage((page.props.refunds as Paginated<Refund>).current_page - 1)"
                        >
                            Previous
                        </Button>
                        <Button 
                            v-if="(page.props.refunds as Paginated<Refund>).next_page_url" 
                            variant="outline" 
                            size="sm"
                            @click="goToPage((page.props.refunds as Paginated<Refund>).current_page + 1)"
                        >
                            Next
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>
    </AppLayout>
</template> 