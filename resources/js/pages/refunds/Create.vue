<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed, onBeforeUnmount } from 'vue';
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
}>();

const proofImages = ref<File[]>([]);
const proofImageUrls = ref<string[]>([]);
const selections = ref<Record<number, {
    qty: number;
}>>({});

const form = useForm({
    request_type: 'refund' as 'refund',
    description: '',
    damaged_items_terms: '',
    is_damaged: false,
    items: [] as Array<{
        invoice_item_id: number;
        product_id: number;
        quantity: number;
    }>,
    proof_images: [] as File[],
});

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

function handleImageUpload(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.files) {
        const files = Array.from(target.files);
        // Validate file size (5MB max per image)
        const validFiles = files.filter(file => {
            if (file.size > 5 * 1024 * 1024) {
                alert(`File "${file.name}" is too large. Maximum size is 5MB.`);
                return false;
            }
            return true;
        });
        
        // Create object URLs for preview
        const newUrls = validFiles.map(file => URL.createObjectURL(file));
        
        // Combine with existing images (max 5 total)
        const combinedFiles = [...proofImages.value, ...validFiles].slice(0, 5);
        const combinedUrls = [...proofImageUrls.value, ...newUrls].slice(0, 5);
        
        // Revoke URLs for files that were removed due to the 5-image limit
        const removedCount = (proofImages.value.length + validFiles.length) - 5;
        if (removedCount > 0) {
            const startIndex = proofImageUrls.value.length;
            for (let i = startIndex; i < startIndex + removedCount; i++) {
                if (proofImageUrls.value[i]) {
                    URL.revokeObjectURL(proofImageUrls.value[i]);
                }
            }
        }
        
        proofImages.value = combinedFiles;
        proofImageUrls.value = combinedUrls;
    }
    // Reset the input so the same file can be selected again if needed
    target.value = '';
}

function removeImage(index: number) {
    // Revoke the object URL to free memory
    if (proofImageUrls.value[index]) {
        URL.revokeObjectURL(proofImageUrls.value[index]);
    }
    proofImages.value.splice(index, 1);
    proofImageUrls.value.splice(index, 1);
}

// Cleanup object URLs when component is unmounted
onBeforeUnmount(() => {
    proofImageUrls.value.forEach(url => {
        if (url) {
            URL.revokeObjectURL(url);
        }
    });
});

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
            return {
                invoice_item_id: Number(invoice_item_id),
                product_id: item.product_id,
                quantity: Math.min(selection.qty, item.quantity),
            };
        });
    
    // Update form data directly
    form.request_type = 'refund';
    form.items = items;
    // Set proof_images to the current files array (only set if there are files)
    if (proofImages.value.length > 0) {
        form.proof_images = proofImages.value;
    } else {
        form.proof_images = [];
    }
    
    form.post(`/invoices/${props.invoice.id}/refund-request`, {
        forceFormData: true,
    });
}
</script>

<template>
    <AppLayout>
        <Head :title="`Refund Request - ${props.invoice.reference_number}`" />

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
                        <CardTitle>Products to Refund</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
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
                                                :value="selections[item.id]?.qty ?? 0"
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
                                    Description / Reason *
                                </label>
                                <textarea
                                    v-model="form.description"
                                    rows="4"
                                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    placeholder="Describe the reason for the refund"
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
                                        <img v-if="proofImageUrls[index]" :src="proofImageUrls[index]" alt="Proof image" class="w-full h-32 object-cover rounded-md border" />
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
                                <label class="flex items-center gap-2 mb-3">
                                    <input
                                        type="checkbox"
                                        v-model="form.is_damaged"
                                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    />
                                    <span class="text-sm font-medium text-gray-700">Damaged Items</span>
                                </label>
                                <p class="text-xs text-gray-500 mb-3">Check this box if the items you are returning are damaged. Damaged items will not be restored to inventory stock.</p>
                            </div>

                            <div>
                                <label class="block text-sm text-gray-600 mb-1">
                                    Terms for Damaged Items
                                </label>
                                <textarea
                                    v-model="form.damaged_items_terms"
                                    rows="3"
                                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    placeholder="Any special terms or conditions regarding damaged items (e.g., partial refund for minor damage, etc.)"
                                ></textarea>
                                <p class="text-xs text-gray-500 mt-1">Specify any special terms or expectations regarding damaged items.</p>
                            </div>

                            <div class="pt-2">
                                <Button :disabled="!canSubmit" @click="submit">
                                    Submit Refund Request
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
                                <h4 class="font-semibold mb-2">Proof Images:</h4>
                                <div class="text-gray-600">
                                    <p>Upload clear photos showing:</p>
                                    <ul class="list-disc pl-5 space-y-1 text-gray-600 mt-1">
                                        <li>The item(s) condition</li>
                                        <li>Any damage or defects</li>
                                        <li>Package/receipt if applicable</li>
                                    </ul>
                                    <p class="mt-2">This helps us process your request faster and ensures accurate assessment.</p>
                                </div>
                            </div>

                            <div>
                                <h4 class="font-semibold mb-2">What Happens Next:</h4>
                                <ol class="list-decimal pl-5 space-y-1 text-gray-600">
                                    <li>Submit your request</li>
                                    <li>Admin reviews your request (usually within 1-2 business days)</li>
                                    <li>You'll receive an email notification about the decision</li>
                                    <li>If approved, follow instructions to return items</li>
                                </ol>
                            </div>

                            <div>
                                <h4 class="font-semibold mb-2">Damaged Items:</h4>
                                <div class="text-gray-600">
                                    <p>If items are damaged, please specify your expectations:</p>
                                    <ul class="list-disc pl-5 space-y-1 text-gray-600 mt-1">
                                        <li>Full refund expected</li>
                                        <li>Partial refund acceptable</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
