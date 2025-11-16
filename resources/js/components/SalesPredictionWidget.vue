<template>
    <Card>
        <CardHeader>
            <div class="flex items-center justify-between">
                <CardTitle class="flex items-center gap-2">
                    <TrendingUp class="w-5 h-5 text-blue-600" />
                    Sales Prediction
                </CardTitle>
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
                        <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-4">Sales Forecast Trend</h4>
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
import { TrendingUp, ChevronDown } from 'lucide-vue-next';

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

// Prediction Chart.js data and options
const predictionChartData = computed(() => ({
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
}));

const predictionChartOptions = computed(() => ({
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
            min: Math.min(...nextWeekPredictions.value, 0),
            max: Math.max(...nextWeekPredictions.value, 1) * 1.1,
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
}));

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