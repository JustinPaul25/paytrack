<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import { Button } from '@/components/ui/button';

interface InvoiceItemLite {
    id: number;
    product_id: number;
    product_name: string;
    quantity: number;
    price: number;
    total: number;
}

const props = defineProps<{
    invoice: {
        id: number;
        reference_number: string;
        customer?: { name?: string; email?: string; phone?: string };
        invoice_items: InvoiceItemLite[];
    };
}>();

const description = ref('');
const mediaLink = ref('');
const selections = ref<Record<number, number>>({}); // invoice_item_id -> qty

function formatCurrency(amount: number) {
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', minimumFractionDigits: 2 }).format(amount);
}

function clampQuantity(requested: number, maxQty: number): number {
    const n = Number.isFinite(requested) ? Math.floor(requested) : 0;
    if (n < 0) return 0;
    if (n > maxQty) return maxQty;
    return n;
}

function setSelection(invoiceItemId: number, requested: number, maxQty: number) {
    selections.value[invoiceItemId] = clampQuantity(requested, maxQty);
}

const canSubmit = computed(() => {
    // At least one positive qty and none exceed their max
    const pairs = Object.entries(selections.value);
    if (pairs.length === 0) return false;
    let anyPositive = false;
    for (const [id, qty] of pairs) {
        const item = props.invoice.invoice_items.find(i => i.id === Number(id));
        if (!item) continue;
        const clamped = clampQuantity(qty as number, item.quantity);
        if (clamped !== qty) return false;
        if (clamped > 0) anyPositive = true;
    }
    return anyPositive;
});

const totalRefund = computed(() => {
    return props.invoice.invoice_items.reduce((sum, item) => {
        const qty = clampQuantity(selections.value[item.id] ?? 0, item.quantity);
        return sum + (qty * item.price);
    }, 0);
});

function submit() {
    const items = Object.entries(selections.value)
        .filter(([_, qty]) => (qty as number) > 0)
        .map(([invoice_item_id, qty]) => {
            const item = props.invoice.invoice_items.find(i => i.id === Number(invoice_item_id))!;
            return {
                invoice_item_id: Number(invoice_item_id),
                product_id: item.product_id,
                quantity: Math.min(Number(qty), item.quantity),
            };
        });

    router.post(`/invoices/${props.invoice.id}/refund-request`, {
        description: description.value || undefined,
        media_link: mediaLink.value || undefined,
        items,
    });
}
</script>

<template>
    <AppLayout>
        <Head :title="`Request Refund - ${props.invoice.reference_number}`" />

        <div class="flex items-center justify-between my-6">
            <h1 class="text-2xl font-bold">Request Refund</h1>
            <div class="flex gap-2">
                <Link :href="route('invoices.show', props.invoice.id)">
                    <Button variant="ghost">Back to Invoice</Button>
                </Link>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Invoice</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <div class="text-sm text-gray-500">Invoice number</div>
                                <div class="font-semibold">{{ props.invoice.reference_number }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Products to refund</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <table class="min-w-full divide-y divide-border">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left">Product</th>
                                    <th class="px-4 py-2 text-left">Qty Purchased</th>
                                    <th class="px-4 py-2 text-left">Price</th>
                                    <th class="px-4 py-2 text-left">Refund Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in props.invoice.invoice_items" :key="item.id" class="hover:bg-muted">
                                    <td class="px-4 py-2">
                                        <div class="font-medium">{{ item.product_name }}</div>
                                    </td>
                                    <td class="px-4 py-2">{{ item.quantity }}</td>
                                    <td class="px-4 py-2">{{ formatCurrency(item.price) }}</td>
                                    <td class="px-4 py-2">
                                        <input
                                            type="number"
                                            min="0"
                                            :max="item.quantity"
                                            step="1"
                                            inputmode="numeric"
                                            pattern="[0-9]*"
                                            class="w-24 rounded-md border border-input bg-background px-3 py-2 text-sm"
                                            :value="selections[item.id] ?? 0"
                                            @input="(e:any) => setSelection(item.id, Number(e?.target?.value ?? 0), item.quantity)"
                                            @blur="(e:any) => setSelection(item.id, Number(e?.target?.value ?? 0), item.quantity)"
                                            @wheel.prevent
                                        />
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="border-t">
                                    <td class="px-4 py-2 text-right font-medium" colspan="3">Total refund amount:</td>
                                    <td class="px-4 py-2 font-semibold">{{ formatCurrency(totalRefund) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Additional details</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Description</label>
                                <textarea
                                    v-model="description"
                                    rows="4"
                                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    placeholder="Describe the reason for the refund"
                                ></textarea>
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Video/Image link</label>
                                <input
                                    v-model="mediaLink"
                                    type="url"
                                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    placeholder="https://..."
                                />
                            </div>
                            <div class="pt-2">
                                <Button :disabled="!canSubmit" @click="submit">Submit Request</Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Instructions</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ul class="list-disc pl-5 text-sm text-gray-600 space-y-2">
                            <li>Select the items and quantities to refund.</li>
                            <li>Provide a brief description of the issue.</li>
                            <li>Paste a link to photos or a short video if available.</li>
                        </ul>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>


