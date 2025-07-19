<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Select } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardFooter from '@/components/ui/card/CardFooter.vue';
import Label from '@/components/ui/label/Label.vue';
import Swal from 'sweetalert2';
import { type BreadcrumbItem } from '@/types';
import InputError from '@/components/InputError.vue';

interface Product {
    id: number;
    name: string;
    selling_price: number;
}

interface Customer {
    id: number;
    name: string;
    company_name?: string;
}

interface InvoiceItem {
    id: number;
    product_id: number;
    quantity: number;
    price: number;
    total: number;
    product: Product;
}

interface Invoice {
    id: number;
    customer: Customer;
    invoice_items: InvoiceItem[];
}

interface RefundType {
    value: string;
    label: string;
}

interface RefundMethod {
    value: string;
    label: string;
}

interface Refund {
    id: number;
    refund_number: string;
    invoice: Invoice;
    product: Product;
    quantity_refunded: number;
    refund_amount: number;
    status: string;
    refund_type: string;
    refund_method: string;
    reason?: string;
    notes?: string;
    reference_number?: string;
}

const props = defineProps<{ 
    refund: Refund;
    refundTypes: RefundType[];
    refundMethods: RefundMethod[];
}>();

const form = useForm({
    quantity_refunded: props.refund.quantity_refunded,
    refund_amount: props.refund.refund_amount,
    refund_type: props.refund.refund_type,
    refund_method: props.refund.refund_method,
    reason: props.refund.reason || '',
    notes: props.refund.notes || '',
    reference_number: props.refund.reference_number || '',
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Refunds',
        href: '/refunds',
    },
    {
        title: `Refund ${props.refund.refund_number}`,
        href: `/refunds/${props.refund.id}`,
    },
    {
        title: 'Edit Refund',
        href: `/refunds/${props.refund.id}/edit`,
    }
];

// Computed values
const maxQuantity = computed(() => {
    if (!props.refund.invoice?.invoice_items || !Array.isArray(props.refund.invoice.invoice_items)) {
        return 0;
    }
    const invoiceItem = props.refund.invoice.invoice_items.find(item => item.product_id === props.refund.product.id);
    return invoiceItem?.quantity || 0;
});

// Update refund amount when quantity changes
function onQuantityChange() {
    if (form.quantity_refunded) {
        const itemPrice = props.refund.product.selling_price;
        form.refund_amount = form.quantity_refunded * itemPrice;
        
        // Check if quantity exceeds available
        if (form.quantity_refunded > maxQuantity.value) {
            form.quantity_refunded = maxQuantity.value;
            form.refund_amount = maxQuantity.value * itemPrice;
            
            Swal.fire({
                icon: 'info',
                title: 'Quantity Adjusted',
                text: `Quantity has been adjusted to the maximum available (${maxQuantity.value} units).`,
                confirmButtonText: 'OK',
                toast: true,
                position: 'top-end',
                timer: 3000,
                timerProgressBar: true,
            });
        }
    }
}

// Update refund amount when refund type changes
function onRefundTypeChange() {
    if (form.refund_type === 'full') {
        form.quantity_refunded = maxQuantity.value;
        form.refund_amount = maxQuantity.value * props.refund.product.selling_price;
    } else if (form.refund_type === 'partial') {
        // Keep current quantity and amount
    } else if (form.refund_type === 'exchange') {
        form.quantity_refunded = maxQuantity.value;
        form.refund_amount = 0; // No refund amount for exchange
    }
}

function submit() {
    // Validate quantity
    if (form.quantity_refunded > maxQuantity.value) {
        form.quantity_refunded = maxQuantity.value;
        onQuantityChange();
        
        Swal.fire({
            icon: 'warning',
            title: 'Quantity Auto-Adjusted',
            text: `Quantity was automatically adjusted to ${maxQuantity.value} units due to limitations.`,
            confirmButtonText: 'OK'
        });
    }
    
    form.put(route('refunds.update', props.refund.id), {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Refund updated successfully',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
            });
        },
    });
}

function formatCurrency(amount: number) {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2
    }).format(amount);
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Edit Refund" />
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">Edit Refund {{ props.refund.refund_number }}</h1>
        </div>
        
        <form @submit.prevent="submit()" class="space-y-6">
            <!-- Refund Details -->
            <Card>
                <CardHeader>
                    <CardTitle>Refund Details</CardTitle>
                </CardHeader>
                <CardContent class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <Label for="refund_type">Refund Type</Label>
                            <Select
                                v-model="form.refund_type"
                                :options="refundTypes"
                                placeholder="Select refund type"
                                class="mt-1"
                                @update:model-value="onRefundTypeChange"
                                required
                            />
                            <InputError :message="form.errors.refund_type" />
                        </div>
                        
                        <div>
                            <Label for="refund_method">Refund Method</Label>
                            <Select
                                v-model="form.refund_method"
                                :options="refundMethods"
                                placeholder="Select refund method"
                                class="mt-1"
                                required
                            />
                            <InputError :message="form.errors.refund_method" />
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <Label for="quantity_refunded">Quantity to Refund</Label>
                            <input
                                v-model.number="form.quantity_refunded"
                                type="number"
                                id="quantity_refunded"
                                min="1"
                                :max="maxQuantity"
                                class="w-full rounded-md border border-input bg-transparent px-3 py-2 mt-1 text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none"
                                @input="onQuantityChange"
                                required
                            />
                            <div class="text-xs text-gray-500 mt-1">
                                Maximum available: {{ maxQuantity }}
                            </div>
                            <InputError :message="form.errors.quantity_refunded" />
                        </div>
                        
                        <div>
                            <Label for="refund_amount">Refund Amount</Label>
                            <input
                                v-model.number="form.refund_amount"
                                type="number"
                                id="refund_amount"
                                min="0"
                                step="0.01"
                                class="w-full rounded-md border border-input bg-transparent px-3 py-2 mt-1 text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none"
                                :disabled="form.refund_type === 'exchange'"
                                required
                            />
                            <InputError :message="form.errors.refund_amount" />
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <Label for="reference_number">Reference Number</Label>
                            <input
                                v-model="form.reference_number"
                                type="text"
                                id="reference_number"
                                class="w-full rounded-md border border-input bg-transparent px-3 py-2 mt-1 text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none"
                                placeholder="Enter reference number"
                            />
                            <InputError :message="form.errors.reference_number" />
                        </div>
                        
                        <div>
                            <Label for="reason">Reason</Label>
                            <input
                                v-model="form.reason"
                                type="text"
                                id="reason"
                                class="w-full rounded-md border border-input bg-transparent px-3 py-2 mt-1 text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none"
                                placeholder="Enter refund reason"
                                required
                            />
                            <InputError :message="form.errors.reason" />
                        </div>
                    </div>
                    
                    <div>
                        <Label for="notes">Notes</Label>
                        <textarea 
                            id="notes" 
                            v-model="form.notes" 
                            class="w-full rounded-md border border-input bg-transparent px-3 py-2 mt-1 text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none" 
                            rows="3" 
                            placeholder="Additional notes..."
                        />
                        <InputError :message="form.errors.notes" />
                    </div>
                </CardContent>
            </Card>

            <!-- Original Item Details -->
            <Card>
                <CardHeader>
                    <CardTitle>Original Item Details</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-500">Product</label>
                            <p class="text-lg font-semibold">{{ props.refund.product.name }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Original Quantity</label>
                            <p class="text-lg font-semibold">{{ maxQuantity }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Original Price</label>
                            <p class="text-lg font-semibold">{{ formatCurrency(props.refund.product.selling_price) }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Form Actions -->
            <Card>
                <CardFooter class="flex gap-2 justify-end">
                    <Button type="submit" variant="default">Update Refund</Button>
                    <Link :href="route('refunds.show', props.refund.id)">
                        <Button type="button" variant="ghost">Cancel</Button>
                    </Link>
                </CardFooter>
            </Card>
        </form>
    </AppLayout>
</template> 