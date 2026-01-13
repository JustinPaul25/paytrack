<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch, watchEffect } from 'vue';
import { Button } from '@/components/ui/button';
import { Select } from '@/components/ui/select';
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
    reference_number: string;
    customer: { id: number; name: string; company_name?: string };
    user: { id: number; name: string };
    total_amount: number;
    status: string;
    payment_method: string;
    payment_status: string;
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
    pendingPaymentInvoices: number;
}

const page = usePage();
const userRoles = Array.isArray((page.props as any).auth?.userRoles) ? (page.props as any).auth.userRoles : [];
const isCustomer = userRoles.includes('Customer');
const isAdmin = userRoles.includes('Admin');
const isStaff = userRoles.includes('Staff');
const canCreateInvoice = isStaff && !isAdmin; // Only Staff (not Admin) can create invoices
const canEditInvoice = isStaff && !isAdmin; // Only Staff (not Admin) can edit invoices
const canDeleteInvoice = isStaff && !isAdmin; // Only Staff (not Admin) can delete invoices
const filters = ref<{ search?: string; status?: string; payment_status?: string }>(
    page.props.filters ? (page.props.filters as { search?: string; status?: string; payment_status?: string }) : {}
);
const search = ref(typeof filters.value.search === 'string' ? filters.value.search : '');
const status = ref(typeof filters.value.status === 'string' ? filters.value.status : '');
const paymentStatus = ref(typeof filters.value.payment_status === 'string' ? filters.value.payment_status : '');
const showStats = ref(false);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Invoices', href: '/invoices' },
];

watchEffect(() => {
    search.value = (page.props.filters && typeof (page.props.filters as { search?: string }).search === 'string')
        ? (page.props.filters as { search?: string }).search!
        : '';
    status.value = (page.props.filters && typeof (page.props.filters as { status?: string }).status === 'string')
        ? (page.props.filters as { status?: string }).status!
        : '';
    paymentStatus.value = (page.props.filters && typeof (page.props.filters as { payment_status?: string }).payment_status === 'string')
        ? (page.props.filters as { payment_status?: string }).payment_status!
        : '';
});

// Status filter options
const statusOptions = [
    { value: '', label: 'All Statuses' },
    { value: 'draft', label: 'Draft' },
    { value: 'pending', label: 'Pending' },
    { value: 'completed', label: 'Completed' },
    { value: 'cancelled', label: 'Cancelled' }
];

// Payment status filter options
const paymentStatusOptions = [
    { value: '', label: 'All Payment Status' },
    { value: 'pending', label: 'Pending Payment' },
    { value: 'paid', label: 'Paid' },
    { value: 'Cancelled Order', label: 'Cancelled Order' }
];

let searchTimeout: ReturnType<typeof setTimeout> | null = null;
watch(search, (val) => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/invoices', {
            search: val || undefined,
            status: status.value || undefined,
            payment_status: paymentStatus.value || undefined,
        }, { preserveState: true, replace: true });
    }, 400);
});

watch(status, (val) => {
    router.get('/invoices', {
        search: search.value || undefined,
        status: val || undefined,
        payment_status: paymentStatus.value || undefined,
    }, { preserveState: true, replace: true });
});

watch(paymentStatus, (val) => {
    router.get('/invoices', {
        search: search.value || undefined,
        status: status.value || undefined,
        payment_status: val || undefined,
    }, { preserveState: true, replace: true });
});

function goToPage(pageNum: number) {
    router.get('/invoices', {
        search: search.value || undefined,
        status: status.value || undefined,
        payment_status: paymentStatus.value || undefined,
        page: pageNum,
    }, { preserveState: true, replace: true });
}

async function deleteInvoice(id: number) {
    const result = await Swal.fire({
        title: 'Delete this invoice?',
        text: 'This will remove the invoice permanently. You can’t undo this.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#8f5be8',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it',
    });
    if (result.isConfirmed) {
        router.delete(`/invoices/${id}`, {
            onSuccess: () => {
                Swal.fire('Deleted', 'The invoice was deleted.', 'success');
            },
        });
    }
}

function markAsPaid(id: number) {
    router.post(route('invoices.markPaid', id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Invoice marked as paid',
                showConfirmButton: false,
                timer: 2000,
            });
        },
    });
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

function getPaymentStatusBadgeClass(paymentStatus: string) {
    switch (paymentStatus) {
        case 'paid': return 'bg-emerald-100 text-emerald-800';
        case 'pending': return 'bg-orange-100 text-orange-800';
        case 'Cancelled Order': return 'bg-red-100 text-red-800';
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
        <Head title="Invoices" />
        
        <!-- Enhanced Invoice Stats Widget -->
        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 transform -translate-y-4"
            enter-to-class="opacity-100 transform translate-y-0"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 transform translate-y-0"
            leave-to-class="opacity-0 transform -translate-y-4"
        >
            <div v-show="showStats" class="grid grid-cols-1 lg:grid-cols-5 gap-6 mt-8 mb-6">
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

            <!-- Pending Payment Invoices -->
            <Card class="relative overflow-hidden">
                <CardContent class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Pending Payment</p>
                            <p class="text-xl font-bold text-orange-600">{{ (page.props.stats as InvoiceStats).pendingPaymentInvoices }}</p>
                        </div>
                        <div class="h-12 w-12 rounded-full bg-orange-100 flex items-center justify-center">
                            <svg class="h-6 w-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
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
        </Transition>

        <!-- Search and Actions -->
        <div class="flex items-center justify-between mt-4 mb-2">
            <div class="flex gap-2 items-center">
                <input 
                    v-model="search" 
                    type="text" 
                    placeholder="Search by customer name" 
                    class="rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2" 
                />
                <div class="w-48">
                    <Select
                        v-model="status"
                        :options="statusOptions"
                        placeholder="Filter by status"
                    />
                </div>
                <div class="w-48">
                    <Select
                        v-model="paymentStatus"
                        :options="paymentStatusOptions"
                        placeholder="Filter by payment"
                    />
                </div>
            </div>
            <div class="flex items-center gap-2">
            <Link v-if="canCreateInvoice" :href="route('invoices.create')">
                <Button variant="default">
                    <span class="mr-2">+</span>
                    Add New Invoice
                </Button>
            </Link>
            </div>
        </div>


        <Card>
            <CardContent>
                <div v-if="(page.props.invoices as Paginated<Invoice>).data.length === 0">
                    <!-- If filters are applied, show filtered empty state -->
                    <div v-if="(search && search.trim().length) || (status && status.trim().length) || (paymentStatus && paymentStatus.trim().length)" class="py-12 text-center">
                        <div class="text-xl font-semibold mb-2">No results found</div>
                        <p class="text-sm text-gray-600 mb-6">
                            We couldn't find any invoices matching your search/filter.
                        </p>
                        <div class="flex items-center justify-center gap-2">
                            <Button variant="outline" @click="search = ''; status = ''; paymentStatus = ''">Clear search</Button>
                            <Link v-if="canCreateInvoice" :href="route('invoices.create')">
                                <Button variant="default">
                                    <span class="mr-2">+</span>
                                    Create Invoice
                                </Button>
                            </Link>
                        </div>
                    </div>
                    <!-- Otherwise, show empty state for first use -->
                    <div v-else class="py-12 text-center">
                        <div class="text-xl font-semibold mb-2">No invoices yet</div>
                        <p class="text-sm text-gray-600 mb-6" v-if="canCreateInvoice">Create your first invoice to get started.</p>
                        <Link v-if="canCreateInvoice" :href="route('invoices.create')">
                            <Button variant="default">
                                <span class="mr-2">+</span>
                                Create Invoice
                            </Button>
                        </Link>
                    </div>
                </div>
                <div v-else>
                    <table class="min-w-full divide-y divide-border">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 text-left">Reference #</th>
                                <th class="px-4 py-2 text-left">Customer</th>
                                <th class="px-4 py-2 text-left">Total</th>
                                <th class="px-4 py-2 text-left">Status</th>
                                <th class="px-4 py-2 text-left">Payment Status</th>
                                <th class="px-4 py-2 text-left">Payment Method</th>
                                <th class="px-4 py-2 text-left">Date</th>
                                <th class="px-4 py-2 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="invoice in (page.props.invoices as Paginated<Invoice>).data" :key="invoice.id" class="hover:bg-muted">
                                <td class="px-4 py-2 font-medium">
                                    <Link :href="route('invoices.show', invoice.id)" class="underline underline-offset-4">
                                        {{ invoice.reference_number }}
                                    </Link>
                                </td>
                                <td class="px-4 py-2">
                                    <div>
                                        <div class="font-medium">{{ invoice.customer.name }}</div>
                                        <div v-if="invoice.customer.company_name" class="text-sm text-gray-500">{{ invoice.customer.company_name }}</div>
                                    </div>
                                </td>
                                <td class="px-4 py-2 font-medium">{{ formatCurrency(invoice.total_amount) }}</td>
                                <td class="px-4 py-2">
                                    <span :class="['px-2 py-1 rounded-full text-xs font-medium', getStatusBadgeClass(invoice.status)]">
                                        {{ invoice.status.charAt(0).toUpperCase() + invoice.status.slice(1) }}
                                    </span>
                                </td>
                                <td class="px-4 py-2">
                                    <span :class="['px-2 py-1 rounded-full text-xs font-medium', getPaymentStatusBadgeClass(invoice.payment_status)]">
                                        {{ invoice.payment_status === 'paid' ? 'Paid' : invoice.payment_status === 'Cancelled Order' ? 'Cancelled Order' : 'Pending Payment' }}
                                    </span>
                                </td>
                                <td class="px-4 py-2">
                                    <span class="text-sm">{{ invoice.payment_method.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) }}</span>
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ formatDateFriendly(invoice.created_at) }}</td>
                                <td class="px-4 py-2">
                                    <div class="flex gap-2">
                                        <Link :href="route('invoices.show', invoice.id)">
                                            <Button variant="ghost" size="sm" title="View">
                                                <Icon name="eye" class="h-4 w-4" />
                                            </Button>
                                        </Link>
                                        <Link v-if="canEditInvoice" :href="route('invoices.edit', invoice.id)">
                                            <Button variant="ghost" size="sm" title="Edit">
                                                <Icon name="edit" class="h-4 w-4" />
                                            </Button>
                                        </Link>
                                        <Button v-if="canEditInvoice && invoice.payment_status === 'pending' && invoice.status !== 'cancelled'" variant="ghost" size="sm" @click="markAsPaid(invoice.id)" title="Mark as Paid">
                                            <Icon name="check" class="h-4 w-4" />
                                        </Button>
                                        <Button v-if="canDeleteInvoice" variant="ghost" size="sm" @click="deleteInvoice(invoice.id)" title="Delete">
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