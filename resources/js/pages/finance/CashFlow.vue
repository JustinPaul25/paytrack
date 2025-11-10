<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import BaseChart from '@/components/charts/BaseChart.vue';
import { Select } from '@/components/ui/select';
import type { BreadcrumbItem } from '@/types';
import { ArrowDownCircle, ArrowUpCircle, BarChart3, PiggyBank, TrendingUp } from 'lucide-vue-next';

interface CashFlowPoint {
    month: string;
    label: string;
    income: number;
    expenses: number;
    net: number;
    running_balance: number;
}

interface CashFlowSummaries {
    average_income: number;
    average_expenses: number;
    average_net: number;
    current_cash_position: number;
    current_month_net: number;
    next_month_projection: number;
    projected_three_month_net: number;
    cash_runway_months: number;
}

const props = defineProps<{
    filters: {
        months: number;
        forecast_months: number;
    };
    historical: CashFlowPoint[];
    projections: CashFlowPoint[];
    summaries: CashFlowSummaries;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Sales', href: '/sales/analytics' },
    { title: 'Cash Flow', href: '/finance/cash-flow' },
];

const historyMonths = ref(props.filters.months);
const forecastMonths = ref(props.filters.forecast_months);
const historical = computed(() => props.historical);
const projections = computed(() => props.projections);
const summaries = computed(() => props.summaries);

watch(() => props.filters.months, (value) => {
    if (typeof value === 'number' && historyMonths.value !== value) {
        historyMonths.value = value;
    }
});

watch(() => props.filters.forecast_months, (value) => {
    if (typeof value === 'number' && forecastMonths.value !== value) {
        forecastMonths.value = value;
    }
});

watch([historyMonths, forecastMonths], () => {
    router.get('/finance/cash-flow', {
        months: historyMonths.value,
        forecast_months: forecastMonths.value,
    }, {
        preserveState: true,
        replace: true,
    });
});

const combinedSeries = computed(() => [
    ...historical.value,
    ...projections.value.map((point) => ({
        ...point,
        isForecast: true,
    })),
]);

const chartLabels = computed(() => combinedSeries.value.map((point) => point.label));

const actualNetSeries = computed(() => historical.value.map((point) => point.net));
const forecastNetSeries = computed(() => combinedSeries.value.map((point, index) => (
    index >= historical.value.length ? point.net : null
)));

const runningBalanceSeries = computed(() => combinedSeries.value.map((point) => point.running_balance));

const cashFlowChartData = computed(() => ({
    labels: chartLabels.value,
    datasets: [
        {
            label: 'Net Cash Flow',
            data: [
                ...actualNetSeries.value,
                ...projections.value.map(() => null),
            ],
            borderColor: 'rgb(34, 197, 94)',
            backgroundColor: 'rgba(34, 197, 94, 0.1)',
            tension: 0.35,
            fill: true,
        },
        {
            label: 'Projected Net Cash Flow',
            data: forecastNetSeries.value,
            borderColor: 'rgb(234, 179, 8)',
            backgroundColor: 'rgba(234, 179, 8, 0.1)',
            tension: 0.35,
            borderDash: [6, 6],
            fill: false,
        },
        {
            label: 'Running Cash Position',
            data: runningBalanceSeries.value,
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            yAxisID: 'y1',
            tension: 0.3,
            fill: false,
        },
    ],
}));

const cashFlowChartOptions = computed(() => ({
    plugins: {
        legend: {
            position: 'top' as const,
        },
        tooltip: {
            mode: 'index' as const,
            intersect: false,
        },
    },
    interaction: {
        mode: 'index' as const,
        intersect: false,
    },
    scales: {
        y: {
            title: {
                display: true,
                text: 'Net Cash Flow (₱)',
            },
        },
        y1: {
            position: 'right' as const,
            title: {
                display: true,
                text: 'Cash Position (₱)',
            },
            grid: {
                drawOnChartArea: false,
            },
        },
    },
}));

function formatCurrency(amount: number): string {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2,
    }).format(amount);
}

const historyOptions = [
    { label: 'Last 3 Months', value: 3 },
    { label: 'Last 6 Months', value: 6 },
    { label: 'Last 12 Months', value: 12 },
    { label: 'Last 18 Months', value: 18 },
    { label: 'Last 24 Months', value: 24 },
];

const forecastOptions = [
    { label: 'Forecast 3 Months', value: 3 },
    { label: 'Forecast 6 Months', value: 6 },
    { label: 'Forecast 9 Months', value: 9 },
    { label: 'Forecast 12 Months', value: 12 },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Cash Flow Projections" />

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold">Cash Flow Projections</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Track actual cash performance and forecast runway across upcoming periods.
                </p>
            </div>
            <div class="flex gap-4">
                <div class="w-48">
                    <Select
                        v-model="historyMonths"
                        :options="historyOptions"
                        placeholder="History Range"
                    />
                </div>
                <div class="w-48">
                    <Select
                        v-model="forecastMonths"
                        :options="forecastOptions"
                        placeholder="Forecast Range"
                    />
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <Card>
                <CardContent class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Average Monthly Net</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                                {{ formatCurrency(summaries.average_net) }}
                            </p>
                        </div>
                        <div class="h-12 w-12 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                            <TrendingUp class="h-6 w-6 text-green-600" />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Cash Position</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                                {{ formatCurrency(summaries.current_cash_position) }}
                            </p>
                        </div>
                        <div class="h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                            <PiggyBank class="h-6 w-6 text-blue-600" />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Next Month Projection</p>
                            <p class="text-2xl font-semibold" :class="summaries.next_month_projection >= 0 ? 'text-green-600' : 'text-red-600'">
                                {{ formatCurrency(summaries.next_month_projection) }}
                            </p>
                        </div>
                        <div class="h-12 w-12 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                            <BarChart3 class="h-6 w-6 text-amber-500" />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Cash Runway</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                                {{ summaries.cash_runway_months }} months
                            </p>
                        </div>
                        <div class="h-12 w-12 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                            <ArrowUpCircle v-if="summaries.cash_runway_months >= 6" class="h-6 w-6 text-purple-600" />
                            <ArrowDownCircle v-else class="h-6 w-6 text-purple-600" />
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <Card class="mb-8">
            <CardHeader>
                <CardTitle>Net Cash Flow & Runway</CardTitle>
            </CardHeader>
            <CardContent>
                <BaseChart
                    type="line"
                    :data="cashFlowChartData"
                    :options="cashFlowChartOptions"
                    height="360px"
                />
            </CardContent>
        </Card>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <Card>
                <CardHeader>
                    <CardTitle>Historical Performance</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-4 py-2 text-left text-sm font-semibold text-gray-600">Month</th>
                                    <th class="px-4 py-2 text-right text-sm font-semibold text-gray-600">Income</th>
                                    <th class="px-4 py-2 text-right text-sm font-semibold text-gray-600">Expenses</th>
                                    <th class="px-4 py-2 text-right text-sm font-semibold text-gray-600">Net</th>
                                    <th class="px-4 py-2 text-right text-sm font-semibold text-gray-600">Cash</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="point in historical" :key="`hist-${point.month}`" class="border-b last:border-0">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-700">{{ point.label }}</td>
                                    <td class="px-4 py-3 text-sm text-right text-green-600">{{ formatCurrency(point.income) }}</td>
                                    <td class="px-4 py-3 text-sm text-right text-red-600">{{ formatCurrency(point.expenses) }}</td>
                                    <td class="px-4 py-3 text-sm text-right font-semibold" :class="point.net >= 0 ? 'text-green-600' : 'text-red-600'">
                                        {{ formatCurrency(point.net) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-700">{{ formatCurrency(point.running_balance) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Projection Outlook</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-4 py-2 text-left text-sm font-semibold text-gray-600">Month</th>
                                    <th class="px-4 py-2 text-right text-sm font-semibold text-gray-600">Projected Income</th>
                                    <th class="px-4 py-2 text-right text-sm font-semibold text-gray-600">Projected Expenses</th>
                                    <th class="px-4 py-2 text-right text-sm font-semibold text-gray-600">Projected Net</th>
                                    <th class="px-4 py-2 text-right text-sm font-semibold text-gray-600">Projected Cash</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="!projections.length">
                                    <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">
                                        Not enough history to generate a forecast yet.
                                    </td>
                                </tr>
                                <tr v-for="point in projections" :key="`proj-${point.month}`" class="border-b last:border-0">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-700">{{ point.label }}</td>
                                    <td class="px-4 py-3 text-sm text-right text-green-600">{{ formatCurrency(point.income) }}</td>
                                    <td class="px-4 py-3 text-sm text-right text-red-600">{{ formatCurrency(point.expenses) }}</td>
                                    <td class="px-4 py-3 text-sm text-right font-semibold" :class="point.net >= 0 ? 'text-green-600' : 'text-red-600'">
                                        {{ formatCurrency(point.net) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-700">{{ formatCurrency(point.running_balance) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Summary Insights</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="rounded-lg border border-gray-200 p-4">
                        <p class="text-xs uppercase tracking-wide text-gray-500">Average Monthly Income</p>
                        <p class="mt-1 text-xl font-semibold text-gray-900">{{ formatCurrency(summaries.average_income) }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 p-4">
                        <p class="text-xs uppercase tracking-wide text-gray-500">Average Monthly Expenses</p>
                        <p class="mt-1 text-xl font-semibold text-gray-900">{{ formatCurrency(summaries.average_expenses) }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 p-4">
                        <p class="text-xs uppercase tracking-wide text-gray-500">Projected Net (Next 3 Months)</p>
                        <p class="mt-1 text-xl font-semibold" :class="summaries.projected_three_month_net >= 0 ? 'text-green-600' : 'text-red-600'">
                            {{ formatCurrency(summaries.projected_three_month_net) }}
                        </p>
                    </div>
                </div>
            </CardContent>
        </Card>
    </AppLayout>
</template>

