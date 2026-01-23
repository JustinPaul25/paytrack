<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardDescription from '@/components/ui/card/CardDescription.vue';
import { Button } from '@/components/ui/button';
import { Select } from '@/components/ui/select';
import { AlertCircle, Calendar, BarChart3 } from 'lucide-vue-next';

interface MonthlyPoint { month: string; total: number }
interface AovPoint { month: string; aov: number }

interface Reminder {
    id: number;
    title: string;
    description: string;
    due_date: string;
    due_date_formatted: string;
    amount: number;
    currency: string;
    priority: 'low' | 'medium' | 'high';
    is_read: boolean;
    invoice_id: number | null;
    invoice_reference: string | null;
    days_until_due: number;
}

const props = defineProps<{
    customer: { id: number | null, name?: string, email?: string } | null,
    monthlySpend: MonthlyPoint[],
    statusBreakdown: { paid: number, pending: number, cancelled: number },
    topProducts: { id: number, name: string, total_quantity: number }[],
    categorySpend: { category: string, total: number }[],
    aovTrend: AovPoint[],
    reminders?: Reminder[],
    filters?: {
        start_date?: string;
        end_date?: string;
    };
}>();

// Filter state
const monthlySpendFilterPeriod = ref('year');
const monthlySpendFilterStartDate = ref(props.filters?.start_date || '');
const monthlySpendFilterEndDate = ref(props.filters?.end_date || '');

const topProductsFilterPeriod = ref('year');
const topProductsFilterStartDate = ref(props.filters?.start_date || '');
const topProductsFilterEndDate = ref(props.filters?.end_date || '');

const categorySpendFilterPeriod = ref('year');
const categorySpendFilterStartDate = ref(props.filters?.start_date || '');
const categorySpendFilterEndDate = ref(props.filters?.end_date || '');

const aovTrendFilterPeriod = ref('year');
const aovTrendFilterStartDate = ref(props.filters?.start_date || '');
const aovTrendFilterEndDate = ref(props.filters?.end_date || '');

const periodOptions = [
    { value: 'week', label: 'Last 7 Days' },
    { value: 'month', label: 'Last 30 Days' },
    { value: 'quarter', label: 'Last 3 Months' },
    { value: 'year', label: 'Last 12 Months' },
    { value: 'custom', label: 'Choose Dates' },
];

// Helper function to calculate date range from period
function getDateRangeFromPeriod(periodValue: string, fallbackStart?: string, fallbackEnd?: string): { start: string; end: string } {
    const end = new Date();
    end.setHours(23, 59, 59, 999);
    let start = new Date();
    
    switch (periodValue) {
        case 'week':
            start.setDate(start.getDate() - 7);
            break;
        case 'month':
            start.setMonth(start.getMonth() - 1);
            break;
        case 'quarter':
            start.setMonth(start.getMonth() - 3);
            break;
        case 'year':
            start.setFullYear(start.getFullYear() - 1);
            break;
        case 'custom':
            if (fallbackStart && fallbackEnd) {
                return { start: fallbackStart, end: fallbackEnd };
            }
            break;
        default:
            start.setFullYear(start.getFullYear() - 1);
    }
    
    start.setHours(0, 0, 0, 0);
    return {
        start: start.toISOString().split('T')[0],
        end: end.toISOString().split('T')[0]
    };
}

// Flags to prevent initial watcher triggers
const isMonthlySpendFilterInitialized = ref(false);
const isTopProductsFilterInitialized = ref(false);
const isCategorySpendFilterInitialized = ref(false);
const isAovTrendFilterInitialized = ref(false);

// Watch filter period changes to update dates
watch(monthlySpendFilterPeriod, (newPeriod) => {
    if (newPeriod !== 'custom') {
        const range = getDateRangeFromPeriod(newPeriod);
        monthlySpendFilterStartDate.value = range.start;
        monthlySpendFilterEndDate.value = range.end;
    }
});

watch(topProductsFilterPeriod, (newPeriod) => {
    if (newPeriod !== 'custom') {
        const range = getDateRangeFromPeriod(newPeriod);
        topProductsFilterStartDate.value = range.start;
        topProductsFilterEndDate.value = range.end;
    }
});

watch(categorySpendFilterPeriod, (newPeriod) => {
    if (newPeriod !== 'custom') {
        const range = getDateRangeFromPeriod(newPeriod);
        categorySpendFilterStartDate.value = range.start;
        categorySpendFilterEndDate.value = range.end;
    }
});

watch(aovTrendFilterPeriod, (newPeriod) => {
    if (newPeriod !== 'custom') {
        const range = getDateRangeFromPeriod(newPeriod);
        aovTrendFilterStartDate.value = range.start;
        aovTrendFilterEndDate.value = range.end;
    }
});

// Watch filter changes and update data
watch([monthlySpendFilterPeriod, monthlySpendFilterStartDate, monthlySpendFilterEndDate], () => {
    if (!isMonthlySpendFilterInitialized.value) {
        isMonthlySpendFilterInitialized.value = true;
        return;
    }
    
    const range = getDateRangeFromPeriod(monthlySpendFilterPeriod.value, monthlySpendFilterStartDate.value, monthlySpendFilterEndDate.value);
    router.get('/dashboard', {
        monthly_spend_start_date: range.start,
        monthly_spend_end_date: range.end,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['monthlySpend'],
    });
});

watch([topProductsFilterPeriod, topProductsFilterStartDate, topProductsFilterEndDate], () => {
    if (!isTopProductsFilterInitialized.value) {
        isTopProductsFilterInitialized.value = true;
        return;
    }
    
    const range = getDateRangeFromPeriod(topProductsFilterPeriod.value, topProductsFilterStartDate.value, topProductsFilterEndDate.value);
    router.get('/dashboard', {
        top_products_start_date: range.start,
        top_products_end_date: range.end,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['topProducts'],
    });
});

watch([categorySpendFilterPeriod, categorySpendFilterStartDate, categorySpendFilterEndDate], () => {
    if (!isCategorySpendFilterInitialized.value) {
        isCategorySpendFilterInitialized.value = true;
        return;
    }
    
    const range = getDateRangeFromPeriod(categorySpendFilterPeriod.value, categorySpendFilterStartDate.value, categorySpendFilterEndDate.value);
    router.get('/dashboard', {
        category_spend_start_date: range.start,
        category_spend_end_date: range.end,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['categorySpend'],
    });
});

watch([aovTrendFilterPeriod, aovTrendFilterStartDate, aovTrendFilterEndDate], () => {
    if (!isAovTrendFilterInitialized.value) {
        isAovTrendFilterInitialized.value = true;
        return;
    }
    
    const range = getDateRangeFromPeriod(aovTrendFilterPeriod.value, aovTrendFilterStartDate.value, aovTrendFilterEndDate.value);
    router.get('/dashboard', {
        aov_trend_start_date: range.start,
        aov_trend_end_date: range.end,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['aovTrend'],
    });
});

onMounted(() => {
    // Initialize filter dates based on period
    if (monthlySpendFilterPeriod.value !== 'custom') {
        const range = getDateRangeFromPeriod(monthlySpendFilterPeriod.value);
        monthlySpendFilterStartDate.value = range.start;
        monthlySpendFilterEndDate.value = range.end;
    }
    
    if (topProductsFilterPeriod.value !== 'custom') {
        const range = getDateRangeFromPeriod(topProductsFilterPeriod.value);
        topProductsFilterStartDate.value = range.start;
        topProductsFilterEndDate.value = range.end;
    }
    
    if (categorySpendFilterPeriod.value !== 'custom') {
        const range = getDateRangeFromPeriod(categorySpendFilterPeriod.value);
        categorySpendFilterStartDate.value = range.start;
        categorySpendFilterEndDate.value = range.end;
    }
    
    if (aovTrendFilterPeriod.value !== 'custom') {
        const range = getDateRangeFromPeriod(aovTrendFilterPeriod.value);
        aovTrendFilterStartDate.value = range.start;
        aovTrendFilterEndDate.value = range.end;
    }
    
    // Mark as initialized after a short delay
    setTimeout(() => {
        isMonthlySpendFilterInitialized.value = true;
        isTopProductsFilterInitialized.value = true;
        isCategorySpendFilterInitialized.value = true;
        isAovTrendFilterInitialized.value = true;
    }, 100);
});

function formatCurrency(amount: number) {
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', minimumFractionDigits: 2 }).format(amount);
}

function getPriorityClass(priority: string): string {
    switch (priority) {
        case 'high':
            return 'border-l-4 border-l-red-500 bg-red-50 dark:bg-red-950/20';
        case 'medium':
            return 'border-l-4 border-l-yellow-500 bg-yellow-50 dark:bg-yellow-950/20';
        case 'low':
            return 'border-l-4 border-l-blue-500 bg-blue-50 dark:bg-blue-950/20';
        default:
            return 'border-l-4 border-l-gray-500 bg-gray-50 dark:bg-gray-950/20';
    }
}

function getPriorityText(priority: string): string {
    return priority.charAt(0).toUpperCase() + priority.slice(1);
}

function getStatusText(daysUntilDue: number): string {
    if (daysUntilDue < 0) {
        return `Overdue by ${Math.abs(daysUntilDue)} day(s)`;
    } else if (daysUntilDue === 0) {
        return 'Due today';
    } else {
        return `Due in ${daysUntilDue} day(s)`;
    }
}

function getStatusClass(daysUntilDue: number): string {
    if (daysUntilDue < 0) {
        return 'text-red-600 dark:text-red-400 font-semibold';
    } else if (daysUntilDue === 0) {
        return 'text-orange-600 dark:text-orange-400 font-semibold';
    } else if (daysUntilDue <= 3) {
        return 'text-yellow-600 dark:text-yellow-400 font-semibold';
    } else {
        return 'text-blue-600 dark:text-blue-400';
    }
}
</script>

<template>
    <AppLayout>
        <Head title="My Dashboard" />

        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">My Dashboard</h1>
            <Link :href="route('orders.create')">
                <Button variant="default">
                    <span class="mr-2">+</span>
                    Create Order
                </Button>
            </Link>
        </div>

        <!-- Payment Reminders Section -->
        <div v-if="reminders && reminders.length > 0" class="mb-6">
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <AlertCircle class="w-5 h-5 text-orange-500" />
                        Payment Reminders
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-3">
                        <div
                            v-for="reminder in reminders"
                            :key="reminder.id"
                            :class="['p-4 rounded-lg', getPriorityClass(reminder.priority)]"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <h3 class="font-semibold text-gray-900 dark:text-gray-100">
                                            {{ reminder.title }}
                                        </h3>
                                        <span
                                            :class="['px-2 py-1 text-xs rounded-full', 
                                                reminder.priority === 'high' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300' :
                                                reminder.priority === 'medium' ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300' :
                                                'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'
                                            ]"
                                        >
                                            {{ getPriorityText(reminder.priority) }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                        {{ reminder.description }}
                                    </p>
                                    <div class="flex flex-wrap items-center gap-4 text-sm">
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-semibold text-gray-900 dark:text-gray-100">
                                                {{ formatCurrency(reminder.amount) }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <Calendar class="w-4 h-4 text-gray-500" />
                                            <span class="text-gray-600 dark:text-gray-400">
                                                {{ reminder.due_date_formatted }}
                                            </span>
                                        </div>
                                        <div>
                                            <span :class="getStatusClass(reminder.days_until_due)">
                                                {{ getStatusText(reminder.days_until_due) }}
                                            </span>
                                        </div>
                                        <div v-if="reminder.invoice_reference" class="text-gray-500 dark:text-gray-400">
                                            Invoice: {{ reminder.invoice_reference }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Monthly Spend Trend -->
            <Card>
                <CardHeader>
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                        <div>
                            <CardTitle>Monthly spend</CardTitle>
                            <CardDescription>Your spending over time</CardDescription>
                        </div>
                    </div>
                    <div class="filter-group" style="display: flex; flex-direction: column; gap: 0.5rem; margin-top: 1rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <Calendar class="w-4 h-4" />
                            <label style="font-size: 0.875rem; font-weight: 500;">Date Filter</label>
                        </div>
                        <Select
                            v-model="monthlySpendFilterPeriod"
                            :options="periodOptions"
                            placeholder="Choose time period"
                            class="period-select"
                        />
                        <div v-if="monthlySpendFilterPeriod === 'custom'" style="display: flex; gap: 0.5rem;">
                            <input
                                v-model="monthlySpendFilterStartDate"
                                type="date"
                                style="flex: 1; padding: 0.375rem; border: 1px solid #e5e7eb; border-radius: 0.375rem; font-size: 0.875rem;"
                            />
                            <input
                                v-model="monthlySpendFilterEndDate"
                                type="date"
                                style="flex: 1; padding: 0.375rem; border: 1px solid #e5e7eb; border-radius: 0.375rem; font-size: 0.875rem;"
                            />
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="space-y-2">
                        <div v-if="monthlySpend.length === 0" class="text-sm text-muted-foreground">
                            No data yet.
                        </div>
                        <div v-else v-for="p in monthlySpend" :key="p.month" class="flex items-center gap-3">
                            <div class="w-24 text-xs text-muted-foreground">{{ p.month }}</div>
                            <div class="flex-1 h-2 rounded bg-muted overflow-hidden">
                                <div class="h-2 bg-primary" :style="{ width: Math.min(100, Math.round(p.total / (Math.max(...monthlySpend.map(m => m.total)) || 1) * 100)) + '%' }"></div>
                            </div>
                            <div class="w-24 text-right text-sm">{{ formatCurrency(p.total) }}</div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Status Breakdown -->
            <Card>
                <CardHeader>
                    <CardTitle>Order status</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-3 gap-4 text-center">
                        <div>
                            <div class="text-2xl font-bold">{{ statusBreakdown.paid }}</div>
                            <div class="text-xs text-muted-foreground">Paid</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold">{{ statusBreakdown.pending }}</div>
                            <div class="text-xs text-muted-foreground">Pending</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold">{{ statusBreakdown.cancelled }}</div>
                            <div class="text-xs text-muted-foreground">Cancelled</div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Top Products -->
            <Card>
                <CardHeader>
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                        <div>
                            <CardTitle>Top products purchased</CardTitle>
                            <CardDescription>Your most purchased products</CardDescription>
                        </div>
                    </div>
                    <div class="filter-group" style="display: flex; flex-direction: column; gap: 0.5rem; margin-top: 1rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <Calendar class="w-4 h-4" />
                            <label style="font-size: 0.875rem; font-weight: 500;">Date Filter</label>
                        </div>
                        <Select
                            v-model="topProductsFilterPeriod"
                            :options="periodOptions"
                            placeholder="Choose time period"
                            class="period-select"
                        />
                        <div v-if="topProductsFilterPeriod === 'custom'" style="display: flex; gap: 0.5rem;">
                            <input
                                v-model="topProductsFilterStartDate"
                                type="date"
                                style="flex: 1; padding: 0.375rem; border: 1px solid #e5e7eb; border-radius: 0.375rem; font-size: 0.875rem;"
                            />
                            <input
                                v-model="topProductsFilterEndDate"
                                type="date"
                                style="flex: 1; padding: 0.375rem; border: 1px solid #e5e7eb; border-radius: 0.375rem; font-size: 0.875rem;"
                            />
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <table class="min-w-full divide-y divide-border">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 text-left">Product</th>
                                <th class="px-4 py-2 text-right">Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="p in topProducts" :key="p.id" class="hover:bg-muted">
                                <td class="px-4 py-2">{{ p.name }}</td>
                                <td class="px-4 py-2 text-right">{{ p.total_quantity }}</td>
                            </tr>
                            <tr v-if="topProducts.length === 0">
                                <td colspan="2" class="px-4 py-4 text-sm text-muted-foreground text-center">No purchases yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </CardContent>
            </Card>

            <!-- Category Spend -->
            <Card>
                <CardHeader>
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                        <div>
                            <CardTitle>Spend by category</CardTitle>
                            <CardDescription>Your spending by product category</CardDescription>
                        </div>
                    </div>
                    <div class="filter-group" style="display: flex; flex-direction: column; gap: 0.5rem; margin-top: 1rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <Calendar class="w-4 h-4" />
                            <label style="font-size: 0.875rem; font-weight: 500;">Date Filter</label>
                        </div>
                        <Select
                            v-model="categorySpendFilterPeriod"
                            :options="periodOptions"
                            placeholder="Choose time period"
                            class="period-select"
                        />
                        <div v-if="categorySpendFilterPeriod === 'custom'" style="display: flex; gap: 0.5rem;">
                            <input
                                v-model="categorySpendFilterStartDate"
                                type="date"
                                style="flex: 1; padding: 0.375rem; border: 1px solid #e5e7eb; border-radius: 0.375rem; font-size: 0.875rem;"
                            />
                            <input
                                v-model="categorySpendFilterEndDate"
                                type="date"
                                style="flex: 1; padding: 0.375rem; border: 1px solid #e5e7eb; border-radius: 0.375rem; font-size: 0.875rem;"
                            />
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="space-y-2">
                        <div v-if="categorySpend.length === 0" class="text-sm text-muted-foreground">
                            No spend yet.
                        </div>
                        <div v-else v-for="c in categorySpend" :key="c.category" class="flex items-center gap-3">
                            <div class="w-40 text-xs text-muted-foreground truncate">{{ c.category }}</div>
                            <div class="flex-1 h-2 rounded bg-muted overflow-hidden">
                                <div class="h-2 bg-primary/70" :style="{ width: Math.min(100, Math.round(c.total / (Math.max(...categorySpend.map(m => m.total)) || 1) * 100)) + '%' }"></div>
                            </div>
                            <div class="w-24 text-right text-sm">{{ formatCurrency(c.total) }}</div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- AOV Trend -->
            <Card class="lg:col-span-2">
                <CardHeader>
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                        <div>
                            <CardTitle>Average order value (monthly)</CardTitle>
                            <CardDescription>Your average order value over time</CardDescription>
                        </div>
                    </div>
                    <div class="filter-group" style="display: flex; flex-direction: column; gap: 0.5rem; margin-top: 1rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <Calendar class="w-4 h-4" />
                            <label style="font-size: 0.875rem; font-weight: 500;">Date Filter</label>
                        </div>
                        <Select
                            v-model="aovTrendFilterPeriod"
                            :options="periodOptions"
                            placeholder="Choose time period"
                            class="period-select"
                        />
                        <div v-if="aovTrendFilterPeriod === 'custom'" style="display: flex; gap: 0.5rem;">
                            <input
                                v-model="aovTrendFilterStartDate"
                                type="date"
                                style="flex: 1; padding: 0.375rem; border: 1px solid #e5e7eb; border-radius: 0.375rem; font-size: 0.875rem;"
                            />
                            <input
                                v-model="aovTrendFilterEndDate"
                                type="date"
                                style="flex: 1; padding: 0.375rem; border: 1px solid #e5e7eb; border-radius: 0.375rem; font-size: 0.875rem;"
                            />
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="space-y-2">
                        <div v-if="aovTrend.length === 0" class="text-sm text-muted-foreground">
                            No data yet.
                        </div>
                        <div v-else v-for="p in aovTrend" :key="p.month" class="flex items-center gap-3">
                            <div class="w-24 text-xs text-muted-foreground">{{ p.month }}</div>
                            <div class="flex-1 h-2 rounded bg-muted overflow-hidden">
                                <div class="h-2 bg-emerald-500" :style="{ width: Math.min(100, Math.round(p.aov / (Math.max(...aovTrend.map(m => m.aov)) || 1) * 100)) + '%' }"></div>
                            </div>
                            <div class="w-24 text-right text-sm">{{ formatCurrency(p.aov) }}</div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>







