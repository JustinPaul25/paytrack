<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem } from '@/types';
import { FileSpreadsheet, Printer } from 'lucide-vue-next';

interface ReportRow {
    month: string;
    label: string;
    income: number;
    expenses: number;
    net: number;
}

interface Totals {
    income: number;
    expenses: number;
    net: number;
}

const props = defineProps<{
    filters: {
        start_month: string;
        end_month: string;
    };
    rows: ReportRow[];
    totals: Totals;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Sales', href: '/sales/analytics' },
    { title: 'Financial Reports', href: '/finance/reports' },
];

const startMonth = ref(props.filters.start_month);
const endMonth = ref(props.filters.end_month);

watch(() => props.filters.start_month, (value) => {
    if (value && startMonth.value !== value) {
        startMonth.value = value;
    }
});

watch(() => props.filters.end_month, (value) => {
    if (value && endMonth.value !== value) {
        endMonth.value = value;
    }
});

watch([startMonth, endMonth], () => {
    if (!startMonth.value || !endMonth.value) {
        return;
    }

    router.get('/finance/reports', {
        start_month: startMonth.value,
        end_month: endMonth.value,
    }, {
        preserveState: true,
        replace: true,
    });
});

const hasRows = computed(() => props.rows.length > 0);

function formatCurrency(amount: number): string {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2,
    }).format(amount);
}

function handlePrint() {
    window.print();
}

function handleExport() {
    const url = route('finance.reports.export', {
        start_month: startMonth.value,
        end_month: endMonth.value,
    });

    window.location.href = url;
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Financial Reports" />

        <div class="flex flex-col gap-6 print:gap-4">
            <div class="flex items-start justify-between flex-wrap gap-4 print:hidden">
                <div>
                    <h1 class="text-2xl font-bold">Financial Reports</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Review monthly income, expenses, and net cash results in a printable, exportable table.
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" @click="handlePrint">
                        <Printer class="h-4 w-4 mr-2" />
                        Print
                    </Button>
                    <Button variant="default" @click="handleExport">
                        <FileSpreadsheet class="h-4 w-4 mr-2" />
                        Export CSV
                    </Button>
                </div>
            </div>

            <Card>
                <CardHeader class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <CardTitle>Reporting Period</CardTitle>
                    <div class="flex gap-4 flex-wrap print:hidden">
                        <div class="flex flex-col">
                            <label for="start-month" class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                Start Month
                            </label>
                            <input
                                id="start-month"
                                v-model="startMonth"
                                type="month"
                                class="mt-1 rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                            />
                        </div>
                        <div class="flex flex-col">
                            <label for="end-month" class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                End Month
                            </label>
                            <input
                                id="end-month"
                                v-model="endMonth"
                                type="month"
                                class="mt-1 rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                            />
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div v-if="hasRows" class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 print:divide-gray-300">
                            <thead class="bg-gray-50 print:bg-white">
                                <tr>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600 uppercase tracking-wide">
                                        Month
                                    </th>
                                    <th class="px-4 py-3 text-right text-sm font-semibold text-gray-600 uppercase tracking-wide">
                                        Income (₱)
                                    </th>
                                    <th class="px-4 py-3 text-right text-sm font-semibold text-gray-600 uppercase tracking-wide">
                                        Expenses (₱)
                                    </th>
                                    <th class="px-4 py-3 text-right text-sm font-semibold text-gray-600 uppercase tracking-wide">
                                        Net (₱)
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 print:divide-gray-300">
                                <tr v-for="row in rows" :key="row.month" class="bg-white even:bg-gray-50 print:bg-white">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-800">
                                        {{ row.label }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm text-green-600 font-semibold">
                                        {{ formatCurrency(row.income) }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm text-red-600 font-semibold">
                                        {{ formatCurrency(row.expenses) }}
                                    </td>
                                    <td
                                        class="px-4 py-3 text-right text-sm font-semibold"
                                        :class="row.net >= 0 ? 'text-green-700' : 'text-red-700'"
                                    >
                                        {{ formatCurrency(row.net) }}
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray-100 print:bg-gray-200">
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 uppercase tracking-wide">
                                        Totals
                                    </th>
                                    <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700">
                                        {{ formatCurrency(totals.income) }}
                                    </th>
                                    <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700">
                                        {{ formatCurrency(totals.expenses) }}
                                    </th>
                                    <th
                                        class="px-4 py-3 text-right text-sm font-semibold"
                                        :class="totals.net >= 0 ? 'text-green-700' : 'text-red-700'"
                                    >
                                        {{ formatCurrency(totals.net) }}
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div v-else class="py-16 text-center text-sm text-gray-500">
                        No financial activity found for the selected period.
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

