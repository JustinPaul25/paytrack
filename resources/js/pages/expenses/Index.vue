<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch, watchEffect, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Select } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import Icon from '@/components/Icon.vue';
import Swal from 'sweetalert2';
import { type BreadcrumbItem } from '@/types';
import { Calendar, Filter, X } from 'lucide-vue-next';

interface Expense {
    id: number;
    amount: number;
    expense_type: string;
    description: string;
    date: string;
    created_at: string;
    updated_at: string;
}

interface ExpenseStats {
    totalExpenses: number;
    totalCount: number;
    expensesByType: Record<string, number>;
}

const page = usePage();
const filters = ref<{ search?: string; expense_type?: string; start_date?: string; end_date?: string }>((page.props as any).filters || {});
const search = ref(filters.value.search || '');
const expenseTypeFilter = ref(filters.value.expense_type || '');
const startDateFilter = ref(filters.value.start_date || '');
const endDateFilter = ref(filters.value.end_date || '');
const showStats = ref(false);
const showFilters = ref(false);

const expenseTypeOptions = [
    { value: '', label: 'All Types' },
    { value: 'Bills', label: 'Bills' },
    { value: 'Cash Advance', label: 'Cash Advance' },
    { value: 'Insurance', label: 'Insurance' },
    { value: 'Maintenance', label: 'Maintenance & Repairs' },
    { value: 'Marketing', label: 'Marketing & Advertising' },
    { value: 'Office Supplies', label: 'Office Supplies' },
    { value: 'Professional Services', label: 'Professional Services' },
    { value: 'Rent', label: 'Rent' },
    { value: 'Salary', label: 'Salary' },
    { value: 'Tax', label: 'Tax' },
    { value: 'Transportation', label: 'Transportation' },
    { value: 'Utilities', label: 'Utilities' },
    { value: 'Other', label: 'Other' },
];

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Expenses', href: '/expenses' },
];

watchEffect(() => {
    const propsFilters = (page.props.filters as any) || {};
    search.value = propsFilters.search || '';
    expenseTypeFilter.value = propsFilters.expense_type || '';
    startDateFilter.value = propsFilters.start_date || '';
    endDateFilter.value = propsFilters.end_date || '';
});

function updateFilters() {
    router.get('/expenses', {
        search: search.value,
        expense_type: expenseTypeFilter.value || undefined,
        start_date: startDateFilter.value || undefined,
        end_date: endDateFilter.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}

let searchTimeout: ReturnType<typeof setTimeout> | null = null;
watch(search, (val) => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        updateFilters();
    }, 400);
});

watch([expenseTypeFilter, startDateFilter, endDateFilter], () => {
    updateFilters();
});

function clearFilters() {
    search.value = '';
    expenseTypeFilter.value = '';
    startDateFilter.value = '';
    endDateFilter.value = '';
    updateFilters();
}

const hasActiveFilters = computed(() => {
    return expenseTypeFilter.value || startDateFilter.value || endDateFilter.value;
});

async function deleteExpense(id: number) {
    const result = await Swal.fire({
        title: 'Delete Expense?',
        text: 'Are you sure you want to delete this expense? This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#8f5be8',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete expense',
    });
    if (result.isConfirmed) {
        router.delete(`/expenses/${id}`, {
            onSuccess: () => {
                Swal.fire('Expense deleted', 'Expense deleted successfully.', 'success');
            },
        });
    }
}

function getExpenseTypeBadgeClass(type: string) {
    switch (type) {
        case 'Bills': return 'bg-blue-100 text-blue-800';
        case 'Cash Advance': return 'bg-purple-100 text-purple-800';
        case 'Insurance': return 'bg-cyan-100 text-cyan-800';
        case 'Maintenance': return 'bg-orange-100 text-orange-800';
        case 'Marketing': return 'bg-pink-100 text-pink-800';
        case 'Office Supplies': return 'bg-indigo-100 text-indigo-800';
        case 'Professional Services': return 'bg-teal-100 text-teal-800';
        case 'Rent': return 'bg-amber-100 text-amber-800';
        case 'Salary': return 'bg-green-100 text-green-800';
        case 'Tax': return 'bg-red-100 text-red-800';
        case 'Transportation': return 'bg-yellow-100 text-yellow-800';
        case 'Utilities': return 'bg-sky-100 text-sky-800';
        case 'Other': return 'bg-gray-100 text-gray-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

function formatCurrency(amount: number) {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2
    }).format(amount);
}

function formatDate(date: string) {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Expenses" />
        
        <!-- Enhanced Expense Stats Widget -->
        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 transform -translate-y-4"
            enter-to-class="opacity-100 transform translate-y-0"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 transform translate-y-0"
            leave-to-class="opacity-0 transform -translate-y-4"
        >
            <div v-show="showStats" class="grid grid-cols-1 lg:grid-cols-4 gap-6 mt-8 mb-6">
                <!-- Total Expenses -->
                <Card class="relative overflow-hidden">
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Total Expenses</p>
                                <p class="text-xl font-bold text-red-600">{{ formatCurrency((page.props as any).stats?.totalExpenses || 0) }}</p>
                            </div>
                            <div class="h-12 w-12 rounded-full bg-red-100 flex items-center justify-center">
                                <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Total Count -->
                <Card class="relative overflow-hidden">
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Total Count</p>
                                <p class="text-xl font-bold text-blue-600">{{ (page.props as any).stats?.totalCount || 0 }}</p>
                            </div>
                            <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center">
                                <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Highest Type -->
                <Card class="relative overflow-hidden">
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Highest Type</p>
                                <p class="text-xl font-bold text-green-600">
                                    {{ Object.keys((page.props as any).stats?.expensesByType || {}).length > 0 
                                        ? Object.entries((page.props as any).stats?.expensesByType || {}).reduce((a: [string, any], b: [string, any]) => (a[1] as number) > (b[1] as number) ? a : b)[0] 
                                        : 'N/A' }}
                                </p>
                            </div>
                            <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center">
                                <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                </svg>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Average -->
                <Card class="relative overflow-hidden">
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Average</p>
                                <p class="text-xl font-bold text-purple-600">
                                    {{ (page.props as any).stats?.totalCount > 0 
                                        ? formatCurrency(((page.props as any).stats?.totalExpenses || 0) / (page.props as any).stats?.totalCount) 
                                        : '₱0.00' }}
                                </p>
                            </div>
                            <div class="h-12 w-12 rounded-full bg-purple-100 flex items-center justify-center">
                                <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </Transition>

        <!-- Search, Filters, and Actions -->
        <div class="space-y-3 mt-4 mb-2">
            <div class="flex items-center justify-between gap-2 flex-wrap">
                <div class="flex gap-2 items-center flex-1 min-w-[200px]">
                    <input 
                        v-model="search" 
                        type="text" 
                        placeholder="Search expenses by type or description..." 
                        class="flex-1 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2" 
                    />
                    <Button 
                        variant="outline" 
                        @click="showFilters = !showFilters"
                        :class="hasActiveFilters ? 'border-blue-500 text-blue-600' : ''"
                    >
                        <Filter class="w-4 h-4 mr-2" />
                        Filters
                        <span v-if="hasActiveFilters" class="ml-2 bg-blue-500 text-white rounded-full w-5 h-5 text-xs flex items-center justify-center">
                            {{ (expenseTypeFilter ? 1 : 0) + (startDateFilter ? 1 : 0) + (endDateFilter ? 1 : 0) }}
                        </span>
                    </Button>
                </div>
                <Link :href="route('expenses.create')">
                    <Button variant="default">
                        <span class="mr-2">+</span>
                        Add New Expense
                    </Button>
                </Link>
            </div>
            
            <!-- Filter Panel -->
            <Transition
                enter-active-class="transition-all duration-200 ease-out"
                enter-from-class="opacity-0 transform -translate-y-2"
                enter-to-class="opacity-100 transform translate-y-0"
                leave-active-class="transition-all duration-150 ease-in"
                leave-from-class="opacity-100 transform translate-y-0"
                leave-to-class="opacity-0 transform -translate-y-2"
            >
                <Card v-show="showFilters" class="mt-2">
                    <CardContent class="p-4">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-semibold flex items-center gap-2">
                                <Filter class="w-4 h-4" />
                                Filter Expenses
                            </h3>
                            <div class="flex gap-2">
                                <Button 
                                    v-if="hasActiveFilters" 
                                    variant="ghost" 
                                    size="sm"
                                    @click="clearFilters"
                                    class="text-xs"
                                >
                                    <X class="w-3 h-3 mr-1" />
                                    Clear All
                                </Button>
                                <Button variant="ghost" size="sm" @click="showFilters = false" class="text-xs">
                                    <X class="w-3 h-3" />
                                </Button>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="text-xs font-medium text-muted-foreground mb-1 block">Expense Type</label>
                                <Select
                                    v-model="expenseTypeFilter"
                                    :options="expenseTypeOptions"
                                    placeholder="Select type"
                                    class="w-full"
                                />
                            </div>
                            <div>
                                <label class="text-xs font-medium text-muted-foreground mb-1 block flex items-center gap-1">
                                    <Calendar class="w-3 h-3" />
                                    Start Date
                                </label>
                                <input
                                    v-model="startDateFilter"
                                    type="date"
                                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                />
                            </div>
                            <div>
                                <label class="text-xs font-medium text-muted-foreground mb-1 block flex items-center gap-1">
                                    <Calendar class="w-3 h-3" />
                                    End Date
                                </label>
                                <input
                                    v-model="endDateFilter"
                                    type="date"
                                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </Transition>
        </div>

        <Card>
            <CardContent>
                <table class="min-w-full divide-y divide-border">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left">ID</th>
                            <th class="px-4 py-2 text-left">Type</th>
                            <th class="px-4 py-2 text-left">Description</th>
                            <th class="px-4 py-2 text-left">Amount</th>
                            <th class="px-4 py-2 text-left">Date</th>
                            <th class="px-4 py-2 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody v-if="(page.props as any).expenses && (page.props as any).expenses.length">
                        <tr v-for="expense in (page.props as any).expenses" :key="expense.id" class="hover:bg-muted">
                            <td class="px-4 py-2 font-medium">#{{ expense.id }}</td>
                            <td class="px-4 py-2">
                                <span :class="getExpenseTypeBadgeClass(expense.expense_type)" class="px-2 py-1 text-xs font-medium rounded-full">
                                    {{ expense.expense_type }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-500">{{ expense.description }}</td>
                            <td class="px-4 py-2 font-medium">{{ formatCurrency(expense.amount) }}</td>
                            <td class="px-4 py-2 text-sm text-gray-500">{{ formatDate(expense.date) }}</td>
                            <td class="px-4 py-2">
                                <div class="flex gap-2">
                                    <Link :href="route('expenses.edit', expense.id)">
                                        <Button variant="ghost" size="sm">
                                            <Icon name="edit" class="h-4 w-4" />
                                        </Button>
                                    </Link>
                                    <Button variant="ghost" size="sm" @click="deleteExpense(expense.id)">
                                        <Icon name="trash" class="h-4 w-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                    <tbody v-else>
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-500">
                                No expenses found.
                                <button v-if="(search && search.toString().trim().length)" type="button" class="underline underline-offset-4 ml-1" @click="search = ''">
                                    Clear search
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
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