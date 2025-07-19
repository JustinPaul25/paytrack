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

const props = defineProps<{ 
    invoice?: Invoice;
    invoiceItem?: InvoiceItem;
    availableInvoices: Invoice[];
    invoiceStats?: {
        totalInvoices: number;
        completedInvoices: number;
        recentInvoices: number;
        availableForRefund: number;
    };
    refundTypes: RefundType[];
    refundMethods: RefundMethod[];
}>();

const form = useForm({
    invoice_id: props.invoice?.id || null as number | null,
    invoice_item_id: props.invoiceItem?.id || null as number | null,
    product_id: props.invoiceItem?.product_id || null as number | null,
    quantity_refunded: props.invoiceItem?.quantity || 1,
    refund_amount: props.invoiceItem?.total || 0,
    refund_type: 'full',
    refund_method: 'cash',
    reason: '',
    notes: '',
    reference_number: 'Auto-generated',
    create_another: undefined as number | undefined,
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Refunds',
        href: '/refunds',
    },
    {
        title: 'Create Refund',
        href: '/refunds/create',
    }
];

// Computed values
const selectedInvoice = computed(() => {
    if (form.invoice_id) {
        return props.availableInvoices.find(inv => inv.id === form.invoice_id);
    }
    return props.invoice;
});

const selectedInvoiceItem = computed(() => {
    if (form.invoice_item_id && selectedInvoice.value) {
        return selectedInvoice.value.invoice_items.find(item => item.id === form.invoice_item_id);
    }
    return props.invoiceItem;
});

const maxQuantity = computed(() => {
    if (!selectedInvoiceItem.value) return 0;
    
    // Get the original quantity from the invoice item
    const originalQuantity = selectedInvoiceItem.value.quantity;
    
    // Check if there are any existing refunds for this item
    // For now, we'll assume no refunds exist (you may need to add this logic later)
    // This is a placeholder - you might want to fetch existing refunds from the backend
    
    return originalQuantity;
});

// Invoice options
const invoiceOptions = computed(() => [
    { value: null, label: 'Select invoice' },
    ...props.availableInvoices.map(invoice => ({
        value: invoice.id,
        label: `Invoice #${invoice.id} - ${invoice.customer.name}${invoice.customer.company_name ? ` (${invoice.customer.company_name})` : ''}`
    }))
]);

// Debug information for available invoices
const availableInvoiceCount = computed(() => props.availableInvoices.length);

// Invoice item options
const invoiceItemOptions = computed(() => {
    if (!selectedInvoice.value) return [{ value: null, label: 'Select invoice first' }];
    
    return [
        { value: null, label: 'Select item' },
        ...selectedInvoice.value.invoice_items.map(item => ({
            value: item.id,
            label: `${item.product.name} - Qty: ${item.quantity} - ₱${item.total.toFixed(2)}`
        }))
    ];
});

// Update refund amount when item changes
function onInvoiceItemChange() {
    if (selectedInvoiceItem.value) {
        form.product_id = selectedInvoiceItem.value.product_id;
        form.quantity_refunded = selectedInvoiceItem.value.quantity;
        form.refund_amount = selectedInvoiceItem.value.total;
    }
}

// Update refund amount when quantity changes
function onQuantityChange() {
    if (selectedInvoiceItem.value && form.quantity_refunded) {
        const itemPrice = selectedInvoiceItem.value.price;
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
    if (selectedInvoiceItem.value) {
        if (form.refund_type === 'full') {
            form.quantity_refunded = selectedInvoiceItem.value.quantity;
            form.refund_amount = selectedInvoiceItem.value.total;
        } else if (form.refund_type === 'partial') {
            // Keep current quantity and amount
        } else if (form.refund_type === 'exchange') {
            form.quantity_refunded = selectedInvoiceItem.value.quantity;
            form.refund_amount = 0; // No refund amount for exchange
        }
    }
}

function submit(createAnother = false) {
    // Validate that an invoice item is selected
    if (!selectedInvoiceItem.value) {
        Swal.fire({
            icon: 'error',
            title: 'No Invoice Item Selected',
            text: 'Please select an invoice item before creating a refund.',
            confirmButtonText: 'OK'
        });
        return;
    }

    // Validate that quantity is available for refund
    if (maxQuantity.value === 0) {
        Swal.fire({
            icon: 'error',
            title: 'No Quantity Available',
            text: 'This item has no quantity available for refund.',
            confirmButtonText: 'OK'
        });
        return;
    }

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

    if (createAnother) {
        form.create_another = 1;
    } else {
        delete form.create_another;
    }
    
    form.post(route('refunds.store'), {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Refund created successfully',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
            });
            if (createAnother) {
                form.reset();
                form.invoice_id = null;
                form.invoice_item_id = null;
                form.product_id = null;
                form.quantity_refunded = 1;
                form.refund_amount = 0;
                form.reference_number = 'Auto-generated';
            }
        },
    });
}

// Helper function to get form errors for nested fields
function getFormError(field: string): string | undefined {
    return form.errors[field as keyof typeof form.errors] as string | undefined;
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
        <Head title="Create Refund" />
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">Create Refund</h1>
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
                            <Label for="invoice_id">Invoice</Label>
                            <SearchSelect
                                v-model="form.invoice_id"
                                :options="invoiceOptions"
                                placeholder="Select invoice"
                                search-placeholder="Search by invoice # or customer name..."
                                class="mt-1"
                                required
                            />
                            <div class="text-xs text-gray-500 mt-1">
                                Available invoices: {{ availableInvoiceCount }} (completed invoices from last 3 months)
                            </div>
                            <div v-if="availableInvoiceCount === 0" class="text-xs text-orange-500 mt-1">
                                No completed invoices available for refund. Only invoices with "completed" status from the last 3 months can be refunded.
                            </div>
                            <div v-else class="text-xs text-gray-400 mt-1">
                                Search by: invoice number (e.g., "123") or customer name
                            </div>
                            <InputError :message="form.errors.invoice_id" />
                        </div>
                        
                        <div>
                            <Label for="invoice_item_id">Invoice Item</Label>
                            <SearchSelect
                                v-model="form.invoice_item_id"
                                :options="invoiceItemOptions"
                                placeholder="Select item"
                                search-placeholder="Search items..."
                                class="mt-1"
                                :disabled="!selectedInvoice"
                                @update:model-value="onInvoiceItemChange"
                                required
                            />
                            <div v-if="selectedInvoice && !selectedInvoiceItem" class="text-xs text-blue-500 mt-1">
                                Please select an item from this invoice to proceed with the refund
                            </div>
                            <InputError :message="form.errors.invoice_item_id" />
                        </div>
                    </div>
                    
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
                                :disabled="!selectedInvoiceItem || maxQuantity === 0"
                                class="w-full rounded-md border border-input px-3 py-2 mt-1 text-foreground focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none"
                                :class="!selectedInvoiceItem || maxQuantity === 0 ? 'bg-gray-100 dark:bg-gray-800 cursor-not-allowed' : 'bg-transparent dark:bg-input/30'"
                                @input="onQuantityChange"
                                required
                            />
                            <div v-if="!selectedInvoiceItem" class="text-xs text-orange-500 mt-1">
                                Please select an invoice item first
                            </div>
                            <div v-else-if="maxQuantity === 0" class="text-xs text-red-500 mt-1">
                                No quantity available for refund on this item
                            </div>
                            <div v-else class="text-xs text-gray-500 mt-1">
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
                                :disabled="form.refund_type === 'exchange' || !selectedInvoiceItem || maxQuantity === 0"
                                class="w-full rounded-md border border-input px-3 py-2 mt-1 text-foreground focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none"
                                :class="form.refund_type === 'exchange' || !selectedInvoiceItem || maxQuantity === 0 ? 'bg-gray-100 dark:bg-gray-800 cursor-not-allowed' : 'bg-transparent dark:bg-input/30'"
                                required
                            />
                            <InputError :message="form.errors.refund_amount" />
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <Label for="reference_number" class="flex items-center gap-2">
                                Reference Number
                                <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full">Auto-generated</span>
                            </Label>
                            <input
                                v-model="form.reference_number"
                                type="text"
                                id="reference_number"
                                class="w-full rounded-md border border-input bg-gray-100 dark:bg-gray-800 px-3 py-2 mt-1 text-foreground cursor-not-allowed"
                                placeholder="Auto-generated reference number"
                                readonly
                            />
                            <div class="text-xs text-gray-500 mt-1">
                                Format: RRNYYYYMMDD#### (e.g., RRN202412150001)
                            </div>
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

            <!-- Selected Item Details -->
            <Card v-if="selectedInvoiceItem">
                <CardHeader>
                    <CardTitle>Selected Item Details</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-500">Product</label>
                            <p class="text-lg font-semibold">{{ selectedInvoiceItem.product.name }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Original Quantity</label>
                            <p class="text-lg font-semibold">{{ selectedInvoiceItem.quantity }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Original Price</label>
                            <p class="text-lg font-semibold">{{ formatCurrency(selectedInvoiceItem.price) }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Debug Information (only show if no invoices available) -->
            <Card v-if="availableInvoiceCount === 0" class="border-orange-200 bg-orange-50 dark:border-orange-800 dark:bg-orange-950/20">
                <CardHeader>
                    <CardTitle class="text-orange-800 dark:text-orange-200">No Invoices Available for Refund</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-2 text-sm text-orange-700 dark:text-orange-300">
                        <p><strong>Requirements for refundable invoices:</strong></p>
                        <ul class="list-disc list-inside space-y-1 ml-4">
                            <li>Invoice status must be "completed"</li>
                            <li>Invoice must be created within the last 3 months</li>
                            <li>Invoice must have at least one item</li>
                        </ul>
                        <div class="mt-3 p-3 bg-white dark:bg-gray-800 rounded border">
                            <p class="font-semibold mb-2">Invoice Statistics:</p>
                            <ul class="space-y-1 text-xs">
                                <li>Total invoices: {{ props.invoiceStats?.totalInvoices || 0 }}</li>
                                <li>Completed invoices: {{ props.invoiceStats?.completedInvoices || 0 }}</li>
                                <li>Recent invoices (last 3 months): {{ props.invoiceStats?.recentInvoices || 0 }}</li>
                                <li>Available for refund: {{ props.invoiceStats?.availableForRefund || 0 }}</li>
                            </ul>
                        </div>
                        <p class="mt-2">
                            <Link :href="route('invoices.index')" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200 underline">
                                View all invoices →
                            </Link>
                        </p>
                    </div>
                </CardContent>
            </Card>

            <!-- Available Invoices List (for debugging) -->
            <Card v-if="availableInvoiceCount > 0 && availableInvoiceCount <= 5" class="border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-950/20">
                <CardHeader>
                    <CardTitle class="text-blue-800 dark:text-blue-200">Available Invoices for Refund</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-2 text-sm text-blue-700 dark:text-blue-300">
                        <p class="mb-3">You can search for any of these invoices:</p>
                        <div class="space-y-1">
                            <div v-for="invoice in props.availableInvoices" :key="invoice.id" class="p-2 bg-white dark:bg-gray-800 rounded border">
                                <strong>Invoice #{{ invoice.id }}</strong> - {{ invoice.customer.name }}
                                <span v-if="invoice.customer.company_name" class="text-gray-500">({{ invoice.customer.company_name }})</span>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Form Actions -->
            <Card>
                <CardFooter class="flex gap-2 justify-end">
                    <Button 
                        type="submit" 
                        variant="default"
                        :disabled="!selectedInvoiceItem || maxQuantity === 0"
                    >
                        Create Refund
                    </Button>
                    <Button 
                        type="button" 
                        variant="secondary" 
                        @click="submit(true)"
                        :disabled="!selectedInvoiceItem || maxQuantity === 0"
                    >
                        Create & create another
                    </Button>
                    <Link :href="route('refunds.index')">
                        <Button type="button" variant="ghost">Cancel</Button>
                    </Link>
                </CardFooter>
            </Card>
        </form>
    </AppLayout>
</template> 