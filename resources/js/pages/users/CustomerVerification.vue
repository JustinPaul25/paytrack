<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import Icon from '@/components/Icon.vue';
import Swal from 'sweetalert2';
import { type BreadcrumbItem } from '@/types';
import { CheckCircle2, XCircle } from 'lucide-vue-next';

interface Customer {
    id: number;
    name: string;
    company_name?: string;
    email: string;
    phone?: string;
    address?: string;
    purok?: string;
    barangay?: string;
    city_municipality?: string;
    province?: string;
    verified_at?: string;
    created_at: string;
    is_verified: boolean;
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

const page = usePage();
const filters = ref<{ search?: string; status?: string }>(
    page.props.filters ? (page.props.filters as { search?: string; status?: string }) : { status: 'pending' }
);
const search = ref(typeof filters.value.search === 'string' ? filters.value.search : '');
const status = ref(typeof filters.value.status === 'string' ? filters.value.status : 'pending');

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Users', href: '/users' },
    { title: 'Customer Verification', href: '/users/customer-verification' },
];

let searchTimeout: ReturnType<typeof setTimeout> | null = null;
watch(search, (val) => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/users/customer-verification', { search: val || undefined, status: status.value }, { preserveState: true, replace: true });
    }, 400);
});

watch(status, (val) => {
    router.get('/users/customer-verification', { search: search.value || undefined, status: val }, { preserveState: true, replace: true });
});

function goToPage(pageNum: number) {
    router.get('/users/customer-verification', { search: search.value, status: status.value, page: pageNum }, { preserveState: true, replace: true });
}

async function verifyCustomer(id: number, name: string) {
    const result = await Swal.fire({
        title: 'Verify Customer?',
        text: `Are you sure you want to verify ${name}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, verify customer',
    });
    if (result.isConfirmed) {
        router.post(`/customers/${id}/verify`, {}, {
            onSuccess: () => {
                Swal.fire('Customer Verified', 'Customer has been verified successfully.', 'success');
            },
            onError: () => {
                Swal.fire('Error', 'Failed to verify customer. Please try again.', 'error');
            },
        });
    }
}

async function unverifyCustomer(id: number, name: string) {
    const result = await Swal.fire({
        title: 'Remove Verification?',
        text: `Are you sure you want to remove verification for ${name}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, remove verification',
    });
    if (result.isConfirmed) {
        router.post(`/customers/${id}/unverify`, {}, {
            onSuccess: () => {
                Swal.fire('Verification Removed', 'Customer verification has been removed successfully.', 'success');
            },
            onError: () => {
                Swal.fire('Error', 'Failed to remove verification. Please try again.', 'error');
            },
        });
    }
}

function formatDate(dateString?: string) {
    if (!dateString) return '—';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Customer Verification" />
        
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">Customer Verification</h1>
        </div>

        <!-- Search and Status Filter -->
        <div class="flex items-center justify-between mt-4 mb-2 gap-4">
            <div class="flex gap-2 items-center flex-1">
                <input 
                    v-model="search" 
                    type="text" 
                    placeholder="Search customers by name, company, or email..." 
                    class="rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 flex-1" 
                />
            </div>
            <div class="flex gap-2">
                <Button 
                    @click="status = 'pending'" 
                    :variant="status === 'pending' ? 'default' : 'outline'"
                >
                    Pending
                </Button>
                <Button 
                    @click="status = 'verified'" 
                    :variant="status === 'verified' ? 'default' : 'outline'"
                >
                    Verified
                </Button>
                <Button 
                    @click="status = 'all'" 
                    :variant="status === 'all' ? 'default' : 'outline'"
                >
                    All
                </Button>
            </div>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>
                    <span v-if="status === 'pending'">Pending Verification</span>
                    <span v-else-if="status === 'verified'">Verified Customers</span>
                    <span v-else>All Customers</span>
                    <span class="text-sm font-normal text-muted-foreground ml-2">
                        ({{ (page.props.customers as Paginated<Customer>).total }})
                    </span>
                </CardTitle>
            </CardHeader>
            <CardContent>
                <table class="min-w-full divide-y divide-border">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left">Name</th>
                            <th class="px-4 py-2 text-left">Company</th>
                            <th class="px-4 py-2 text-left">Email</th>
                            <th class="px-4 py-2 text-left">Phone</th>
                            <th class="px-4 py-2 text-left">Status</th>
                            <th class="px-4 py-2 text-left">Verified At</th>
                            <th class="px-4 py-2 text-left">Created At</th>
                            <th class="px-4 py-2 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody v-if="(page.props.customers as Paginated<Customer>).data.length">
                        <tr v-for="customer in (page.props.customers as Paginated<Customer>).data" :key="customer.id" class="hover:bg-muted">
                            <td class="px-4 py-2 font-medium">{{ customer.name }}</td>
                            <td class="px-4 py-2 text-sm text-gray-500">{{ customer.company_name || '—' }}</td>
                            <td class="px-4 py-2 text-sm text-gray-500">{{ customer.email }}</td>
                            <td class="px-4 py-2 text-sm text-gray-500">{{ customer.phone || '—' }}</td>
                            <td class="px-4 py-2">
                                <span 
                                    :class="[
                                        'px-2 py-1 text-xs font-semibold rounded flex items-center gap-1 w-fit',
                                        customer.is_verified 
                                            ? 'bg-green-100 text-green-800' 
                                            : 'bg-yellow-100 text-yellow-800'
                                    ]"
                                >
                                    <CheckCircle2 v-if="customer.is_verified" class="h-3 w-3" />
                                    <XCircle v-else class="h-3 w-3" />
                                    {{ customer.is_verified ? 'Verified' : 'Pending' }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-500">{{ formatDate(customer.verified_at) }}</td>
                            <td class="px-4 py-2 text-sm text-gray-500">{{ formatDate(customer.created_at) }}</td>
                            <td class="px-4 py-2">
                                <div class="flex gap-2">
                                    <Button 
                                        v-if="!customer.is_verified"
                                        variant="default" 
                                        size="sm"
                                        class="bg-green-600 hover:bg-green-700"
                                        @click="verifyCustomer(customer.id, customer.name)"
                                    >
                                        <CheckCircle2 class="h-4 w-4 mr-1" />
                                        Verify
                                    </Button>
                                    <Button 
                                        v-else
                                        variant="outline" 
                                        size="sm"
                                        class="border-red-300 text-red-700 hover:bg-red-50"
                                        @click="unverifyCustomer(customer.id, customer.name)"
                                    >
                                        <XCircle class="h-4 w-4 mr-1" />
                                        Unverify
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                    <tbody v-else>
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-sm text-gray-500">
                                No customers found.
                                <button v-if="(search && search.toString().trim().length)" type="button" class="underline underline-offset-4 ml-1" @click="search = ''">
                                    Clear search
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>

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
            </CardContent>
        </Card>
    </AppLayout>
</template>

