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
}

const page = usePage();
const filters = ref<{ search?: string }>(page.props.filters ? (page.props.filters as { search?: string }) : {});
const search = ref(typeof filters.value.search === 'string' ? filters.value.search : '');

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
        router.get('/customers', { search: val }, { preserveState: true, replace: true });
    }, 400);
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
                Swal.fire('Customer deleted', 'Customer deleted successfully.', 'success');
            },
        });
    }
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Customers" />
        <div class="flex items-center justify-between my-6">
            <h1 class="text-2xl font-bold">Customers</h1>
            <div class="flex gap-2 items-center">
                <input v-model="search" type="text" placeholder="Search customers..." class="rounded border px-3 py-2" />
                <Link :href="route('customers.create')">
                    <Button variant="default">New Customer</Button>
                </Link>
            </div>
        </div>
        <Card>
            <CardContent>
                <table class="min-w-full divide-y divide-border">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left">Name</th>
                            <th class="px-4 py-2 text-left">Company</th>
                            <th class="px-4 py-2 text-left">Email</th>
                            <th class="px-4 py-2 text-left">Phone</th>
                            <th class="px-4 py-2 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="customer in (page.props.customers as Paginated<Customer>).data" :key="customer.id" class="hover:bg-muted">
                            <td class="px-4 py-2 font-medium">{{ customer.name }}</td>
                            <td class="px-4 py-2">{{ customer.company_name }}</td>
                            <td class="px-4 py-2">{{ customer.email }}</td>
                            <td class="px-4 py-2">{{ customer.phone }}</td>
                            <td class="px-4 py-2 space-x-2">
                                <Link :href="route('customers.edit', customer.id)" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-8 w-8">
                                    <Icon name="edit" class="h-4 w-4" />
                                </Link>
                                <button class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-8 w-8 text-destructive hover:text-destructive" @click.prevent="deleteCustomer(customer.id)">
                                    <Icon name="trash2" class="h-4 w-4" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </CardContent>
        </Card>
    </AppLayout>
</template> 