<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import { Button } from '@/components/ui/button';
import { AlertCircle, Calendar } from 'lucide-vue-next';

interface MonthlyPoint { month: string; total: number }
interface AovPoint { month: string; aov: number }

interface Reminder {
    id: number;
    title: string;
    description: string;
    due_date: string;
    due_date_formatted: string;
    amount: number;
    currency: string;
    priority: 'low' | 'medium' | 'high';
    is_read: boolean;
    invoice_id: number | null;
    invoice_reference: string | null;
    days_until_due: number;
}

const props = defineProps<{
    customer: { id: number | null, name?: string, email?: string } | null,
    monthlySpend: MonthlyPoint[],
    statusBreakdown: { paid: number, pending: number, cancelled: number },
    topProducts: { id: number, name: string, total_quantity: number }[],
    categorySpend: { category: string, total: number }[],
    aovTrend: AovPoint[],
    reminders?: Reminder[],
}>();

function formatCurrency(amount: number) {
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', minimumFractionDigits: 2 }).format(amount);
}

function getPriorityClass(priority: string): string {
    switch (priority) {
        case 'high':
            return 'border-l-4 border-l-red-500 bg-red-50 dark:bg-red-950/20';
        case 'medium':
            return 'border-l-4 border-l-yellow-500 bg-yellow-50 dark:bg-yellow-950/20';
        case 'low':
            return 'border-l-4 border-l-blue-500 bg-blue-50 dark:bg-blue-950/20';
        default:
            return 'border-l-4 border-l-gray-500 bg-gray-50 dark:bg-gray-950/20';
    }
}

function getPriorityText(priority: string): string {
    return priority.charAt(0).toUpperCase() + priority.slice(1);
}

function getStatusText(daysUntilDue: number): string {
    if (daysUntilDue < 0) {
        return `Overdue by ${Math.abs(daysUntilDue)} day(s)`;
    } else if (daysUntilDue === 0) {
        return 'Due today';
    } else {
        return `Due in ${daysUntilDue} day(s)`;
    }
}

function getStatusClass(daysUntilDue: number): string {
    if (daysUntilDue < 0) {
        return 'text-red-600 dark:text-red-400 font-semibold';
    } else if (daysUntilDue === 0) {
        return 'text-orange-600 dark:text-orange-400 font-semibold';
    } else if (daysUntilDue <= 3) {
        return 'text-yellow-600 dark:text-yellow-400 font-semibold';
    } else {
        return 'text-blue-600 dark:text-blue-400';
    }
}
</script>

<template>
    <AppLayout>
        <Head title="My Dashboard" />

        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">My Dashboard</h1>
            <Link :href="route('orders.create')">
                <Button variant="default">
                    <span class="mr-2">+</span>
                    Create Order
                </Button>
            </Link>
        </div>

        <!-- Payment Reminders Section -->
        <div v-if="reminders && reminders.length > 0" class="mb-6">
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <AlertCircle class="w-5 h-5 text-orange-500" />
                        Payment Reminders
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-3">
                        <div
                            v-for="reminder in reminders"
                            :key="reminder.id"
                            :class="['p-4 rounded-lg', getPriorityClass(reminder.priority)]"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <h3 class="font-semibold text-gray-900 dark:text-gray-100">
                                            {{ reminder.title }}
                                        </h3>
                                        <span
                                            :class="['px-2 py-1 text-xs rounded-full', 
                                                reminder.priority === 'high' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300' :
                                                reminder.priority === 'medium' ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300' :
                                                'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'
                                            ]"
                                        >
                                            {{ getPriorityText(reminder.priority) }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                        {{ reminder.description }}
                                    </p>
                                    <div class="flex flex-wrap items-center gap-4 text-sm">
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-semibold text-gray-900 dark:text-gray-100">
                                                {{ formatCurrency(reminder.amount) }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <Calendar class="w-4 h-4 text-gray-500" />
                                            <span class="text-gray-600 dark:text-gray-400">
                                                {{ reminder.due_date_formatted }}
                                            </span>
                                        </div>
                                        <div>
                                            <span :class="getStatusClass(reminder.days_until_due)">
                                                {{ getStatusText(reminder.days_until_due) }}
                                            </span>
                                        </div>
                                        <div v-if="reminder.invoice_reference" class="text-gray-500 dark:text-gray-400">
                                            Invoice: {{ reminder.invoice_reference }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Monthly Spend Trend -->
            <Card>
                <CardHeader>
                    <CardTitle>Monthly spend (last 12 months)</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-2">
                        <div v-if="monthlySpend.length === 0" class="text-sm text-muted-foreground">
                            No data yet.
                        </div>
                        <div v-else v-for="p in monthlySpend" :key="p.month" class="flex items-center gap-3">
                            <div class="w-24 text-xs text-muted-foreground">{{ p.month }}</div>
                            <div class="flex-1 h-2 rounded bg-muted overflow-hidden">
                                <div class="h-2 bg-primary" :style="{ width: Math.min(100, Math.round(p.total / (Math.max(...monthlySpend.map(m => m.total)) || 1) * 100)) + '%' }"></div>
                            </div>
                            <div class="w-24 text-right text-sm">{{ formatCurrency(p.total) }}</div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Status Breakdown -->
            <Card>
                <CardHeader>
                    <CardTitle>Order status</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-3 gap-4 text-center">
                        <div>
                            <div class="text-2xl font-bold">{{ statusBreakdown.paid }}</div>
                            <div class="text-xs text-muted-foreground">Paid</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold">{{ statusBreakdown.pending }}</div>
                            <div class="text-xs text-muted-foreground">Pending</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold">{{ statusBreakdown.cancelled }}</div>
                            <div class="text-xs text-muted-foreground">Cancelled</div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Top Products -->
            <Card>
                <CardHeader>
                    <CardTitle>Top products purchased</CardTitle>
                </CardHeader>
                <CardContent>
                    <table class="min-w-full divide-y divide-border">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 text-left">Product</th>
                                <th class="px-4 py-2 text-right">Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="p in topProducts" :key="p.id" class="hover:bg-muted">
                                <td class="px-4 py-2">{{ p.name }}</td>
                                <td class="px-4 py-2 text-right">{{ p.total_quantity }}</td>
                            </tr>
                            <tr v-if="topProducts.length === 0">
                                <td colspan="2" class="px-4 py-4 text-sm text-muted-foreground text-center">No purchases yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </CardContent>
            </Card>

            <!-- Category Spend -->
            <Card>
                <CardHeader>
                    <CardTitle>Spend by category</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-2">
                        <div v-if="categorySpend.length === 0" class="text-sm text-muted-foreground">
                            No spend yet.
                        </div>
                        <div v-else v-for="c in categorySpend" :key="c.category" class="flex items-center gap-3">
                            <div class="w-40 text-xs text-muted-foreground truncate">{{ c.category }}</div>
                            <div class="flex-1 h-2 rounded bg-muted overflow-hidden">
                                <div class="h-2 bg-primary/70" :style="{ width: Math.min(100, Math.round(c.total / (Math.max(...categorySpend.map(m => m.total)) || 1) * 100)) + '%' }"></div>
                            </div>
                            <div class="w-24 text-right text-sm">{{ formatCurrency(c.total) }}</div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- AOV Trend -->
            <Card class="lg:col-span-2">
                <CardHeader>
                    <CardTitle>Average order value (monthly)</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-2">
                        <div v-if="aovTrend.length === 0" class="text-sm text-muted-foreground">
                            No data yet.
                        </div>
                        <div v-else v-for="p in aovTrend" :key="p.month" class="flex items-center gap-3">
                            <div class="w-24 text-xs text-muted-foreground">{{ p.month }}</div>
                            <div class="flex-1 h-2 rounded bg-muted overflow-hidden">
                                <div class="h-2 bg-emerald-500" :style="{ width: Math.min(100, Math.round(p.aov / (Math.max(...aovTrend.map(m => m.aov)) || 1) * 100)) + '%' }"></div>
                            </div>
                            <div class="w-24 text-right text-sm">{{ formatCurrency(p.aov) }}</div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>







