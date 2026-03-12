<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardFooter from '@/components/ui/card/CardFooter.vue';
import Label from '@/components/ui/label/Label.vue';
import InputError from '@/components/InputError.vue';
import Swal from 'sweetalert2';
import { type BreadcrumbItem } from '@/types';
import { PlusCircle, Trash2 } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Purchase Records', href: '/purchase-records' },
    { title: 'Add Record', href: '/purchase-records/create' },
];

const form = useForm({
    supplier_name:    '',
    supplier_tin:     '',
    supplier_address: '',
    receipt_number:   '',
    date:             new Date().toISOString().split('T')[0],
    payment_type:     'cash',
    buyer_name:       '',
    vatable_sales:    '' as string | number,
    vat_amount:       '' as string | number,
    withholding_tax:  '' as string | number,
    notes:            '',
    items: [
        { qty: '', unit: '', description: '', unit_price: '', amount: '' },
    ] as { qty: string | number; unit: string; description: string; unit_price: string | number; amount: string | number }[],
});

function addItem() {
    form.items.push({ qty: '', unit: '', description: '', unit_price: '', amount: '' });
}

function removeItem(index: number) {
    if (form.items.length > 1) form.items.splice(index, 1);
}

function recalcAmount(index: number) {
    const item = form.items[index];
    const qty  = parseFloat(String(item.qty)) || 0;
    const price = parseFloat(String(item.unit_price)) || 0;
    item.amount = parseFloat((qty * price).toFixed(2));
}

const VAT_RATE = 0.12;
const WITHHOLDING_TAX_RATE = 0.01;

const itemsTotal = computed(() =>
    form.items.reduce((sum, item) => sum + (parseFloat(String(item.amount)) || 0), 0)
);

const totalAmountDue = computed(() => {
    const tax = parseFloat(String(form.withholding_tax)) || 0;
    return Math.max(0, itemsTotal.value - tax);
});

const calculatedVatableSales = computed(() => parseFloat((itemsTotal.value || 0).toFixed(2)));
const calculatedVatAmount = computed(() => parseFloat((calculatedVatableSales.value * VAT_RATE).toFixed(2)));
const calculatedWithholdingTax = computed(() => parseFloat((calculatedVatableSales.value * WITHHOLDING_TAX_RATE).toFixed(2)));

function isEmpty(val: string | number): boolean {
    return val === '' || val === undefined;
}
function isEmptyOrZero(val: string | number): boolean {
    if (isEmpty(val)) return true;
    const n = parseFloat(String(val));
    return !Number.isFinite(n) || n === 0;
}

watch(itemsTotal, (total) => {
    if (total <= 0) return;
    if (isEmptyOrZero(form.vatable_sales)) {
        form.vatable_sales = calculatedVatableSales.value;
    }
    if (isEmptyOrZero(form.vat_amount)) {
        form.vat_amount = calculatedVatAmount.value;
    }
    if (isEmpty(form.withholding_tax)) {
        form.withholding_tax = calculatedWithholdingTax.value;
    }
}, { immediate: true });

function effectiveNumeric(val: string | number): number {
    const n = parseFloat(String(val));
    return Number.isFinite(n) ? n : 0;
}

function submit() {
    form.vatable_sales = form.vatable_sales !== '' && form.vatable_sales !== undefined
        ? effectiveNumeric(form.vatable_sales)
        : calculatedVatableSales.value;
    form.vat_amount = form.vat_amount !== '' && form.vat_amount !== undefined
        ? effectiveNumeric(form.vat_amount)
        : calculatedVatAmount.value;
    form.withholding_tax = form.withholding_tax !== '' && form.withholding_tax !== undefined
        ? effectiveNumeric(form.withholding_tax)
        : calculatedWithholdingTax.value;

    form.post(route('purchase-records.store'), {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                toast: true, position: 'top-end', icon: 'success',
                title: 'Purchase record saved!',
                showConfirmButton: false, timer: 3000, timerProgressBar: true,
            });
        },
    });
}

function formatCurrency(val: number) {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency', currency: 'PHP', minimumFractionDigits: 2,
    }).format(val);
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Add Purchase Record" />

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold">Add Purchase Record</h1>
                <p class="text-sm text-muted-foreground mt-1">Record a supplier receipt or old purchase document.</p>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Supplier & Receipt Info -->
            <Card>
                <CardHeader>
                    <CardTitle>Supplier & Receipt Information</CardTitle>
                </CardHeader>
                <CardContent class="space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <Label for="supplier_name">Supplier Name <span class="text-red-500">*</span></Label>
                            <input
                                id="supplier_name"
                                v-model="form.supplier_name"
                                type="text"
                                placeholder="e.g. Meraki Consumer Goods Trading"
                                class="w-full rounded-md border border-input bg-transparent px-3 py-2 mt-1 text-sm text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none"
                                required
                            />
                            <InputError :message="form.errors.supplier_name" />
                        </div>
                        <div>
                            <Label for="supplier_tin">Supplier TIN</Label>
                            <input
                                id="supplier_tin"
                                v-model="form.supplier_tin"
                                type="text"
                                placeholder="e.g. 721-995-223-00000"
                                class="w-full rounded-md border border-input bg-transparent px-3 py-2 mt-1 text-sm text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none"
                            />
                            <InputError :message="form.errors.supplier_tin" />
                        </div>
                    </div>

                    <div>
                        <Label for="supplier_address">Supplier Address</Label>
                        <input
                            id="supplier_address"
                            v-model="form.supplier_address"
                            type="text"
                            placeholder="e.g. MCM Realty Bldg., Prk. Alogbati Gredu, 8105 Panabo City"
                            class="w-full rounded-md border border-input bg-transparent px-3 py-2 mt-1 text-sm text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none"
                        />
                        <InputError :message="form.errors.supplier_address" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <Label for="receipt_number">Receipt / Invoice No.</Label>
                            <input
                                id="receipt_number"
                                v-model="form.receipt_number"
                                type="text"
                                placeholder="e.g. 000475"
                                class="w-full rounded-md border border-input bg-transparent px-3 py-2 mt-1 text-sm text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none"
                            />
                            <InputError :message="form.errors.receipt_number" />
                        </div>
                        <div>
                            <Label for="date">Date <span class="text-red-500">*</span></Label>
                            <input
                                id="date"
                                v-model="form.date"
                                type="date"
                                class="w-full rounded-md border border-input bg-transparent px-3 py-2 mt-1 text-sm text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none"
                                required
                            />
                            <InputError :message="form.errors.date" />
                        </div>
                        <div>
                            <Label for="payment_type">Payment Type <span class="text-red-500">*</span></Label>
                            <select
                                id="payment_type"
                                v-model="form.payment_type"
                                class="w-full rounded-md border border-input bg-transparent px-3 py-2 mt-1 text-sm text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none"
                                required
                            >
                                <option value="cash">Cash</option>
                                <option value="credit">Credit</option>
                            </select>
                            <InputError :message="form.errors.payment_type" />
                        </div>
                    </div>

                    <div>
                        <Label for="buyer_name">Buyer / Customer Name</Label>
                        <input
                            id="buyer_name"
                            v-model="form.buyer_name"
                            type="text"
                            placeholder="e.g. Algist NHS"
                            class="w-full rounded-md border border-input bg-transparent px-3 py-2 mt-1 text-sm text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none"
                        />
                        <div class="text-[11px] text-muted-foreground mt-1">Name of buyer as written on the receipt.</div>
                        <InputError :message="form.errors.buyer_name" />
                    </div>
                </CardContent>
            </Card>

            <!-- Line Items -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <CardTitle>Line Items</CardTitle>
                        <Button type="button" variant="outline" size="sm" @click="addItem">
                            <PlusCircle class="h-4 w-4 mr-1" /> Add Item
                        </Button>
                    </div>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-muted/50 border-b border-border">
                                <tr>
                                    <th class="px-4 py-2 text-left font-semibold text-muted-foreground w-20">Qty</th>
                                    <th class="px-4 py-2 text-left font-semibold text-muted-foreground w-24">Unit</th>
                                    <th class="px-4 py-2 text-left font-semibold text-muted-foreground">Description</th>
                                    <th class="px-4 py-2 text-right font-semibold text-muted-foreground w-32">Unit Price</th>
                                    <th class="px-4 py-2 text-right font-semibold text-muted-foreground w-32">Amount</th>
                                    <th class="px-4 py-2 w-10"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <tr v-for="(item, i) in form.items" :key="i">
                                    <td class="px-3 py-2">
                                        <input
                                            v-model="item.qty"
                                            type="number"
                                            min="0.01"
                                            step="0.01"
                                            @input="recalcAmount(i)"
                                            class="w-full rounded border border-input bg-transparent px-2 py-1 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                            placeholder="10"
                                        />
                                        <InputError :message="(form.errors as any)[`items.${i}.qty`]" class="text-[10px]" />
                                    </td>
                                    <td class="px-3 py-2">
                                        <input
                                            v-model="item.unit"
                                            type="text"
                                            class="w-full rounded border border-input bg-transparent px-2 py-1 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                            placeholder="Sachet"
                                        />
                                    </td>
                                    <td class="px-3 py-2">
                                        <input
                                            v-model="item.description"
                                            type="text"
                                            class="w-full rounded border border-input bg-transparent px-2 py-1 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                            placeholder="e.g. Detergent Powder"
                                            required
                                        />
                                        <InputError :message="(form.errors as any)[`items.${i}.description`]" class="text-[10px]" />
                                    </td>
                                    <td class="px-3 py-2">
                                        <input
                                            v-model="item.unit_price"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            @input="recalcAmount(i)"
                                            class="w-full rounded border border-input bg-transparent px-2 py-1 text-sm text-right focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                            placeholder="348.00"
                                        />
                                        <InputError :message="(form.errors as any)[`items.${i}.unit_price`]" class="text-[10px]" />
                                    </td>
                                    <td class="px-3 py-2">
                                        <input
                                            v-model="item.amount"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            class="w-full rounded border border-input bg-transparent px-2 py-1 text-sm text-right focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                            placeholder="3480.00"
                                        />
                                        <InputError :message="(form.errors as any)[`items.${i}.amount`]" class="text-[10px]" />
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <button
                                            type="button"
                                            @click="removeItem(i)"
                                            :disabled="form.items.length === 1"
                                            class="text-red-500 disabled:opacity-30 hover:text-red-700"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-muted/30 border-t border-border">
                                <tr>
                                    <td colspan="4" class="px-4 py-2 text-right text-sm font-semibold text-muted-foreground">Items Total</td>
                                    <td class="px-3 py-2 text-right font-semibold">{{ formatCurrency(itemsTotal) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <InputError :message="form.errors.items" class="px-4 pb-2" />
                </CardContent>
            </Card>

            <!-- Tax & Totals -->
            <Card>
                <CardHeader>
                    <CardTitle>Tax & Totals</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-4">
                            <div>
                                <Label for="vatable_sales">VATable Sales Amount</Label>
                                <input
                                    id="vatable_sales"
                                    v-model="form.vatable_sales"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="w-full rounded-md border border-input bg-transparent px-3 py-2 mt-1 text-sm text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none"
                                    placeholder="9425.00"
                                />
                                <InputError :message="form.errors.vatable_sales" />
                            </div>
                            <div>
                                <Label for="vat_amount">VAT Amount</Label>
                                <input
                                    id="vat_amount"
                                    v-model="form.vat_amount"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="w-full rounded-md border border-input bg-transparent px-3 py-2 mt-1 text-sm text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none"
                                    placeholder="0.00"
                                />
                                <InputError :message="form.errors.vat_amount" />
                            </div>
                            <div>
                                <Label for="withholding_tax">Less: Withholding Tax</Label>
                                <input
                                    id="withholding_tax"
                                    v-model="form.withholding_tax"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="w-full rounded-md border border-input bg-transparent px-3 py-2 mt-1 text-sm text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none"
                                    placeholder="505.45"
                                />
                                <InputError :message="form.errors.withholding_tax" />
                            </div>
                        </div>

                        <!-- Summary Box -->
                        <div class="rounded-lg border border-border bg-muted/30 p-5 space-y-3 self-start">
                            <h3 class="font-semibold text-sm text-muted-foreground uppercase tracking-wide">Summary</h3>
                            <div class="flex justify-between text-sm">
                                <span class="text-muted-foreground">Items Subtotal</span>
                                <span class="font-medium">{{ formatCurrency(itemsTotal) }}</span>
                            </div>
                            <div class="flex justify-between text-sm" v-if="parseFloat(String(form.withholding_tax)) > 0">
                                <span class="text-muted-foreground">Less: Withholding Tax</span>
                                <span class="font-medium text-red-600">- {{ formatCurrency(parseFloat(String(form.withholding_tax)) || 0) }}</span>
                            </div>
                            <div class="border-t border-border pt-3 flex justify-between">
                                <span class="font-bold">Total Amount Due</span>
                                <span class="font-bold text-lg text-indigo-600 dark:text-indigo-400">{{ formatCurrency(totalAmountDue) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5">
                        <Label for="notes">Notes</Label>
                        <textarea
                            id="notes"
                            v-model="form.notes"
                            rows="3"
                            placeholder="Any additional notes about this purchase..."
                            class="w-full rounded-md border border-input bg-transparent px-3 py-2 mt-1 text-sm text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none resize-none"
                        />
                        <InputError :message="form.errors.notes" />
                    </div>
                </CardContent>
            </Card>

            <!-- Actions -->
            <Card>
                <CardFooter class="flex gap-2 justify-end">
                    <Link :href="route('purchase-records.index')">
                        <Button type="button" variant="ghost">Cancel</Button>
                    </Link>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Save Record' }}
                    </Button>
                </CardFooter>
            </Card>
        </form>
    </AppLayout>
</template>
