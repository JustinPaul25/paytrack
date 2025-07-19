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
import CardFooter from '@/components/ui/card/CardFooter.vue';
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

const page = usePage();
const filters = ref<{ search?: string }>(page.props.filters ? (page.props.filters as { search?: string }) : {});
const search = ref(typeof filters.value.search === 'string' ? filters.value.search : '');

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
        <div class="flex items-center justify-between my-6">
            <h1 class="text-2xl font-bold">Roles</h1>
            <div class="flex gap-2 items-center">
                <input v-model="search" type="text" placeholder="Search roles..." class="rounded border px-3 py-2" />
                <Link :href="route('roles.create')">
                    <Button variant="default">New Role</Button>
                </Link>
            </div>
        </div>
        <Card>
            <CardContent>
                <table class="min-w-full divide-y divide-border">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left">Name</th>
                            <th class="px-4 py-2 text-left">Guard Name</th>
                            <th class="px-4 py-2 text-left">Permissions</th>
                            <th class="px-4 py-2 text-left">Updated At</th>
                            <th class="px-4 py-2 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="role in (page.props.roles as Paginated<Role>).data" :key="role.id" class="hover:bg-muted">
                            <td class="px-4 py-2 font-medium">{{ role.name }}</td>
                            <td class="px-4 py-2">
                                <span class="inline-block rounded bg-secondary px-2 py-0.5 text-xs font-semibold text-secondary-foreground">{{ role.guard_name }}</span>
                            </td>
                            <td class="px-4 py-2">
                                <span class="inline-block rounded bg-primary text-primary-foreground px-2 py-0.5 text-xs font-semibold">{{ role.permissions_count }}</span>
                            </td>
                            <td class="px-4 py-2 text-sm">{{ new Date(role.updated_at).toLocaleString() }}</td>
                            <td class="px-4 py-2 space-x-2">
                                <Link :href="route('roles.edit', role.id)" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-8 w-8">
                                    <Icon name="edit" class="h-4 w-4" />
                                </Link>
                                <button class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-8 w-8 text-destructive hover:text-destructive" @click.prevent="deleteRole(role.id)">
                                    <Icon name="trash2" class="h-4 w-4" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="flex justify-between mt-4">
                    <button v-if="(page.props.roles as Paginated<Role>).prev_page_url" @click="goToPage((page.props.roles as Paginated<Role>).current_page - 1)" class="btn">Previous</button>
                    <button v-if="(page.props.roles as Paginated<Role>).next_page_url" @click="goToPage((page.props.roles as Paginated<Role>).current_page + 1)" class="btn">Next</button>
                </div>
            </CardContent>
        </Card>
    </AppLayout>
</template> 