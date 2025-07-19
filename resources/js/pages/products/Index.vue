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
import ProductStatsWidget from '@/components/ProductStatsWidget.vue';
import Swal from 'sweetalert2';
import { type BreadcrumbItem } from '@/types';

interface Product {
    id: number;
    name: string;
    description?: string;
    category?: { id: number; name: string };
    purchase_price: number;
    selling_price: number;
    stock: number;
    SKU: string;
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

interface ProductStats {
    totalProducts: number;
    totalStock: number;
    lowStockItems: number;
    totalValue: number;
}

const page = usePage();
const filters = ref<{ search?: string }>(page.props.filters ? (page.props.filters as { search?: string }) : {});
const search = ref(typeof filters.value.search === 'string' ? filters.value.search : '');

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Products', href: '/products' },
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
        router.get('/products', { search: val }, { preserveState: true, replace: true });
    }, 400);
});

function goToPage(pageNum: number) {
    router.get('/products', { search: search.value, page: pageNum }, { preserveState: true, replace: true });
}

function formatCurrency(amount: number) {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2
    }).format(amount);
}

async function deleteProduct(id: number) {
    const result = await Swal.fire({
        title: 'Delete Product?',
        text: 'Are you sure you want to delete this product? This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#8f5be8',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete product',
    });
    if (result.isConfirmed) {
        router.delete(`/products/${id}`, {
            onSuccess: () => {
                Swal.fire('Product deleted', 'Product deleted successfully.', 'success');
            },
        });
    }
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Products" />
        
        <!-- Product Stats Widget -->
        <ProductStatsWidget 
            :stats="(page.props.stats as ProductStats)" 
            class="mb-6"
        >
            <template #actions>
                <div class="flex gap-2 items-center">
                    <input v-model="search" type="text" placeholder="Search products..." class="rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2" />
                    <Link :href="route('products.create')">
                        <Button variant="default">
                            <span class="mr-2">+</span>
                            Add New Product
                        </Button>
                    </Link>
                </div>
            </template>
        </ProductStatsWidget>
        <Card>
            <CardContent>
                <table class="min-w-full divide-y divide-border">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left">Name</th>
                            <th class="px-4 py-2 text-left">Category</th>
                            <th class="px-4 py-2 text-left">Selling Price</th>
                            <th class="px-4 py-2 text-left">Stock</th>
                            <th class="px-4 py-2 text-left">SKU</th>
                            <th class="px-4 py-2 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="product in (page.props.products as Paginated<Product>).data" :key="product.id" class="hover:bg-muted">
                            <td class="px-4 py-2 font-medium">{{ product.name }}</td>
                            <td class="px-4 py-2">{{ product.category ? product.category.name : '' }}</td>
                            <td class="px-4 py-2">{{ formatCurrency(product.selling_price) }}</td>
                            <td class="px-4 py-2">{{ product.stock }}</td>
                            <td class="px-4 py-2">{{ product.SKU }}</td>
                            <td class="px-4 py-2 space-x-2">
                                <Link :href="route('products.edit', product.id)" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-8 w-8">
                                    <Icon name="edit" class="h-4 w-4" />
                                </Link>
                                <button class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-8 w-8 text-destructive hover:text-destructive" @click.prevent="deleteProduct(product.id)">
                                    <Icon name="trash2" class="h-4 w-4" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="flex justify-between mt-4">
                    <button v-if="(page.props.products as Paginated<Product>).prev_page_url" @click="goToPage((page.props.products as Paginated<Product>).current_page - 1)" class="btn">Previous</button>
                    <button v-if="(page.props.products as Paginated<Product>).next_page_url" @click="goToPage((page.props.products as Paginated<Product>).current_page + 1)" class="btn">Next</button>
                </div>
            </CardContent>
        </Card>
    </AppLayout>
</template> 