<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch, watchEffect, computed } from 'vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import Icon from '@/components/Icon.vue';
import Swal from 'sweetalert2';
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

interface Customer {
    id: number;
    name: string;
    company_name?: string;
    email: string;
    phone?: string;
    address?: string;
    location?: { lat: number; lng: number };
    updated_at?: string;
    verified_at?: string | null;
}

interface CustomerStats {
    totalCustomers: number;
    customersWithCompany: number;
    customersWithPhone: number;
    recentlyAdded: number;
    pendingApprovals?: number;
}

const page = usePage();
const filters = ref<{ search?: string; pending?: boolean }>(page.props.filters ? (page.props.filters as { search?: string; pending?: boolean }) : {});
const search = ref(typeof filters.value.search === 'string' ? filters.value.search : '');
const showPending = ref(typeof filters.value.pending === 'boolean' ? filters.value.pending : false);
const showStats = ref(false);

// Check user role
const isAdmin = computed(() => Array.isArray((page.props as any).auth?.userRoles) && 
    (page.props as any).auth.userRoles.includes('Admin'));
const isStaff = computed(() => Array.isArray((page.props as any).auth?.userRoles) && 
    (page.props as any).auth.userRoles.includes('Staff') && !isAdmin.value);
const canModifyCustomers = computed(() => isAdmin.value); // Only admins can modify customers

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Customers', href: '/customers' },
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
        const next = (val ?? '').toString().trim();
        const current = (page.props.filters && typeof (page.props.filters as { search?: string }).search === 'string')
            ? (page.props.filters as { search?: string }).search!
            : '';
        // Avoid redundant navigation if value didn't effectively change
        if (next === current) return;
        router.get('/customers', { 
            search: next || undefined, 
            pending: showPending.value || undefined 
        }, { preserveState: true, replace: true });
    }, 400);
});

watch(showPending, (val) => {
    router.get('/customers', { 
        search: search.value || undefined, 
        pending: val || undefined 
    }, { preserveState: true, replace: true });
});

function goToPage(pageNum: number) {
    router.get('/customers', { 
        search: search.value, 
        pending: showPending.value || undefined,
        page: pageNum 
    }, { preserveState: true, replace: true });
}

async function approveCustomer(customer: Customer) {
    const result = await Swal.fire({
        title: 'Verify Customer?',
        text: `Are you sure you want to verify ${customer.name}? They will receive an email notification and be able to log in.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#8f5be8',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, verify',
        cancelButtonText: 'Cancel',
    });
    
    if (result.isConfirmed) {
        router.post(`/customers/${customer.id}/approve`, {}, {
            onSuccess: () => {
                Swal.fire('Customer Verified', 'The customer has been verified and notified via email.', 'success');
            },
            onError: () => {
                const flash = (page.props as unknown as { flash?: { error?: string } }).flash;
                const message = flash?.error ?? 'Failed to verify customer.';
                Swal.fire('Error', message, 'error');
            },
        });
    }
}

// Numbered pagination helpers
const customersPagination = computed(() => (page.props.customers as Paginated<Customer>));
const currentPage = computed(() => customersPagination.value.current_page);
const lastPage = computed(() => customersPagination.value.last_page);
const pageNumbers = computed<(number | string)[]>(() => {
    const total = lastPage.value || 1;
    const current = currentPage.value || 1;
    const delta = 2;
    const range: (number | string)[] = [];
    const start = Math.max(1, current - delta);
    const end = Math.min(total, current + delta);

    for (let i = start; i <= end; i++) range.push(i);

    if (start > 1) {
        // Ensure first page is visible
        if (start > 2) {
            range.unshift('...');
        }
        range.unshift(1);
    }
    if (end < total) {
        // Ensure last page is visible
        if (end < total - 1) {
            range.push('...');
        }
        range.push(total);
    }
    return range;
});

async function deleteCustomer(id: number) {
    const result = await Swal.fire({
        title: 'Delete Customer?',
        text: 'Are you sure you want to delete this customer? This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#8f5be8',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete customer',
    });
    if (result.isConfirmed) {
        router.delete(`/customers/${id}`, {
            onSuccess: () => {
                const flash = (page.props as unknown as { flash?: { error?: string; success?: string } }).flash;
                if (flash?.error) {
                    Swal.fire({
                        title: 'Cannot delete',
                        text: flash.error,
                        icon: 'error',
                        confirmButtonText: 'View related deliveries',
                        showCancelButton: true,
                        cancelButtonText: 'Close',
                        confirmButtonColor: '#8f5be8',
                    }).then((choice) => {
                        if (choice.isConfirmed) {
                            router.get('/deliveries', { customer_id: id }, { preserveState: true, replace: true });
                        }
                    });
                    return;
                }
                Swal.fire('Customer deleted', 'Customer deleted successfully.', 'success');
            },
            onError: () => {
                const flash = (page.props as unknown as { flash?: { error?: string } }).flash;
                const message = flash?.error ?? 'Cannot delete this customer because there are related records.';
                Swal.fire({
                    title: 'Cannot delete',
                    text: message,
                    icon: 'error',
                    confirmButtonText: 'View related deliveries',
                    showCancelButton: true,
                    cancelButtonText: 'Close',
                    confirmButtonColor: '#8f5be8',
                }).then((choice) => {
                    if (choice.isConfirmed) {
                        router.get('/deliveries', { customer_id: id }, { preserveState: true, replace: true });
                    }
                });
            },
        });
    }
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Customers" />
        
        <!-- Enhanced Customer Stats Widget -->
        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 transform -translate-y-4"
            enter-to-class="opacity-100 transform translate-y-0"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 transform translate-y-0"
            leave-to-class="opacity-0 transform -translate-y-4"
        >
            <div v-show="showStats" class="grid grid-cols-1 lg:grid-cols-4 gap-6 mt-8 mb-6">
                <!-- Total Customers -->
                <Card class="relative overflow-hidden">
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Total Customers</p>
                                <p class="text-xl font-bold text-blue-600">{{ (page.props.stats as CustomerStats)?.totalCustomers || 0 }}</p>
                            </div>
                            <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center">
                                <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- With Company -->
                <Card class="relative overflow-hidden">
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">With Company</p>
                                <p class="text-xl font-bold text-green-600">{{ (page.props.stats as CustomerStats)?.customersWithCompany || 0 }}</p>
                            </div>
                            <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center">
                                <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- With Phone -->
                <Card class="relative overflow-hidden">
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">With Phone</p>
                                <p class="text-xl font-bold text-yellow-600">{{ (page.props.stats as CustomerStats)?.customersWithPhone || 0 }}</p>
                            </div>
                            <div class="h-12 w-12 rounded-full bg-yellow-100 flex items-center justify-center">
                                <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                </svg>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Recently Added -->
                <Card class="relative overflow-hidden">
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Recently Added</p>
                                <p class="text-xl font-bold text-emerald-600">{{ (page.props.stats as CustomerStats)?.recentlyAdded || 0 }}</p>
                            </div>
                            <div class="h-12 w-12 rounded-full bg-emerald-100 flex items-center justify-center">
                                <svg class="h-6 w-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Pending Approvals (Admin only) -->
                <Card v-if="isAdmin" class="relative overflow-hidden">
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Pending Approvals</p>
                                <p class="text-xl font-bold text-orange-600">{{ (page.props.stats as CustomerStats)?.pendingApprovals || 0 }}</p>
                            </div>
                            <div class="h-12 w-12 rounded-full bg-orange-100 flex items-center justify-center">
                                <svg class="h-6 w-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
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
                    placeholder="Search customers by name, company, or email..." 
                    class="rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2" 
                />
                <Button 
                    v-if="isAdmin" 
                    :variant="showPending ? 'default' : 'outline'"
                    @click="showPending = !showPending"
                >
                    <Icon name="Clock" class="h-4 w-4 mr-2" />
                    Pending Approvals
                    <span v-if="(page.props.stats as CustomerStats)?.pendingApprovals" class="ml-2 bg-white/20 px-2 py-0.5 rounded-full text-xs">
                        {{ (page.props.stats as CustomerStats)?.pendingApprovals }}
                    </span>
                </Button>
            </div>
            <div class="flex gap-2">
                <Link :href="route('customers.logs.index')">
                    <Button variant="outline">
                        <Icon name="file-text" class="h-4 w-4 mr-2" />
                        View All Logs
                    </Button>
                </Link>
                <Link v-if="canModifyCustomers" :href="route('customers.create')">
                    <Button variant="default">
                        <span class="mr-2">+</span>
                        Add New Customer
                    </Button>
                </Link>
            </div>
        </div>

        <Card>
            <CardContent>
				<!-- Empty State: No customers at all -->
				<div v-if="(page.props.customers as Paginated<Customer>).total === 0" class="py-12 text-center">
                    <div class="mx-auto max-w-md">
                        <h3 class="text-lg font-semibold mb-2">No customers yet</h3>
                        <p class="text-sm text-muted-foreground mb-6">
                            Customers you add will appear here. You can create invoices faster when customers are saved.
                        </p>
                        <Link v-if="canModifyCustomers" :href="route('customers.create')">
                            <Button variant="default">
                                Add your first customer
                            </Button>
                        </Link>
                    </div>
                </div>

				<!-- Empty State: No results due to search/filter -->
				<div
					v-else-if="(page.props.customers as Paginated<Customer>).data.length === 0 && (search && search.toString().trim().length)"
					class="py-12 text-center"
				>
					<div class="mx-auto max-w-md">
						<h3 class="text-lg font-semibold mb-2">No results found</h3>
						<p class="text-sm text-muted-foreground mb-6">
							We couldn't find any customers matching your search.
						</p>
						<Button variant="outline" @click="search = ''">
							Clear search
						</Button>
					</div>
				</div>

                <!-- Table -->
				<table v-else class="min-w-full divide-y divide-border">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left">Name</th>
                            <th class="px-4 py-2 text-left">Company</th>
                            <th class="px-4 py-2 text-left">Email</th>
                            <th class="px-4 py-2 text-left">Phone</th>
                            <th v-if="isAdmin" class="px-4 py-2 text-left">Status</th>
                            <th class="px-4 py-2 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="customer in (page.props.customers as Paginated<Customer>).data" :key="customer.id" class="hover:bg-muted">
							<td class="px-4 py-2 font-medium">
								<Link v-if="canModifyCustomers" :href="route('customers.edit', customer.id)" class="underline underline-offset-4">
									{{ customer.name }}
								</Link>
								<span v-else>{{ customer.name }}</span>
							</td>
                            <td class="px-4 py-2">{{ customer.company_name || '-' }}</td>
                            <td class="px-4 py-2">{{ customer.email }}</td>
                            <td class="px-4 py-2">{{ customer.phone || '-' }}</td>
                            <td v-if="isAdmin" class="px-4 py-2">
                                <span v-if="!customer.verified_at" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                    Pending Verification
                                </span>
                                <span v-else class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Verified
                                </span>
                            </td>
                            <td class="px-4 py-2">
                                <div class="flex gap-2 items-center">
                                    <Link :href="route('customers.logs.show', customer.id)">
                                        <Button variant="ghost" size="sm" title="View Logs">
                                            <Icon name="file-text" class="h-4 w-4" />
                                        </Button>
                                    </Link>
                                    <div v-if="canModifyCustomers" class="flex gap-2">
                                        <Button 
                                            v-if="!customer.verified_at" 
                                            variant="default" 
                                            size="sm" 
                                            @click="approveCustomer(customer)"
                                            class="bg-green-600 hover:bg-green-700"
                                        >
                                            <Icon name="Check" class="h-4 w-4 mr-1" />
                                            Verify
                                        </Button>
                                        <Link :href="route('customers.edit', customer.id)">
                                            <Button variant="ghost" size="sm">
                                                <Icon name="edit" class="h-4 w-4" />
                                            </Button>
                                        </Link>
                                        <Button variant="ghost" size="sm" @click="deleteCustomer(customer.id)">
                                            <Icon name="trash" class="h-4 w-4" />
                                        </Button>
                                    </div>
                                    <span v-else class="text-sm text-muted-foreground">View only</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="(page.props.customers as Paginated<Customer>).last_page > 1" class="flex items-center justify-between mt-6">
                    <div class="text-sm text-gray-700">
                        Showing {{ (page.props.customers as Paginated<Customer>).from }} to {{ (page.props.customers as Paginated<Customer>).to }} of {{ (page.props.customers as Paginated<Customer>).total }} results
                    </div>
                    <div class="flex items-center gap-1">
                        <Button
                            v-if="(page.props.customers as Paginated<Customer>).prev_page_url"
                            variant="outline"
                            size="sm"
                            @click="goToPage((page.props.customers as Paginated<Customer>).current_page - 1)"
                        >
                            Previous
                        </Button>

                        <!-- Numbered pages -->
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
                            v-if="(page.props.customers as Paginated<Customer>).next_page_url"
                            variant="outline"
                            size="sm"
                            @click="goToPage((page.props.customers as Paginated<Customer>).current_page - 0 + 1)"
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