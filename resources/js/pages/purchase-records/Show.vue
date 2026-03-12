<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import { type BreadcrumbItem } from '@/types';
import Swal from 'sweetalert2';
import { Edit, Trash2, ArrowLeft, Printer } from 'lucide-vue-next';

interface PurchaseRecordItem {
    id: number;
    qty: string;
    unit: string | null;
    description: string;
    unit_price: string;
    amount: string;
}

interface PurchaseRecord {
    id: number;
    reference_number: string;
    supplier_name: string;
    supplier_tin: string | null;
    supplier_address: string | null;
    receipt_number: string | null;
    date: string;
    payment_type: 'cash' | 'credit';
    buyer_name: string | null;
    vatable_sales: string;
    vat_amount: string;
    total_due: string;
    withholding_tax: string;
    total_amount_due: string;
    notes: string | null;
    items: PurchaseRecordItem[];
    user: { id: number; name: string };
    created_at: string;
    updated_at: string;
}

const props = defineProps<{ record: PurchaseRecord }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Purchase Records', href: '/purchase-records' },
    { title: props.record.reference_number, href: `/purchase-records/${props.record.id}` },
];

async function deleteRecord() {
    const result = await Swal.fire({
        title: 'Delete Record?',
        text: `Delete ${props.record.reference_number}? This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete it',
    });
    if (result.isConfirmed) {
        router.delete(`/purchase-records/${props.record.id}`, {
            onSuccess: () => {
                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Record deleted.', showConfirmButton: false, timer: 3000, timerProgressBar: true });
            },
        });
    }
}

function formatCurrency(val: string | number) {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency', currency: 'PHP', minimumFractionDigits: 2,
    }).format(Number(val));
}

function formatDate(d: string) {
    return new Date(d).toLocaleDateString('en-PH', {
        year: 'numeric', month: 'long', day: 'numeric',
    });
}

function printRecord() {
    window.print();
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Purchase Record — ${record.reference_number}`" />

        <!-- Header Actions -->
        <div class="flex items-center justify-between mb-6 print:hidden">
            <div class="flex items-center gap-3">
                <Link :href="route('purchase-records.index')">
                    <Button variant="ghost" size="sm">
                        <ArrowLeft class="h-4 w-4 mr-1" /> Back
                    </Button>
                </Link>
                <div>
                    <h1 class="text-xl font-bold font-mono">{{ record.reference_number }}</h1>
                    <p class="text-xs text-muted-foreground">Recorded by {{ record.user?.name }} on {{ formatDate(record.created_at) }}</p>
                </div>
            </div>
            <div class="flex gap-2">
                <Button variant="outline" size="sm" @click="printRecord">
                    <Printer class="h-4 w-4 mr-1" /> Print
                </Button>
                <Link :href="route('purchase-records.edit', record.id)">
                    <Button variant="outline" size="sm">
                        <Edit class="h-4 w-4 mr-1" /> Edit
                    </Button>
                </Link>
                <Button variant="destructive" size="sm" @click="deleteRecord">
                    <Trash2 class="h-4 w-4 mr-1" /> Delete
                </Button>
            </div>
        </div>

        <!-- Receipt Card -->
        <Card class="max-w-4xl mx-auto print:shadow-none print:border-none">
            <CardContent class="p-8">
                <!-- Receipt Header -->
                <div class="text-center mb-6 pb-6 border-b border-border">
                    <h2 class="text-xl font-bold uppercase tracking-wide">{{ record.supplier_name }}</h2>
                    <p v-if="record.supplier_address" class="text-sm text-muted-foreground mt-1">{{ record.supplier_address }}</p>
                    <p v-if="record.supplier_tin" class="text-xs text-muted-foreground mt-0.5">VAT Reg. TIN: {{ record.supplier_tin }}</p>
                </div>

                <!-- Meta Info -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6 text-sm">
                    <div>
                        <p class="text-xs text-muted-foreground uppercase font-semibold">Date</p>
                        <p class="font-medium mt-0.5">{{ formatDate(record.date) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground uppercase font-semibold">Receipt No.</p>
                        <p class="font-mono font-medium mt-0.5">{{ record.receipt_number ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground uppercase font-semibold">Payment</p>
                        <span
                            :class="record.payment_type === 'cash' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'"
                            class="inline-block px-2 py-0.5 rounded-full text-xs font-medium capitalize mt-0.5"
                        >
                            {{ record.payment_type }}
                        </span>
                    </div>
                    <div v-if="record.buyer_name">
                        <p class="text-xs text-muted-foreground uppercase font-semibold">Customer</p>
                        <p class="font-medium mt-0.5">{{ record.buyer_name }}</p>
                    </div>
                </div>

                <!-- Line Items Table -->
                <div class="overflow-x-auto mb-6">
                    <table class="w-full text-sm border border-border">
                        <thead class="bg-muted/60">
                            <tr>
                                <th class="px-3 py-2 text-left font-semibold text-muted-foreground border-b border-border">QTY</th>
                                <th class="px-3 py-2 text-left font-semibold text-muted-foreground border-b border-border">UNIT</th>
                                <th class="px-3 py-2 text-left font-semibold text-muted-foreground border-b border-border">DESCRIPTION</th>
                                <th class="px-3 py-2 text-right font-semibold text-muted-foreground border-b border-border">UNIT PRICE</th>
                                <th class="px-3 py-2 text-right font-semibold text-muted-foreground border-b border-border">AMOUNT</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr v-for="item in record.items" :key="item.id" class="hover:bg-muted/20">
                                <td class="px-3 py-2">{{ item.qty }}</td>
                                <td class="px-3 py-2 text-muted-foreground">{{ item.unit ?? '—' }}</td>
                                <td class="px-3 py-2 font-medium">{{ item.description }}</td>
                                <td class="px-3 py-2 text-right">{{ formatCurrency(item.unit_price) }}</td>
                                <td class="px-3 py-2 text-right font-semibold">{{ formatCurrency(item.amount) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Totals -->
                <div class="flex justify-end">
                    <div class="w-full max-w-xs space-y-2 text-sm">
                        <div v-if="parseFloat(record.vatable_sales) > 0" class="flex justify-between">
                            <span class="text-muted-foreground">VATable Sales</span>
                            <span>{{ formatCurrency(record.vatable_sales) }}</span>
                        </div>
                        <div v-if="parseFloat(record.vat_amount) > 0" class="flex justify-between">
                            <span class="text-muted-foreground">Add: VAT</span>
                            <span>{{ formatCurrency(record.vat_amount) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Total Due</span>
                            <span class="font-medium">{{ formatCurrency(record.total_due) }}</span>
                        </div>
                        <div v-if="parseFloat(record.withholding_tax) > 0" class="flex justify-between text-red-600">
                            <span>Less: Withholding Tax</span>
                            <span>- {{ formatCurrency(record.withholding_tax) }}</span>
                        </div>
                        <div class="border-t border-border pt-2 flex justify-between font-bold text-base">
                            <span>TOTAL AMOUNT DUE</span>
                            <span class="text-indigo-600 dark:text-indigo-400">{{ formatCurrency(record.total_amount_due) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div v-if="record.notes" class="mt-6 pt-6 border-t border-border">
                    <p class="text-xs font-semibold text-muted-foreground uppercase mb-1">Notes</p>
                    <p class="text-sm whitespace-pre-wrap">{{ record.notes }}</p>
                </div>
            </CardContent>
        </Card>
    </AppLayout>
</template>

<style>
@media print {
    .print\:hidden { display: none !important; }
    body { background: white !important; }
}
</style>
