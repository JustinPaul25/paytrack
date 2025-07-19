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
const filters = ref<{ search?: string }>((page.props as any).filters || {});
const search = ref(filters.value.search || '');

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Expenses', href: '/expenses' },
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
        router.get('/expenses', { search: val }, { preserveState: true, replace: true });
    }, 400);
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
                Swal.fire('Expense deleted', 'Expense deleted successfully (Demo - no data saved).', 'success');
            },
        });
    }
}

function getExpenseTypeBadgeClass(type: string) {
    switch (type) {
        case 'Salary': return 'bg-green-100 text-green-800';
        case 'Bills': return 'bg-blue-100 text-blue-800';
        case 'Transportation': return 'bg-yellow-100 text-yellow-800';
        case 'Cash Advance': return 'bg-purple-100 text-purple-800';
        case 'Tax': return 'bg-red-100 text-red-800';
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
        
        <!-- Demo Notice -->
        <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
            <div class="flex items-center">
                <Icon name="alert-triangle" class="h-5 w-5 text-yellow-600 mr-2" />
                <div>
                    <h3 class="text-sm font-medium text-yellow-800">Demo Mode</h3>
                    <p class="text-sm text-yellow-700 mt-1">
                        This is a demo page with dummy data. No actual data will be saved to the database.
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Search and Actions Bar -->
        <Card class="my-6">
            <CardContent class="p-4">
                <div class="flex flex-col sm:flex-row gap-4 items-center justify-between">
                    <div class="flex-1 max-w-md">
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            <input 
                                v-model="search" 
                                type="text" 
                                placeholder="Search expenses by type or description..." 
                                class="w-full pl-10 pr-4 py-2 rounded-lg border border-input bg-background text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2" 
                            />
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <Link :href="route('expenses.create')">
                            <Button variant="default" class="flex items-center gap-2">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Create Expense
                            </Button>
                        </Link>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Enhanced Expense Stats Widget -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">
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

            <Card>
                <CardContent class="p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Highest Type</p>
                            <p class="text-lg font-bold text-gray-900">
                                {{ Object.keys((page.props as any).stats?.expensesByType || {}).length > 0 
                                    ? Object.entries((page.props as any).stats?.expensesByType || {}).reduce((a, b) => a[1] > b[1] ? a : b)[0] 
                                    : 'N/A' }}
                            </p>
                        </div>
                        <div class="p-2 bg-green-100 rounded-lg">
                            <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Average</p>
                            <p class="text-lg font-bold text-gray-900">
                                {{ page.props.stats?.totalCount > 0 
                                    ? formatCurrency((page.props.stats?.totalExpenses || 0) / page.props.stats?.totalCount) 
                                    : '₱0.00' }}
                            </p>
                        </div>
                        <div class="p-2 bg-purple-100 rounded-lg">
                            <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                    </div>
                </CardContent>
            </Card>
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
                    <tbody>
                        <tr v-for="expense in (page.props as any).expenses" :key="expense.id" class="hover:bg-muted">
                            <td class="px-4 py-2 font-medium">#{{ expense.id }}</td>
                            <td class="px-4 py-2">
                                <span :class="getExpenseTypeBadgeClass(expense.expense_type)" class="px-2 py-1 text-xs font-medium rounded-full">
                                    {{ expense.expense_type }}
                                </span>
                            </td>
                            <td class="px-4 py-2">{{ expense.description }}</td>
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
                </table>
            </CardContent>
        </Card>
    </AppLayout>
</template> 