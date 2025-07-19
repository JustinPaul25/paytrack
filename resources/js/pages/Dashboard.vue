<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import Icon from '@/components/Icon.vue';
import BaseChart from '@/components/charts/BaseChart.vue';
import { ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

// Mock data for the dashboard
const summaryCards = [
    {
        title: 'Total Revenue',
        value: '₱ 3,000',
        change: '+16%',
        changeType: 'positive',
        icon: 'dollar-sign',
        color: 'blue'
    },
    {
        title: 'Categories',
        value: '11',
        change: '+12%',
        changeType: 'positive',
        icon: 'paper-plane',
        color: 'orange'
    },
    {
        title: 'Total Products',
        value: '37',
        change: '+8%',
        changeType: 'positive',
        icon: 'shopping-cart',
        color: 'green'
    },
    {
        title: 'Total Users',
        value: '7',
        change: '+5%',
        changeType: 'positive',
        icon: 'user',
        color: 'purple'
    }
];

const salesData = {
    labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5', 'Week 6'],
    datasets: [{
        label: 'Sales',
        data: [25000, 45000, 32000, 42000, 35000, 40000],
        borderColor: '#3b82f6',
        backgroundColor: 'rgba(59, 130, 246, 0.1)',
        fill: true,
        tension: 0.4
    }]
};

const marginData = {
    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
    datasets: [
        {
            label: 'Sales',
            data: [85000, 92000, 78000, 95000, 88000, 102000],
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            fill: false,
            tension: 0.4
        },
        {
            label: 'Expenses',
            data: [65000, 72000, 68000, 75000, 70000, 82000],
            borderColor: '#ef4444',
            backgroundColor: 'rgba(239, 68, 68, 0.1)',
            fill: false,
            tension: 0.4
        }
    ]
};

const predictionData = {
    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug'],
    datasets: [
        {
            label: 'Actual Sales',
            data: [85000, 92000, 78000, 95000, 88000, 102000, null, null],
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            fill: false,
            tension: 0.4,
            borderWidth: 2
        },
        {
            label: 'Predicted Sales',
            data: [null, null, null, null, null, 102000, 108000, 115000],
            borderColor: '#f59e0b',
            backgroundColor: 'rgba(245, 158, 11, 0.1)',
            fill: false,
            tension: 0.4,
            borderWidth: 2,
            borderDash: [5, 5]
        }
    ]
};

const topProducts = [
    { name: 'Ink (brother 500 C, Cyan)', sold: 5 },
    { name: 'Desktop Condenser Microphone', sold: 2 },
    { name: 'Scientific Calculator', sold: 1 }
];

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
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4" @click="closeNotifications">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <Icon name="clock" class="h-5 w-5 text-muted-foreground" />
                    <h1 class="text-2xl font-semibold">Dashboard</h1>
                </div>
                <div class="flex items-center gap-2 relative">
                    <button 
                        @click.stop="toggleNotifications"
                        class="relative p-2 rounded-lg hover:bg-muted transition-colors"
                    >
                        <Icon name="bell" class="h-5 w-5 text-muted-foreground" />
                        <span v-if="unreadCount > 0" 
                              class="absolute -top-1 -right-1 h-5 w-5 rounded-full bg-red-500 text-white text-xs flex items-center justify-center font-medium">
                            {{ unreadCount }}
                        </span>
                    </button>
                    
                    <!-- Notification Popup -->
                    <div v-if="showNotifications" 
                         class="absolute top-full right-0 mt-2 w-80 bg-background border border-border rounded-lg shadow-lg z-50 max-h-96 overflow-y-auto">
                        <div class="p-4 border-b border-border">
                            <div class="flex items-center justify-between">
                                <h3 class="font-semibold">Notifications</h3>
                                <span class="text-sm text-muted-foreground">{{ unreadCount }} unread</span>
                            </div>
                        </div>
                        <div class="divide-y divide-border">
                            <div v-for="notification in notifications" 
                                 :key="notification.id"
                                 @click="markAsRead(notification.id)"
                                 :class="[
                                     'p-4 cursor-pointer hover:bg-muted/50 transition-colors',
                                     !notification.read ? 'bg-blue-50/50 dark:bg-blue-950/20' : ''
                                 ]">
                                <div class="flex items-start gap-3">
                                    <div class="flex-shrink-0 mt-1">
                                        <div :class="[
                                            'h-2 w-2 rounded-full',
                                            notification.type === 'order' ? 'bg-blue-500' :
                                            notification.type === 'alert' ? 'bg-red-500' :
                                            notification.type === 'success' ? 'bg-green-500' :
                                            'bg-purple-500'
                                        ]"></div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-foreground">{{ notification.title }}</p>
                                        <p class="text-sm text-muted-foreground mt-1">{{ notification.message }}</p>
                                        <p class="text-xs text-muted-foreground mt-2">{{ notification.time }}</p>
                                    </div>
                                    <div v-if="!notification.read" class="flex-shrink-0">
                                        <div class="h-2 w-2 rounded-full bg-blue-500"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="p-3 border-t border-border">
                            <button class="w-full text-sm text-muted-foreground hover:text-foreground transition-colors">
                                View all notifications
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Demo Data Notice -->
            <div class="rounded-lg bg-blue-50 border border-blue-200 p-3 dark:bg-blue-950/20 dark:border-blue-800">
                <div class="flex items-center gap-2">
                    <Icon name="info" class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                    <p class="text-sm text-blue-800 dark:text-blue-200">
                        This dashboard displays demo/dummy data for demonstration purposes.
                    </p>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card v-for="card in summaryCards" :key="card.title" 
                      :class="['border-l-4', getColorClasses(card.color)]">
                    <CardContent class="p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">{{ card.title }}</p>
                                <p class="text-2xl font-bold">{{ card.value }}</p>
                                <p class="text-sm text-green-600 dark:text-green-400">
                                    {{ card.change }} from last month
                                </p>
                            </div>
                            <div :class="['p-2 rounded-lg', getIconColorClasses(card.color)]">
                                <Icon :name="card.icon" class="h-4 w-4" />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Charts and Analytics Section -->
            <div class="grid gap-4 md:grid-cols-2">
                <!-- Sales Analytics -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <Icon name="bar-chart" class="h-5 w-5 text-muted-foreground" />
                                <CardTitle>Sales Analytics</CardTitle>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="relative">
                                    <select class="appearance-none bg-background border border-border rounded-md px-3 py-1.5 pr-8 text-sm focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 dark:focus:ring-offset-background">
                                        <option>Weekly</option>
                                        <option>Monthly</option>
                                        <option>Yearly</option>
                                    </select>
                                    <Icon name="chevron-down" class="absolute right-2 top-1/2 h-3 w-3 -translate-y-1/2 text-muted-foreground pointer-events-none" />
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-2xl font-bold">₱ 84,994.80</p>
                                <p class="text-sm text-green-600 dark:text-green-400">↑ 16% from last month</p>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="h-64">
                            <BaseChart 
                                type="line" 
                                :data="salesData"
                                :options="{
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            ticks: {
                                                callback: function(value: number) {
                                                    return '₱' + (value / 1000) + 'k';
                                                }
                                            }
                                        }
                                    },
                                    plugins: {
                                        legend: {
                                            display: false
                                        }
                                    }
                                }"
                            />
                        </div>
                    </CardContent>
                </Card>

                <!-- Top Selling Products -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center gap-2">
                            <Icon name="flame" class="h-5 w-5 text-muted-foreground" />
                            <CardTitle>Top Selling Products</CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div v-for="product in topProducts" :key="product.name" 
                                 class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="h-2 w-2 rounded-full bg-blue-500"></div>
                                    <span class="text-sm font-medium">{{ product.name }}</span>
                                </div>
                                <span class="text-sm text-muted-foreground">{{ product.sold }} sold</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Margin Analysis and Sales Prediction Charts -->
            <div class="grid gap-4 md:grid-cols-2">
                <!-- Margin Analysis Chart -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <Icon name="trending-up" class="h-5 w-5 text-muted-foreground" />
                                <CardTitle>Sales vs Expenses Margin</CardTitle>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="flex items-center gap-2">
                                    <div class="h-3 w-3 rounded-full bg-green-500"></div>
                                    <span class="text-sm text-muted-foreground">Sales</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="h-3 w-3 rounded-full bg-red-500"></div>
                                    <span class="text-sm text-muted-foreground">Expenses</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-2xl font-bold">₱ 20,000</p>
                                <p class="text-sm text-green-600 dark:text-green-400">Net Profit Margin</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-muted-foreground">Average Margin</p>
                                <p class="text-lg font-semibold text-green-600 dark:text-green-400">24.5%</p>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="h-80">
                            <BaseChart 
                                type="line" 
                                :data="marginData"
                                :options="{
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    interaction: {
                                        intersect: false,
                                        mode: 'index'
                                    },
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            ticks: {
                                                callback: function(value: number) {
                                                    return '₱' + (value / 1000) + 'k';
                                                }
                                            }
                                        }
                                    },
                                    plugins: {
                                        legend: {
                                            display: false
                                        },
                                        tooltip: {
                                            callbacks: {
                                                label: function(context: any) {
                                                    return context.dataset.label + ': ₱' + context.parsed.y.toLocaleString();
                                                }
                                            }
                                        }
                                    }
                                }"
                            />
                        </div>
                    </CardContent>
                </Card>

                <!-- Sales Prediction Chart -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <Icon name="crystal-ball" class="h-5 w-5 text-muted-foreground" />
                                <CardTitle>Sales Forecast</CardTitle>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="flex items-center gap-2">
                                    <div class="h-3 w-3 rounded-full bg-blue-500"></div>
                                    <span class="text-sm text-muted-foreground">Actual</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="h-3 w-3 rounded-full bg-amber-500"></div>
                                    <span class="text-sm text-muted-foreground">Predicted</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-2xl font-bold">₱ 115,000</p>
                                <p class="text-sm text-amber-600 dark:text-amber-400">Predicted for Aug</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-muted-foreground">Growth Rate</p>
                                <p class="text-lg font-semibold text-green-600 dark:text-green-400">+12.7%</p>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="h-80">
                            <BaseChart 
                                type="line" 
                                :data="predictionData"
                                :options="{
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    interaction: {
                                        intersect: false,
                                        mode: 'index'
                                    },
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            ticks: {
                                                callback: function(value: number) {
                                                    return '₱' + (value / 1000) + 'k';
                                                }
                                            }
                                        }
                                    },
                                    plugins: {
                                        legend: {
                                            display: false
                                        },
                                        tooltip: {
                                            callbacks: {
                                                label: function(context: any) {
                                                    if (context.parsed.y !== null) {
                                                        return context.dataset.label + ': ₱' + context.parsed.y.toLocaleString();
                                                    }
                                                    return null;
                                                }
                                            }
                                        }
                                    }
                                }"
                            />
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
