<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Select, SearchSelect } from '@/components/ui/select';
import { Trash2 } from 'lucide-vue-next';
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

interface Customer {
    id: number;
    name: string;
    company_name?: string;
}

interface Product {
    id: number;
    name: string;
    selling_price: number;
    stock: number;
    unit: string;
}

interface InvoiceItem {
    id?: number;
    product_id: number | null;
    quantity: number;
    price: number;
    total: number;
    product?: Product;
}

interface Invoice {
    id: number;
    customer_id: number;
    status: string;
    payment_method: string;
    payment_status?: string;
    payment_reference?: string;
    notes?: string;
    invoice_items: InvoiceItem[];
}

const props = defineProps<{ 
    invoice: Invoice;
    customers: Customer[];
    products: Product[];
    isPaymentOnlyEdit?: boolean;
}>();

const form = useForm({
    customer_id: props.invoice.customer_id,
    status: props.invoice.status,
    payment_method: props.invoice.payment_method,
    payment_status: props.invoice.payment_status || 'pending',
    invoice_type: props.invoice.invoice_type || 'walk_in',
    notes: props.invoice.notes || '',
    invoice_items: props.invoice.invoice_items.map(item => ({
        id: item.id,
        product_id: item.product_id,
        quantity: item.quantity,
        price: item.price,
        total: item.total
    })),
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Invoices',
        href: '/invoices',
    },
    {
        title: 'Edit Invoice',
        href: `/invoices/${props.invoice.id}/edit`,
    }
];

// Computed totals (VAT and withholding tax calculated separately)
const subtotalAmount = computed(() => {
    return form.invoice_items.reduce((sum: number, item: any) => sum + item.total, 0);
});

const vatAmount = computed(() => {
    return subtotalAmount.value * 0.12; // 12% VAT
});

const withholdingTaxAmount = computed(() => {
    return (subtotalAmount.value + vatAmount.value) * 0.01; // 1% withholding tax
});

const totalAmount = computed(() => {
    return subtotalAmount.value + vatAmount.value - withholdingTaxAmount.value;
});

// Basic validation to ensure form is ready for submission
const canSubmit = computed(() => {
    // For payment-only edits, only need payment_status
    if (props.isPaymentOnlyEdit) {
        return !!form.payment_status;
    }
    // For regular edits, validate all fields
    if (!form.customer_id) return false;
    if (!form.invoice_items.length) return false;
    return form.invoice_items.some((it: any) => !!it.product_id && it.quantity > 0 && it.price >= 0);
});

// Add new invoice item
function addInvoiceItem() {
    form.invoice_items.push({
        id: undefined,
        product_id: null,
        quantity: 1,
        price: 0,
        total: 0
    });
}

// Remove invoice item
function removeInvoiceItem(index: number) {
    if (form.invoice_items.length > 1) {
        form.invoice_items.splice(index, 1);
    }
}

// Update item total when product, quantity, or price changes
function updateItemTotal(index: number) {
    const item = form.invoice_items[index];
    if (item.product_id && item.quantity && item.price) {
        item.total = item.quantity * item.price;
    } else {
        item.total = 0;
    }
}

// Auto-fill price when product is selected
function onProductChange(index: number) {
    const item = form.invoice_items[index];
    if (item.product_id) {
        const product = props.products.find(p => p.id === item.product_id);
        if (product) {
            item.price = product.selling_price;
            updateItemTotal(index);
        }
    }
}

// Update total when quantity or price changes
function onQuantityChange(index: number) {
    updateItemTotal(index);
}

function onPriceChange(index: number) {
    updateItemTotal(index);
}

function submit() {
    form.post(route('invoices.update', props.invoice.id), {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Invoice updated successfully',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
            });
        },
        method: 'post',
    });
}

// Helper function to get form errors for nested fields
function getFormError(field: string): string | undefined {
    return form.errors[field as keyof typeof form.errors] as string | undefined;
}

// Customer options
const customerOptions = computed(() => [
    { value: null, label: 'Select customer' },
    ...props.customers.map(customer => ({
        value: customer.id,
        label: `${customer.name}${customer.company_name ? ` (${customer.company_name})` : ''}`
    }))
]);

// Status options
const statusOptions = [
    { value: 'draft', label: 'Draft' },
    { value: 'pending', label: 'Pending' },
    { value: 'completed', label: 'Completed' },
    { value: 'cancelled', label: 'Cancelled' }
];

// Payment method options
const paymentMethodOptions = [
    { value: 'cash', label: 'Cash' },
    { value: 'credit', label: 'Credit' }
];

// Payment status options
const paymentStatusOptions = [
    { value: 'pending', label: 'Pending' },
    { value: 'paid', label: 'Paid' },
    { value: 'failed', label: 'Failed' }
];

// Invoice type options
const invoiceTypeOptions = [
    { value: 'walk_in', label: 'Walk-in' },
    { value: 'delivery', label: 'Delivery' }
];

// Product options for each item
function getProductOptions() {
    return [
        { value: null, label: 'Select product' },
        ...props.products.map(product => ({
            value: product.id,
            label: `${product.name} - ₱${product.selling_price.toFixed(2)}`
        }))
    ];
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Edit Invoice" />
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">Edit Invoice #{{ props.invoice.id }}</h1>
        </div>
        
        <form @submit.prevent="submit" class="space-y-6">
            <!-- Payment Status Only Edit (for completed invoices with pending payment) -->
            <Card v-if="isPaymentOnlyEdit">
                <CardHeader>
                    <CardTitle>Update Payment Status</CardTitle>
                </CardHeader>
                <CardContent class="space-y-6">
                    <div class="bg-yellow-50 dark:bg-yellow-950/20 border border-yellow-200 dark:border-yellow-800 rounded-md p-4 mb-4">
                        <p class="text-sm text-yellow-800 dark:text-yellow-200">
                            This invoice is completed. You can only update the payment status.
                        </p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <Label for="payment_status">Payment Status *</Label>
                            <Select
                                v-model="form.payment_status"
                                :options="paymentStatusOptions"
                                placeholder="Select payment status"
                                class="mt-1"
                                required
                            />
                            <InputError :message="form.errors.payment_status" />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Full Invoice Edit (for non-completed or non-pending-payment invoices) -->
            <template v-else>
            <!-- Invoice Details -->
            <Card>
                <CardHeader>
                    <CardTitle>Invoice Details</CardTitle>
                </CardHeader>
                <CardContent class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <Label for="customer_id">Customer *</Label>
                            <SearchSelect
                                v-model="form.customer_id"
                                :options="customerOptions"
                                placeholder="Select customer"
                                search-placeholder="Search customers..."
                                class="mt-1"
                                required
                            />
                            <InputError :message="form.errors.customer_id" />
                        </div>
                        
                        <div>
                            <Label for="status">Status *</Label>
                            <Select
                                v-model="form.status"
                                :options="statusOptions"
                                placeholder="Select status"
                                class="mt-1"
                                required
                            />
                            <div class="text-[11px] text-gray-500 mt-1">
                                Draft: still editing, Pending: awaiting payment, Completed: paid, Cancelled: void.
                            </div>
                            <InputError :message="form.errors.status" />
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <Label for="payment_method">Payment Method *</Label>
                            <Select
                                v-model="form.payment_method"
                                :options="paymentMethodOptions"
                                placeholder="Select payment method"
                                class="mt-1"
                                required
                            />
                            <div class="text-[11px] text-gray-500 mt-1">
                                Pick how the customer will pay (Cash or Credit).
                            </div>
                            <InputError :message="form.errors.payment_method" />
                        </div>
                        
                        <div>
                            <Label for="invoice_type">Invoice Type *</Label>
                            <Select
                                v-model="form.invoice_type"
                                :options="invoiceTypeOptions"
                                placeholder="Select invoice type"
                                class="mt-1"
                                required
                            />
                            <div class="text-[11px] text-gray-500 mt-1">
                                Walk-in: Customer purchases in-store. Delivery: Items will be delivered.
                            </div>
                            <InputError :message="form.errors.invoice_type" />
                        </div>
                    </div>
                    
                    <div>
                        <Label for="notes">Notes</Label>
                        <textarea 
                            id="notes" 
                            v-model="form.notes" 
                            class="w-full rounded-md border border-input bg-transparent px-3 py-2 mt-1 text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none" 
                            rows="3" 
                        />
                        <InputError :message="form.errors.notes" />
                    </div>
                </CardContent>
            </Card>

            <!-- Invoice Items -->
            <Card>
                <CardHeader>
                    <div class="flex justify-between items-center">
                        <CardTitle>Invoice Items</CardTitle>
                        <Button type="button" variant="outline" @click="addInvoiceItem">
                            <span class="mr-2">+</span>
                            Add Item
                        </Button>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="space-y-3">
                        <div v-for="(item, index) in form.invoice_items" :key="index" class="border rounded-md p-3">
                            <div class="flex justify-between items-center mb-3">
                                <h4 class="font-medium text-sm">Item {{ index + 1 }}</h4>
                                <Button 
                                    v-if="form.invoice_items.length > 1" 
                                    type="button" 
                                    variant="ghost" 
                                    size="sm"
                                    class="h-7 w-7 p-0 text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/20"
                                    @click="removeInvoiceItem(index)"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                                <div>
                                    <Label :for="`product_id_${index}`" class="text-xs">Product *</Label>
                                    <SearchSelect
                                        v-model="item.product_id"
                                        :options="getProductOptions()"
                                        placeholder="Select product"
                                        search-placeholder="Search products..."
                                        class="mt-1 h-8"
                                        @update:model-value="onProductChange(index)"
                                        required
                                    />
                                    <InputError :message="getFormError(`invoice_items.${index}.product_id`)" />
                                </div>
                                
                                <div>
                                    <Label :for="`quantity_${index}`" class="text-xs">Quantity *</Label>
                                    <input 
                                        :id="`quantity_${index}`" 
                                        v-model.number="item.quantity" 
                                        type="number" 
                                        min="1" 
                                        class="w-full rounded-md border border-input bg-transparent px-2 py-1 mt-1 text-sm text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none h-8"
                                        @input="onQuantityChange(index)"
                                        required
                                    />
                                    <div v-if="item.product_id" class="text-[11px] text-gray-500 mt-1">
                                        In stock: {{ (props.products.find(p => p.id === item.product_id) || { stock: 0 }).stock }}
                                    </div>
                                    <InputError :message="getFormError(`invoice_items.${index}.quantity`)" />
                                </div>
                                
                                <div>
                                    <Label :for="`price_${index}`" class="text-xs">Price *</Label>
                                    <input 
                                        :id="`price_${index}`" 
                                        v-model.number="item.price" 
                                        type="number" 
                                        min="0" 
                                        step="0.01" 
                                        class="w-full rounded-md border border-input bg-transparent px-2 py-1 mt-1 text-sm text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none h-8"
                                        @input="onPriceChange(index)"
                                        required
                                    />
                                    <InputError :message="getFormError(`invoice_items.${index}.price`)" />
                                </div>
                                
                                <div>
                                    <Label class="text-xs">Total</Label>
                                    <div class="w-full rounded-md border border-input bg-transparent px-2 py-1 mt-1 text-sm text-foreground dark:bg-input/30 h-8 flex items-center">
                                        ₱{{ item.total.toFixed(2) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Total Amount Breakdown -->
                    <div class="mt-4 pt-3 border-t">
                        <div class="flex justify-end">
                            <div class="text-right space-y-2">
                                <div class="flex justify-between gap-8">
                                    <span class="text-sm text-gray-600">Subtotal:</span>
                                    <span class="text-sm font-medium">₱{{ subtotalAmount.toFixed(2) }}</span>
                                </div>
                                <div class="flex justify-between gap-8">
                                    <span class="text-sm text-gray-600">VAT (12%):</span>
                                    <span class="text-sm font-medium">₱{{ vatAmount.toFixed(2) }}</span>
                                </div>
                                <div class="flex justify-between gap-8 border-t pt-2">
                                    <span class="text-sm text-gray-600">Amount Net of VAT:</span>
                                    <span class="text-sm font-medium">₱{{ (subtotalAmount + vatAmount).toFixed(2) }}</span>
                                </div>
                                <div class="flex justify-between gap-8">
                                    <span class="text-sm text-red-600">Less: W/Holding Tax (1%):</span>
                                    <span class="text-sm font-medium text-red-600">-₱{{ withholdingTaxAmount.toFixed(2) }}</span>
                                </div>
                                <div class="flex justify-between gap-8 border-t-2 pt-2">
                                    <span class="text-base font-bold">Total Amount Due:</span>
                                    <span class="text-base font-bold">₱{{ totalAmount.toFixed(2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
            </template>

            <!-- Form Actions -->
            <Card>
                <CardFooter class="flex gap-2 justify-end">
                    <Button type="submit" :disabled="!canSubmit" variant="default">
                        {{ isPaymentOnlyEdit ? 'Update Payment Status' : 'Update Invoice' }}
                    </Button>
                    <Link :href="route('invoices.index')">
                        <Button type="button" variant="ghost">Cancel</Button>
                    </Link>
                </CardFooter>
            </Card>
        </form>
    </AppLayout>
</template> 