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

interface Invoice {
    id: number;
    customer: { id: number; name: string; company_name?: string };
    user: { id: number; name: string };
    total_amount: number;
    status: string;
    payment_method: string;
    payment_reference?: string;
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

interface InvoiceStats {
    totalInvoices: number;
    totalAmount: number;
    pendingInvoices: number;
    paidInvoices: number;
}

const page = usePage();
const filters = ref<{ search?: string }>(page.props.filters ? (page.props.filters as { search?: string }) : {});
const search = ref(typeof filters.value.search === 'string' ? filters.value.search : '');

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Invoices', href: '/invoices' },
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
        router.get('/invoices', { search: val }, { preserveState: true, replace: true });
    }, 400);
});

function goToPage(pageNum: number) {
    router.get('/invoices', { search: search.value, page: pageNum }, { preserveState: true, replace: true });
}

async function deleteInvoice(id: number) {
    const result = await Swal.fire({
        title: 'Delete Invoice?',
        text: 'Are you sure you want to delete this invoice? This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#8f5be8',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete invoice',
    });
    if (result.isConfirmed) {
        router.delete(`/invoices/${id}`, {
            onSuccess: () => {
                Swal.fire('Invoice deleted', 'Invoice deleted successfully.', 'success');
            },
        });
    }
}

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
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Invoices" />
        
        <!-- Search and Actions Bar -->
        <Card class="my-6">
            <CardContent class="p-4">
                <div class="flex flex-col sm:flex-row gap-4 items-center justify-between">
                    <div class="flex-1 max-w-md">
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            <input 
                                v-model="search" 
                                type="text" 
                                placeholder="Search invoices by customer name..." 
                                class="w-full pl-10 pr-4 py-2 rounded-lg border border-input bg-background text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2" 
                            />
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <Link :href="route('invoices.create')">
                            <Button variant="default" class="flex items-center gap-2">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Create Invoice
                            </Button>
                        </Link>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Enhanced Invoice Stats Widget -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Invoices -->
            <Card class="relative overflow-hidden">
                <CardContent class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Total Invoices</p>
                            <p class="text-xl font-bold text-blue-600">{{ (page.props.stats as InvoiceStats).totalInvoices }}</p>
                        </div>
                        <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center">
                            <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
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
                            <p class="text-xl font-bold text-green-600">{{ formatCurrency((page.props.stats as InvoiceStats).totalAmount) }}</p>
                        </div>
                        <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center">
                            <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Pending Invoices -->
            <Card class="relative overflow-hidden">
                <CardContent class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Pending</p>
                            <p class="text-xl font-bold text-yellow-600">{{ (page.props.stats as InvoiceStats).pendingInvoices }}</p>
                        </div>
                        <div class="h-12 w-12 rounded-full bg-yellow-100 flex items-center justify-center">
                            <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Paid Invoices -->
            <Card class="relative overflow-hidden">
                <CardContent class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Paid</p>
                            <p class="text-xl font-bold text-emerald-600">{{ (page.props.stats as InvoiceStats).paidInvoices }}</p>
                        </div>
                        <div class="h-12 w-12 rounded-full bg-emerald-100 flex items-center justify-center">
                            <svg class="h-6 w-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                            <th class="px-4 py-2 text-left">Invoice #</th>
                            <th class="px-4 py-2 text-left">Customer</th>
                            <th class="px-4 py-2 text-left">Total Amount</th>
                            <th class="px-4 py-2 text-left">Status</th>
                            <th class="px-4 py-2 text-left">Payment Method</th>
                            <th class="px-4 py-2 text-left">Created</th>
                            <th class="px-4 py-2 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="invoice in (page.props.invoices as Paginated<Invoice>).data" :key="invoice.id" class="hover:bg-muted">
                            <td class="px-4 py-2 font-medium">#{{ invoice.id }}</td>
                            <td class="px-4 py-2">
                                <div>
                                    <div class="font-medium">{{ invoice.customer.name }}</div>
                                    <div v-if="invoice.customer.company_name" class="text-sm text-gray-500">{{ invoice.customer.company_name }}</div>
                                </div>
                            </td>
                            <td class="px-4 py-2 font-medium">{{ formatCurrency(invoice.total_amount) }}</td>
                            <td class="px-4 py-2">
                                <span :class="['px-2 py-1 rounded-full text-xs font-medium', getStatusBadgeClass(invoice.status)]">
                                    {{ invoice.status }}
                                </span>
                            </td>
                            <td class="px-4 py-2">{{ invoice.payment_method.replace('_', ' ') }}</td>
                            <td class="px-4 py-2 text-sm text-gray-500">{{ new Date(invoice.created_at).toLocaleDateString() }}</td>
                            <td class="px-4 py-2">
                                <div class="flex gap-2">
                                    <Link :href="route('invoices.show', invoice.id)">
                                        <Button variant="ghost" size="sm">
                                            <Icon name="eye" class="h-4 w-4" />
                                        </Button>
                                    </Link>
                                    <Link :href="route('invoices.edit', invoice.id)">
                                        <Button variant="ghost" size="sm">
                                            <Icon name="edit" class="h-4 w-4" />
                                        </Button>
                                    </Link>
                                    <Button variant="ghost" size="sm" @click="deleteInvoice(invoice.id)">
                                        <Icon name="trash" class="h-4 w-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="(page.props.invoices as Paginated<Invoice>).last_page > 1" class="flex items-center justify-between mt-6">
                    <div class="text-sm text-gray-700">
                        Showing {{ (page.props.invoices as Paginated<Invoice>).from }} to {{ (page.props.invoices as Paginated<Invoice>).to }} of {{ (page.props.invoices as Paginated<Invoice>).total }} results
                    </div>
                    <div class="flex gap-2">
                        <Button 
                            v-if="(page.props.invoices as Paginated<Invoice>).prev_page_url" 
                            variant="outline" 
                            size="sm"
                            @click="goToPage((page.props.invoices as Paginated<Invoice>).current_page - 1)"
                        >
                            Previous
                        </Button>
                        <Button 
                            v-if="(page.props.invoices as Paginated<Invoice>).next_page_url" 
                            variant="outline" 
                            size="sm"
                            @click="goToPage((page.props.invoices as Paginated<Invoice>).current_page + 1)"
                        >
                            Next
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>
    </AppLayout>
</template> 