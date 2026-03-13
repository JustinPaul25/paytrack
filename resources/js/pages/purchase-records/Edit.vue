<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
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
import { SearchSelect } from '@/components/ui/select';
import { unitOptionsForRow } from '@/lib/productUnits';
import { PlusCircle, Trash2 } from 'lucide-vue-next';

interface PurchaseRecordItem {
    id?: number;
    qty: string | number;
    unit: string | null;
    description: string;
    unit_price: string | number;
    amount: string | number;
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
    withholding_tax: string;
    notes: string | null;
    items: PurchaseRecordItem[];
}

const props = defineProps<{ record: PurchaseRecord }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Purchase Records', href: '/purchase-records' },
    { title: props.record.reference_number, href: `/purchase-records/${props.record.id}` },
    { title: 'Edit', href: `/purchase-records/${props.record.id}/edit` },
];

const form = useForm({
    supplier_name:    props.record.supplier_name,
    supplier_tin:     props.record.supplier_tin ?? '',
    supplier_address: props.record.supplier_address ?? '',
    receipt_number:   props.record.receipt_number ?? '',
    date:             props.record.date,
    payment_type:     props.record.payment_type,
    buyer_name:       props.record.buyer_name ?? '',
    vatable_sales:    props.record.vatable_sales,
    vat_amount:       props.record.vat_amount,
    withholding_tax:  props.record.withholding_tax,
    notes:            props.record.notes ?? '',
    items: props.record.items.map(i => ({
        qty: i.qty,
        unit: i.unit ?? '',
        description: i.description,
        unit_price: i.unit_price,
        amount: i.amount,
    })) as { qty: string | number; unit: string; description: string; unit_price: string | number; amount: string | number }[],
});

function addItem() {
    form.items.push({ qty: '', unit: '', description: '', unit_price: '', amount: '' });
}

function removeItem(index: number) {
    if (form.items.length > 1) form.items.splice(index, 1);
}

function recalcAmount(index: number) {
    const item  = form.items[index];
    const qty   = parseFloat(String(item.qty)) || 0;
    const price = parseFloat(String(item.unit_price)) || 0;
    item.amount = parseFloat((qty * price).toFixed(2));
}

const itemsTotal = computed(() =>
    form.items.reduce((sum, item) => sum + (parseFloat(String(item.amount)) || 0), 0)
);

const totalAmountDue = computed(() => {
    const tax = parseFloat(String(form.withholding_tax)) || 0;
    return Math.max(0, itemsTotal.value - tax);
});

function submit() {
    form.post(route('purchase-records.update', props.record.id), {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                toast: true, position: 'top-end', icon: 'success',
                title: 'Record updated!',
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
        <Head :title="`Edit — ${record.reference_number}`" />

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold">Edit Purchase Record</h1>
                <p class="text-sm text-muted-foreground font-mono mt-0.5">{{ record.reference_number }}</p>
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
                            <Label>Supplier Name <span class="text-red-500">*</span></Label>
                            <input
                                v-model="form.supplier_name"
                                type="text"
                                class="w-full rounded-md border border-input bg-transparent px-3 py-2 mt-1 text-sm text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none"
                                required
                            />
                            <InputError :message="form.errors.supplier_name" />
                        </div>
                        <div>
                            <Label>Supplier TIN</Label>
                            <input
                                v-model="form.supplier_tin"
                                type="text"
                                class="w-full rounded-md border border-input bg-transparent px-3 py-2 mt-1 text-sm text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none"
                            />
                            <InputError :message="form.errors.supplier_tin" />
                        </div>
                    </div>

                    <div>
                        <Label>Supplier Address</Label>
                        <input
                            v-model="form.supplier_address"
                            type="text"
                            class="w-full rounded-md border border-input bg-transparent px-3 py-2 mt-1 text-sm text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none"
                        />
                        <InputError :message="form.errors.supplier_address" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <Label>Receipt / Invoice No.</Label>
                            <input
                                v-model="form.receipt_number"
                                type="text"
                                class="w-full rounded-md border border-input bg-transparent px-3 py-2 mt-1 text-sm text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none"
                            />
                            <InputError :message="form.errors.receipt_number" />
                        </div>
                        <div>
                            <Label>Date <span class="text-red-500">*</span></Label>
                            <input
                                v-model="form.date"
                                type="date"
                                class="w-full rounded-md border border-input bg-transparent px-3 py-2 mt-1 text-sm text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none"
                                required
                            />
                            <InputError :message="form.errors.date" />
                        </div>
                        <div>
                            <Label>Payment Type <span class="text-red-500">*</span></Label>
                            <select
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
                        <Label>Buyer / Customer Name</Label>
                        <input
                            v-model="form.buyer_name"
                            type="text"
                            class="w-full rounded-md border border-input bg-transparent px-3 py-2 mt-1 text-sm text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none"
                        />
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
                                        <input v-model="item.qty" type="number" min="0.01" step="0.01" @input="recalcAmount(i)" class="w-full rounded border border-input bg-transparent px-2 py-1 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" />
                                        <InputError :message="(form.errors as any)[`items.${i}.qty`]" class="text-[10px]" />
                                    </td>
                                    <td class="px-3 py-2">
                                        <SearchSelect
                                            v-model="item.unit"
                                            :options="unitOptionsForRow(item.unit)"
                                            placeholder="Unit"
                                            search-placeholder="Search unit..."
                                            class="h-8 min-w-0 text-sm"
                                        />
                                    </td>
                                    <td class="px-3 py-2">
                                        <input v-model="item.description" type="text" class="w-full rounded border border-input bg-transparent px-2 py-1 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" required />
                                        <InputError :message="(form.errors as any)[`items.${i}.description`]" class="text-[10px]" />
                                    </td>
                                    <td class="px-3 py-2">
                                        <input v-model="item.unit_price" type="number" min="0" step="0.01" @input="recalcAmount(i)" class="w-full rounded border border-input bg-transparent px-2 py-1 text-sm text-right focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" />
                                        <InputError :message="(form.errors as any)[`items.${i}.unit_price`]" class="text-[10px]" />
                                    </td>
                                    <td class="px-3 py-2">
                                        <input v-model="item.amount" type="number" min="0" step="0.01" class="w-full rounded border border-input bg-transparent px-2 py-1 text-sm text-right focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" />
                                        <InputError :message="(form.errors as any)[`items.${i}.amount`]" class="text-[10px]" />
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <button type="button" @click="removeItem(i)" :disabled="form.items.length === 1" class="text-red-500 disabled:opacity-30 hover:text-red-700">
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
                <CardHeader><CardTitle>Tax & Totals</CardTitle></CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-4">
                            <div>
                                <Label>VATable Sales Amount</Label>
                                <input v-model="form.vatable_sales" type="number" min="0" step="0.01" class="w-full rounded-md border border-input bg-transparent px-3 py-2 mt-1 text-sm text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none" />
                                <InputError :message="form.errors.vatable_sales" />
                            </div>
                            <div>
                                <Label>VAT Amount</Label>
                                <input v-model="form.vat_amount" type="number" min="0" step="0.01" class="w-full rounded-md border border-input bg-transparent px-3 py-2 mt-1 text-sm text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none" />
                                <InputError :message="form.errors.vat_amount" />
                            </div>
                            <div>
                                <Label>Less: Withholding Tax</Label>
                                <input v-model="form.withholding_tax" type="number" min="0" step="0.01" class="w-full rounded-md border border-input bg-transparent px-3 py-2 mt-1 text-sm text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none" />
                                <InputError :message="form.errors.withholding_tax" />
                            </div>
                        </div>

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
                            <div class="border-t border-border pt-3 flex justify-between font-bold">
                                <span>Total Amount Due</span>
                                <span class="text-lg text-indigo-600 dark:text-indigo-400">{{ formatCurrency(totalAmountDue) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5">
                        <Label>Notes</Label>
                        <textarea
                            v-model="form.notes"
                            rows="3"
                            class="w-full rounded-md border border-input bg-transparent px-3 py-2 mt-1 text-sm text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none resize-none"
                        />
                        <InputError :message="form.errors.notes" />
                    </div>
                </CardContent>
            </Card>

            <!-- Actions -->
            <Card>
                <CardFooter class="flex gap-2 justify-end">
                    <Link :href="route('purchase-records.show', record.id)">
                        <Button type="button" variant="ghost">Cancel</Button>
                    </Link>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Update Record' }}
                    </Button>
                </CardFooter>
            </Card>
        </form>
    </AppLayout>
</template>
