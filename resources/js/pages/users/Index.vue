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

interface User {
    id: number;
    name: string;
    email: string;
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
interface Filters {
    search?: string;
}

interface UserStats {
    totalUsers: number;
    activeUsers: number;
    recentlyRegistered: number;
    adminUsers: number;
}

const page = usePage();
const filters = ref<Filters>(page.props.filters ? (page.props.filters as Filters) : {});
const search = ref(typeof filters.value.search === 'string' ? filters.value.search : '');
const showStats = ref(false);
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Users',
        href: '/users',
    },
];

// Keep search ref in sync with page prop
watchEffect(() => {
    search.value = (page.props.filters && typeof (page.props.filters as Filters).search === 'string') ? (page.props.filters as Filters).search! : '';
});

// Debounce search watcher
let searchTimeout: ReturnType<typeof setTimeout> | null = null;
watch(search, (val) => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/users', { search: val }, { preserveState: true, replace: true });
    }, 400);
});

function goToPage(pageNum: number) {
    router.get('/users', { search: search.value, page: pageNum }, { preserveState: true, replace: true });
}

async function deleteUser(id: number) {
    const result = await Swal.fire({
        title: 'Delete User?',
        text: 'Are you sure you want to delete this user? This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#8f5be8',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete user',
    });
    if (result.isConfirmed) {
        router.delete(`/users/${id}`, {
            onSuccess: () => {
                Swal.fire('User deleted', 'User deleted successfully.', 'success');
            },
        });
    }
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Users" />
        
        <!-- Enhanced User Stats Widget -->
        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 transform -translate-y-4"
            enter-to-class="opacity-100 transform translate-y-0"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 transform translate-y-0"
            leave-to-class="opacity-0 transform -translate-y-4"
        >
            <div v-show="showStats" class="grid grid-cols-1 lg:grid-cols-4 gap-6 mt-8 mb-6">
                <!-- Total Users -->
                <Card class="relative overflow-hidden">
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Total Users</p>
                                <p class="text-xl font-bold text-blue-600">{{ (page.props.stats as UserStats)?.totalUsers || 0 }}</p>
                            </div>
                            <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center">
                                <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Active Users -->
                <Card class="relative overflow-hidden">
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Active Users</p>
                                <p class="text-xl font-bold text-green-600">{{ (page.props.stats as UserStats)?.activeUsers || 0 }}</p>
                            </div>
                            <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center">
                                <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Recently Registered -->
                <Card class="relative overflow-hidden">
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Recently Registered</p>
                                <p class="text-xl font-bold text-yellow-600">{{ (page.props.stats as UserStats)?.recentlyRegistered || 0 }}</p>
                            </div>
                            <div class="h-12 w-12 rounded-full bg-yellow-100 flex items-center justify-center">
                                <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Admin Users -->
                <Card class="relative overflow-hidden">
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Admin Users</p>
                                <p class="text-xl font-bold text-purple-600">{{ (page.props.stats as UserStats)?.adminUsers || 0 }}</p>
                            </div>
                            <div class="h-12 w-12 rounded-full bg-purple-100 flex items-center justify-center">
                                <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
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
                    placeholder="Search users by name or email..." 
                    class="rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2" 
                />
            </div>
            <Link :href="route('users.create')">
                <Button variant="default">
                    <span class="mr-2">+</span>
                    Add New User
                </Button>
            </Link>
        </div>

        <Card>
            <CardContent>
                <table class="min-w-full divide-y divide-border">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left">Name</th>
                            <th class="px-4 py-2 text-left">Email</th>
                            <th class="px-4 py-2 text-left">Updated At</th>
                            <th class="px-4 py-2 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody v-if="(page.props.users as Paginated<User>).data.length">
                        <tr v-for="user in (page.props.users as Paginated<User>).data" :key="user.id" class="hover:bg-muted">
                            <td class="px-4 py-2 font-medium">{{ user.name }}</td>
                            <td class="px-4 py-2 text-sm text-gray-500">{{ user.email }}</td>
                            <td class="px-4 py-2 text-sm text-gray-500">{{ new Date(user.updated_at).toLocaleDateString() }}</td>
                            <td class="px-4 py-2">
                                <div class="flex gap-2">
                                    <Link :href="route('users.edit', user.id)">
                                        <Button variant="ghost" size="sm">
                                            <Icon name="edit" class="h-4 w-4" />
                                        </Button>
                                    </Link>
                                    <Button variant="ghost" size="sm" @click="deleteUser(user.id)">
                                        <Icon name="trash" class="h-4 w-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                    <tbody v-else>
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-sm text-gray-500">
                                No users found.
                                <button v-if="(search && search.toString().trim().length)" type="button" class="underline underline-offset-4 ml-1" @click="search = ''">
                                    Clear search
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="(page.props.users as Paginated<User>).last_page > 1" class="flex items-center justify-between mt-6">
                    <div class="text-sm text-gray-700">
                        Showing {{ (page.props.users as Paginated<User>).from }} to {{ (page.props.users as Paginated<User>).to }} of {{ (page.props.users as Paginated<User>).total }} results
                    </div>
                    <div class="flex gap-2">
                        <Button 
                            v-if="(page.props.users as Paginated<User>).prev_page_url" 
                            variant="outline" 
                            size="sm"
                            @click="goToPage((page.props.users as Paginated<User>).current_page - 1)"
                        >
                            Previous
                        </Button>
                        <Button 
                            v-if="(page.props.users as Paginated<User>).next_page_url" 
                            variant="outline" 
                            size="sm"
                            @click="goToPage((page.props.users as Paginated<User>).current_page + 1)"
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