<template>
    <Card>
        <CardHeader>
            <div class="flex items-center justify-between">
                <CardTitle class="flex items-center gap-2">
                    <Package class="w-5 h-5 text-indigo-600" />
                    Product Sales Trend
                </CardTitle>
                <div class="flex flex-col gap-2" style="min-width: 200px;">
                    <div class="flex items-center gap-2">
                        <Calendar class="w-4 h-4" />
                        <label class="text-sm font-medium">Date Filter</label>
                    </div>
                    <Select
                        v-model="productTrendFilterPeriod"
                        :options="periodOptions"
                        placeholder="Choose time period"
                        class="w-full"
                    />
                    <div v-if="productTrendFilterPeriod === 'custom'" class="flex gap-2">
                        <input
                            v-model="productTrendFilterStartDate"
                            type="date"
                            class="flex-1 px-2 py-1 text-sm border rounded"
                        />
                        <input
                            v-model="productTrendFilterEndDate"
                            type="date"
                            class="flex-1 px-2 py-1 text-sm border rounded"
                        />
                    </div>
                </div>
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
import { Package, Trophy, TrendingUp, ChevronDown, Calendar } from 'lucide-vue-next';
import { Select } from '@/components/ui/select';

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

// Date filter for Product Sales Trend Widget
const productTrendFilterPeriod = ref('week');
const productTrendFilterStartDate = ref('');
const productTrendFilterEndDate = ref('');

const periodOptions = [
    { value: 'week', label: 'Last 7 Days' },
    { value: 'month', label: 'Last 30 Days' },
    { value: 'quarter', label: 'Last 3 Months' },
    { value: 'year', label: 'Last 12 Months' },
    { value: 'custom', label: 'Choose Dates' },
];

// Initialize dates on mount
const initializeDates = () => {
    const end = new Date();
    const start = new Date();
    start.setDate(start.getDate() - 7);
    productTrendFilterStartDate.value = start.toISOString().split('T')[0];
    productTrendFilterEndDate.value = end.toISOString().split('T')[0];
};

// Helper function to filter data by date range
function filterByDateRange<T extends { date: string }>(
    data: T[],
    periodValue: string,
    startDateStr?: string,
    endDateStr?: string
): T[] {
    if (!data || data.length === 0) return [];
    
    let start: Date;
    let end: Date = new Date();
    
    if (periodValue === 'custom' && startDateStr && endDateStr) {
        start = new Date(startDateStr);
        end = new Date(endDateStr);
    } else {
        start = new Date();
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
            default:
                start.setDate(start.getDate() - 7);
        }
    }
    
    return data.filter(item => {
        const itemDate = new Date(item.date);
        return itemDate >= start && itemDate <= end;
    });
}

// Get filtered sales data for product trend widget
const filteredSalesDataForProductTrend = computed(() => {
    return filterByDateRange(
        props.salesByDate,
        productTrendFilterPeriod.value,
        productTrendFilterStartDate.value || undefined,
        productTrendFilterEndDate.value || undefined
    );
});

// Generate last 7 days (or filtered range) with proper labels
const last7DaysData = computed(() => {
    // Get filtered data
    const filteredData = filteredSalesDataForProductTrend.value;
    
    if (!filteredData || filteredData.length === 0) {
        // Fallback to last 7 days if no filtered data
        const today = new Date();
        const days = [];
        for (let i = 6; i >= 0; i--) {
            const date = new Date(today);
            date.setDate(date.getDate() - i);
            days.push({
                date: date.toISOString().split('T')[0],
                dateObj: date,
                sales: 0
            });
        }
        return days;
    }
    
    // Use filtered data to create chart data
    const days = filteredData.map(item => {
        const date = new Date(item.date);
        return {
            date: item.date,
            dateObj: date,
            sales: item.sales
        };
    });
    
    // Sort by date
    days.sort((a, b) => a.dateObj.getTime() - b.dateObj.getTime());
    
    return days;
});

// Product Chart.js data and options
const productChartData = computed(() => ({
    labels: last7DaysData.value.map(item => {
        return item.dateObj.toLocaleDateString('en-US', { weekday: 'short' });
    }),
    datasets: [
        {
            label: 'Daily Sales',
            data: last7DaysData.value.map(item => item.sales),
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

// Compute max value for y-axis
const chartMaxValue = computed(() => {
    const maxValue = Math.max(...last7DaysData.value.map(item => item.sales), 0);
    if (maxValue === 0) return 100;
    const rounded = Math.ceil(maxValue / 50) * 50;
    return Math.max(rounded, 100);
});

const chartStepSize = computed(() => {
    const maxValue = Math.max(...last7DaysData.value.map(item => item.sales), 0);
    if (maxValue === 0) return 50;
    const rounded = Math.ceil(maxValue / 50) * 50;
    return rounded >= 400 ? 100 : 50;
});

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
            suggestedMax: chartMaxValue.value,
            grid: {
                color: 'rgba(156, 163, 175, 0.1)',
            },
            ticks: {
                stepSize: chartStepSize.value,
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
    const data = last7DaysData.value;
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
    const data = last7DaysData.value;
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