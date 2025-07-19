<script setup lang="ts">
import { ShoppingCart, Package, AlertTriangle, DollarSign } from 'lucide-vue-next';
import { cn } from '@/lib/utils';

interface ProductStats {
    totalProducts: number;
    totalStock: number;
    lowStockItems: number;
    totalValue: number;
}

interface Props {
    stats: ProductStats;
    class?: string;
}

const props = defineProps<Props>();

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2
    }).format(amount);
};
</script>

<template>
    <div :class="cn('space-y-6', props.class)">
        <!-- Header Section -->
        <div class="bg-card text-card-foreground rounded-xl border shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="bg-primary rounded-lg p-3">
                        <ShoppingCart class="h-6 w-6 text-primary-foreground" />
                    </div>
                    <h1 class="text-2xl font-bold">Product Management</h1>
                </div>
                <slot name="actions">
                    <!-- Actions can be passed as a slot -->
                </slot>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Products -->
            <div class="bg-card text-card-foreground rounded-xl border shadow-sm p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-muted-foreground">Total Products</p>
                        <p class="text-1xl font-bold">{{ stats.totalProducts }}</p>
                    </div>
                    <div class="bg-secondary rounded-lg p-3">
                        <Package class="h-6 w-6 text-secondary-foreground" />
                    </div>
                </div>
            </div>

            <!-- Total Stock -->
            <div class="bg-card text-card-foreground rounded-xl border shadow-sm p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-muted-foreground">Total Stock</p>
                        <p class="text-1xl font-bold">{{ stats.totalStock }}</p>
                    </div>
                    <div class="bg-accent rounded-lg p-3">
                        <ShoppingCart class="h-6 w-6 text-accent-foreground" />
                    </div>
                </div>
            </div>

            <!-- Low Stock Items -->
            <div class="bg-card text-card-foreground rounded-xl border shadow-sm p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-muted-foreground">Low Stock Items</p>
                        <p class="text-1xl font-bold">{{ stats.lowStockItems }}</p>
                    </div>
                    <div class="bg-destructive/10 rounded-lg p-3">
                        <AlertTriangle class="h-6 w-6 text-destructive" />
                    </div>
                </div>
            </div>

            <!-- Total Value -->
            <div class="bg-card text-card-foreground rounded-xl border shadow-sm p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-muted-foreground">Total Value</p>
                        <p class="text-1xl font-bold">{{ formatCurrency(stats.totalValue) }}</p>
                    </div>
                    <div class="bg-primary/10 rounded-lg p-3">
                        <DollarSign class="h-6 w-6 text-primary" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template> 