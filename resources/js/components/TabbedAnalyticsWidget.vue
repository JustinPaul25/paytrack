<template>
    <Card>
        <CardHeader>
            <div class="flex items-center justify-between">
                <CardTitle class="flex items-center gap-2">
                    <BarChart3 class="w-5 h-5 text-blue-600" />
                    Analytics Dashboard
                </CardTitle>
                <button
                    @click="toggleCollapse"
                    class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors duration-200"
                    :aria-expanded="!isCollapsed"
                    aria-label="Toggle analytics details"
                >
                    <ChevronDown 
                        class="w-5 h-5 text-gray-500 transition-transform duration-200"
                        :class="{ 'rotate-180': !isCollapsed }"
                    />
                </button>
            </div>
        </CardHeader>
        
        <!-- Collapsible Content -->
        <div 
            class="overflow-hidden transition-all duration-300 ease-in-out"
            :class="{ 'max-h-0': isCollapsed, 'max-h-[2000px]': !isCollapsed }"
        >
            <CardContent>
                <!-- Tab Navigation -->
                <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
                    <nav class="flex space-x-8">
                        <button
                            v-for="tab in tabs"
                            :key="tab.id"
                            @click="activeTab = tab.id"
                            class="py-2 px-1 border-b-2 font-medium text-sm transition-colors duration-200"
                            :class="activeTab === tab.id 
                                ? 'border-blue-500 text-blue-600 dark:text-blue-400' 
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                        >
                            <div class="flex items-center gap-2">
                                <component :is="tab.icon" class="w-4 h-4" />
                                {{ tab.name }}
                            </div>
                        </button>
                    </nav>
                </div>

                <!-- Tab Content -->
                <div class="space-y-6">
                    <!-- Customers Tab -->
                    <div v-if="activeTab === 'customers'" class="space-y-6">
                        <!-- Customer Overview Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="bg-blue-50 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-blue-600 dark:text-blue-400">Total Customers</p>
                                        <p class="text-2xl font-bold text-blue-700 dark:text-blue-300">1,247</p>
                                    </div>
                                    <Users class="w-8 h-8 text-blue-600" />
                                </div>
                                <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">+12% from last month</p>
                            </div>

                            <div class="bg-green-50 dark:bg-green-950/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-green-600 dark:text-green-400">Active Customers</p>
                                        <p class="text-2xl font-bold text-green-700 dark:text-green-300">892</p>
                                    </div>
                                    <UserCheck class="w-8 h-8 text-green-600" />
                                </div>
                                <p class="text-xs text-green-600 dark:text-green-400 mt-1">71.5% of total</p>
                            </div>

                            <div class="bg-yellow-50 dark:bg-yellow-950/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-yellow-600 dark:text-yellow-400">New Customers</p>
                                        <p class="text-2xl font-bold text-yellow-700 dark:text-yellow-300">156</p>
                                    </div>
                                    <UserPlus class="w-8 h-8 text-yellow-600" />
                                </div>
                                <p class="text-xs text-yellow-600 dark:text-yellow-400 mt-1">This month</p>
                            </div>

                            <div class="bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-red-600 dark:text-red-400">Churned</p>
                                        <p class="text-2xl font-bold text-red-700 dark:text-red-300">65</p>
                                    </div>
                                    <UserX class="w-8 h-8 text-red-600" />
                                </div>
                                <p class="text-xs text-red-600 dark:text-red-400 mt-1">-5.2% rate</p>
                            </div>
                        </div>

                        <!-- Customer Growth Chart -->
                        <div>
                            <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-4">Customer Growth Trend</h4>
                            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                                <Line
                                    :data="customerChartData"
                                    :options="customerChartOptions"
                                    height="200px"
                                />
                            </div>
                        </div>

                        <!-- Customer Segments -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-4">Customer Segments</h4>
                                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                                    <div class="space-y-3">
                                        <div v-for="segment in customerSegments" :key="segment.name" class="flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <div class="w-3 h-3 rounded-full" :style="{ backgroundColor: segment.color }"></div>
                                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ segment.name }}</span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <div class="w-16 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                                    <div class="h-full rounded-full transition-all duration-300"
                                                         :style="{ width: segment.percentage + '%', backgroundColor: segment.color }"></div>
                                                </div>
                                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ segment.percentage }}%</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-4">Customer Satisfaction</h4>
                                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                                    <div class="text-center">
                                        <div class="text-3xl font-bold text-green-600 mb-2">4.7/5</div>
                                        <div class="flex justify-center mb-2">
                                            <Star v-for="i in 5" :key="i" class="w-5 h-5" :class="i <= 4 ? 'text-yellow-400 fill-current' : 'text-gray-300'" />
                                        </div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Based on 892 reviews</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sales Tab -->
                    <div v-if="activeTab === 'sales'" class="space-y-6">
                        <!-- Sales Overview Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="bg-green-50 dark:bg-green-950/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-green-600 dark:text-green-400">Total Sales</p>
                                        <p class="text-2xl font-bold text-green-700 dark:text-green-300">₱2.4M</p>
                                    </div>
                                    <DollarSign class="w-8 h-8 text-green-600" />
                                </div>
                                <p class="text-xs text-green-600 dark:text-green-400 mt-1">+18% from last month</p>
                            </div>

                            <div class="bg-blue-50 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-blue-600 dark:text-blue-400">Orders</p>
                                        <p class="text-2xl font-bold text-blue-700 dark:text-blue-300">1,856</p>
                                    </div>
                                    <ShoppingCart class="w-8 h-8 text-blue-600" />
                                </div>
                                <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">+24% from last month</p>
                            </div>

                            <div class="bg-purple-50 dark:bg-purple-950/20 border border-purple-200 dark:border-purple-800 rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-purple-600 dark:text-purple-400">Avg Order</p>
                                        <p class="text-2xl font-bold text-purple-700 dark:text-purple-300">₱1,294</p>
                                    </div>
                                    <TrendingUp class="w-8 h-8 text-purple-600" />
                                </div>
                                <p class="text-xs text-purple-600 dark:text-purple-400 mt-1">+5% from last month</p>
                            </div>

                            <div class="bg-orange-50 dark:bg-orange-950/20 border border-orange-200 dark:border-orange-800 rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-orange-600 dark:text-orange-400">Conversion</p>
                                        <p class="text-2xl font-bold text-orange-700 dark:text-orange-300">3.2%</p>
                                    </div>
                                    <Target class="w-8 h-8 text-orange-600" />
                                </div>
                                <p class="text-xs text-orange-600 dark:text-orange-400 mt-1">+0.8% from last month</p>
                            </div>
                        </div>

                        <!-- Sales Trend Chart -->
                        <div>
                            <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-4">Sales Trend (Last 6 Months)</h4>
                            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                                <Line
                                    :data="salesChartData"
                                    :options="salesChartOptions"
                                    height="200px"
                                />
                            </div>
                        </div>

                        <!-- Sales by Category -->
                        <div>
                            <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-4">Sales by Category</h4>
                            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                                <div class="space-y-3">
                                    <div v-for="category in salesByCategory" :key="category.name" class="flex items-center justify-between">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ category.name }}</span>
                                        <div class="flex items-center gap-2">
                                            <div class="w-24 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                                <div class="h-full bg-green-500 rounded-full transition-all duration-300"
                                                     :style="{ width: category.percentage + '%' }"></div>
                                            </div>
                                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ category.percentage }}%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Products Tab -->
                    <div v-if="activeTab === 'products'" class="space-y-6">
                        <!-- Product Overview Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="bg-indigo-50 dark:bg-indigo-950/20 border border-indigo-200 dark:border-indigo-800 rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-indigo-600 dark:text-indigo-400">Total Products</p>
                                        <p class="text-2xl font-bold text-indigo-700 dark:text-indigo-300">342</p>
                                    </div>
                                    <Package class="w-8 h-8 text-indigo-600" />
                                </div>
                                <p class="text-xs text-indigo-600 dark:text-indigo-400 mt-1">+8 new this month</p>
                            </div>

                            <div class="bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-800 rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-emerald-600 dark:text-emerald-400">Top Seller</p>
                                        <p class="text-2xl font-bold text-emerald-700 dark:text-emerald-300">Ink Cartridge</p>
                                    </div>
                                    <Trophy class="w-8 h-8 text-emerald-600" />
                                </div>
                                <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1">1,247 units sold</p>
                            </div>

                            <div class="bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-amber-600 dark:text-amber-400">Low Stock</p>
                                        <p class="text-2xl font-bold text-amber-700 dark:text-amber-300">23</p>
                                    </div>
                                    <AlertTriangle class="w-8 h-8 text-amber-600" />
                                </div>
                                <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">Need reorder</p>
                            </div>

                            <div class="bg-rose-50 dark:bg-rose-950/20 border border-rose-200 dark:border-rose-800 rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-rose-600 dark:text-rose-400">Out of Stock</p>
                                        <p class="text-2xl font-bold text-rose-700 dark:text-rose-300">7</p>
                                    </div>
                                    <XCircle class="w-8 h-8 text-rose-600" />
                                </div>
                                <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">Urgent restock</p>
                            </div>
                        </div>

                        <!-- Product Performance Chart -->
                        <div>
                            <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-4">Top Products by Revenue</h4>
                            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                                <div class="space-y-3">
                                    <div v-for="product in topProducts" :key="product.name" class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 bg-indigo-100 dark:bg-indigo-900/20 rounded-full flex items-center justify-center">
                                                <span class="text-sm font-medium text-indigo-600">{{ product.name.charAt(0) }}</span>
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ product.name }}</p>
                                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ product.category }}</p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-semibold text-gray-900 dark:text-gray-100">{{ formatCurrency(product.revenue) }}</p>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ product.units }} units</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Product Categories -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-4">Product Categories</h4>
                                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                                    <div class="space-y-3">
                                        <div v-for="category in productCategories" :key="category.name" class="flex items-center justify-between">
                                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ category.name }}</span>
                                            <div class="flex items-center gap-2">
                                                <div class="w-16 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                                    <div class="h-full bg-indigo-500 rounded-full transition-all duration-300"
                                                         :style="{ width: category.percentage + '%' }"></div>
                                                </div>
                                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ category.percentage }}%</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-4">Inventory Status</h4>
                                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                                    <div class="space-y-3">
                                        <div v-for="status in inventoryStatus" :key="status.name" class="flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <div class="w-3 h-3 rounded-full" :style="{ backgroundColor: status.color }"></div>
                                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ status.name }}</span>
                                            </div>
                                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ status.count }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </CardContent>
        </div>
    </Card>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Line } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler
} from 'chart.js';
import { 
    BarChart3, 
    Users, 
    UserCheck, 
    UserPlus, 
    UserX, 
    DollarSign, 
    ShoppingCart, 
    TrendingUp, 
    Target, 
    Package, 
    Trophy, 
    AlertTriangle, 
    XCircle, 
    Star, 
    ChevronDown 
} from 'lucide-vue-next';

// Register Chart.js components
ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler
);

// Collapsible state
const isCollapsed = ref(true);

// Active tab
const activeTab = ref('customers');

// Tab configuration
const tabs = [
    { id: 'customers', name: 'Customers', icon: Users },
    { id: 'sales', name: 'Sales', icon: DollarSign },
    { id: 'products', name: 'Products', icon: Package }
];

// Customer data
const customerData = [
    { month: 'Jul', customers: 1100 },
    { month: 'Aug', customers: 1150 },
    { month: 'Sep', customers: 1180 },
    { month: 'Oct', customers: 1220 },
    { month: 'Nov', customers: 1250 },
    { month: 'Dec', customers: 1247 }
];

// Chart.js data and options
const customerChartData = computed(() => ({
    labels: customerData.map(item => item.month),
    datasets: [
        {
            label: 'Total Customers',
            data: customerData.map(item => item.customers),
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            fill: true,
            tension: 0.4,
            pointBackgroundColor: 'rgb(59, 130, 246)',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 5,
        },
        {
            label: 'New Customers',
            data: customerData.map(item => Math.floor(item.customers * 0.15)),
            borderColor: 'rgb(16, 185, 129)',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            fill: false,
            tension: 0.4,
            borderDash: [5, 5],
            pointBackgroundColor: 'rgb(16, 185, 129)',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 4,
        }
    ]
}));

const customerChartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'top' as const,
            labels: {
                usePointStyle: true,
                padding: 20,
                font: {
                    size: 12
                }
            }
        },
        tooltip: {
            mode: 'index' as const,
            intersect: false,
        }
    },
    scales: {
        y: {
            beginAtZero: false,
            min: 900,
            max: 1300,
            grid: {
                color: 'rgba(156, 163, 175, 0.1)',
            },
            ticks: {
                stepSize: 100,
                font: {
                    size: 11
                }
            }
        },
        x: {
            grid: {
                color: 'rgba(156, 163, 175, 0.1)',
            },
            ticks: {
                font: {
                    size: 11
                }
            }
        }
    },
    interaction: {
        mode: 'nearest' as const,
        axis: 'x' as const,
        intersect: false
    }
}));

const customerSegments = [
    { name: 'High Value', percentage: 25, color: '#10B981' },
    { name: 'Medium Value', percentage: 45, color: '#3B82F6' },
    { name: 'Low Value', percentage: 20, color: '#F59E0B' },
    { name: 'New Customers', percentage: 10, color: '#8B5CF6' }
];

// Sales data
const salesData = [
    { month: 'Jul', sales: 1800000 },
    { month: 'Aug', sales: 1950000 },
    { month: 'Sep', sales: 2100000 },
    { month: 'Oct', sales: 2250000 },
    { month: 'Nov', sales: 2350000 },
    { month: 'Dec', sales: 2400000 }
];

// Sales Chart.js data and options
const salesChartData = computed(() => ({
    labels: salesData.map(item => item.month),
    datasets: [
        {
            label: 'Total Sales',
            data: salesData.map(item => item.sales),
            borderColor: 'rgb(34, 197, 94)',
            backgroundColor: 'rgba(34, 197, 94, 0.1)',
            fill: true,
            tension: 0.4,
            pointBackgroundColor: 'rgb(34, 197, 94)',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 5,
        },
        {
            label: 'Revenue Growth',
            data: salesData.map((item, index) => {
                if (index === 0) return 0;
                return ((item.sales - salesData[index - 1].sales) / salesData[index - 1].sales) * 100;
            }),
            borderColor: 'rgb(236, 72, 153)',
            backgroundColor: 'rgba(236, 72, 153, 0.1)',
            fill: false,
            tension: 0.4,
            borderDash: [5, 5],
            pointBackgroundColor: 'rgb(236, 72, 153)',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 4,
            yAxisID: 'y1',
        }
    ]
}));

const salesChartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'top' as const,
            labels: {
                usePointStyle: true,
                padding: 20,
                font: {
                    size: 12
                }
            }
        },
        tooltip: {
            mode: 'index' as const,
            intersect: false,
            callbacks: {
                label: function(context: any) {
                    if (context.datasetIndex === 0) {
                        return `${context.dataset.label}: ${formatCurrency(context.parsed.y)}`;
                    } else {
                        return `${context.dataset.label}: ${context.parsed.y.toFixed(1)}%`;
                    }
                }
            }
        }
    },
    scales: {
        y: {
            type: 'linear' as const,
            display: true,
            position: 'left' as const,
            beginAtZero: false,
            min: 1500000,
            max: 2500000,
            grid: {
                color: 'rgba(156, 163, 175, 0.1)',
            },
            ticks: {
                callback: function(value: any) {
                    return formatCurrency(value);
                },
                font: {
                    size: 11
                }
            }
        },
        y1: {
            type: 'linear' as const,
            display: true,
            position: 'right' as const,
            beginAtZero: true,
            grid: {
                drawOnChartArea: false,
            },
            ticks: {
                callback: function(value: any) {
                    return value + '%';
                },
                font: {
                    size: 11
                }
            }
        },
        x: {
            grid: {
                color: 'rgba(156, 163, 175, 0.1)',
            },
            ticks: {
                font: {
                    size: 11
                }
            }
        }
    },
    interaction: {
        mode: 'nearest' as const,
        axis: 'x' as const,
        intersect: false
    }
}));

const salesByCategory = [
    { name: 'Office Supplies', percentage: 35 },
    { name: 'Printing Materials', percentage: 28 },
    { name: 'Computer Accessories', percentage: 22 },
    { name: 'Electronics', percentage: 15 }
];

// Product data
const topProducts = [
    { name: 'Ink Cartridge', category: 'Printing', revenue: 187500, units: 1250 },
    { name: 'Paper A4', category: 'Office', revenue: 160000, units: 4000 },
    { name: 'USB Cable', category: 'Electronics', revenue: 90000, units: 6000 },
    { name: 'Mouse Wireless', category: 'Electronics', revenue: 72000, units: 1200 }
];

const productCategories = [
    { name: 'Office Supplies', percentage: 40 },
    { name: 'Printing Materials', percentage: 30 },
    { name: 'Electronics', percentage: 20 },
    { name: 'Furniture', percentage: 10 }
];

const inventoryStatus = [
    { name: 'In Stock', count: 298, color: '#10B981' },
    { name: 'Low Stock', count: 23, color: '#F59E0B' },
    { name: 'Out of Stock', count: 7, color: '#EF4444' },
    { name: 'Discontinued', count: 14, color: '#6B7280' }
];

// Chart computations
const customerGrowthPoints = computed(() => {
    const points = customerData.map((item, index) => {
        // Ensure proper spacing across the full width
        const x = (index / (customerData.length - 1)) * 100;
        // Scale the y value properly with padding
        const minValue = 900;
        const maxValue = 1300;
        const y = 100 - (((item.customers - minValue) / (maxValue - minValue)) * 80 + 10);
        return `${x},${y}`;
    });
    return points.join(' ');
});

const customerGrowthPointsArray = computed(() => {
    return customerData.map((item, index) => {
        const x = (index / (customerData.length - 1)) * 100;
        const minValue = 900;
        const maxValue = 1300;
        const y = 100 - (((item.customers - minValue) / (maxValue - minValue)) * 80 + 10);
        return { x, y };
    });
});

const salesTrendPoints = computed(() => {
    const points = salesData.map((item, index) => {
        // Ensure proper spacing across the full width
        const x = (index / (salesData.length - 1)) * 100;
        // Scale the y value properly with padding
        const minValue = 1500000;
        const maxValue = 2500000;
        const y = 100 - (((item.sales - minValue) / (maxValue - minValue)) * 80 + 10);
        return `${x},${y}`;
    });
    return points.join(' ');
});

const salesTrendPointsArray = computed(() => {
    return salesData.map((item, index) => {
        const x = (index / (salesData.length - 1)) * 100;
        const minValue = 1500000;
        const maxValue = 2500000;
        const y = 100 - (((item.sales - minValue) / (maxValue - minValue)) * 80 + 10);
        return { x, y };
    });
});

function toggleCollapse() {
    isCollapsed.value = !isCollapsed.value;
}

function formatCurrency(amount: number): string {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount);
}
</script> 