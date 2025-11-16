<template>
    <Card>
        <CardHeader>
            <div class="flex items-center justify-between">
                <CardTitle class="flex items-center gap-2">
                    <Package class="w-5 h-5 text-indigo-600" />
                    Product Sales Trend
                </CardTitle>
            </div>
        </CardHeader>
        
        <!-- Collapsible Content -->
        <div 
            class="overflow-hidden transition-all duration-300 ease-in-out"
            :class="{ 'max-h-0': isCollapsed, 'max-h-[2000px]': !isCollapsed }"
        >
            <CardContent>
                <!-- Product Sales Overview -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-indigo-50 dark:bg-indigo-950/20 border border-indigo-200 dark:border-indigo-800 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-indigo-600 dark:text-indigo-400">Total Products Sold</p>
                                <p class="text-2xl font-bold text-indigo-700 dark:text-indigo-300">8,456</p>
                            </div>
                            <Package class="w-8 h-8 text-indigo-600" />
                        </div>
                        <p class="text-xs text-indigo-600 dark:text-indigo-400 mt-1">+15% from last month</p>
                    </div>

                    <div class="bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-800 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-emerald-600 dark:text-emerald-400">Top Product</p>
                                <p class="text-lg font-bold text-emerald-700 dark:text-emerald-300">Ink Cartridge</p>
                            </div>
                            <Trophy class="w-8 h-8 text-emerald-600" />
                        </div>
                        <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1">1,247 units sold</p>
                    </div>

                    <div class="bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-amber-600 dark:text-amber-400">Avg Daily Sales</p>
                                <p class="text-2xl font-bold text-amber-700 dark:text-amber-300">282</p>
                            </div>
                            <TrendingUp class="w-8 h-8 text-amber-600" />
                        </div>
                        <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">+8% from last week</p>
                    </div>
                </div>

                <!-- Product Sales Trend Chart -->
                <div class="mb-6">
                    <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-4">Product Sales Trend (Last 7 Days)</h4>
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                        <Line
                            :data="productChartData"
                            :options="productChartOptions"
                            height="200px"
                        />
                    </div>
                </div>

                <!-- Top Selling Products -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-4">Top Selling Products</h4>
                        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                            <div class="space-y-3">
                                <div v-for="product in topSellingProducts" :key="product.name" class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 bg-indigo-100 dark:bg-indigo-900/20 rounded-full flex items-center justify-center">
                                            <span class="text-sm font-medium text-indigo-600">{{ product.name.charAt(0) }}</span>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ product.name }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-semibold text-gray-900 dark:text-gray-100">{{ product.units }} units</p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ formatCurrency(product.revenue) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-4">Product Performance</h4>
                        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                            <div class="space-y-3">
                                <div v-for="metric in productMetrics" :key="metric.name" class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <div class="w-3 h-3 rounded-full" :style="{ backgroundColor: metric.color }"></div>
                                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ metric.name }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="w-16 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full transition-all duration-300"
                                                 :style="{ width: metric.percentage + '%', backgroundColor: metric.color }"></div>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ metric.percentage }}%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Status removed: now using live data -->
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
import { Package, Trophy, TrendingUp, ChevronDown } from 'lucide-vue-next';

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

// Collapsible state (disabled: content always open)
const isCollapsed = ref(false);

interface SalesByDate { date: string; sales: number; invoices: number }
interface TopProduct { name: string; total_quantity: number; total_revenue: number }

const props = defineProps<{
    salesByDate: SalesByDate[];
    topProducts: TopProduct[];
}>();

// Transform last 7 days for chart
const last7Days = computed(() => {
    const arr = props.salesByDate.slice(-7);
    return arr;
});

// Product Chart.js data and options
const productChartData = computed(() => ({
    labels: last7Days.value.map(item => {
        const d = new Date(item.date);
        return d.toLocaleDateString('en-US', { weekday: 'short' });
    }),
    datasets: [
        {
            label: 'Daily Sales',
            data: last7Days.value.map(item => item.sales),
            borderColor: 'rgb(99, 102, 241)',
            backgroundColor: 'rgba(99, 102, 241, 0.1)',
            fill: true,
            tension: 0.4,
            pointBackgroundColor: 'rgb(99, 102, 241)',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 5,
        }
    ]
}));

const productChartOptions = computed(() => ({
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
                        return `${context.dataset.label}: ${context.parsed.y} units`;
                    } else {
                        return `${context.dataset.label}: ${context.parsed.y} units`;
                    }
                }
            }
        }
    },
    scales: {
        y: {
            beginAtZero: true,
            min: 0,
            max: 400,
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

const topSellingProducts = computed(() => props.topProducts.map(p => ({
    name: p.name,
    units: p.total_quantity,
    revenue: p.total_revenue
})));

const productMetrics = [
    { name: 'High Performance', percentage: 35, color: '#10B981' },
    { name: 'Medium Performance', percentage: 45, color: '#3B82F6' },
    { name: 'Low Performance', percentage: 20, color: '#F59E0B' }
];

// Chart computations (kept for potential SVG usage; now based on live data)
const productSalesPoints = computed(() => {
    const data = last7Days.value;
    const maxValue = Math.max(...data.map(i => i.sales), 1);
    const minValue = 0;
    const points = data.map((item, index) => {
        const x = (index / Math.max(1, data.length - 1)) * 100;
        const y = 100 - (((item.sales - minValue) / (maxValue - minValue)) * 80 + 10);
        return `${x},${y}`;
    });
    return points.join(' ');
});

const productSalesPointsArray = computed(() => {
    const data = last7Days.value;
    const maxValue = Math.max(...data.map(i => i.sales), 1);
    const minValue = 0;
    return data.map((item, index) => {
        const x = (index / Math.max(1, data.length - 1)) * 100;
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