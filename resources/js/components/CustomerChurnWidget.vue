<template>
    <Card>
        <CardHeader>
            <div class="flex items-center justify-between">
                <CardTitle class="flex items-center gap-2">
                    <Users class="w-5 h-5 text-red-600" />
                    Customer Churn Analysis
                </CardTitle>
            </div>
        </CardHeader>
        
        <!-- Collapsible Content -->
        <div 
            class="overflow-hidden transition-all duration-300 ease-in-out"
            :class="{ 'max-h-0': isCollapsed, 'max-h-[2000px]': !isCollapsed }"
        >
            <CardContent>
                <div class="space-y-6">
                    <!-- Churn Overview Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Churn Rate -->
                        <div class="bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-red-600 dark:text-red-400">Churn Rate</p>
                                    <p class="text-2xl font-bold text-red-700 dark:text-red-300">
                                        {{ metrics.churnRate }}%
                                    </p>
                                </div>
                                <div class="p-2 bg-red-100 dark:bg-red-900/30 rounded-full">
                                    <TrendingDown class="w-5 h-5 text-red-600" />
                                </div>
                            </div>
                            <p class="text-xs text-red-600 dark:text-red-400 mt-1">
                                Last 30 days
                            </p>
                        </div>

                        <!-- Retention Rate -->
                        <div class="bg-green-50 dark:bg-green-950/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-green-600 dark:text-green-400">Retention Rate</p>
                                    <p class="text-2xl font-bold text-green-700 dark:text-green-300">
                                        {{ metrics.retentionRate }}%
                                    </p>
                                </div>
                                <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-full">
                                    <TrendingUp class="w-5 h-5 text-green-600" />
                                </div>
                            </div>
                            <p class="text-xs text-green-600 dark:text-green-400 mt-1">
                                Last 30 days
                            </p>
                        </div>

                        <!-- At-Risk Customers -->
                        <div class="bg-yellow-50 dark:bg-yellow-950/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-yellow-600 dark:text-yellow-400">At Risk</p>
                                    <p class="text-2xl font-bold text-yellow-700 dark:text-yellow-300">
                                        {{ metrics.atRiskCustomers }}
                                    </p>
                                </div>
                                <div class="p-2 bg-yellow-100 dark:bg-yellow-900/30 rounded-full">
                                    <AlertTriangle class="w-5 h-5 text-yellow-600" />
                                </div>
                            </div>
                            <p class="text-xs text-yellow-600 dark:text-yellow-400 mt-1">
                                Need attention
                            </p>
                        </div>
                    </div>

                    <!-- Churn Trend Chart -->
                    <div>
                        <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-4">Churn Trend (Last 6 Months)</h4>
                        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                            <Line
                                :data="churnChartData"
                                :options="churnChartOptions"
                                height="200px"
                            />
                        </div>
                    </div>

                    <!-- Customer Segments -->
                    <div>
                        <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-4">Customer Segments</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Segment Distribution -->
                            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                                <h5 class="font-medium text-gray-900 dark:text-gray-100 mb-3">Segment Distribution</h5>
                                <div class="space-y-3" v-if="metrics.customerSegments && metrics.customerSegments.length">
                                    <div v-for="segment in metrics.customerSegments" :key="segment.name" class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <div 
                                                class="w-3 h-3 rounded-full"
                                                :style="{ backgroundColor: segment.color }"
                                            ></div>
                                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ segment.name }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <div class="w-16 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                                <div 
                                                    class="h-full rounded-full transition-all duration-300"
                                                    :style="{ 
                                                        width: segment.percentage + '%',
                                                        backgroundColor: segment.color 
                                                    }"
                                                ></div>
                                            </div>
                                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ segment.percentage }}%
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Churn by Segment -->
                            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                                <h5 class="font-medium text-gray-900 dark:text-gray-100 mb-3">Churn by Segment</h5>
                                <div class="space-y-3" v-if="metrics.churnBySegment && metrics.churnBySegment.length">
                                    <div v-for="segment in metrics.churnBySegment" :key="segment.name" class="flex items-center justify-between">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ segment.name }}</span>
                                        <div class="flex items-center gap-2">
                                            <div class="w-16 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                                <div 
                                                    class="h-full bg-red-500 rounded-full transition-all duration-300"
                                                    :style="{ width: segment.churnRate + '%' }"
                                                ></div>
                                            </div>
                                            <span class="text-sm font-medium text-red-600">
                                                {{ segment.churnRate }}%
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- At-Risk Customer List -->
                    <div>
                        <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-4">At-Risk Customers</h4>
                        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                            <div class="space-y-3">
                                <div 
                                    v-for="customer in metrics.atRiskCustomerList" 
                                    :key="customer.id"
                                    class="flex items-center justify-between p-3 bg-white dark:bg-gray-700 rounded-lg border border-yellow-200 dark:border-yellow-800"
                                >
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 bg-yellow-100 dark:bg-yellow-900/20 rounded-full flex items-center justify-center">
                                            <span class="text-sm font-medium text-yellow-600">{{ customer.name.charAt(0) }}</span>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ customer.name }}</p>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                                Last purchase: {{ customer.lastPurchase }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-medium text-yellow-600">{{ customer.riskLevel }}</p>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">
                                            {{ customer.daysSincePurchase }} days ago
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recommendations -->
                    <div>
                        <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-4">Recommendations</h4>
                        <div class="bg-blue-50 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                            <div class="space-y-3">
                                <div v-for="recommendation in metrics.recommendations" :key="recommendation.id" class="flex items-start gap-3">
                                    <div class="w-2 h-2 bg-blue-500 rounded-full mt-2 flex-shrink-0"></div>
                                    <div>
                                        <p class="text-sm font-medium text-blue-900 dark:text-blue-100">{{ recommendation.title }}</p>
                                        <p class="text-sm text-blue-700 dark:text-blue-300">{{ recommendation.description }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Status removed: now using live data -->
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
import { Users, TrendingDown, TrendingUp, AlertTriangle, ChevronDown } from 'lucide-vue-next';

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

interface ChurnMetrics {
    churnRate: number;
    retentionRate: number;
    atRiskCustomers: number;
    churnTrend: Array<{ month: string; rate: number }>;
    customerSegments?: Array<{ name: string; percentage: number; color: string }>;
    churnBySegment?: Array<{ name: string; churnRate: number }>;
    atRiskCustomerList?: Array<{ id: number; name: string; lastPurchase: string; riskLevel: string; daysSincePurchase: number }>;
    recommendations?: Array<{ id: number; title: string; description: string }>;
}

const props = defineProps<{
    metrics: ChurnMetrics
}>();

const metrics = computed<ChurnMetrics>(() => props.metrics);

// Churn Chart.js data and options
const churnChartData = computed(() => ({
    labels: (metrics.value.churnTrend || []).map(item => item.month),
    datasets: [
        {
            label: 'Churn Rate',
            data: (metrics.value.churnTrend || []).map(item => item.rate),
            borderColor: 'rgb(239, 68, 68)',
            backgroundColor: 'rgba(239, 68, 68, 0.1)',
            fill: true,
            tension: 0.4,
            pointBackgroundColor: 'rgb(239, 68, 68)',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 5,
        },
        {
            label: 'Retention Rate',
            data: (metrics.value.churnTrend || []).map(item => 100 - item.rate),
            borderColor: 'rgb(34, 197, 94)',
            backgroundColor: 'rgba(34, 197, 94, 0.1)',
            fill: false,
            tension: 0.4,
            borderDash: [5, 5],
            pointBackgroundColor: 'rgb(34, 197, 94)',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 4,
        }
    ]
}));

const churnChartOptions = computed(() => ({
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
                    return `${context.dataset.label}: ${context.parsed.y.toFixed(1)}%`;
                }
            }
        }
    },
    scales: {
        y: {
            beginAtZero: true,
            max: 15,
            grid: {
                color: 'rgba(156, 163, 175, 0.1)',
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

const churnChartPoints = computed(() => {
    const points = (metrics.value.churnTrend || []).map((item, index) => {
        const x = (index / 5) * 100;
        const y = 100 - ((item.rate / 8) * 100); // Scale to 8% max
        return `${x},${y}`;
    });
    return points.join(' ');
});

const churnChartPointsArray = computed(() => {
    return (metrics.value.churnTrend || []).map((item, index) => {
        const x = (index / 5) * 100;
        const y = 100 - ((item.rate / 8) * 100);
        return { x, y };
    });
});

function toggleCollapse() {
    isCollapsed.value = !isCollapsed.value;
}
</script> 