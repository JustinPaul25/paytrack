<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Select } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import BaseChart from '@/components/charts/BaseChart.vue';
import SalesPredictionWidget from '@/components/SalesPredictionWidget.vue';
import CustomerChurnWidget from '@/components/CustomerChurnWidget.vue';
import ProductSalesTrendWidget from '@/components/ProductSalesTrendWidget.vue';
import { type BreadcrumbItem } from '@/types';
import { TrendingUp, TrendingDown, DollarSign, FileText, Clock, Package } from 'lucide-vue-next';

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

interface Branch {
    id: number;
    name: string;
    code: string;
    status: string;
}

interface Filters {
    period: string;
    start_date: string;
    end_date: string;
    branch_id?: string;
}

const props = defineProps<{
    salesData: SalesData;
    topProducts: TopProduct[];
    salesByDate: SalesByDate[];
    salesByCategory: SalesByCategory[];
    recentInvoices: RecentInvoice[];
    filters: Filters;
    branches: Branch[];
}>();

const period = ref(props.filters.period);
const startDate = ref(props.filters.start_date);
const endDate = ref(props.filters.end_date);
const selectedBranch = ref(props.filters.branch_id || 'all');

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const periodOptions = [
    { value: 'week', label: 'Last Week' },
    { value: 'month', label: 'Last Month' },
    { value: 'quarter', label: 'Last Quarter' },
    { value: 'year', label: 'Last Year' },
    { value: 'custom', label: 'Custom Range' },
];

function updateFilters() {
    router.get('/dashboard', {
        period: period.value,
        start_date: startDate.value,
        end_date: endDate.value,
        branch_id: selectedBranch.value,
    }, {
        preserveState: true,
        replace: true,
    });
}

watch([period, startDate, endDate, selectedBranch], () => {
    updateFilters();
});

// Chart data computations
const salesChartData = computed(() => ({
    labels: props.salesByDate.map(item => {
        const date = new Date(item.date);
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    }),
    datasets: [
        {
            label: 'Daily Sales',
            data: props.salesByDate.map(item => item.sales),
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            fill: true,
            tension: 0.4,
        },
        {
            label: 'Invoices',
            data: props.salesByDate.map(item => item.invoices),
            borderColor: 'rgb(16, 185, 129)',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            fill: false,
            tension: 0.4,
            yAxisID: 'y1',
        },
    ],
}));

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
    scales: {
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

const getColorClasses = (color: string) => {
    const colors = {
        blue: 'border-l-blue-500 bg-blue-50 dark:bg-blue-950/20',
        orange: 'border-l-orange-500 bg-orange-50 dark:bg-orange-950/20',
        green: 'border-l-green-500 bg-green-50 dark:bg-green-950/20',
        purple: 'border-l-purple-500 bg-purple-50 dark:bg-purple-950/20'
    };
    return colors[color as keyof typeof colors] || colors.blue;
};

const getIconColorClasses = (color: string) => {
    const colors = {
        blue: 'bg-blue-500 text-white',
        orange: 'bg-orange-500 text-white',
        green: 'bg-green-500 text-white',
        purple: 'bg-purple-500 text-white'
    };
    return colors[color as keyof typeof colors] || colors.blue;
};

// Notification state and data
const showNotifications = ref(false);

const notifications = [
    {
        id: 1,
        title: 'New Order Received',
        message: 'Order #1234 has been placed for ₱2,500',
        time: '2 minutes ago',
        type: 'order',
        read: false
    },
    {
        id: 2,
        title: 'Low Stock Alert',
        message: 'Ink (brother 500 C, Cyan) is running low on stock',
        time: '15 minutes ago',
        type: 'alert',
        read: false
    },
    {
        id: 3,
        title: 'Sales Target Achieved',
        message: 'Congratulations! You\'ve reached 85% of your monthly sales target',
        time: '1 hour ago',
        type: 'success',
        read: true
    },
    {
        id: 4,
        title: 'Payment Received',
        message: 'Payment of ₱1,800 has been received for Invoice #5678',
        time: '2 hours ago',
        type: 'payment',
        read: true
    }
];

const unreadCount = notifications.filter(n => !n.read).length;

const toggleNotifications = () => {
    showNotifications.value = !showNotifications.value;
};

const markAsRead = (id: number) => {
    const notification = notifications.find(n => n.id === id);
    if (notification) {
        notification.read = true;
    }
};

const closeNotifications = () => {
    showNotifications.value = false;
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Dashboard" />
        
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">Dashboard</h1>
            
            <!-- Filters -->
            <div class="flex gap-4 items-center">
                <!-- Branch Selection -->
                <div class="w-56">
                    <Select
                        v-model="selectedBranch"
                        :options="[
                            { value: 'all', label: 'All Branches' },
                            ...props.branches.map(branch => ({
                                value: branch.id.toString(),
                                label: `${branch.name} (${branch.code})`
                            }))
                        ]"
                        placeholder="Select branch"
                    />
                </div>
                
                <!-- Period Selection -->
                <div class="w-48">
                    <Select
                        v-model="period"
                        :options="periodOptions"
                        placeholder="Select period"
                    />
                </div>
                
                <!-- Custom Date Range -->
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

        <!-- Main Analytics Section -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Sales Trend Chart - Main Focus -->
            <div class="lg:col-span-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <TrendingUp class="w-5 h-5 text-blue-600" />
                            Sales Performance Overview
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <BaseChart
                            type="line"
                            :data="salesChartData"
                            :options="salesChartOptions"
                            height="350px"
                        />
                    </CardContent>
                </Card>
            </div>

            <!-- Right Side Analytics Column -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Sales Prediction Widget -->
                <SalesPredictionWidget :sales-data="salesByDate" />
                
                <!-- Customer Churn Analysis -->
                <CustomerChurnWidget />
                
                <!-- Product Sales Trend Widget -->
                <ProductSalesTrendWidget />
            </div>
        </div>

        <!-- Sales by Category Chart -->
        <div class="mb-8">
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Package class="w-5 h-5 text-green-600" />
                        Revenue by Product Category
                    </CardTitle>
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
        </div>

        <!-- Business Intelligence Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Top Products Table -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Package class="w-5 h-5 text-indigo-600" />
                        Top Performing Products
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b">
                                    <th class="text-left py-3 px-4 font-medium">Product</th>
                                    <th class="text-right py-3 px-4 font-medium">Quantity Sold</th>
                                    <th class="text-right py-3 px-4 font-medium">Revenue Generated</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="product in topProducts" :key="product.id" class="border-b hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <td class="py-3 px-4 font-medium">{{ product.name }}</td>
                                    <td class="py-3 px-4 text-right">{{ product.total_quantity.toLocaleString() }}</td>
                                    <td class="py-3 px-4 text-right font-semibold text-green-600">{{ formatCurrency(product.total_revenue) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            <!-- Recent Invoices Table -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <FileText class="w-5 h-5 text-purple-600" />
                        Recent Transaction Activity
                    </CardTitle>
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
                                        <a :href="`/invoices/${invoice.id}`" class="text-blue-600 hover:underline font-medium">
                                            #{{ invoice.id }}
                                        </a>
                                    </td>
                                    <td class="py-3 px-4">{{ invoice.customer_name }}</td>
                                    <td class="py-3 px-4 text-right font-semibold">{{ formatCurrency(invoice.total_amount) }}</td>
                                    <td class="py-3 px-4 text-center">
                                        <span :class="{
                                            'px-3 py-1 text-xs rounded-full font-medium': true,
                                            'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400': invoice.status === 'paid',
                                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400': invoice.status === 'pending',
                                            'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400': invoice.status === 'cancelled'
                                        }">
                                            {{ invoice.status.charAt(0).toUpperCase() + invoice.status.slice(1) }}
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
