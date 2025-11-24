<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import Icon from '@/components/Icon.vue';
import Swal from 'sweetalert2';
import { type BreadcrumbItem, type Paginated } from '@/types';
import { Badge } from '@/components/ui/badge';
import { RotateCcw, Trash2 } from 'lucide-vue-next';

interface User {
    id: number;
    name: string;
    email: string;
    roles: string[];
    deleted_at: string;
    profile_image_url?: string;
}

const page = usePage();
const users = computed<Paginated<User>>(() => page.props.users as Paginated<User>);
const filters = computed(() => page.props.filters ? (page.props.filters as { search?: string }) : {});
const search = ref(typeof filters.value.search === 'string' ? filters.value.search : '');

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Users', href: '/users' },
    { title: 'Archives', href: '/users/archives' },
];

watch(() => page.props.filters, (newFilters) => {
    if (newFilters) {
        const f = newFilters as { search?: string };
        search.value = typeof f.search === 'string' ? f.search : '';
    }
}, { immediate: true });

let searchTimeout: ReturnType<typeof setTimeout> | null = null;
watch(search, (val) => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/users/archives', { search: val }, { preserveState: true, replace: true });
    }, 400);
});

function goToPage(pageNum: number) {
    router.get('/users/archives', { search: search.value, page: pageNum }, { preserveState: true, replace: true });
}

async function restoreUser(id: number) {
    const result = await Swal.fire({
        title: 'Restore User?',
        text: 'Are you sure you want to restore this user?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#8f5be8',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, restore user',
    });
    if (result.isConfirmed) {
        router.post(`/users/${id}/restore`, {}, {
            onSuccess: () => {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'User restored successfully',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                });
            },
        });
    }
}

async function forceDeleteUser(id: number) {
    const result = await Swal.fire({
        title: 'Permanently Delete User?',
        text: 'Are you sure you want to permanently delete this user? This action cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete permanently',
    });
    if (result.isConfirmed) {
        router.delete(`/users/${id}/force-delete`, {
            onSuccess: () => {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'User permanently deleted',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                });
            },
        });
    }
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Archived Users" />

        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">Archived Users</h1>
            <Link :href="route('users.staff')">
                <Button variant="outline">
                    <Icon name="arrow-left" class="h-4 w-4 mr-2" />
                    Back to Staff
                </Button>
            </Link>
        </div>

        <div class="flex items-center justify-between mt-4 mb-2">
            <input
                v-model="search"
                type="text"
                placeholder="Search archived users by name or email..."
                class="rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
            />
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Deleted Users</CardTitle>
            </CardHeader>
            <CardContent>
                <table class="min-w-full divide-y divide-border">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left">Name</th>
                            <th class="px-4 py-2 text-left">Email</th>
                            <th class="px-4 py-2 text-left">Roles</th>
                            <th class="px-4 py-2 text-left">Deleted At</th>
                            <th class="px-4 py-2 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody v-if="users.value.data.length">
                        <tr v-for="user in users.value.data" :key="user.id" class="hover:bg-muted">
                            <td class="px-4 py-2 font-medium">{{ user.name }}</td>
                            <td class="px-4 py-2 text-sm text-gray-500">{{ user.email }}</td>
                            <td class="px-4 py-2">
                                <div class="flex gap-1">
                                    <Badge v-for="role in user.roles" :key="role" variant="secondary">{{ role }}</Badge>
                                </div>
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-500">{{ new Date(user.deleted_at).toLocaleString() }}</td>
                            <td class="px-4 py-2">
                                <div class="flex gap-2">
                                    <Button variant="ghost" size="sm" @click="restoreUser(user.id)">
                                        <RotateCcw class="h-4 w-4 mr-1" />
                                        Restore
                                    </Button>
                                    <Button variant="ghost" size="sm" @click="forceDeleteUser(user.id)" class="text-red-600 hover:text-red-700">
                                        <Trash2 class="h-4 w-4 mr-1" />
                                        Delete Permanently
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                    <tbody v-else>
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-sm text-gray-500">
                                No archived users found.
                                <button v-if="(search && search.toString().trim().length)" type="button" class="underline underline-offset-4 ml-1" @click="search = ''">
                                    Clear search
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="users.value.last_page > 1" class="flex items-center justify-between mt-6">
                    <div class="text-sm text-gray-700">
                        Showing {{ users.value.from }} to {{ users.value.to }} of {{ users.value.total }} results
                    </div>
                    <div class="flex gap-2">
                        <Button
                            v-if="users.value.prev_page_url"
                            variant="outline"
                            size="sm"
                            @click="goToPage(users.value.current_page - 1)"
                        >
                            Previous
                        </Button>
                        <Button
                            v-if="users.value.next_page_url"
                            variant="outline"
                            size="sm"
                            @click="goToPage(users.value.current_page + 1)"
                        >
                            Next
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>
    </AppLayout>
</template>

