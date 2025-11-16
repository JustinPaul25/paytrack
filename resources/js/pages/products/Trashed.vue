<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import { Button } from '@/components/ui/button';
import Swal from 'sweetalert2';
import { type BreadcrumbItem } from '@/types';

interface Product {
    id: number;
    name: string;
    SKU: string;
    stock: number;
    category?: { id: number; name: string };
    deleted_at: string;
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
const search = ref<string>((page.props.filters as any)?.search ?? '');

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Products', href: '/products' },
    { title: 'Deleted', href: '/products/trashed' },
];

watch(search, (val) => {
    router.get(route('products.trashed.index'), { search: val }, { preserveState: true, replace: true });
});

function goToPage(pageNum: number) {
    router.get(route('products.trashed.index'), { search: search.value, page: pageNum }, { preserveState: true, replace: true });
}

async function restoreProduct(id: number) {
    const result = await Swal.fire({
        title: 'Restore Product?',
        text: 'This will make the product available again.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#8f5be8',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, restore product',
    });
    if (result.isConfirmed) {
        router.post(route('products.restore', id), {}, {
            onSuccess: () => {
                Swal.fire('Restored', 'Product restored successfully.', 'success');
            },
        });
    }
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Deleted Products" />

        <div class="flex items-center justify-between mt-4 mb-2">
            <h1 class="text-2xl font-bold">Deleted Products</h1>
            <div class="flex gap-2 items-center">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search deleted products..."
                    class="rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                />
                <Link :href="route('products.index')">
                    <Button variant="outline">Back to Products</Button>
                </Link>
            </div>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Trashed</CardTitle>
            </CardHeader>
            <CardContent>
                <table class="min-w-full divide-y divide-border">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left">Name</th>
                            <th class="px-4 py-2 text-left">Category</th>
                            <th class="px-4 py-2 text-left">SKU</th>
                            <th class="px-4 py-2 text-left">Deleted At</th>
                            <th class="px-4 py-2 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="product in (page.props.products as Paginated<Product>).data" :key="product.id" class="hover:bg-muted">
                            <td class="px-4 py-2 font-medium">{{ product.name }}</td>
                            <td class="px-4 py-2 text-sm text-gray-500">{{ product.category ? product.category.name : 'No category' }}</td>
                            <td class="px-4 py-2 text-sm text-gray-500">{{ product.SKU }}</td>
                            <td class="px-4 py-2 text-sm text-gray-500">{{ new Date(product.deleted_at).toLocaleString() }}</td>
                            <td class="px-4 py-2">
                                <div class="flex gap-2">
                                    <Button variant="default" size="sm" @click="restoreProduct(product.id)">
                                        Restore
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="(page.props.products as Paginated<Product>).last_page > 1" class="flex items-center justify-between mt-6">
                    <div class="text-sm text-gray-700">
                        Showing {{ (page.props.products as Paginated<Product>).from }} to {{ (page.props.products as Paginated<Product>).to }} of {{ (page.props.products as Paginated<Product>).total }} results
                    </div>
                    <div class="flex gap-2">
                        <Button
                            v-if="(page.props.products as Paginated<Product>).prev_page_url"
                            variant="outline"
                            size="sm"
                            @click="goToPage((page.props.products as Paginated<Product>).current_page - 1)"
                        >
                            Previous
                        </Button>
                        <Button
                            v-if="(page.props.products as Paginated<Product>).next_page_url"
                            variant="outline"
                            size="sm"
                            @click="goToPage((page.props.products as Paginated<Product>).current_page + 1)"
                        >
                            Next
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>
    </AppLayout>
</template>


