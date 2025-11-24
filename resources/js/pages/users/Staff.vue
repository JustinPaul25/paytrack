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
import { Archive } from 'lucide-vue-next';

interface User {
    id: number;
    name: string;
    email: string;
    roles: string[];
    updated_at: string;
    profile_image_url?: string;
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
const filters = ref<{ search?: string }>(page.props.filters ? (page.props.filters as { search?: string }) : {});
const search = ref(typeof filters.value.search === 'string' ? filters.value.search : '');

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Users', href: '/users' },
    { title: 'Staff', href: '/users/staff' },
];

let searchTimeout: ReturnType<typeof setTimeout> | null = null;
watch(search, (val) => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/users/staff', { search: val || undefined }, { preserveState: true, replace: true });
    }, 400);
});

function goToPage(pageNum: number) {
    router.get('/users/staff', { search: search.value, page: pageNum }, { preserveState: true, replace: true });
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

function getRoleBadgeClass(role: string) {
    switch (role.toLowerCase()) {
        case 'admin': return 'bg-purple-100 text-purple-800';
        case 'staff': return 'bg-blue-100 text-blue-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Staff" />
        
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">Staff Management</h1>
            <div class="flex gap-2">
                <Link :href="route('users.archives')">
                    <Button variant="outline">
                        <Archive class="h-4 w-4 mr-2" />
                        Archives
                    </Button>
                </Link>
                <Link :href="route('users.create')">
                    <Button variant="default">
                        <span class="mr-2">+</span>
                        Add New User
                    </Button>
                </Link>
            </div>
        </div>

        <!-- Search -->
        <div class="flex items-center justify-between mt-4 mb-2">
            <div class="flex gap-2 items-center">
                <input 
                    v-model="search" 
                    type="text" 
                    placeholder="Search staff by name or email..." 
                    class="rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2" 
                />
            </div>
        </div>

        <Card>
            <CardContent>
                <table class="min-w-full divide-y divide-border">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left">Name</th>
                            <th class="px-4 py-2 text-left">Email</th>
                            <th class="px-4 py-2 text-left">Roles</th>
                            <th class="px-4 py-2 text-left">Updated At</th>
                            <th class="px-4 py-2 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody v-if="(page.props.users as Paginated<User>).data.length">
                        <tr v-for="user in (page.props.users as Paginated<User>).data" :key="user.id" class="hover:bg-muted">
                            <td class="px-4 py-2 font-medium">{{ user.name }}</td>
                            <td class="px-4 py-2 text-sm text-gray-500">{{ user.email }}</td>
                            <td class="px-4 py-2">
                                <div class="flex gap-1 flex-wrap">
                                    <span 
                                        v-for="role in user.roles" 
                                        :key="role"
                                        :class="['px-2 py-1 text-xs font-semibold rounded', getRoleBadgeClass(role)]"
                                    >
                                        {{ role }}
                                    </span>
                                </div>
                            </td>
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
                            <td colspan="5" class="px-4 py-10 text-center text-sm text-gray-500">
                                No staff members found.
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
    </AppLayout>
</template>

