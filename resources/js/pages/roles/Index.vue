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

interface Role {
    id: number;
    name: string;
    guard_name: string;
    permissions_count: number;
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

interface RoleStats {
    totalRoles: number;
    rolesWithPermissions: number;
    rolesWithoutPermissions: number;
    recentlyUpdated: number;
}

const page = usePage();
const filters = ref<{ search?: string }>(page.props.filters ? (page.props.filters as { search?: string }) : {});
const search = ref(typeof filters.value.search === 'string' ? filters.value.search : '');
const showStats = ref(false);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Roles', href: '/roles' },
];

// Keep search ref in sync with page prop
watchEffect(() => {
    search.value = (page.props.filters && typeof (page.props.filters as { search?: string }).search === 'string')
        ? (page.props.filters as { search?: string }).search!
        : '';
});

// Debounce search watcher
let searchTimeout: ReturnType<typeof setTimeout> | null = null;
watch(search, (val) => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/roles', { search: val }, { preserveState: true, replace: true });
    }, 400);
});

function goToPage(pageNum: number) {
    router.get('/roles', { search: search.value, page: pageNum }, { preserveState: true, replace: true });
}

async function deleteRole(id: number) {
    const result = await Swal.fire({
        title: 'Delete Role?',
        text: 'Are you sure you want to delete this role? This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#8f5be8',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete role',
    });
    if (result.isConfirmed) {
        router.delete(`/roles/${id}`, {
            onSuccess: () => {
                Swal.fire('Role deleted', 'Role deleted successfully.', 'success');
            },
        });
    }
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Roles" />
        
        <!-- Enhanced Role Stats Widget -->
        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 transform -translate-y-4"
            enter-to-class="opacity-100 transform translate-y-0"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 transform translate-y-0"
            leave-to-class="opacity-0 transform -translate-y-4"
        >
            <div v-show="showStats" class="grid grid-cols-1 lg:grid-cols-4 gap-6 mt-8 mb-6">
                <!-- Total Roles -->
                <Card class="relative overflow-hidden">
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Total Roles</p>
                                <p class="text-xl font-bold text-blue-600">{{ (page.props.stats as RoleStats)?.totalRoles || 0 }}</p>
                            </div>
                            <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center">
                                <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- With Permissions -->
                <Card class="relative overflow-hidden">
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">With Permissions</p>
                                <p class="text-xl font-bold text-green-600">{{ (page.props.stats as RoleStats)?.rolesWithPermissions || 0 }}</p>
                            </div>
                            <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center">
                                <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Without Permissions -->
                <Card class="relative overflow-hidden">
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Without Permissions</p>
                                <p class="text-xl font-bold text-yellow-600">{{ (page.props.stats as RoleStats)?.rolesWithoutPermissions || 0 }}</p>
                            </div>
                            <div class="h-12 w-12 rounded-full bg-yellow-100 flex items-center justify-center">
                                <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Recently Updated -->
                <Card class="relative overflow-hidden">
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Recently Updated</p>
                                <p class="text-xl font-bold text-purple-600">{{ (page.props.stats as RoleStats)?.recentlyUpdated || 0 }}</p>
                            </div>
                            <div class="h-12 w-12 rounded-full bg-purple-100 flex items-center justify-center">
                                <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
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
                    placeholder="Search roles by name..." 
                    class="rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2" 
                />
            </div>
            <Link :href="route('roles.create')">
                <Button variant="default">
                    <span class="mr-2">+</span>
                    Add New Role
                </Button>
            </Link>
        </div>

        <Card>
            <CardContent>
                <table class="min-w-full divide-y divide-border">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left">Name</th>
                            <th class="px-4 py-2 text-left">Updated At</th>
                            <th class="px-4 py-2 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody v-if="(page.props.roles as Paginated<Role>).data.length">
                        <tr v-for="role in (page.props.roles as Paginated<Role>).data" :key="role.id" class="hover:bg-muted">
                            <td class="px-4 py-2 font-medium">{{ role.name }}</td>
                            <td class="px-4 py-2 text-sm text-gray-500">{{ new Date(role.updated_at).toLocaleDateString() }}</td>
                            <td class="px-4 py-2">
                                <div class="flex gap-2">
                                    <Link :href="route('roles.edit', role.id)">
                                        <Button variant="ghost" size="sm">
                                            <Icon name="edit" class="h-4 w-4" />
                                        </Button>
                                    </Link>
                                    <Button variant="ghost" size="sm" @click="deleteRole(role.id)">
                                        <Icon name="trash" class="h-4 w-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                    <tbody v-else>
                        <tr>
                            <td colspan="3" class="px-4 py-10 text-center text-sm text-gray-500">
                                No roles found.
                                <button v-if="(search && search.toString().trim().length)" type="button" class="underline underline-offset-4 ml-1" @click="search = ''">
                                    Clear search
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="(page.props.roles as Paginated<Role>).last_page > 1" class="flex items-center justify-between mt-6">
                    <div class="text-sm text-gray-700">
                        Showing {{ (page.props.roles as Paginated<Role>).from }} to {{ (page.props.roles as Paginated<Role>).to }} of {{ (page.props.roles as Paginated<Role>).total }} results
                    </div>
                    <div class="flex gap-2">
                        <Button 
                            v-if="(page.props.roles as Paginated<Role>).prev_page_url" 
                            variant="outline" 
                            size="sm"
                            @click="goToPage((page.props.roles as Paginated<Role>).current_page - 1)"
                        >
                            Previous
                        </Button>
                        <Button 
                            v-if="(page.props.roles as Paginated<Role>).next_page_url" 
                            variant="outline" 
                            size="sm"
                            @click="goToPage((page.props.roles as Paginated<Role>).current_page + 1)"
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