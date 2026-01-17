<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import { Button } from '@/components/ui/button';
import { Select } from '@/components/ui/select';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs';
import { type BreadcrumbItem } from '@/types';
import { Printer, FileText, TrendingUp, CreditCard, Truck } from 'lucide-vue-next';

interface SalesReport {
    total_sales: number;
    total_invoices: number;
    average_order_value: number;
    sales_by_customer: Array<{
        customer_name: string;
        company_name?: string;
        total_invoices: number;
        total_amount: number;
    }>;
    sales_by_month: Array<{
        month: string;
        total_sales: number;
        invoice_count: number;
    }>;
}

interface Transaction {
    id: number;
    invoice_number: string;
    customer_name: string;
    company_name?: string;
    product_name: string;
    quantity: number;
    total_amount: number;
    status: string;
    payment_method: string;
    transaction_date: string;
}

interface FinancialReport {
    rows: Array<{
        month: string;
        income: number;
        expenses: number;
        net: number;
    }>;
    totals: {
        income: number;
        expenses: number;
        net: number;
    };
}

interface Delivery {
    id: number;
    customer_name: string;
    delivery_address: string;
    contact_person: string;
    contact_phone: string;
    delivery_date: string;
    delivery_time: string;
    status: string;
    delivery_fee: number;
    invoice_number: string;
}

interface DeliverySummary {
    total: number;
    pending: number;
    completed: number;
    cancelled: number;
    total_fee: number;
}

const page = usePage();

// Default values for reports
const defaultSalesReport: SalesReport = {
    total_sales: 0,
    total_invoices: 0,
    average_order_value: 0,
    sales_by_customer: [],
    sales_by_month: [],
};

const defaultFinancialReport: FinancialReport = {
    rows: [],
    totals: {
        income: 0,
        expenses: 0,
        net: 0,
    },
};

const defaultDeliverySummary: DeliverySummary = {
    total: 0,
    pending: 0,
    completed: 0,
    cancelled: 0,
    total_fee: 0,
};

// Use computed to make data reactive to prop changes with safe defaults
const salesReport = computed<SalesReport>(() => {
    try {
        const data = page.props.salesReport as SalesReport | undefined;
        if (!data || typeof data !== 'object' || !('total_sales' in data)) {
            return defaultSalesReport;
        }
        return {
            total_sales: data.total_sales ?? 0,
            total_invoices: data.total_invoices ?? 0,
            average_order_value: data.average_order_value ?? 0,
            sales_by_customer: Array.isArray(data.sales_by_customer) ? data.sales_by_customer : [],
            sales_by_month: Array.isArray(data.sales_by_month) ? data.sales_by_month : [],
        };
    } catch (e) {
        return defaultSalesReport;
    }
});

const transactions = computed<Transaction[]>(() => {
    const data = page.props.transactions as Transaction[] | undefined;
    return Array.isArray(data) ? data : [];
});

const financialReport = computed<FinancialReport>(() => {
    const data = page.props.financialReport as FinancialReport | undefined;
    if (!data || typeof data !== 'object') {
        return defaultFinancialReport;
    }
    return {
        rows: Array.isArray(data.rows) ? data.rows : [],
        totals: {
            income: data.totals?.income ?? 0,
            expenses: data.totals?.expenses ?? 0,
            net: data.totals?.net ?? 0,
        },
    };
});

const deliveries = computed<Delivery[]>(() => {
    const data = page.props.deliveries as Delivery[] | undefined;
    return Array.isArray(data) ? data : [];
});

const deliverySummary = computed<DeliverySummary>(() => {
    const data = page.props.deliverySummary as DeliverySummary | undefined;
    if (!data || typeof data !== 'object') {
        return defaultDeliverySummary;
    }
    return {
        total: data.total ?? 0,
        pending: data.pending ?? 0,
        completed: data.completed ?? 0,
        cancelled: data.cancelled ?? 0,
        total_fee: data.total_fee ?? 0,
    };
});

const filters = computed(() => page.props.filters ? (page.props.filters as any) : {});

const filterType = ref(filters.value.filter_type || 'all');
const filterMonth = ref(filters.value.filter_month || '');
const filterYear = ref(filters.value.filter_year || new Date().getFullYear().toString());
const startDate = ref(filters.value.start_date || '');
const endDate = ref(filters.value.end_date || '');

// Financial Report specific filters
const financialFilterType = ref(filters.value.financial_filter_type || 'all');
const financialFilterMonth = ref(filters.value.financial_filter_month || '');
const financialFilterYear = ref(filters.value.financial_filter_year || new Date().getFullYear().toString());
const financialStartDate = ref(filters.value.financial_start_date || '');
const financialEndDate = ref(filters.value.financial_end_date || '');

// Transactions Report specific filters
const transactionFilterType = ref(filters.value.transaction_filter_type || 'all');
const transactionFilterMonth = ref(filters.value.transaction_filter_month || '');
const transactionFilterYear = ref(filters.value.transaction_filter_year || new Date().getFullYear().toString());
const transactionStartDate = ref(filters.value.transaction_start_date || '');
const transactionEndDate = ref(filters.value.transaction_end_date || '');

// Watch for prop changes and update local filter refs
watch(() => page.props.filters, (newFilters) => {
    if (newFilters) {
        const f = newFilters as any;
        filterType.value = f.filter_type || 'all';
        filterMonth.value = f.filter_month || '';
        filterYear.value = f.filter_year || new Date().getFullYear().toString();
        startDate.value = f.start_date || '';
        endDate.value = f.end_date || '';
        
        // Financial report filters
        financialFilterType.value = f.financial_filter_type || 'all';
        financialFilterMonth.value = f.financial_filter_month || '';
        financialFilterYear.value = f.financial_filter_year || new Date().getFullYear().toString();
        financialStartDate.value = f.financial_start_date || '';
        financialEndDate.value = f.financial_end_date || '';
        
        // Transaction report filters
        transactionFilterType.value = f.transaction_filter_type || 'all';
        transactionFilterMonth.value = f.transaction_filter_month || '';
        transactionFilterYear.value = f.transaction_filter_year || new Date().getFullYear().toString();
        transactionStartDate.value = f.transaction_start_date || '';
        transactionEndDate.value = f.transaction_end_date || '';
    }
}, { immediate: true });

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Reports', href: '/reports' },
];

const filterTypeOptions = [
    { value: 'all', label: 'All Time' },
    { value: 'month', label: 'Per Month' },
    { value: 'year', label: 'Per Year' },
    { value: 'date_range', label: 'Date Range' },
];

// Generate month options (last 12 months)
const monthOptions = computed(() => {
    const months = [];
    const now = new Date();
    for (let i = 0; i < 12; i++) {
        const date = new Date(now.getFullYear(), now.getMonth() - i, 1);
        const value = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
        const label = date.toLocaleDateString('en-US', { year: 'numeric', month: 'long' });
        months.push({ value, label });
    }
    return months;
});

// Generate year options (last 10 years)
const yearOptions = computed(() => {
    const years = [];
    const currentYear = new Date().getFullYear();
    for (let i = 0; i < 10; i++) {
        const year = currentYear - i;
        years.push({ value: year.toString(), label: year.toString() });
    }
    return years;
});

let filterTimeout: ReturnType<typeof setTimeout> | null = null;
let financialFilterTimeout: ReturnType<typeof setTimeout> | null = null;
let transactionFilterTimeout: ReturnType<typeof setTimeout> | null = null;

watch([filterType, filterMonth, filterYear, startDate, endDate], () => {
    if (filterTimeout) clearTimeout(filterTimeout);
    filterTimeout = setTimeout(() => {
        updateFilters();
    }, 300);
});

watch([financialFilterType, financialFilterMonth, financialFilterYear, financialStartDate, financialEndDate], () => {
    if (financialFilterTimeout) clearTimeout(financialFilterTimeout);
    financialFilterTimeout = setTimeout(() => {
        updateFinancialFilters();
    }, 300);
});

watch([transactionFilterType, transactionFilterMonth, transactionFilterYear, transactionStartDate, transactionEndDate], () => {
    if (transactionFilterTimeout) clearTimeout(transactionFilterTimeout);
    transactionFilterTimeout = setTimeout(() => {
        updateTransactionFilters();
    }, 300);
});

function updateFilters() {
    const params: any = {};
    
    if (filterType.value !== 'all') {
        params.filter_type = filterType.value;
        
        if (filterType.value === 'month' && filterMonth.value) {
            params.filter_month = filterMonth.value;
        } else if (filterType.value === 'year' && filterYear.value) {
            params.filter_year = filterYear.value;
        } else if (filterType.value === 'date_range' && startDate.value && endDate.value) {
            params.start_date = startDate.value;
            params.end_date = endDate.value;
        }
    }
    
    // Preserve financial report filters
    if (financialFilterType.value !== 'all') {
        params.financial_filter_type = financialFilterType.value;
        if (financialFilterType.value === 'month' && financialFilterMonth.value) {
            params.financial_filter_month = financialFilterMonth.value;
        } else if (financialFilterType.value === 'year' && financialFilterYear.value) {
            params.financial_filter_year = financialFilterYear.value;
        } else if (financialFilterType.value === 'date_range' && financialStartDate.value && financialEndDate.value) {
            params.financial_start_date = financialStartDate.value;
            params.financial_end_date = financialEndDate.value;
        }
    }
    
    // Preserve transaction filters
    if (transactionFilterType.value !== 'all') {
        params.transaction_filter_type = transactionFilterType.value;
        if (transactionFilterType.value === 'month' && transactionFilterMonth.value) {
            params.transaction_filter_month = transactionFilterMonth.value;
        } else if (transactionFilterType.value === 'year' && transactionFilterYear.value) {
            params.transaction_filter_year = transactionFilterYear.value;
        } else if (transactionFilterType.value === 'date_range' && transactionStartDate.value && transactionEndDate.value) {
            params.transaction_start_date = transactionStartDate.value;
            params.transaction_end_date = transactionEndDate.value;
        }
    }
    
    router.get('/reports', params, { preserveState: true, replace: true });
}

function updateFinancialFilters() {
    const params: any = {};
    
    // Preserve global filters
    if (filterType.value !== 'all') {
        params.filter_type = filterType.value;
        if (filterType.value === 'month' && filterMonth.value) {
            params.filter_month = filterMonth.value;
        } else if (filterType.value === 'year' && filterYear.value) {
            params.filter_year = filterYear.value;
        } else if (filterType.value === 'date_range' && startDate.value && endDate.value) {
            params.start_date = startDate.value;
            params.end_date = endDate.value;
        }
    }
    
    // Add financial report filters
    if (financialFilterType.value !== 'all') {
        params.financial_filter_type = financialFilterType.value;
        
        if (financialFilterType.value === 'month' && financialFilterMonth.value) {
            params.financial_filter_month = financialFilterMonth.value;
        } else if (financialFilterType.value === 'year' && financialFilterYear.value) {
            params.financial_filter_year = financialFilterYear.value;
        } else if (financialFilterType.value === 'date_range' && financialStartDate.value && financialEndDate.value) {
            params.financial_start_date = financialStartDate.value;
            params.financial_end_date = financialEndDate.value;
        }
    }
    
    // Preserve transaction filters
    if (transactionFilterType.value !== 'all') {
        params.transaction_filter_type = transactionFilterType.value;
        if (transactionFilterType.value === 'month' && transactionFilterMonth.value) {
            params.transaction_filter_month = transactionFilterMonth.value;
        } else if (transactionFilterType.value === 'year' && transactionFilterYear.value) {
            params.transaction_filter_year = transactionFilterYear.value;
        } else if (transactionFilterType.value === 'date_range' && transactionStartDate.value && transactionEndDate.value) {
            params.transaction_start_date = transactionStartDate.value;
            params.transaction_end_date = transactionEndDate.value;
        }
    }
    
    router.get('/reports', params, { preserveState: true, replace: true });
}

function updateTransactionFilters() {
    const params: any = {};
    
    // Preserve global filters
    if (filterType.value !== 'all') {
        params.filter_type = filterType.value;
        if (filterType.value === 'month' && filterMonth.value) {
            params.filter_month = filterMonth.value;
        } else if (filterType.value === 'year' && filterYear.value) {
            params.filter_year = filterYear.value;
        } else if (filterType.value === 'date_range' && startDate.value && endDate.value) {
            params.start_date = startDate.value;
            params.end_date = endDate.value;
        }
    }
    
    // Preserve financial filters
    if (financialFilterType.value !== 'all') {
        params.financial_filter_type = financialFilterType.value;
        if (financialFilterType.value === 'month' && financialFilterMonth.value) {
            params.financial_filter_month = financialFilterMonth.value;
        } else if (financialFilterType.value === 'year' && financialFilterYear.value) {
            params.financial_filter_year = financialFilterYear.value;
        } else if (financialFilterType.value === 'date_range' && financialStartDate.value && financialEndDate.value) {
            params.financial_start_date = financialStartDate.value;
            params.financial_end_date = financialEndDate.value;
        }
    }
    
    // Add transaction report filters
    if (transactionFilterType.value !== 'all') {
        params.transaction_filter_type = transactionFilterType.value;
        
        if (transactionFilterType.value === 'month' && transactionFilterMonth.value) {
            params.transaction_filter_month = transactionFilterMonth.value;
        } else if (transactionFilterType.value === 'year' && transactionFilterYear.value) {
            params.transaction_filter_year = transactionFilterYear.value;
        } else if (transactionFilterType.value === 'date_range' && transactionStartDate.value && transactionEndDate.value) {
            params.transaction_start_date = transactionStartDate.value;
            params.transaction_end_date = transactionEndDate.value;
        }
    }
    
    router.get('/reports', params, { preserveState: true, replace: true });
}

function formatCurrency(amount: number | null | undefined) {
    const value = amount ?? 0;
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2
    }).format(value);
}

// Helper function to format filter text
function formatFilterText(filterType: string, filterMonth: string, filterYear: string, startDate: string, endDate: string): string {
    switch (filterType) {
        case 'month':
            if (filterMonth) {
                try {
                    const date = new Date(filterMonth + '-01');
                    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long' });
                } catch (e) {
                    return 'All Time';
                }
            }
            return 'All Time';
            
        case 'year':
            if (filterYear) {
                return filterYear;
            }
            return 'All Time';
            
        case 'date_range':
            if (startDate && endDate) {
                const start = new Date(startDate);
                const end = new Date(endDate);
                return `${start.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })} - ${end.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}`;
            }
            return 'All Time';
            
        default:
            return 'All Time';
    }
}

// Computed properties for active filter text
const activeSalesFilterText = computed(() => {
    return formatFilterText(filterType.value, filterMonth.value, filterYear.value, startDate.value, endDate.value);
});

const activeFinancialFilterText = computed(() => {
    const type = financialFilterType.value !== 'all' ? financialFilterType.value : filterType.value;
    const month = financialFilterMonth.value || filterMonth.value;
    const year = financialFilterYear.value || filterYear.value;
    const start = financialStartDate.value || startDate.value;
    const end = financialEndDate.value || endDate.value;
    return formatFilterText(type, month, year, start, end);
});

const activeDeliveryFilterText = computed(() => {
    return formatFilterText(filterType.value, filterMonth.value, filterYear.value, startDate.value, endDate.value);
});

const activeTransactionFilterText = computed(() => {
    const type = transactionFilterType.value !== 'all' ? transactionFilterType.value : filterType.value;
    const month = transactionFilterMonth.value || filterMonth.value;
    const year = transactionFilterYear.value || filterYear.value;
    const start = transactionStartDate.value || startDate.value;
    const end = transactionEndDate.value || endDate.value;
    return formatFilterText(type, month, year, start, end);
});

// Check if filters are active
const isSalesFilterActive = computed(() => filterType.value !== 'all');
const isFinancialFilterActive = computed(() => financialFilterType.value !== 'all' || filterType.value !== 'all');
const isDeliveryFilterActive = computed(() => filterType.value !== 'all');
const isTransactionFilterActive = computed(() => transactionFilterType.value !== 'all' || filterType.value !== 'all');

function printReport() {
    window.print();
}

function printAllReports() {
    const params: any = {};
    
    if (filterType.value !== 'all') {
        params.filter_type = filterType.value;
        
        if (filterType.value === 'month' && filterMonth.value) {
            params.filter_month = filterMonth.value;
        } else if (filterType.value === 'year' && filterYear.value) {
            params.filter_year = filterYear.value;
        } else if (filterType.value === 'date_range' && startDate.value && endDate.value) {
            params.start_date = startDate.value;
            params.end_date = endDate.value;
        }
    }
    
    // Build query string
    const queryString = new URLSearchParams(params).toString();
    const url = `/reports/print-all${queryString ? '?' + queryString : ''}`;
    
    // Open in new window for printing
    window.open(url, '_blank');
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Reports" />
        
        <div class="no-print flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">Comprehensive Reports</h1>
            <div class="flex gap-2">
                <Button @click="printAllReports" variant="default">
                    <Printer class="h-4 w-4 mr-2" />
                    Print All Reports
                </Button>
                <Button @click="printReport" variant="outline">
                    <Printer class="h-4 w-4 mr-2" />
                    Print Current Tab
                </Button>
            </div>
        </div>

        <!-- Filters -->
        <Card class="no-print mb-6">
            <CardHeader>
                <CardTitle>Filters - Sales Report & Delivery Summary</CardTitle>
            </CardHeader>
            <CardContent>
                <p class="text-sm text-gray-600 mb-4">These filters apply to the <strong>Sales Report</strong> and <strong>Delivery Summary</strong> tabs.</p>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Filter Type</label>
                        <Select
                            v-model="filterType"
                            :options="filterTypeOptions"
                            placeholder="Select filter"
                            class="w-full"
                        />
                    </div>
                    <div v-if="filterType === 'month'">
                        <label class="block text-sm font-medium mb-1">Month</label>
                        <Select
                            v-model="filterMonth"
                            :options="monthOptions"
                            placeholder="Select month"
                            class="w-full"
                        />
                    </div>
                    <div v-if="filterType === 'year'">
                        <label class="block text-sm font-medium mb-1">Year</label>
                        <Select
                            v-model="filterYear"
                            :options="yearOptions"
                            placeholder="Select year"
                            class="w-full"
                        />
                    </div>
                    <div v-if="filterType === 'date_range'">
                        <label class="block text-sm font-medium mb-1">Start Date</label>
                        <input
                            v-model="startDate"
                            type="date"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        />
                    </div>
                    <div v-if="filterType === 'date_range'">
                        <label class="block text-sm font-medium mb-1">End Date</label>
                        <input
                            v-model="endDate"
                            type="date"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        />
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Tabs -->
        <Tabs default-value="sales" class="w-full">
            <TabsList class="grid w-full grid-cols-4 no-print">
                <TabsTrigger value="sales">
                    <TrendingUp class="h-4 w-4 mr-2" />
                    Sales
                </TabsTrigger>
                <TabsTrigger value="financial">
                    <FileText class="h-4 w-4 mr-2" />
                    Financial Report
                </TabsTrigger>
                <TabsTrigger value="delivery">
                    <Truck class="h-4 w-4 mr-2" />
                    Delivery Summary
                </TabsTrigger>
                <TabsTrigger value="transactions">
                    <CreditCard class="h-4 w-4 mr-2" />
                    Transactions Report
                </TabsTrigger>
            </TabsList>

            <!-- Sales Tab -->
            <TabsContent value="sales" class="print-break">
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <CardTitle class="flex items-center gap-2">
                                <TrendingUp class="h-5 w-5" />
                                Sales Report
                            </CardTitle>
                            <div v-if="isSalesFilterActive" class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">
                                Filter: {{ activeSalesFilterText }}
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="mb-4 grid grid-cols-3 gap-4">
                            <div class="bg-blue-50 p-3 rounded">
                                <p class="text-sm text-gray-600">Total Sales</p>
                                <p class="text-xl font-bold text-blue-600">{{ formatCurrency(salesReport?.total_sales) }}</p>
                            </div>
                            <div class="bg-green-50 p-3 rounded">
                                <p class="text-sm text-gray-600">Total Invoices</p>
                                <p class="text-xl font-bold text-green-600">{{ salesReport?.total_invoices ?? 0 }}</p>
                            </div>
                            <div class="bg-purple-50 p-3 rounded">
                                <p class="text-sm text-gray-600">Average Order Value</p>
                                <p class="text-xl font-bold text-purple-600">{{ formatCurrency(salesReport?.average_order_value) }}</p>
                            </div>
                        </div>

                        <h3 class="font-semibold mb-2 mt-4">Sales by Month</h3>
                        <table class="min-w-full border-collapse border border-gray-300">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="border border-gray-300 px-4 py-2 text-left">Month</th>
                                    <th class="border border-gray-300 px-4 py-2 text-right">Total Sales</th>
                                    <th class="border border-gray-300 px-4 py-2 text-right">Invoice Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="month in (salesReport?.sales_by_month || [])" :key="month.month" class="hover:bg-gray-50">
                                    <td class="border border-gray-300 px-4 py-2">{{ month.month }}</td>
                                    <td class="border border-gray-300 px-4 py-2 text-right">{{ formatCurrency(month.total_sales) }}</td>
                                    <td class="border border-gray-300 px-4 py-2 text-right">{{ month.invoice_count }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </CardContent>
                </Card>
            </TabsContent>

            <!-- Financial Report Tab -->
            <TabsContent value="financial" class="print-break">
                <Card class="mb-6">
                    <CardHeader>
                        <CardTitle>Financial Report Filters</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-sm text-gray-600 mb-4">These filters apply <strong>only</strong> to the <strong>Financial Report</strong>. If not set, the global filters from above will be used.</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">Filter Type</label>
                                <Select
                                    v-model="financialFilterType"
                                    :options="filterTypeOptions"
                                    placeholder="Select filter"
                                    class="w-full"
                                />
                            </div>
                            <div v-if="financialFilterType === 'month'">
                                <label class="block text-sm font-medium mb-1">Month</label>
                                <Select
                                    v-model="financialFilterMonth"
                                    :options="monthOptions"
                                    placeholder="Select month"
                                    class="w-full"
                                />
                            </div>
                            <div v-if="financialFilterType === 'year'">
                                <label class="block text-sm font-medium mb-1">Year</label>
                                <Select
                                    v-model="financialFilterYear"
                                    :options="yearOptions"
                                    placeholder="Select year"
                                    class="w-full"
                                />
                            </div>
                            <div v-if="financialFilterType === 'date_range'">
                                <label class="block text-sm font-medium mb-1">Start Date</label>
                                <input
                                    v-model="financialStartDate"
                                    type="date"
                                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                />
                            </div>
                            <div v-if="financialFilterType === 'date_range'">
                                <label class="block text-sm font-medium mb-1">End Date</label>
                                <input
                                    v-model="financialEndDate"
                                    type="date"
                                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>
                
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <CardTitle class="flex items-center gap-2">
                                <FileText class="h-5 w-5" />
                                Financial Report
                            </CardTitle>
                            <div v-if="isFinancialFilterActive" class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">
                                Filter: {{ activeFinancialFilterText }}
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <table class="min-w-full border-collapse border border-gray-300">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="border border-gray-300 px-4 py-2 text-left">Month</th>
                                    <th class="border border-gray-300 px-4 py-2 text-right">Income</th>
                                    <th class="border border-gray-300 px-4 py-2 text-right">Expenses</th>
                                    <th class="border border-gray-300 px-4 py-2 text-right">Net</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in (financialReport?.rows || [])" :key="row.month" class="hover:bg-gray-50">
                                    <td class="border border-gray-300 px-4 py-2">{{ row.month }}</td>
                                    <td class="border border-gray-300 px-4 py-2 text-right">{{ formatCurrency(row.income) }}</td>
                                    <td class="border border-gray-300 px-4 py-2 text-right">{{ formatCurrency(row.expenses) }}</td>
                                    <td class="border border-gray-300 px-4 py-2 text-right" :class="row.net >= 0 ? 'text-green-600' : 'text-red-600'">
                                        {{ formatCurrency(row.net) }}
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray-100 font-bold">
                                    <td class="border border-gray-300 px-4 py-2">Total</td>
                                    <td class="border border-gray-300 px-4 py-2 text-right">{{ formatCurrency(financialReport?.totals?.income) }}</td>
                                    <td class="border border-gray-300 px-4 py-2 text-right">{{ formatCurrency(financialReport?.totals?.expenses) }}</td>
                                    <td class="border border-gray-300 px-4 py-2 text-right" :class="(financialReport?.totals?.net ?? 0) >= 0 ? 'text-green-600' : 'text-red-600'">
                                        {{ formatCurrency(financialReport?.totals?.net) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </CardContent>
                </Card>
            </TabsContent>

            <!-- Delivery Summary Tab -->
            <TabsContent value="delivery" class="print-break">
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <CardTitle class="flex items-center gap-2">
                                <Truck class="h-5 w-5" />
                                Delivery Summary
                            </CardTitle>
                            <div v-if="isDeliveryFilterActive" class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">
                                Filter: {{ activeDeliveryFilterText }}
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="mb-4 grid grid-cols-4 gap-4">
                            <div class="bg-blue-50 p-3 rounded">
                                <p class="text-sm text-gray-600">Total Deliveries</p>
                                <p class="text-xl font-bold text-blue-600">{{ deliverySummary?.total ?? 0 }}</p>
                            </div>
                            <div class="bg-yellow-50 p-3 rounded">
                                <p class="text-sm text-gray-600">Pending</p>
                                <p class="text-xl font-bold text-yellow-600">{{ deliverySummary?.pending ?? 0 }}</p>
                            </div>
                            <div class="bg-green-50 p-3 rounded">
                                <p class="text-sm text-gray-600">Completed</p>
                                <p class="text-xl font-bold text-green-600">{{ deliverySummary?.completed ?? 0 }}</p>
                            </div>
                            <div class="bg-red-50 p-3 rounded">
                                <p class="text-sm text-gray-600">Total Delivery Fees</p>
                                <p class="text-xl font-bold text-red-600">{{ formatCurrency(deliverySummary?.total_fee) }}</p>
                            </div>
                        </div>

                        <table class="min-w-full border-collapse border border-gray-300">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="border border-gray-300 px-4 py-2 text-left">ID</th>
                                    <th class="border border-gray-300 px-4 py-2 text-left">Customer</th>
                                    <th class="border border-gray-300 px-4 py-2 text-left">Delivery Address</th>
                                    <th class="border border-gray-300 px-4 py-2 text-left">Contact Person</th>
                                    <th class="border border-gray-300 px-4 py-2 text-left">Contact Phone</th>
                                    <th class="border border-gray-300 px-4 py-2 text-left">Delivery Date</th>
                                    <th class="border border-gray-300 px-4 py-2 text-left">Time</th>
                                    <th class="border border-gray-300 px-4 py-2 text-left">Status</th>
                                    <th class="border border-gray-300 px-4 py-2 text-right">Fee</th>
                                    <th class="border border-gray-300 px-4 py-2 text-left">Invoice #</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="delivery in (deliveries || [])" :key="delivery.id" class="hover:bg-gray-50">
                                    <td class="border border-gray-300 px-4 py-2">{{ delivery.id }}</td>
                                    <td class="border border-gray-300 px-4 py-2">{{ delivery.customer_name }}</td>
                                    <td class="border border-gray-300 px-4 py-2">{{ delivery.delivery_address }}</td>
                                    <td class="border border-gray-300 px-4 py-2">{{ delivery.contact_person }}</td>
                                    <td class="border border-gray-300 px-4 py-2">{{ delivery.contact_phone }}</td>
                                    <td class="border border-gray-300 px-4 py-2">{{ delivery.delivery_date }}</td>
                                    <td class="border border-gray-300 px-4 py-2">{{ delivery.delivery_time }}</td>
                                    <td class="border border-gray-300 px-4 py-2">{{ delivery.status }}</td>
                                    <td class="border border-gray-300 px-4 py-2 text-right">{{ formatCurrency(delivery.delivery_fee) }}</td>
                                    <td class="border border-gray-300 px-4 py-2">{{ delivery.invoice_number }}</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray-100 font-bold">
                                    <td colspan="8" class="border border-gray-300 px-4 py-2 text-right">Total Delivery Fees:</td>
                                    <td class="border border-gray-300 px-4 py-2 text-right">{{ formatCurrency(deliverySummary?.total_fee) }}</td>
                                    <td class="border border-gray-300 px-4 py-2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </CardContent>
                </Card>
            </TabsContent>

            <!-- Transactions Report Tab -->
            <TabsContent value="transactions" class="print-break">
                <Card class="mb-6">
                    <CardHeader>
                        <CardTitle>Transactions Report Filters</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-sm text-gray-600 mb-4">These filters apply <strong>only</strong> to the <strong>Transactions Report</strong>. If not set, the global filters from above will be used.</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">Filter Type</label>
                                <Select
                                    v-model="transactionFilterType"
                                    :options="filterTypeOptions"
                                    placeholder="Select filter"
                                    class="w-full"
                                />
                            </div>
                            <div v-if="transactionFilterType === 'month'">
                                <label class="block text-sm font-medium mb-1">Month</label>
                                <Select
                                    v-model="transactionFilterMonth"
                                    :options="monthOptions"
                                    placeholder="Select month"
                                    class="w-full"
                                />
                            </div>
                            <div v-if="transactionFilterType === 'year'">
                                <label class="block text-sm font-medium mb-1">Year</label>
                                <Select
                                    v-model="transactionFilterYear"
                                    :options="yearOptions"
                                    placeholder="Select year"
                                    class="w-full"
                                />
                            </div>
                            <div v-if="transactionFilterType === 'date_range'">
                                <label class="block text-sm font-medium mb-1">Start Date</label>
                                <input
                                    v-model="transactionStartDate"
                                    type="date"
                                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                />
                            </div>
                            <div v-if="transactionFilterType === 'date_range'">
                                <label class="block text-sm font-medium mb-1">End Date</label>
                                <input
                                    v-model="transactionEndDate"
                                    type="date"
                                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>
                
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <CardTitle class="flex items-center gap-2">
                                <CreditCard class="h-5 w-5" />
                                Transactions Report
                            </CardTitle>
                            <div v-if="isTransactionFilterActive" class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">
                                Filter: {{ activeTransactionFilterText }}
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <p class="text-sm text-gray-600 mb-4">Total Transactions: {{ (transactions || []).length }}</p>
                        <table class="min-w-full border-collapse border border-gray-300 text-sm">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="border border-gray-300 px-4 py-2 text-left">Invoice #</th>
                                    <th class="border border-gray-300 px-4 py-2 text-left">Customer</th>
                                    <th class="border border-gray-300 px-4 py-2 text-left">Company</th>
                                    <th class="border border-gray-300 px-4 py-2 text-left">Product</th>
                                    <th class="border border-gray-300 px-4 py-2 text-right">Quantity</th>
                                    <th class="border border-gray-300 px-4 py-2 text-right">Amount</th>
                                    <th class="border border-gray-300 px-4 py-2 text-left">Status</th>
                                    <th class="border border-gray-300 px-4 py-2 text-left">Payment Method</th>
                                    <th class="border border-gray-300 px-4 py-2 text-left">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="transaction in (transactions || [])" :key="transaction.id" class="hover:bg-gray-50">
                                    <td class="border border-gray-300 px-4 py-2">{{ transaction.invoice_number }}</td>
                                    <td class="border border-gray-300 px-4 py-2">{{ transaction.customer_name }}</td>
                                    <td class="border border-gray-300 px-4 py-2">{{ transaction.company_name || 'N/A' }}</td>
                                    <td class="border border-gray-300 px-4 py-2">{{ transaction.product_name }}</td>
                                    <td class="border border-gray-300 px-4 py-2 text-right">{{ transaction.quantity }}</td>
                                    <td class="border border-gray-300 px-4 py-2 text-right">{{ formatCurrency(transaction.total_amount) }}</td>
                                    <td class="border border-gray-300 px-4 py-2">{{ transaction.status }}</td>
                                    <td class="border border-gray-300 px-4 py-2">{{ transaction.payment_method }}</td>
                                    <td class="border border-gray-300 px-4 py-2">{{ transaction.transaction_date }}</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray-100 font-bold">
                                    <td colspan="5" class="border border-gray-300 px-4 py-2 text-right">Total:</td>
                                    <td class="border border-gray-300 px-4 py-2 text-right">{{ formatCurrency((transactions || []).reduce((sum, t) => sum + (t.total_amount || 0), 0)) }}</td>
                                    <td colspan="3" class="border border-gray-300 px-4 py-2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </CardContent>
                </Card>
            </TabsContent>
        </Tabs>
    </AppLayout>
</template>

<style scoped>
@media print {
    .no-print {
        display: none !important;
    }
    .print-break {
        page-break-after: always;
    }
    body {
        background: white !important;
    }
    .card {
        box-shadow: none !important;
        border: 1px solid #e5e7eb !important;
    }
}
</style>
