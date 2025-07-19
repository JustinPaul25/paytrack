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

const page = usePage();
const filters = ref<Filters>(page.props.filters ? (page.props.filters as Filters) : {});
const search = ref(typeof filters.value.search === 'string' ? filters.value.search : '');
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
        <div class="flex items-center justify-between my-6">
            <h1 class="text-2xl font-bold">Users</h1>
            <div class="flex gap-2 items-center">
                <input v-model="search" type="text" placeholder="Search users..." class="rounded border px-3 py-2" />
                <Link :href="route('users.create')">
                    <Button variant="default">New User</Button>
                </Link>
            </div>
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
                    <tbody>
                        <tr v-for="user in (page.props.users as Paginated<User>).data" :key="user.id" class="hover:bg-muted">
                            <td class="px-4 py-2 font-medium">{{ user.name }}</td>
                            <td class="px-4 py-2">{{ user.email }}</td>
                            <td class="px-4 py-2 text-sm">{{ new Date(user.updated_at).toLocaleString() }}</td>
                            <td class="px-4 py-2 space-x-2">
                                <Link :href="route('users.edit', user.id)" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-8 w-8">
                                    <Icon name="edit" class="h-4 w-4" />
                                </Link>
                                <button class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-8 w-8 text-destructive hover:text-destructive" @click.prevent="deleteUser(user.id)">
                                    <Icon name="trash2" class="h-4 w-4" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="flex justify-between mt-4">
                    <button v-if="(page.props.users as Paginated<User>).prev_page_url" @click="goToPage((page.props.users as Paginated<User>).current_page - 1)" class="btn">Previous</button>
                    <button v-if="(page.props.users as Paginated<User>).next_page_url" @click="goToPage((page.props.users as Paginated<User>).current_page + 1)" class="btn">Next</button>
                </div>
            </CardContent>
        </Card>
    </AppLayout>
</template> 