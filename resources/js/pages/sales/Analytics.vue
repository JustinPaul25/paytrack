<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted } from 'vue';
import { Button } from '@/components/ui/button';
import { Select } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import BaseChart from '@/components/charts/BaseChart.vue';
import { type BreadcrumbItem } from '@/types';
import type { ChartDataset } from 'chart.js';
import { TrendingUp, DollarSign, FileText, Clock } from 'lucide-vue-next';
import { generateSalesForecast, type SalesForecastPoint } from '@/lib/analytics/salesForecast';

interface SalesData {
    total_sales: number;
    total_invoices: number;
    average_order_value: number;
    pending_invoices: number;
}

interface TopProduct {
    id: number;
    name: string;
    total_quantity: number;
    total_revenue: number;
}

interface SalesByDate {
    date: string;
    sales: number;
    invoices: number;
}

interface SalesByCategory {
    category_name: string;
    total_revenue: number;
    total_quantity: number;
}

interface RecentInvoice {
    id: number;
    customer_name: string;
    total_amount: number;
    status: string;
    created_at: string;
    items_count: number;
}

interface Filters {
    period: string;
    start_date: string;
    end_date: string;
}

const props = defineProps<{
    salesData: SalesData;
    topProducts: TopProduct[];
    salesByDate: SalesByDate[];
    salesByCategory: SalesByCategory[];
    recentInvoices: RecentInvoice[];
    filters: Filters;
}>();

const period = ref(props.filters.period);
const startDate = ref(props.filters.start_date);
const endDate = ref(props.filters.end_date);
const forecastedSales = ref<SalesForecastPoint[]>([]);
const forecastLoss = ref<number | null>(null);
const forecastError = ref<string | null>(null);
const isForecastLoading = ref(false);
let forecastRequestId = 0;

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Sales',
        href: '/sales/analytics',
    },
    {
        title: 'Analytics',
        href: '/sales/analytics',
    }
];

const periodOptions = [
    { value: 'week', label: 'Last Week' },
    { value: 'month', label: 'Last Month' },
    { value: 'quarter', label: 'Last Quarter' },
    { value: 'year', label: 'Last Year' },
    { value: 'custom', label: 'Custom Range' },
];

function updateFilters() {
    router.get('/sales/analytics', {
        period: period.value,
        start_date: startDate.value,
        end_date: endDate.value,
    }, {
        preserveState: true,
        replace: true,
    });
}

watch([period, startDate, endDate], () => {
    updateFilters();
});

async function buildForecast() {
    const requestId = ++forecastRequestId;

    if (typeof window === 'undefined') {
        return;
    }

    if (!props.salesByDate.length) {
        forecastedSales.value = [];
        forecastLoss.value = null;
        forecastError.value = null;
        return;
    }

    isForecastLoading.value = true;
    forecastError.value = null;

    try {
        const history = props.salesByDate.map((item) => ({
            date: item.date,
            sales: item.sales,
        }));

        const result = await generateSalesForecast(history);

        if (requestId !== forecastRequestId) {
            return;
        }

        forecastedSales.value = result.points;
        forecastLoss.value = Number.isFinite(result.trainingLoss) ? Number(result.trainingLoss.toFixed(4)) : null;
    } catch (error) {
        forecastedSales.value = [];
        forecastLoss.value = null;
        forecastError.value = error instanceof Error ? error.message : 'Unable to generate forecast';
    } finally {
        isForecastLoading.value = false;
    }
}

onMounted(() => {
    buildForecast();
});

watch(
    () => props.salesByDate,
    () => {
        buildForecast();
    },
    { deep: true }
);

function toChartLabel(dateString: string) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

function formatDisplayDate(dateString: string) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        weekday: 'short',
    });
}

const hasForecast = computed(() => forecastedSales.value.length > 0);

const combinedSalesLabels = computed(() => {
    const historicalLabels = props.salesByDate.map((item) => toChartLabel(item.date));
    const futureLabels = forecastedSales.value.map((item) => toChartLabel(item.date));
    return [...historicalLabels, ...futureLabels];
});

const historicalSalesSeries = computed(() => [
    ...props.salesByDate.map((item) => item.sales),
    ...forecastedSales.value.map(() => null),
]);

const historicalInvoiceSeries = computed(() => [
    ...props.salesByDate.map((item) => item.invoices),
    ...forecastedSales.value.map(() => null),
]);

const forecastSalesSeries = computed(() => {
    if (!hasForecast.value || !props.salesByDate.length) {
        return [];
    }

    const lastHistoricalIndex = props.salesByDate.length - 1;
    const totalLength = combinedSalesLabels.value.length;
    const historicalSales = props.salesByDate.map((item) => item.sales);
    const series: Array<number | null> = [];

    for (let index = 0; index < totalLength; index += 1) {
        if (index < lastHistoricalIndex) {
            series.push(null);
        } else if (index === lastHistoricalIndex) {
            series.push(historicalSales[historicalSales.length - 1]);
        } else {
            const forecastIndex = index - props.salesByDate.length;
            series.push(forecastedSales.value[forecastIndex]?.sales ?? null);
        }
    }

    return series;
});

const nextDayForecast = computed(() => forecastedSales.value[0] ?? null);
const sevenDayForecastTotal = computed(() => forecastedSales.value.reduce((total, item) => total + item.sales, 0));
const averageForecastSales = computed(() => {
    if (!forecastedSales.value.length) {
        return null;
    }

    return sevenDayForecastTotal.value / forecastedSales.value.length;
});

const salesChartData = computed(() => {
    const labels = combinedSalesLabels.value;

    const datasets: ChartDataset<'line', (number | null)[]>[] = [
        {
            label: 'Daily Sales',
            data: historicalSalesSeries.value,
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            fill: true,
            tension: 0.4,
            spanGaps: true,
        },
        {
            label: 'Invoices',
            data: historicalInvoiceSeries.value,
            borderColor: 'rgb(16, 185, 129)',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            fill: false,
            tension: 0.4,
            yAxisID: 'y1',
            spanGaps: true,
        },
    ];

    if (hasForecast.value) {
        datasets.push({
            label: 'Forecasted Sales',
            data: forecastSalesSeries.value,
            borderColor: 'rgb(234, 179, 8)',
            backgroundColor: 'rgba(234, 179, 8, 0.15)',
            borderDash: [6, 6],
            fill: false,
            tension: 0.3,
            spanGaps: true,
        });
    }

    return {
        labels,
        datasets,
    };
});

const salesChartOptions = computed(() => ({
    plugins: {
        title: {
            display: true,
            text: 'Sales Trend',
        },
        legend: {
            position: 'top' as const,
        },
    },
    interaction: {
        mode: 'index' as const,
        intersect: false,
    },
    scales: {
        x: {
            title: {
                display: true,
                text: 'Date',
            },
            ticks: {
                maxRotation: 0,
                autoSkip: true,
            },
        },
        y: {
            type: 'linear' as const,
            display: true,
            position: 'left' as const,
            title: {
                display: true,
                text: 'Sales Amount (₱)',
            },
        },
        y1: {
            type: 'linear' as const,
            display: true,
            position: 'right' as const,
            title: {
                display: true,
                text: 'Number of Invoices',
            },
            grid: {
                drawOnChartArea: false,
            },
        },
    },
}));

const categoryChartData = computed(() => ({
    labels: props.salesByCategory.map(item => item.category_name),
    datasets: [
        {
            data: props.salesByCategory.map(item => item.total_revenue),
            backgroundColor: [
                '#3B82F6',
                '#10B981',
                '#F59E0B',
                '#EF4444',
                '#8B5CF6',
                '#06B6D4',
                '#84CC16',
                '#F97316',
                '#EC4899',
                '#6366F1',
            ],
            borderWidth: 2,
            borderColor: '#ffffff',
        },
    ],
}));

const categoryChartOptions = computed(() => ({
    plugins: {
        title: {
            display: true,
            text: 'Sales by Category',
        },
        legend: {
            position: 'bottom' as const,
        },
    },
}));

const topProductsChartData = computed(() => ({
    labels: props.topProducts.map(item => item.name),
    datasets: [
        {
            label: 'Revenue',
            data: props.topProducts.map(item => item.total_revenue),
            backgroundColor: 'rgba(59, 130, 246, 0.8)',
            borderColor: 'rgb(59, 130, 246)',
            borderWidth: 1,
        },
    ],
}));

const topProductsChartOptions = computed(() => ({
    plugins: {
        title: {
            display: true,
            text: 'Top Products by Revenue',
        },
        legend: {
            display: false,
        },
    },
    scales: {
        y: {
            title: {
                display: true,
                text: 'Revenue (₱)',
            },
        },
    },
}));

function formatCurrency(amount: number) {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2
    }).format(amount);
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Sales Analytics" />
        
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">Sales Analytics</h1>
            
            <!-- Filters -->
            <div class="flex gap-4 items-center">
                <div class="w-48">
                    <Select
                        v-model="period"
                        :options="periodOptions"
                        placeholder="Select period"
                    />
                </div>
                <div v-if="period === 'custom'" class="flex gap-2">
                    <input
                        v-model="startDate"
                        type="date"
                        class="px-3 py-2 border rounded-md"
                    />
                    <input
                        v-model="endDate"
                        type="date"
                        class="px-3 py-2 border rounded-md"
                    />
                </div>
            </div>
        </div>

        <!-- Sales Overview Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <Card>
                <CardContent class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Sales</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                {{ formatCurrency(salesData.total_sales) }}
                            </p>
                        </div>
                        <div class="p-3 bg-blue-100 dark:bg-blue-900/20 rounded-full">
                            <DollarSign class="w-6 h-6 text-blue-600" />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Invoices</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                {{ salesData.total_invoices }}
                            </p>
                        </div>
                        <div class="p-3 bg-green-100 dark:bg-green-900/20 rounded-full">
                            <FileText class="w-6 h-6 text-green-600" />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Average Order Value</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                {{ formatCurrency(salesData.average_order_value) }}
                            </p>
                        </div>
                        <div class="p-3 bg-yellow-100 dark:bg-yellow-900/20 rounded-full">
                            <TrendingUp class="w-6 h-6 text-yellow-600" />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Pending Invoices</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                {{ salesData.pending_invoices }}
                            </p>
                        </div>
                        <div class="p-3 bg-orange-100 dark:bg-orange-900/20 rounded-full">
                            <Clock class="w-6 h-6 text-orange-600" />
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Sales Trend Chart -->
            <Card>
                <CardHeader>
                    <CardTitle>Sales Trend</CardTitle>
                </CardHeader>
                <CardContent>
                    <BaseChart
                        type="line"
                        :data="salesChartData"
                        :options="salesChartOptions"
                        height="300px"
                    />
                </CardContent>
            </Card>

            <!-- Sales by Category Chart -->
            <Card>
                <CardHeader>
                    <CardTitle>Sales by Category</CardTitle>
                </CardHeader>
                <CardContent>
                    <BaseChart
                        type="doughnut"
                        :data="categoryChartData"
                        :options="categoryChartOptions"
                        height="300px"
                    />
                </CardContent>
            </Card>

            <!-- Forecast Insights -->
            <div class="lg:col-span-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Forecast Outlook</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="forecastError" class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            {{ forecastError }}
                        </div>
                        <div v-else>
                            <div v-if="isForecastLoading" class="text-sm text-gray-500">Training TensorFlow.js model...</div>
                            <div v-else>
                                <div v-if="forecastedSales.length" class="space-y-6">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div class="rounded-lg border border-gray-200 p-4 shadow-sm">
                                            <p class="text-xs uppercase tracking-wide text-gray-500">Next Day Projection</p>
                                            <p class="text-2xl font-semibold text-gray-900">
                                                {{ nextDayForecast ? formatCurrency(nextDayForecast.sales) : '—' }}
                                            </p>
                                            <p v-if="nextDayForecast" class="text-xs text-gray-500">{{ formatDisplayDate(nextDayForecast.date) }}</p>
                                        </div>
                                        <div class="rounded-lg border border-gray-200 p-4 shadow-sm">
                                            <p class="text-xs uppercase tracking-wide text-gray-500">7-Day Projected Total</p>
                                            <p class="text-2xl font-semibold text-gray-900">
                                                {{ formatCurrency(sevenDayForecastTotal) }}
                                            </p>
                                            <p class="text-xs text-gray-500">Based on live sales trend</p>
                                        </div>
                                        <div class="rounded-lg border border-gray-200 p-4 shadow-sm">
                                            <p class="text-xs uppercase tracking-wide text-gray-500">Average Daily Projection</p>
                                            <p class="text-2xl font-semibold text-gray-900">
                                                {{ averageForecastSales ? formatCurrency(averageForecastSales) : '—' }}
                                            </p>
                                            <p v-if="forecastLoss !== null" class="text-xs text-gray-500">
                                                Model loss: {{ forecastLoss }}
                                            </p>
                                        </div>
                                    </div>

                                    <div>
                                        <h4 class="text-sm font-semibold text-gray-600 mb-2">Upcoming Daily Forecast</h4>
                                        <div class="overflow-x-auto">
                                            <table class="w-full text-sm">
                                                <thead>
                                                    <tr class="border-b text-left">
                                                        <th class="py-2 pr-4 font-medium text-gray-500">Date</th>
                                                        <th class="py-2 pr-4 font-medium text-gray-500">Projected Sales</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr v-for="point in forecastedSales" :key="point.date" class="border-b last:border-transparent">
                                                        <td class="py-2 pr-4 text-gray-700">{{ formatDisplayDate(point.date) }}</td>
                                                        <td class="py-2 pr-4 font-medium text-gray-900">{{ formatCurrency(point.sales) }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-sm text-gray-500">Not enough historical data to generate a forecast yet.</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Tables Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Top Products Table -->
            <Card>
                <CardHeader>
                    <CardTitle>Top Products</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b">
                                    <th class="text-left py-3 px-4 font-medium">Product</th>
                                    <th class="text-right py-3 px-4 font-medium">Quantity</th>
                                    <th class="text-right py-3 px-4 font-medium">Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="product in topProducts" :key="product.id" class="border-b hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <td class="py-3 px-4">{{ product.name }}</td>
                                    <td class="py-3 px-4 text-right">{{ product.total_quantity }}</td>
                                    <td class="py-3 px-4 text-right font-medium">{{ formatCurrency(product.total_revenue) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            <!-- Recent Invoices Table -->
            <Card>
                <CardHeader>
                    <CardTitle>Recent Invoices</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b">
                                    <th class="text-left py-3 px-4 font-medium">Invoice #</th>
                                    <th class="text-left py-3 px-4 font-medium">Customer</th>
                                    <th class="text-right py-3 px-4 font-medium">Amount</th>
                                    <th class="text-center py-3 px-4 font-medium">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="invoice in recentInvoices" :key="invoice.id" class="border-b hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <td class="py-3 px-4">
                                        <a :href="`/invoices/${invoice.id}`" class="text-blue-600 hover:underline">
                                            #{{ invoice.id }}
                                        </a>
                                    </td>
                                    <td class="py-3 px-4">{{ invoice.customer_name }}</td>
                                    <td class="py-3 px-4 text-right font-medium">{{ formatCurrency(invoice.total_amount) }}</td>
                                    <td class="py-3 px-4 text-center">
                                        <span :class="{
                                            'px-2 py-1 text-xs rounded-full': true,
                                            'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400': invoice.status === 'paid',
                                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400': invoice.status === 'pending',
                                            'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400': invoice.status === 'cancelled'
                                        }">
                                            {{ invoice.status }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template> 