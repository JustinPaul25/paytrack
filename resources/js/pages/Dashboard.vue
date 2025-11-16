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
import CardDescription from '@/components/ui/card/CardDescription.vue';
import BaseChart from '@/components/charts/BaseChart.vue';
import SalesPredictionWidget from '@/components/SalesPredictionWidget.vue';
import CustomerChurnWidget from '@/components/CustomerChurnWidget.vue';
import ProductSalesTrendWidget from '@/components/ProductSalesTrendWidget.vue';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs';
import { type BreadcrumbItem } from '@/types';
import { TrendingUp, TrendingDown, FileText, Clock, Package, HelpCircle, Calendar, Users, BarChart3 } from 'lucide-vue-next';

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
    churnMetrics?: any;
    filters: Filters;
}>();

const period = ref(props.filters.period);
const startDate = ref(props.filters.start_date);
const endDate = ref(props.filters.end_date);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const periodOptions = [
    { value: 'week', label: 'Last 7 Days' },
    { value: 'month', label: 'Last 30 Days' },
    { value: 'quarter', label: 'Last 3 Months' },
    { value: 'year', label: 'Last 12 Months' },
    { value: 'custom', label: 'Choose Dates' },
];

function updateFilters() {
    router.get('/dashboard', {
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
        <TooltipProvider>
            <div class="dashboard-wrapper">
                <!-- Welcome Header -->
                <div class="dashboard-header">
                    <div>
                        <h1 class="dashboard-title">Dashboard</h1>
                        <p class="dashboard-subtitle">View your business performance at a glance</p>
                    </div>
                    
                    <!-- Filters -->
                    <div class="dashboard-filters">
                        <div class="filter-group">
                            <label class="filter-label">
                                <Calendar class="w-4 h-4" />
                                View Period
                            </label>
                            <Select
                                v-model="period"
                                :options="periodOptions"
                                placeholder="Choose time period"
                                class="period-select"
                            />
                        </div>
                        
                        <!-- Custom Date Range -->
                        <div v-if="period === 'custom'" class="date-range-group">
                            <div class="date-input-group">
                                <label class="date-label">Start Date</label>
                                <input
                                    v-model="startDate"
                                    type="date"
                                    class="date-input"
                                />
                            </div>
                            <div class="date-input-group">
                                <label class="date-label">End Date</label>
                                <input
                                    v-model="endDate"
                                    type="date"
                                    class="date-input"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Key Metrics Overview -->
                <div class="metrics-section">
                    <h2 class="section-title">Your Business Overview</h2>
                    <p class="section-description">Quick summary of your sales performance</p>
                </div>

                <!-- Sales Overview Cards -->
                <div class="metrics-grid">
                    <Card class="metric-card">
                        <CardContent class="metric-card-content">
                            <div class="metric-header">
                                <div class="metric-info">
                                    <div class="metric-label-group">
                                        <span class="metric-label">Total Revenue</span>
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <HelpCircle class="help-icon" />
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                <p>The total amount of money you've earned from all sales</p>
                                            </TooltipContent>
                                        </Tooltip>
                                    </div>
                                    <p class="metric-value">
                                        {{ formatCurrency(salesData.total_sales) }}
                                    </p>
                                    <p class="metric-description">All sales combined</p>
                                </div>
                                <div class="metric-icon-wrapper metric-icon-blue">
                                    <svg id="Layer_1" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 119.43 122.88" class="metric-icon">
                                        <path d="M118.45,51l1,1-.74,9.11H99A40.52,40.52,0,0,1,81.88,78.43q-11.44,6.28-27.71,7h-15l.5,37.43H21.42l.74-36.94-.24-24.87H1L0,59.84.74,51H21.92l-.25-15.26H1l-1-1,.74-9.11H21.67L21.42.25,63.29,0Q78.8,0,88.65,6.53T102,25.61h16.5l1,1.23-.74,8.87h-15v3.94A53.17,53.17,0,0,1,102.44,51ZM39.65,25.61H81.26Q74.85,14,58.61,13.3L39.89,14l-.24,11.57ZM39.4,51H83.23a39.51,39.51,0,0,0,1.23-9.6,46.17,46.17,0,0,0-.24-5.66H39.65L39.4,51ZM58.61,71.91q12.56-2.72,19.21-10.84H39.4l-.25,10.1,19.46.74Z"/>
                                    </svg>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="metric-card">
                        <CardContent class="metric-card-content">
                            <div class="metric-header">
                                <div class="metric-info">
                                    <div class="metric-label-group">
                                        <span class="metric-label">Total Invoices</span>
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <HelpCircle class="help-icon" />
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                <p>The number of sales transactions you've completed</p>
                                            </TooltipContent>
                                        </Tooltip>
                                    </div>
                                    <p class="metric-value">
                                        {{ salesData.total_invoices }}
                                    </p>
                                    <p class="metric-description">Sales transactions</p>
                                </div>
                                <div class="metric-icon-wrapper metric-icon-green">
                                    <FileText class="metric-icon" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="metric-card">
                        <CardContent class="metric-card-content">
                            <div class="metric-header">
                                <div class="metric-info">
                                    <div class="metric-label-group">
                                        <span class="metric-label">Average Sale Amount</span>
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <HelpCircle class="help-icon" />
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                <p>The average amount customers spend per transaction</p>
                                            </TooltipContent>
                                        </Tooltip>
                                    </div>
                                    <p class="metric-value">
                                        {{ formatCurrency(salesData.average_order_value) }}
                                    </p>
                                    <p class="metric-description">Per transaction</p>
                                </div>
                                <div class="metric-icon-wrapper metric-icon-yellow">
                                    <TrendingUp class="metric-icon" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="metric-card">
                        <CardContent class="metric-card-content">
                            <div class="metric-header">
                                <div class="metric-info">
                                    <div class="metric-label-group">
                                        <span class="metric-label">Awaiting Payment</span>
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <HelpCircle class="help-icon" />
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                <p>Invoices that are still waiting to be paid</p>
                                            </TooltipContent>
                                        </Tooltip>
                                    </div>
                                    <p class="metric-value">
                                        {{ salesData.pending_invoices }}
                                    </p>
                                    <p class="metric-description">Need attention</p>
                                </div>
                                <div class="metric-icon-wrapper metric-icon-orange">
                                    <Clock class="metric-icon" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Analytics Section -->
                <div class="analytics-section">
                    <div class="section-header">
                        <h2 class="section-title">Sales Trends & Insights</h2>
                        <p class="section-description">Track how your sales are performing over time</p>
                    </div>
                </div>

                <!-- Tabs Section -->
                <div class="analytics-tabs-wrapper">
                    <Tabs defaultValue="sales-trend" class="analytics-tabs-full">
                        <TabsList class="tabs-list-full">
                            <TabsTrigger value="sales-trend" class="tab-trigger-full">
                                <TrendingUp class="tab-icon" />
                                Your Sales Trend
                            </TabsTrigger>
                            <TabsTrigger value="sales-prediction" class="tab-trigger-full">
                                <TrendingUp class="tab-icon" />
                                Sales Prediction
                            </TabsTrigger>
                            <TabsTrigger value="customer-churn" class="tab-trigger-full">
                                <Users class="tab-icon" />
                                Customer Churn Analysis
                            </TabsTrigger>
                            <TabsTrigger value="product-trend" class="tab-trigger-full">
                                <BarChart3 class="tab-icon" />
                                Product Sales Trend
                            </TabsTrigger>
                        </TabsList>
                        
                        <TabsContent value="sales-trend" class="tab-content-full">
                            <Card class="chart-card">
                                <CardHeader>
                                    <CardTitle class="chart-title">
                                        <TrendingUp class="chart-title-icon" />
                                        Your Sales Trend
                                    </CardTitle>
                                    <CardDescription>
                                        See how your daily sales and number of transactions change over time
                                    </CardDescription>
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
                        </TabsContent>
                        
                        <TabsContent value="sales-prediction" class="tab-content-full">
                            <SalesPredictionWidget :sales-data="salesByDate" />
                        </TabsContent>
                        
                        <TabsContent value="customer-churn" class="tab-content-full">
                            <CustomerChurnWidget :metrics="churnMetrics" />
                        </TabsContent>
                        
                        <TabsContent value="product-trend" class="tab-content-full">
                            <ProductSalesTrendWidget :sales-by-date="salesByDate" :top-products="topProducts" />
                        </TabsContent>
                    </Tabs>
                </div>

                <!-- Sales by Category Chart -->
                <div class="category-section">
                    <Card class="chart-card">
                        <CardHeader>
                            <CardTitle class="chart-title">
                                <Package class="chart-title-icon" />
                                Sales by Product Category
                            </CardTitle>
                            <CardDescription>
                                See which product categories bring in the most revenue
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="props.salesByCategory.length === 0" class="empty-state">
                                <p class="empty-title">No category data for the selected period</p>
                                <p class="empty-subtitle">Try expanding the date range or confirming there are paid invoices.</p>
                            </div>
                            <BaseChart
                                v-else
                                type="doughnut"
                                :data="categoryChartData"
                                :options="categoryChartOptions"
                                height="300px"
                            />
                        </CardContent>
                    </Card>
                </div>

                <!-- Business Intelligence Section -->
                <div class="section-header">
                    <h2 class="section-title">Product Performance & Recent Activity</h2>
                    <p class="section-description">Your best-selling products and latest transactions</p>
                </div>

                <div class="tables-grid">
                    <!-- Top Products Table -->
                    <Card class="table-card">
                        <CardHeader>
                            <CardTitle class="table-title">
                                <Package class="table-title-icon" />
                                Best-Selling Products
                            </CardTitle>
                            <CardDescription>
                                Products that have generated the most revenue
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="table-wrapper">
                                <table class="data-table">
                                    <thead>
                                        <tr class="table-header-row">
                                            <th class="table-header">Product Name</th>
                                            <th class="table-header table-header-right">Units Sold</th>
                                            <th class="table-header table-header-right">Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="product in topProducts" :key="product.id" class="table-row">
                                            <td class="table-cell table-cell-bold">{{ product.name }}</td>
                                            <td class="table-cell table-cell-right">{{ product.total_quantity.toLocaleString() }}</td>
                                            <td class="table-cell table-cell-right table-cell-highlight">{{ formatCurrency(product.total_revenue) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Recent Invoices Table -->
                    <Card class="table-card">
                        <CardHeader>
                            <CardTitle class="table-title">
                                <FileText class="table-title-icon" />
                                Recent Transactions
                            </CardTitle>
                            <CardDescription>
                                Your most recent sales and invoices
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="table-wrapper">
                                <table class="data-table">
                                    <thead>
                                        <tr class="table-header-row">
                                            <th class="table-header">Invoice #</th>
                                            <th class="table-header">Customer</th>
                                            <th class="table-header table-header-right">Amount</th>
                                            <th class="table-header table-header-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="invoice in recentInvoices" :key="invoice.id" class="table-row">
                                            <td class="table-cell">
                                                <a :href="`/invoices/${invoice.id}`" class="invoice-link">
                                                    #{{ invoice.id }}
                                                </a>
                                            </td>
                                            <td class="table-cell">{{ invoice.customer_name }}</td>
                                            <td class="table-cell table-cell-right table-cell-bold">{{ formatCurrency(invoice.total_amount) }}</td>
                                            <td class="table-cell table-cell-center">
                                                <span :class="{
                                                    'status-badge': true,
                                                    'status-paid': invoice.status === 'paid',
                                                    'status-pending': invoice.status === 'pending',
                                                    'status-cancelled': invoice.status === 'cancelled'
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
            </div>
        </TooltipProvider>
    </AppLayout>
</template>
