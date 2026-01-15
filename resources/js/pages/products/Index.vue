<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch, watchEffect, computed } from 'vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import Icon from '@/components/Icon.vue';
import Swal from 'sweetalert2';
import { type BreadcrumbItem } from '@/types';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import SearchSelect from '@/components/ui/select/SearchSelect.vue';

interface Category {
    id: number;
    name: string;
}

interface Product {
    id: number;
    name: string;
    description?: string;
    category?: { id: number; name: string };
    purchase_price: number;
    selling_price: number;
    stock: number;
    initial_stock: number;
    unit?: string;
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
const filters = ref<{ search?: string; low_stock?: string; category_id?: string; stock_filter?: string; sort_by?: string; sort_order?: string }>(
    page.props.filters ? (page.props.filters as { search?: string; low_stock?: string; category_id?: string; stock_filter?: string; sort_by?: string; sort_order?: string }) : {}
);
const search = ref(typeof filters.value.search === 'string' ? filters.value.search : '');
const lowStock = ref(typeof filters.value.low_stock === 'string' ? filters.value.low_stock : '');
const categoryId = ref<number | null>(typeof filters.value.category_id === 'string' ? parseInt(filters.value.category_id) : (typeof filters.value.category_id === 'number' ? filters.value.category_id : null));
const stockFilter = ref(typeof filters.value.stock_filter === 'string' ? filters.value.stock_filter : '');
const sortBy = ref(typeof filters.value.sort_by === 'string' ? filters.value.sort_by : 'updated_at');
const sortOrder = ref(typeof filters.value.sort_order === 'string' ? filters.value.sort_order : 'desc');
const showStats = ref(false);

// Get categories from props
const categories = ref<Category[]>((page.props.categories as Category[]) || []);
const categoryOptions = computed(() => {
    return [
        { value: null, label: 'All Categories' },
        ...categories.value.map(cat => ({ value: cat.id, label: cat.name }))
    ];
});

// Add stock dialog state
const showAddStockDialog = ref(false);
const selectedProduct = ref<Product | null>(null);
const stockQuantity = ref<number>(1);
const stockNotes = ref<string>('');
const stockErrors = ref<{ quantity?: string; notes?: string }>({});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Products', href: '/products' },
];

watchEffect(() => {
    search.value = (page.props.filters && typeof (page.props.filters as { search?: string }).search === 'string')
        ? (page.props.filters as { search?: string }).search!
        : '';
    lowStock.value = (page.props.filters && typeof (page.props.filters as { low_stock?: string }).low_stock === 'string')
        ? (page.props.filters as { low_stock?: string }).low_stock!
        : '';
    const catId = (page.props.filters as { category_id?: string | number })?.category_id;
    categoryId.value = catId ? (typeof catId === 'number' ? catId : parseInt(catId)) : null;
    stockFilter.value = (page.props.filters && typeof (page.props.filters as { stock_filter?: string }).stock_filter === 'string')
        ? (page.props.filters as { stock_filter?: string }).stock_filter!
        : '';
    categories.value = (page.props.categories as Category[]) || [];
});

let searchTimeout: ReturnType<typeof setTimeout> | null = null;
watch(search, (val) => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/products', { 
            search: val, 
            low_stock: lowStock.value,
            category_id: categoryId.value,
            stock_filter: stockFilter.value,
            sort_by: sortBy.value,
            sort_order: sortOrder.value
        }, { preserveState: true, replace: true });
    }, 400);
});

watch(categoryId, (val) => {
    router.get('/products', { 
        search: search.value, 
        low_stock: lowStock.value,
        category_id: val,
        stock_filter: stockFilter.value,
        sort_by: sortBy.value,
        sort_order: sortOrder.value
    }, { preserveState: true, replace: true });
});

watch(stockFilter, (val) => {
    router.get('/products', { 
        search: search.value, 
        low_stock: lowStock.value,
        category_id: categoryId.value,
        stock_filter: val,
        sort_by: sortBy.value,
        sort_order: sortOrder.value
    }, { preserveState: true, replace: true });
});

watchEffect(() => {
    sortBy.value = (page.props.filters && typeof (page.props.filters as { sort_by?: string }).sort_by === 'string')
        ? (page.props.filters as { sort_by?: string }).sort_by!
        : 'updated_at';
    sortOrder.value = (page.props.filters && typeof (page.props.filters as { sort_order?: string }).sort_order === 'string')
        ? (page.props.filters as { sort_order?: string }).sort_order!
        : 'desc';
});

function toggleLowStock() {
    lowStock.value = lowStock.value ? '' : '1';
    router.get('/products', { 
        search: search.value, 
        low_stock: lowStock.value,
        category_id: categoryId.value,
        stock_filter: stockFilter.value,
        sort_by: sortBy.value,
        sort_order: sortOrder.value
    }, { preserveState: true, replace: true });
}

function toggleStockFilter(filter: 'highest' | 'lowest' | '') {
    stockFilter.value = stockFilter.value === filter ? '' : filter;
    router.get('/products', { 
        search: search.value, 
        low_stock: lowStock.value,
        category_id: categoryId.value,
        stock_filter: stockFilter.value,
        sort_by: sortBy.value,
        sort_order: sortOrder.value
    }, { preserveState: true, replace: true });
}

function goToPage(pageNum: number) {
    router.get('/products', { 
        search: search.value, 
        low_stock: lowStock.value,
        category_id: categoryId.value,
        stock_filter: stockFilter.value,
        page: pageNum,
        sort_by: sortBy.value,
        sort_order: sortOrder.value
    }, { preserveState: true, replace: true });
}

function handleSort(field: string) {
    if (sortBy.value === field) {
        // Toggle sort order if clicking the same field
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
        // Set new sort field with default ascending order
        sortBy.value = field;
        sortOrder.value = 'asc';
    }
    
    router.get('/products', { 
        search: search.value, 
        low_stock: lowStock.value,
        category_id: categoryId.value,
        stock_filter: stockFilter.value,
        sort_by: sortBy.value,
        sort_order: sortOrder.value
    }, { preserveState: true, replace: true });
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

function openAddStockDialog(product: Product) {
    selectedProduct.value = product;
    stockQuantity.value = 1;
    stockNotes.value = '';
    stockErrors.value = {};
    showAddStockDialog.value = true;
}

function closeAddStockDialog() {
    showAddStockDialog.value = false;
    selectedProduct.value = null;
    stockQuantity.value = 1;
    stockNotes.value = '';
    stockErrors.value = {};
}

async function submitAddStock() {
    if (!selectedProduct.value) return;

    // Validate
    stockErrors.value = {};
    if (!stockQuantity.value || stockQuantity.value < 1) {
        stockErrors.value.quantity = 'Quantity must be at least 1';
        return;
    }

    try {
        const response = await fetch(route('products.addStock', selectedProduct.value.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                quantity: stockQuantity.value,
                notes: stockNotes.value,
            }),
        });

        const data = await response.json();

        if (response.ok && data.success) {
            Swal.fire('Success', data.message, 'success');
            closeAddStockDialog();
            // Reload the page to refresh product data
            router.reload({ only: ['products', 'stats'] });
        } else {
            Swal.fire('Error', data.message || 'Failed to add stock', 'error');
        }
    } catch (error) {
        Swal.fire('Error', 'An error occurred while adding stock', 'error');
    }
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Products" />
        
        <!-- Enhanced Product Stats Widget -->
        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 transform -translate-y-4"
            enter-to-class="opacity-100 transform translate-y-0"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 transform translate-y-0"
            leave-to-class="opacity-0 transform -translate-y-4"
        >
            <div v-show="showStats" class="grid grid-cols-1 lg:grid-cols-4 gap-6 mt-8 mb-6">
                <!-- Total Products -->
                <Card class="relative overflow-hidden">
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Total Products</p>
                                <p class="text-xl font-bold text-blue-600">{{ (page.props.stats as ProductStats)?.totalProducts || 0 }}</p>
                            </div>
                            <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center">
                                <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Total Stock -->
                <Card class="relative overflow-hidden">
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Total Stock</p>
                                <p class="text-xl font-bold text-green-600">{{ (page.props.stats as ProductStats)?.totalStock || 0 }}</p>
                            </div>
                            <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center">
                                <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-14 0h14"></path>
                                </svg>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Low Stock Items -->
                <Card class="relative overflow-hidden">
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Low Stock</p>
                                <p class="text-xl font-bold text-yellow-600">{{ (page.props.stats as ProductStats)?.lowStockItems || 0 }}</p>
                            </div>
                            <div class="h-12 w-12 rounded-full bg-yellow-100 flex items-center justify-center">
                                <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Total Value -->
                <Card class="relative overflow-hidden">
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Total Value</p>
                                <p class="text-xl font-bold text-purple-600">{{ formatCurrency((page.props.stats as ProductStats)?.totalValue || 0) }}</p>
                            </div>
                            <div class="h-12 w-12 rounded-full bg-purple-100 flex items-center justify-center">
                                <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </Transition>

        <!-- Search and Actions -->
        <div class="flex items-center justify-between mt-4 mb-2">
            <div class="flex gap-2 items-center flex-wrap">
                <input 
                    v-model="search" 
                    type="text" 
                    placeholder="Search products by name, SKU, or category..." 
                    class="rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2" 
                />
                <div class="w-[200px]">
                    <SearchSelect
                        v-model="categoryId"
                        :options="categoryOptions"
                        placeholder="All Categories"
                        search-placeholder="Search categories..."
                        class="w-full"
                    />
                </div>
                <Button 
                    @click="toggleLowStock" 
                    :variant="lowStock ? 'default' : 'outline'"
                    :class="lowStock ? 'bg-yellow-600 hover:bg-yellow-700 text-white' : ''"
                >
                    <Icon name="alertTriangle" class="w-4 h-4 mr-2" />
                    Low Stock
                </Button>
                <Button 
                    @click="toggleStockFilter('highest')" 
                    :variant="stockFilter === 'highest' ? 'default' : 'outline'"
                    :class="stockFilter === 'highest' ? 'bg-green-600 hover:bg-green-700 text-white' : ''"
                >
                    <Icon name="arrow-up" class="w-4 h-4 mr-2" />
                    Highest Stock
                </Button>
                <Button 
                    @click="toggleStockFilter('lowest')" 
                    :variant="stockFilter === 'lowest' ? 'default' : 'outline'"
                    :class="stockFilter === 'lowest' ? 'bg-red-600 hover:bg-red-700 text-white' : ''"
                >
                    <Icon name="arrow-down" class="w-4 h-4 mr-2" />
                    Lowest Stock
                </Button>
            </div>
            <div class="flex gap-2 items-center">
                <Link :href="route('products.trashed.index')">
                    <Button variant="outline">
                        Deleted Products
                    </Button>
                </Link>
                <Link :href="route('products.create')">
                    <Button variant="default">
                        <span class="mr-2">+</span>
                        Add New Product
                    </Button>
                </Link>
            </div>
        </div>

        <Card>
            <CardContent>
                <table class="min-w-full divide-y divide-border">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left">Name</th>
                            <th class="px-4 py-2 text-left">Category</th>
                            <th class="px-4 py-2 text-left">Selling Price</th>
                            <th 
                                class="px-4 py-2 text-left cursor-pointer hover:bg-gray-50 select-none"
                                @click="handleSort('initial_stock')"
                            >
                                <div class="flex items-center gap-2">
                                    <span>Stock</span>
                                    <div class="flex flex-col">
                                        <svg 
                                            class="w-3 h-3" 
                                            :class="sortBy === 'initial_stock' && sortOrder === 'asc' ? 'text-blue-600' : 'text-gray-300'"
                                            fill="currentColor" 
                                            viewBox="0 0 20 20"
                                        >
                                            <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd" />
                                        </svg>
                                        <svg 
                                            class="w-3 h-3 -mt-1" 
                                            :class="sortBy === 'initial_stock' && sortOrder === 'desc' ? 'text-blue-600' : 'text-gray-300'"
                                            fill="currentColor" 
                                            viewBox="0 0 20 20"
                                        >
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </div>
                            </th>
                            <th 
                                class="px-4 py-2 text-left cursor-pointer hover:bg-gray-50 select-none"
                                @click="handleSort('stock')"
                            >
                                <div class="flex items-center gap-2">
                                    <span>Remaining Stock</span>
                                    <div class="flex flex-col">
                                        <svg 
                                            class="w-3 h-3" 
                                            :class="sortBy === 'stock' && sortOrder === 'asc' ? 'text-blue-600' : 'text-gray-300'"
                                            fill="currentColor" 
                                            viewBox="0 0 20 20"
                                        >
                                            <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd" />
                                        </svg>
                                        <svg 
                                            class="w-3 h-3 -mt-1" 
                                            :class="sortBy === 'stock' && sortOrder === 'desc' ? 'text-blue-600' : 'text-gray-300'"
                                            fill="currentColor" 
                                            viewBox="0 0 20 20"
                                        >
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </div>
                            </th>
                            <th class="px-4 py-2 text-left">Unit</th>
                            <th class="px-4 py-2 text-left">SKU</th>
                            <th class="px-4 py-2 text-left">Actions</th>
                        </tr>
                    </thead>
					<tbody v-if="(page.props.products as Paginated<Product>).data.length">
                        <tr v-for="product in (page.props.products as Paginated<Product>).data" :key="product.id" class="hover:bg-muted">
                            <td class="px-4 py-2 font-medium">{{ product.name }}</td>
                            <td class="px-4 py-2 text-sm text-gray-500">{{ product.category ? product.category.name : 'No category' }}</td>
                            <td class="px-4 py-2 font-medium">{{ formatCurrency(product.selling_price) }}</td>
                            <td class="px-4 py-2">
                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ product.initial_stock ?? 0 }}
                                </span>
                            </td>
                            <td class="px-4 py-2">
                                <span :class="[
                                    'px-2 py-1 rounded-full text-xs font-medium',
                                    product.stock <= 10 ? 'bg-red-100 text-red-800' : 
                                    product.stock <= 50 ? 'bg-yellow-100 text-yellow-800' : 
                                    'bg-green-100 text-green-800'
								]" :title="product.stock <= 10 ? 'Low stock — reorder soon' : (product.stock <= 50 ? 'Moderate stock' : 'In stock')">
                                    {{ product.stock }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-500 uppercase">{{ product.unit || 'pcs' }}</td>
                            <td class="px-4 py-2 text-sm text-gray-500">{{ product.SKU }}</td>
                            <td class="px-4 py-2">
                                <div class="flex gap-2">
                                    <Button variant="ghost" size="sm" @click="openAddStockDialog(product)" title="Add Stock">
                                        <Icon name="plus" class="h-4 w-4" />
                                    </Button>
                                    <Link :href="route('products.stockHistory', product.id)">
                                        <Button variant="ghost" size="sm" title="View Stock History">
                                            <Icon name="history" class="h-4 w-4" />
                                        </Button>
                                    </Link>
                                    <Link :href="route('products.edit', product.id)">
                                        <Button variant="ghost" size="sm">
                                            <Icon name="edit" class="h-4 w-4" />
                                        </Button>
                                    </Link>
                                    <Button variant="ghost" size="sm" @click="deleteProduct(product.id)">
                                        <Icon name="trash" class="h-4 w-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
					</tbody>
					<tbody v-else>
						<tr>
							<td colspan="6" class="px-4 py-10 text-center text-sm text-gray-500">
								No products found.
								<button type="button" class="underline underline-offset-4 ml-1" @click="search = ''">
									Clear search
								</button>
							</td>
						</tr>
					</tbody>
                </table>

                <!-- Pagination -->
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

        <!-- Add Stock Dialog -->
        <Dialog v-model:open="showAddStockDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Add Stock</DialogTitle>
                    <DialogDescription v-if="selectedProduct">
                        Add stock to <strong>{{ selectedProduct.name }}</strong>. This will increment both initial stock and remaining stock.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitAddStock" class="space-y-4">
                    <div>
                        <Label for="stock-quantity">Quantity *</Label>
                        <input
                            id="stock-quantity"
                            v-model.number="stockQuantity"
                            type="number"
                            min="1"
                            step="1"
                            class="w-full rounded border px-3 py-2 mt-1"
                            required
                            placeholder="Enter quantity to add"
                        />
                        <InputError :message="stockErrors.quantity" />
                    </div>

                    <div>
                        <Label for="stock-notes">Notes (Optional)</Label>
                        <textarea
                            id="stock-notes"
                            v-model="stockNotes"
                            rows="3"
                            class="w-full rounded border px-3 py-2 mt-1"
                            placeholder="Add any notes about this stock addition..."
                            maxlength="500"
                        ></textarea>
                        <InputError :message="stockErrors.notes" />
                    </div>

                    <DialogFooter>
                        <DialogClose as-child>
                            <Button type="button" variant="outline" @click="closeAddStockDialog">
                                Cancel
                            </Button>
                        </DialogClose>
                        <Button type="submit" variant="default">
                            Add Stock
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template> 