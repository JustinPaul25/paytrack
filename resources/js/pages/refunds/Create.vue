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

interface Product {
    id: number;
    name: string;
    price: number;
    stock: number;
    sku?: string;
}

const props = defineProps<{
    invoice: {
        id: number;
        reference_number: string;
        customer?: { name?: string; email?: string; phone?: string };
        invoice_items: InvoiceItemLite[];
    };
    availableProducts?: Product[];
}>();

const requestType = ref<'refund' | 'exchange'>('refund');
const description = ref('');
const mediaLink = ref(''); // Keep for backward compatibility
const proofImages = ref<File[]>([]);
const damagedItemsTerms = ref('');
const selections = ref<Record<number, {
    qty: number;
    exchangeProductId?: number;
    exchangeQuantity?: number;
}>>({});

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
    if (!selections.value[invoiceItemId]) {
        selections.value[invoiceItemId] = { qty: 0 };
    }
    selections.value[invoiceItemId].qty = clampQuantity(requested, maxQty);
}

function setExchangeSelection(invoiceItemId: number, productId: number | undefined, quantity: number) {
    if (!selections.value[invoiceItemId]) {
        selections.value[invoiceItemId] = { qty: 0 };
    }
    selections.value[invoiceItemId].exchangeProductId = productId;
    selections.value[invoiceItemId].exchangeQuantity = quantity > 0 ? quantity : undefined;
}

function handleImageUpload(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.files) {
        const files = Array.from(target.files);
        proofImages.value = [...proofImages.value, ...files].slice(0, 5); // Max 5 images
    }
}

function removeImage(index: number) {
    proofImages.value.splice(index, 1);
}

const canSubmit = computed(() => {
    const pairs = Object.entries(selections.value);
    if (pairs.length === 0) return false;
    
    let anyPositive = false;
    for (const [id, selection] of pairs) {
        const item = props.invoice.invoice_items.find(i => i.id === Number(id));
        if (!item) continue;
        
        const clamped = clampQuantity(selection.qty, item.quantity);
        if (clamped !== selection.qty) return false;
        if (clamped > 0) anyPositive = true;
        
        // If exchange, validate exchange product is selected
        if (requestType.value === 'exchange' && clamped > 0) {
            if (!selection.exchangeProductId || !selection.exchangeQuantity || selection.exchangeQuantity <= 0) {
                return false;
            }
        }
    }
    return anyPositive;
});

const totalRefund = computed(() => {
    return props.invoice.invoice_items.reduce((sum, item) => {
        const selection = selections.value[item.id];
        if (!selection || !selection.qty) return sum;
        return sum + (selection.qty * item.price);
    }, 0);
});

function submit() {
    const items = Object.entries(selections.value)
        .filter(([_, selection]) => selection.qty > 0)
        .map(([invoice_item_id, selection]) => {
            const item = props.invoice.invoice_items.find(i => i.id === Number(invoice_item_id))!;
            const payload: any = {
                invoice_item_id: Number(invoice_item_id),
                product_id: item.product_id,
                quantity: Math.min(selection.qty, item.quantity),
            };
            
            if (requestType.value === 'exchange' && selection.exchangeProductId) {
                payload.exchange_product_id = selection.exchangeProductId;
                payload.exchange_quantity = selection.exchangeQuantity || 1;
            }
            
            return payload;
        });
    
    router.post(`/invoices/${props.invoice.id}/refund-request`, {
        request_type: requestType.value,
        description: description.value || undefined,
        media_link: mediaLink.value || undefined,
        damaged_items_terms: damagedItemsTerms.value || undefined,
        items: items,
        proof_images: proofImages.value,
    }, {
        forceFormData: true,
    });
}
</script>

<template>
    <AppLayout>
        <Head :title="`${requestType === 'exchange' ? 'Exchange' : 'Refund'} Request - ${props.invoice.reference_number}`" />

        <div class="flex items-center justify-between my-6">
            <h1 class="text-2xl font-bold">{{ requestType === 'exchange' ? 'Request Exchange' : 'Request Refund' }}</h1>
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
                        <CardTitle>Request Type</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="flex gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="radio"
                                    v-model="requestType"
                                    value="refund"
                                    class="w-4 h-4"
                                />
                                <div>
                                    <div class="font-medium">Refund</div>
                                    <div class="text-sm text-gray-500">Return item(s) for money back</div>
                                </div>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="radio"
                                    v-model="requestType"
                                    value="exchange"
                                    class="w-4 h-4"
                                />
                                <div>
                                    <div class="font-medium">Exchange</div>
                                    <div class="text-sm text-gray-500">Swap item(s) for different product(s)</div>
                                </div>
                            </label>
                        </div>
                    </CardContent>
                </Card>

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
                        <CardTitle>{{ requestType === 'exchange' ? 'Products to Exchange' : 'Products to Refund' }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <table class="min-w-full divide-y divide-border">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-2 text-left">Product</th>
                                        <th class="px-4 py-2 text-left">Qty Purchased</th>
                                        <th class="px-4 py-2 text-left">Price</th>
                                        <th class="px-4 py-2 text-left">{{ requestType === 'exchange' ? 'Exchange Qty' : 'Refund Qty' }}</th>
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
                                                :value="selections[item.id]?.qty ?? 0"
                                                @input="(e:any) => setSelection(item.id, Number(e?.target?.value ?? 0), item.quantity)"
                                                @blur="(e:any) => setSelection(item.id, Number(e?.target?.value ?? 0), item.quantity)"
                                                @wheel.prevent
                                            />
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot v-if="requestType === 'refund'">
                                    <tr class="border-t">
                                        <td class="px-4 py-2 text-right font-medium" colspan="3">Total refund amount:</td>
                                        <td class="px-4 py-2 font-semibold">{{ formatCurrency(totalRefund) }}</td>
                                    </tr>
                                </tfoot>
                            </table>

                            <!-- Exchange Product Selection -->
                            <div v-if="requestType === 'exchange'" class="space-y-4 mt-6 border-t pt-4">
                                <h3 class="font-semibold">Exchange Products</h3>
                                <div v-for="item in props.invoice.invoice_items" :key="`exchange-${item.id}`" class="space-y-2">
                                    <div v-if="(selections[item.id]?.qty ?? 0) > 0" class="p-4 border rounded-md bg-gray-50">
                                        <div class="mb-2">
                                            <span class="font-medium">Exchanging {{ selections[item.id]?.qty }}x {{ item.product_name }}</span>
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm text-gray-600 mb-1">Exchange Product</label>
                                                <select
                                                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                                    :value="selections[item.id]?.exchangeProductId"
                                                    @change="(e:any) => setExchangeSelection(item.id, e.target.value ? Number(e.target.value) : undefined, selections[item.id]?.exchangeQuantity || 1)"
                                                >
                                                    <option value="">Select product...</option>
                                                    <option v-for="product in availableProducts" :key="product.id" :value="product.id">
                                                        {{ product.name }} - {{ formatCurrency(product.price) }} (Stock: {{ product.stock }})
                                                    </option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-sm text-gray-600 mb-1">Exchange Quantity</label>
                                                <input
                                                    type="number"
                                                    min="1"
                                                    :max="availableProducts?.find(p => p.id === selections[item.id]?.exchangeProductId)?.stock || 0"
                                                    step="1"
                                                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                                    :value="selections[item.id]?.exchangeQuantity || 1"
                                                    @input="(e:any) => setExchangeSelection(item.id, selections[item.id]?.exchangeProductId, Number(e.target.value))"
                                                    :disabled="!selections[item.id]?.exchangeProductId"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Additional Details</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">
                                    Description / Reason
                                    <span class="text-gray-400 text-xs">(Required)</span>
                                </label>
                                <textarea
                                    v-model="description"
                                    rows="4"
                                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    :placeholder="requestType === 'exchange' ? 'Describe why you want to exchange this item' : 'Describe the reason for the refund'"
                                    required
                                ></textarea>
                                <p class="text-xs text-gray-500 mt-1">Please provide a clear explanation for your request.</p>
                            </div>

                            <div>
                                <label class="block text-sm text-gray-600 mb-1">
                                    Proof Images
                                    <span class="text-gray-400 text-xs">(Recommended)</span>
                                </label>
                                <input
                                    type="file"
                                    accept="image/jpeg,image/png,image/jpg,image/webp"
                                    multiple
                                    @change="handleImageUpload"
                                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                />
                                <p class="text-xs text-gray-500 mt-1">Upload photos of the item(s) as proof. Maximum 5 images, 5MB each.</p>
                                
                                <div v-if="proofImages.length > 0" class="mt-3 grid grid-cols-2 md:grid-cols-3 gap-2">
                                    <div v-for="(image, index) in proofImages" :key="index" class="relative group">
                                        <img :src="URL.createObjectURL(image)" alt="Proof image" class="w-full h-32 object-cover rounded-md border" />
                                        <button
                                            type="button"
                                            @click="removeImage(index)"
                                            class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition-opacity"
                                        >
                                            ×
                                        </button>
                                        <div class="text-xs text-gray-500 mt-1 truncate">{{ image.name }}</div>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Video/Image Link (Optional)</label>
                                <input
                                    v-model="mediaLink"
                                    type="url"
                                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    placeholder="https://..."
                                />
                                <p class="text-xs text-gray-500 mt-1">Alternative: Paste a link to external photos or video if you prefer.</p>
                            </div>

                            <div>
                                <label class="block text-sm text-gray-600 mb-1">
                                    Terms for Damaged Items
                                </label>
                                <textarea
                                    v-model="damagedItemsTerms"
                                    rows="3"
                                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    placeholder="Any special terms or conditions regarding damaged items (e.g., partial refund for minor damage, etc.)"
                                ></textarea>
                                <p class="text-xs text-gray-500 mt-1">Specify any special terms or expectations regarding damaged items.</p>
                            </div>

                            <div class="pt-2">
                                <Button :disabled="!canSubmit" @click="submit">
                                    Submit {{ requestType === 'exchange' ? 'Exchange' : 'Refund' }} Request
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Instructions & Clarifications</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4 text-sm">
                            <div>
                                <h4 class="font-semibold mb-2">Request Types:</h4>
                                <ul class="list-disc pl-5 space-y-1 text-gray-600">
                                    <li><strong>Refund:</strong> Return the item(s) and receive money back to your original payment method or as store credit.</li>
                                    <li><strong>Exchange:</strong> Swap your item(s) for different product(s) of equal or different value. Price difference will be handled separately.</li>
                                </ul>
                            </div>
                            
                            <div>
                                <h4 class="font-semibold mb-2">Proof Images:</h4>
                                <p class="text-gray-600">Upload clear photos showing:</p>
                                <ul class="list-disc pl-5 space-y-1 text-gray-600 mt-1">
                                    <li>The item(s) condition</li>
                                    <li>Any damage or defects</li>
                                    <li>Package/receipt if applicable</li>
                                </ul>
                                <p class="text-gray-600 mt-2">This helps us process your request faster and ensures accurate assessment.</p>
                            </div>

                            <div>
                                <h4 class="font-semibold mb-2">What Happens Next:</h4>
                                <ol class="list-decimal pl-5 space-y-1 text-gray-600">
                                    <li>Submit your request</li>
                                    <li>Admin reviews your request (usually within 1-2 business days)</li>
                                    <li>You'll receive an email notification about the decision</li>
                                    <li>If approved, follow instructions to return/exchange items</li>
                                </ol>
                            </div>

                            <div>
                                <h4 class="font-semibold mb-2">Damaged Items:</h4>
                                <p class="text-gray-600">If items are damaged, please specify your expectations:
                                    <ul class="list-disc pl-5 space-y-1 text-gray-600 mt-1">
                                        <li>Full refund expected</li>
                                        <li>Partial refund acceptable</li>
                                        <li>Exchange preferred</li>
                                    </ul>
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
