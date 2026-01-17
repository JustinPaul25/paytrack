<template>
    <Card>
        <CardHeader>
            <div class="flex items-center justify-between">
                <CardTitle class="flex items-center gap-2">
                    <TrendingUp class="w-5 h-5 text-blue-600" />
                    Sales Prediction
                </CardTitle>
                <div class="flex flex-col gap-2" style="min-width: 200px;">
                    <div class="flex items-center gap-2">
                        <Calendar class="w-4 h-4" />
                        <label class="text-sm font-medium">Date Filter</label>
                    </div>
                    <Select
                        v-model="predictionFilterPeriod"
                        :options="periodOptions"
                        placeholder="Choose time period"
                        class="w-full"
                    />
                    <div v-if="predictionFilterPeriod === 'custom'" class="flex gap-2">
                        <input
                            v-model="predictionFilterStartDate"
                            type="date"
                            class="flex-1 px-2 py-1 text-sm border rounded"
                        />
                        <input
                            v-model="predictionFilterEndDate"
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
                <!-- Live Predictions Display -->
                <div class="space-y-6">
                    <!-- Next Day Prediction -->
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-950/20 dark:to-indigo-950/20 rounded-lg p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    Tomorrow's Prediction
                                </h4>
                                <p class="text-3xl font-bold text-blue-600">
                                    {{ formatCurrency(nextDayPrediction) }}
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    Expected sales for {{ formatDate(tomorrow) }}
                                </p>
                            </div>
                            <div class="text-right">
                                <div class="text-sm text-gray-600 dark:text-gray-400">Confidence</div>
                                <div class="text-2xl font-bold text-green-600">
                                    {{ (averageConfidence * 100).toFixed(0) }}%
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Weekly Predictions Chart -->
                    <div>
                        <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-4">Next 7 Days Forecast</h4>
                        <div class="space-y-3">
                            <div 
                                v-for="(prediction, index) in nextWeekPredictions" 
                                :key="index"
                                class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/20 rounded-full flex items-center justify-center">
                                        <span class="text-sm font-medium text-blue-600">{{ index + 1 }}</span>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-gray-100">
                                            {{ formatDate(getDateFromIndex(index)) }}
                                        </p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ getDayName(getDateFromIndex(index)) }}
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="font-semibold text-gray-900 dark:text-gray-100">
                                        {{ formatCurrency(prediction) }}
                                    </p>
                                    <div class="flex items-center gap-1 mt-1">
                                        <div 
                                            class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden"
                                            style="width: 60px;"
                                        >
                                            <div 
                                                class="h-full bg-blue-500 rounded-full transition-all duration-300"
                                                :style="{ width: getPredictionBarWidth(prediction) + '%' }"
                                            ></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Prediction Chart -->
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="font-medium text-gray-900 dark:text-gray-100">Sales Forecast Trend</h4>
                            <div class="flex items-center gap-2">
                                <BarChart3 class="w-4 h-4 text-muted-foreground" />
                                <Select
                                    v-model="forecastPeriod"
                                    :options="[
                                        { value: 'monthly', label: 'Monthly' },
                                        { value: 'yearly', label: 'Yearly' }
                                    ]"
                                    placeholder="Select period"
                                    class="w-32"
                                />
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                            <Line
                                :data="predictionChartData"
                                :options="predictionChartOptions"
                                height="200px"
                            />
                        </div>
                    </div>

                    <!-- Monthly Trend -->
                    <div>
                        <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-4">Monthly Trend</h4>
                        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Predicted Monthly Revenue</span>
                                <span class="font-semibold text-gray-900 dark:text-gray-100">
                                    {{ formatCurrency(monthlyPrediction) }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <TrendingUp class="w-4 h-4 text-green-600" />
                                <span class="text-green-600 text-sm font-medium">
                                    +{{ monthlyGrowth.toFixed(1) }}% vs current month
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Model Info -->
                    <div class="border-t pt-4">
                        <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
                            <span v-if="usingDummy">Using sample data</span>
                            <span v-else>Live predictions</span>
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                <span>Ready</span>
                            </div>
                        </div>
                    </div>
                </div>
            </CardContent>
        </div>
    </Card>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
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
import { TrendingUp, ChevronDown, BarChart3, Calendar } from 'lucide-vue-next';
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

interface Props {
    salesData?: Array<{ date: string; sales: number; invoices: number }>;
}

const props = defineProps<Props>();

// Collapsible state (disabled: content always open)
const isCollapsed = ref(false);

// Forecast period filter (monthly/yearly)
const forecastPeriod = ref<'monthly' | 'yearly'>('monthly');

// Date filter for Sales Prediction Widget
const predictionFilterPeriod = ref('month');
const predictionFilterStartDate = ref('');
const predictionFilterEndDate = ref('');

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
    start.setMonth(start.getMonth() - 1);
    predictionFilterStartDate.value = start.toISOString().split('T')[0];
    predictionFilterEndDate.value = end.toISOString().split('T')[0];
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
                start.setMonth(start.getMonth() - 1);
        }
    }
    
    return data.filter(item => {
        const itemDate = new Date(item.date);
        return itemDate >= start && itemDate <= end;
    });
}

// Get filtered sales data for prediction widget
const filteredSalesDataForPrediction = computed(() => {
    if (!props.salesData) return [];
    return filterByDateRange(
        props.salesData,
        predictionFilterPeriod.value,
        predictionFilterStartDate.value || undefined,
        predictionFilterEndDate.value || undefined
    );
});

const tomorrow = computed(() => {
    const date = new Date();
    date.setDate(date.getDate() + 1);
    return date;
});

// Live prediction state
const predictions = ref<Array<{ date: string; predicted_sales: number; confidence: number }>>([]);
const averageConfidence = computed(() => {
    if (!predictions.value.length) return 0.7;
    const sum = predictions.value.reduce((acc, p) => acc + (p.confidence ?? 0.7), 0);
    return sum / predictions.value.length;
});
const nextWeekPredictions = computed<number[]>(() => predictions.value.slice(0, 7).map(p => p.predicted_sales));
const nextDayPrediction = computed<number>(() => (predictions.value[0]?.predicted_sales ?? 0));
const monthlyPrediction = computed<number>(() => {
    if (predictions.value.length >= 30) {
        return predictions.value.slice(0, 30).reduce((acc, p) => acc + p.predicted_sales, 0);
    }
    if (predictions.value.length > 0) {
        const avg = predictions.value.reduce((acc, p) => acc + p.predicted_sales, 0) / predictions.value.length;
        return avg * 30;
    }
    return 0;
});
const monthlyGrowth = computed<number>(() => {
    return Math.max(0, Math.min(25, (averageConfidence.value - 0.6) * 100));
});
const usingDummy = ref(false);

// Group historical sales data by month
const groupedHistoricalData = computed(() => {
    if (!filteredSalesDataForPrediction.value || filteredSalesDataForPrediction.value.length === 0) {
        return new Map<string, { sales: number; date: string; sortKey: string; isHistorical: boolean }>();
    }
    
    const grouped = new Map<string, { sales: number; date: string; sortKey: string; isHistorical: boolean }>();
    
    filteredSalesDataForPrediction.value.forEach(item => {
        // Handle date parsing
        let date: Date;
        if (typeof item.date === 'string') {
            if (item.date.includes('T')) {
                date = new Date(item.date);
            } else {
                date = new Date(item.date + 'T00:00:00');
            }
        } else {
            date = new Date(item.date);
        }
        
        // Skip invalid dates
        if (isNaN(date.getTime())) {
            return;
        }
        
        const monthKey = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
        const monthLabel = date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
        
        if (grouped.has(monthKey)) {
            const existing = grouped.get(monthKey)!;
            existing.sales += item.sales;
        } else {
            grouped.set(monthKey, {
                sales: item.sales,
                date: monthLabel,
                sortKey: monthKey,
                isHistorical: true
            });
        }
    });
    
    return grouped;
});

// Group predictions by month or year based on forecastPeriod
const groupedPredictions = computed(() => {
    if (forecastPeriod.value === 'yearly') {
        // Group by year (for longer forecasts)
        const grouped = new Map<string, { sales: number; date: string; sortKey: string; isHistorical: boolean }>();
        
        predictions.value.forEach((pred, index) => {
            const date = getDateFromIndex(index);
            const year = date.getFullYear().toString();
            const sortKey = year;
            
            if (grouped.has(year)) {
                const existing = grouped.get(year)!;
                existing.sales += pred.predicted_sales;
            } else {
                grouped.set(year, {
                    sales: pred.predicted_sales,
                    date: year,
                    sortKey: sortKey,
                    isHistorical: false
                });
            }
        });
        
        return Array.from(grouped.values())
            .sort((a, b) => a.sortKey.localeCompare(b.sortKey))
            .map(item => ({
                label: item.date,
                sales: item.sales,
                isHistorical: item.isHistorical
            }));
    } else {
        // Group by month - combine historical and predicted data
        const combined = new Map<string, { sales: number; date: string; sortKey: string; isHistorical: boolean }>();
        
        // Add historical data first
        groupedHistoricalData.value.forEach((value, key) => {
            combined.set(key, { ...value });
        });
        
        // Add predicted data (only for future months)
        predictions.value.forEach((pred, index) => {
            const date = getDateFromIndex(index);
            const monthKey = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
            const monthLabel = date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
            
            // Only add if it's a future month (not already in historical data)
            if (!combined.has(monthKey)) {
                combined.set(monthKey, {
                    sales: pred.predicted_sales,
                    date: monthLabel,
                    sortKey: monthKey,
                    isHistorical: false
                });
            } else {
                // If the month exists in historical data, add predictions to it (for current month)
                const existing = combined.get(monthKey)!;
                if (!existing.isHistorical) {
                    existing.sales += pred.predicted_sales;
                }
            }
        });
        
        // Return all available data without forcing specific months
        return Array.from(combined.values())
            .sort((a, b) => a.sortKey.localeCompare(b.sortKey))
            .map(item => ({
                label: item.date,
                sales: item.sales,
                isHistorical: item.isHistorical
            }));
    }
});

// Prediction Chart.js data and options
const predictionChartData = computed(() => {
    if (forecastPeriod.value === 'yearly' || forecastPeriod.value === 'monthly') {
        // Use grouped data for monthly/yearly view - combine historical and predicted
        const historicalData = groupedPredictions.value.filter(item => item.isHistorical);
        const predictedData = groupedPredictions.value.filter(item => !item.isHistorical);
        
        return {
            labels: groupedPredictions.value.map(item => item.label),
            datasets: [
                {
                    label: forecastPeriod.value === 'yearly' ? 'Historical & Predicted Yearly Sales' : 'Historical & Predicted Monthly Sales',
                    data: groupedPredictions.value.map(item => item.sales),
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: groupedPredictions.value.map(item => 
                        item.isHistorical ? 'rgb(16, 185, 129)' : 'rgb(59, 130, 246)'
                    ),
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                },
                {
                    label: 'Confidence Range',
                    data: groupedPredictions.value.map(item => 
                        item.isHistorical ? item.sales : item.sales * 0.95
                    ),
                    borderColor: 'rgba(59, 130, 246, 0.3)',
                    backgroundColor: 'rgba(59, 130, 246, 0.05)',
                    fill: false,
                    tension: 0.4,
                    borderDash: [5, 5],
                    pointRadius: 0,
                }
            ]
        };
    }
    
    // Default: daily predictions (next 7 days)
    return {
        labels: nextWeekPredictions.value.map((_, index) => {
            const date = getDateFromIndex(index);
            return formatDate(date).split(' ')[1];
        }),
        datasets: [
            {
                label: 'Predicted Sales',
                data: nextWeekPredictions.value,
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
                label: 'Confidence Range',
                data: nextWeekPredictions.value.map(pred => pred * 0.95),
                borderColor: 'rgba(59, 130, 246, 0.3)',
                backgroundColor: 'rgba(59, 130, 246, 0.05)',
                fill: false,
                tension: 0.4,
                borderDash: [5, 5],
                pointRadius: 0,
            }
        ]
    };
});

const predictionChartOptions = computed(() => {
    const dataValues = forecastPeriod.value === 'yearly' || forecastPeriod.value === 'monthly'
        ? groupedPredictions.value.map(item => item.sales)
        : nextWeekPredictions.value;
    
    return {
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
                        return `${context.dataset.label}: ${formatCurrency(context.parsed.y)}`;
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: false,
                min: Math.min(...dataValues, 0),
                max: Math.max(...dataValues, 1) * 1.1,
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
    };
});

const maxPrediction = computed(() => Math.max(...nextWeekPredictions.value, 0));

const chartPoints = computed(() => {
    const points = nextWeekPredictions.value.map((value, index) => {
        const x = (index / 6) * 100;
        const y = 100 - ((value / maxPrediction.value) * 100);
        return `${x},${y}`;
    });
    return points.join(' ');
});

const chartPointsArray = computed(() => {
    return nextWeekPredictions.value.map((value, index) => {
        const x = (index / 6) * 100;
        const y = 100 - ((value / maxPrediction.value) * 100);
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

function formatDate(date: Date): string {
    return date.toLocaleDateString('en-US', { 
        month: 'short', 
        day: 'numeric' 
    });
}

function getDayName(date: Date): string {
    return date.toLocaleDateString('en-US', { weekday: 'short' });
}

function getDateFromIndex(index: number): Date {
    const date = new Date();
    date.setDate(date.getDate() + index + 1);
    return date;
}

function getPredictionBarWidth(prediction: number): number {
    const maxPrediction = Math.max(...nextWeekPredictions.value, 0);
    return maxPrediction > 0 ? (prediction / maxPrediction) * 100 : 0;
}

// Fetch live predictions on mount
onMounted(async () => {
    initializeDates();
    try {
        const res = await fetch(`/sales/predictions?period=month&days_ahead=30`, {
            headers: {
                'Accept': 'application/json'
            }
        });
        if (!res.ok) throw new Error(`Failed to fetch predictions: ${res.status}`);
        const data = await res.json();
        usingDummy.value = !!data?.model_info?.note;
        predictions.value = Array.isArray(data?.predictions) ? data.predictions : [];
    } catch (e) {
        usingDummy.value = true;
        predictions.value = [];
        console.error(e);
    }
});
</script> 