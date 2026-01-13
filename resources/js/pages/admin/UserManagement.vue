<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
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

interface UnifiedUser {
    id: number;
    type: 'staff' | 'customer';
    name: string;
    company: string | null;
    email: string;
    user_role: string;
    phone: string | null;
    status: string;
    is_verified: boolean;
    is_archived: boolean;
    deleted_at: string | null;
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

const page = usePage();
const filters = ref<{ 
    search?: string; 
    verification_status?: string; 
    archived?: boolean 
}>(page.props.filters ? (page.props.filters as { search?: string; verification_status?: string; archived?: boolean }) : {});

const search = ref(typeof filters.value.search === 'string' ? filters.value.search : '');
const verificationStatus = ref(typeof filters.value.verification_status === 'string' ? filters.value.verification_status : '');
const archived = ref(typeof filters.value.archived === 'boolean' ? filters.value.archived : false);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '#' },
    { title: 'User Management', href: '/admin/users' },
];

let searchTimeout: ReturnType<typeof setTimeout> | null = null;
watch(search, (val) => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        updateFilters();
    }, 400);
});

watch([verificationStatus, archived], () => {
    updateFilters();
});

function updateFilters() {
    const params: any = {};
    if (search.value) params.search = search.value;
    if (verificationStatus.value) params.verification_status = verificationStatus.value;
    if (archived.value) params.archived = '1';
    
    router.get('/admin/users', params, { preserveState: true, replace: true });
}

function goToPage(pageNum: number) {
    const params: any = { page: pageNum };
    if (search.value) params.search = search.value;
    if (verificationStatus.value) params.verification_status = verificationStatus.value;
    if (archived.value) params.archived = '1';
    
    router.get('/admin/users', params, { preserveState: true, replace: true });
}

async function deleteUser(user: UnifiedUser) {
    const result = await Swal.fire({
        title: 'Delete User?',
        text: `Are you sure you want to delete this ${user.type}? This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#8f5be8',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete',
    });
    
    if (result.isConfirmed) {
        if (user.type === 'staff') {
            router.delete(`/users/${user.id}`, {
                onSuccess: () => {
                    Swal.fire('User deleted', 'User deleted successfully.', 'success');
                },
            });
        } else {
            router.delete(`/customers/${user.id}`, {
                onSuccess: () => {
                    Swal.fire('Customer deleted', 'Customer deleted successfully.', 'success');
                },
            });
        }
    }
}

function getRoleBadgeClass(role: string) {
    switch (role.toLowerCase()) {
        case 'admin': return 'bg-purple-100 text-purple-800';
        case 'staff': return 'bg-blue-100 text-blue-800';
        case 'customer': return 'bg-green-100 text-green-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

function getStatusBadgeClass(status: string) {
    switch (status.toLowerCase()) {
        case 'verified': return 'bg-green-100 text-green-800';
        case 'unverified': return 'bg-yellow-100 text-yellow-800';
        case 'active': return 'bg-blue-100 text-blue-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

function editUser(user: UnifiedUser) {
    if (user.type === 'staff') {
        router.visit(`/users/${user.id}/edit`);
    } else {
        router.visit(`/customers/${user.id}/edit?from=users`);
    }
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="User Management" />
        
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">User Management</h1>
        </div>

        <!-- Customer Logs Card -->
        <Card class="mb-4">
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Icon name="file-text" class="h-5 w-5" />
                    Customer Activity Logs
                </CardTitle>
            </CardHeader>
            <CardContent>
                <p class="text-sm text-muted-foreground mb-4">
                    View and track all customer-related activities, including registrations, updates, verifications, and more.
                </p>
                <Link :href="route('customers.logs.index')">
                    <Button variant="default">
                        <Icon name="file-text" class="h-4 w-4 mr-2" />
                        View Customer Logs
                    </Button>
                </Link>
            </CardContent>
        </Card>

        <!-- Filters -->
        <Card class="mb-4">
            <CardContent class="pt-6">
                <div class="flex flex-col gap-4">
                    <!-- Search -->
                    <div>
                        <label class="block text-sm font-medium mb-2">Search</label>
                        <input 
                            v-model="search" 
                            type="text" 
                            placeholder="Search by name, email, company, or phone..." 
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2" 
                        />
                    </div>

                    <!-- Filter Options -->
                    <div class="flex flex-wrap gap-4">
                        <!-- Verification Status Filter (for customers) -->
                        <div>
                            <label class="block text-sm font-medium mb-2">Customer Verification</label>
                            <select 
                                v-model="verificationStatus" 
                                class="rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            >
                                <option value="">All Customers</option>
                                <option value="verified">Verified Customers</option>
                                <option value="unverified">Unverified Customers</option>
                            </select>
                        </div>

                        <!-- Archived Filter -->
                        <div class="flex items-end">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input 
                                    v-model="archived" 
                                    type="checkbox" 
                                    class="rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                                />
                                <span class="text-sm font-medium">Show Archived</span>
                            </label>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Table -->
        <Card>
            <CardContent>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User Role</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody v-if="(page.props.users as Paginated<UnifiedUser>).data.length" class="bg-white divide-y divide-border">
                            <tr v-for="user in (page.props.users as Paginated<UnifiedUser>).data" :key="`${user.type}-${user.id}`" class="hover:bg-muted">
                                <td class="px-4 py-3 whitespace-nowrap font-medium">{{ user.name }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                    {{ user.company || '-' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ user.email }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span 
                                        :class="['px-2 py-1 text-xs font-semibold rounded', getRoleBadgeClass(user.user_role)]"
                                    >
                                        {{ user.user_role }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                    {{ user.phone || '-' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span 
                                        :class="['px-2 py-1 text-xs font-semibold rounded', getStatusBadgeClass(user.status)]"
                                    >
                                        {{ user.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm">
                                    <div class="flex gap-2">
                                        <Link v-if="user.type === 'customer'" :href="route('customers.logs.show', user.id)">
                                            <Button variant="ghost" size="sm" title="View Logs">
                                                <Icon name="file-text" class="h-4 w-4" />
                                            </Button>
                                        </Link>
                                        <Button variant="ghost" size="sm" @click="editUser(user)">
                                            <Icon name="edit" class="h-4 w-4" />
                                        </Button>
                                        <Button variant="ghost" size="sm" @click="deleteUser(user)">
                                            <Icon name="trash" class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                        <tbody v-else>
                            <tr>
                                <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-500">
                                    No users found.
                                    <button 
                                        v-if="(search && search.toString().trim().length) || verificationStatus || archived" 
                                        type="button" 
                                        class="underline underline-offset-4 ml-1" 
                                        @click="search = ''; verificationStatus = ''; archived = false; updateFilters()"
                                    >
                                        Clear filters
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="(page.props.users as Paginated<UnifiedUser>).last_page > 1" class="flex items-center justify-between mt-6">
                    <div class="text-sm text-gray-700">
                        Showing {{ (page.props.users as Paginated<UnifiedUser>).from }} to {{ (page.props.users as Paginated<UnifiedUser>).to }} of {{ (page.props.users as Paginated<UnifiedUser>).total }} results
                    </div>
                    <div class="flex gap-2">
                        <Button 
                            v-if="(page.props.users as Paginated<UnifiedUser>).prev_page_url" 
                            variant="outline" 
                            size="sm"
                            @click="goToPage((page.props.users as Paginated<UnifiedUser>).current_page - 1)"
                        >
                            Previous
                        </Button>
                        <Button 
                            v-if="(page.props.users as Paginated<UnifiedUser>).next_page_url" 
                            variant="outline" 
                            size="sm"
                            @click="goToPage((page.props.users as Paginated<UnifiedUser>).current_page + 1)"
                        >
                            Next
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>
    </AppLayout>
</template>
