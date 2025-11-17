<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardContent from '@/components/ui/card/CardContent.vue';

interface MonthlyPoint { month: string; total: number }
interface AovPoint { month: string; aov: number }

const props = defineProps<{
    customer: { id: number | null, name?: string, email?: string } | null,
    monthlySpend: MonthlyPoint[],
    statusBreakdown: { paid: number, pending: number, cancelled: number },
    topProducts: { id: number, name: string, total_quantity: number }[],
    categorySpend: { category: string, total: number }[],
    aovTrend: AovPoint[],
}>();

function formatCurrency(amount: number) {
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', minimumFractionDigits: 2 }).format(amount);
}
</script>

<template>
    <AppLayout>
        <Head title="My Dashboard" />

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





