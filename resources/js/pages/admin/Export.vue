<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import { Button } from '@/components/ui/button';
import { type BreadcrumbItem } from '@/types';
import { Download, Tag, Package, Receipt, ShoppingCart, TrendingUp } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Data Export',
        href: '/admin/export',
    },
];

function exportData(type: string) {
    const url = route(`export.${type}`);
    window.location.href = url;
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Data Export" />
        
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">Data Export</h1>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Categories Export -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Tag class="h-5 w-5" />
                        Categories
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-sm text-gray-600 mb-4">
                        Export all category data including name, description, and timestamps.
                    </p>
                    <Button @click="exportData('categories')" variant="default" class="w-full">
                        <Download class="h-4 w-4 mr-2" />
                        Export Categories
                    </Button>
                </CardContent>
            </Card>

            <!-- Products Export -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Package class="h-5 w-5" />
                        Products
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-sm text-gray-600 mb-4">
                        Export all product data including name, description, prices, stock, SKU, and category information.
                    </p>
                    <Button @click="exportData('products')" variant="default" class="w-full">
                        <Download class="h-4 w-4 mr-2" />
                        Export Products
                    </Button>
                </CardContent>
            </Card>

            <!-- Invoices Export -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Receipt class="h-5 w-5" />
                        Invoices
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-sm text-gray-600 mb-4">
                        Export all invoice data including reference numbers, customer information, amounts, payment status, and dates.
                    </p>
                    <Button @click="exportData('invoices')" variant="default" class="w-full">
                        <Download class="h-4 w-4 mr-2" />
                        Export Invoices
                    </Button>
                </CardContent>
            </Card>

            <!-- Orders Export -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <ShoppingCart class="h-5 w-5" />
                        Orders
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-sm text-gray-600 mb-4">
                        Export all order data including reference numbers, customer information, amounts, status, approval details, and dates.
                    </p>
                    <Button @click="exportData('orders')" variant="default" class="w-full">
                        <Download class="h-4 w-4 mr-2" />
                        Export Orders
                    </Button>
                </CardContent>
            </Card>

            <!-- Sales Export -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <TrendingUp class="h-5 w-5" />
                        Sales
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-sm text-gray-600 mb-4">
                        Export all sales transaction data including customer information, product details, tax calculations, cash amounts, and running balance.
                    </p>
                    <Button @click="exportData('sales')" variant="default" class="w-full">
                        <Download class="h-4 w-4 mr-2" />
                        Export Sales
                    </Button>
                </CardContent>
            </Card>
        </div>

        <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
            <p class="text-sm text-gray-700 dark:text-gray-300">
                <strong>Note:</strong> All exports are generated in CSV format and will be downloaded immediately. 
                The files include UTF-8 encoding for proper display of special characters.
            </p>
        </div>
    </AppLayout>
</template>

