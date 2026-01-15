<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
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
    is_walk_in?: boolean;
}

interface Product {
    id: number;
    name: string;
    selling_price: number;
    stock: number;
    unit: string;
}

interface InvoiceItem {
    category_id: number | null;
    product_id: number | null;
    quantity: number;
    price: number;
    total: number;
}

interface Category {
    id: number;
    name: string;
}

const props = withDefaults(defineProps<{ 
    customers: Customer[];
    products: Product[];
    categories?: Category[];
}>(), {
    categories: () => []
});

const isWalkInCustomer = ref(false);

const form = useForm({
    customer_id: null as number | null,
    walk_in_customer: {
        name: '',
        phone: ''
    },
    status: 'draft',
    payment_method: 'cash',
    payment_status: null as string | null,
    invoice_type: 'walk_in',
    credit_term_days: null as number | null,
    notes: '',
    invoice_items: [
        {
            category_id: null,
            product_id: null,
            quantity: 1,
            price: 0,
            total: 0
        }
    ],
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Invoices',
        href: '/invoices',
    },
    {
        title: 'Create Invoice',
        href: '/invoices/create',
    }
];

// Computed totals (VAT and withholding tax calculated separately)
const subtotalAmount = computed(() => {
    return form.invoice_items.reduce((sum, item) => sum + item.total, 0);
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
    // Must have either customer_id or walk-in customer info
    if (isWalkInCustomer.value) {
        if (!form.walk_in_customer.name || form.walk_in_customer.name.trim() === '') return false;
    } else {
        if (!form.customer_id) return false;
    }
    
    if (!form.invoice_items.length) return false;
    // At least one valid line item
    return form.invoice_items.some(it => !!it.product_id && it.quantity > 0 && it.price >= 0);
});

// Add new invoice item
function addInvoiceItem() {
    form.invoice_items.push({
        category_id: null,
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

// Handle category change - reset product when category changes
function onCategoryChange(index: number) {
    const item = form.invoice_items[index];
    // Reset product when category changes
    item.product_id = null;
    item.price = 0;
    item.total = 0;
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
    const item = form.invoice_items[index];
    
    // Check if quantity exceeds stock and auto-adjust
    if (item.product_id && item.quantity) {
        const product = props.products.find(p => p.id === item.product_id);
        if (product && item.quantity > product.stock) {
            // Auto-adjust to maximum available stock
            item.quantity = product.stock;
            
            Swal.fire({
                icon: 'info',
                title: 'Quantity Adjusted',
                text: `Quantity for "${product.name}" has been adjusted to the maximum available stock (${product.stock} units).`,
                confirmButtonText: 'OK',
                toast: true,
                position: 'top-end',
                timer: 3000,
                timerProgressBar: true,
            });
        }
    }
    
    updateItemTotal(index);
}

function onPriceChange(index: number) {
    updateItemTotal(index);
}

function submit() {
    // Double-check if any item quantity exceeds stock (as a safety measure)
    for (let i = 0; i < form.invoice_items.length; i++) {
        const item = form.invoice_items[i];
        if (item.product_id && item.quantity) {
            const product = props.products.find(p => p.id === item.product_id);
            if (product && item.quantity > product.stock) {
                // Auto-adjust to maximum available stock as a final safety measure
                item.quantity = product.stock;
                updateItemTotal(i);
                
                Swal.fire({
                    icon: 'warning',
                    title: 'Quantity Auto-Adjusted',
                    text: `Quantity for "${product.name}" was automatically adjusted to ${product.stock} units due to stock limitations.`,
                    confirmButtonText: 'OK'
                });
            }
        }
    }
    
    // Prepare form data based on customer type
    const formData = { ...form.data() };
    if (isWalkInCustomer.value) {
        // Clear customer_id when using walk-in customer
        formData.customer_id = null;
    } else {
        // Clear walk_in_customer when using existing customer
        formData.walk_in_customer = null;
    }
    
    form.transform(() => formData).post(route('invoices.store'), {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Invoice created successfully',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
            });
        },
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
        label: `${customer.name}${customer.company_name ? ` (${customer.company_name})` : ''}${customer.is_walk_in ? ' [Walk-in]' : ''}`
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
    { value: 'pending', label: 'Pending Payment' },
    { value: 'paid', label: 'Paid' },
    { value: 'failed', label: 'Failed' }
];

// Computed property to determine if payment status field should be shown
const showPaymentStatus = computed(() => {
    return isWalkInCustomer.value && form.invoice_type === 'walk_in' && form.payment_method === 'cash';
});

// Invoice type options
const invoiceTypeOptions = [
    { value: 'walk_in', label: 'Walk-in' },
    { value: 'delivery', label: 'Delivery' }
];

// Format currency helper
function formatCurrency(amount: number): string {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2
    }).format(amount);
}

// Category options
const categoryOptions = computed(() => [
    { value: null, label: 'Select category' },
    ...(props.categories || []).map(category => ({
        value: category.id,
        label: category.name
    }))
]);

// Product options for each item - filtered by selected category
function getProductOptions(index: number) {
    const item = form.invoice_items[index];
    const allProducts = props.products || [];
    const filteredProducts = item.category_id 
        ? allProducts.filter(p => p.category_id === item.category_id)
        : allProducts;
    
    return [
        { value: null, label: 'Select product' },
        ...filteredProducts.map(product => ({
            value: product.id,
            label: product.name,
            description: `${formatCurrency(product.selling_price)}/${product.unit.toUpperCase()} • Stock: ${product.stock}`
        }))
    ];
}

// Watch for customer type changes and clear the opposite field
watch(isWalkInCustomer, (newValue) => {
    if (newValue) {
        // Switching to walk-in: clear customer_id and auto-select walk_in invoice type
        form.customer_id = null;
        form.invoice_type = 'walk_in';
    } else {
        // Switching to registered: clear walk-in customer fields and payment status
        form.walk_in_customer.name = '';
        form.walk_in_customer.phone = '';
        form.payment_status = null;
    }
});

// Auto-set payment status to 'paid' when walk-in customer with cash payment sets status to 'completed'
watch([() => form.status, () => form.payment_method, () => form.invoice_type, isWalkInCustomer], ([status, paymentMethod, invoiceType, isWalkIn]) => {
    // Only show payment status for walk-in customers with cash payment
    if (isWalkIn && invoiceType === 'walk_in' && paymentMethod === 'cash') {
        // Auto-set to 'paid' if status is 'completed' and payment_status is not already set
        if (status === 'completed' && !form.payment_status) {
            form.payment_status = 'paid';
        }
    } else {
        // Clear payment status if conditions are no longer met
        form.payment_status = null;
    }
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Create Invoice" />
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">Create Invoice</h1>
        </div>
        
        <form @submit.prevent="submit()" class="space-y-6">
            <!-- Invoice Details -->
            <Card>
                <CardHeader>
                    <CardTitle>Invoice Details</CardTitle>
                </CardHeader>
                <CardContent class="space-y-6">
                    <!-- Customer Selection -->
                    <div>
                        <div class="flex items-center gap-4 mb-4">
                            <Label class="text-base font-medium">Customer Type</Label>
                            <div class="flex gap-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input 
                                        type="radio" 
                                        :value="false" 
                                        v-model="isWalkInCustomer"
                                        class="w-4 h-4"
                                    />
                                    <span>Registered Customer</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input 
                                        type="radio" 
                                        :value="true" 
                                        v-model="isWalkInCustomer"
                                        class="w-4 h-4"
                                    />
                                    <span>Walk-in Customer</span>
                                </label>
                            </div>
                        </div>
                        
                        <div v-if="!isWalkInCustomer" class="mb-4">
                            <Label for="customer_id">Customer</Label>
                            <SearchSelect
                                v-model="form.customer_id"
                                :options="customerOptions"
                                placeholder="Select customer"
                                search-placeholder="Search customers..."
                                class="mt-1"
                            />
                            <InputError :message="form.errors.customer_id" />
                        </div>
                        
                        <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <Label for="walk_in_name">Customer Name *</Label>
                                <input
                                    id="walk_in_name"
                                    v-model="form.walk_in_customer.name"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                    placeholder="Enter customer name"
                                    required
                                />
                                <div class="text-[11px] text-gray-500 mt-1">
                                    Name of the walk-in customer
                                </div>
                                <InputError :message="form.errors['walk_in_customer.name']" />
                            </div>
                            <div>
                                <Label for="walk_in_phone">Phone (Optional)</Label>
                                <input
                                    id="walk_in_phone"
                                    v-model="form.walk_in_customer.phone"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                    placeholder="Enter phone number"
                                />
                                <div class="text-[11px] text-gray-500 mt-1">
                                    Optional phone number for walk-in customer
                                </div>
                                <InputError :message="form.errors['walk_in_customer.phone']" />
                            </div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                    </div>
                    
                    <!-- Payment Status - Only shown for walk-in customers with cash payment -->
                    <div v-if="showPaymentStatus" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <Label for="payment_status">Payment Status</Label>
                            <Select
                                v-model="form.payment_status"
                                :options="paymentStatusOptions"
                                placeholder="Select payment status"
                                class="mt-1"
                            />
                            <div class="text-[11px] text-gray-500 mt-1">
                                Select payment status. If status is "Completed" and payment method is "Cash", you can mark as "Paid" immediately.
                            </div>
                            <InputError :message="form.errors.payment_status" />
                        </div>
                    </div>
                    
                    <div v-if="form.payment_method === 'credit'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <Label for="credit_term_days">Credit Term (Days)</Label>
                            <input
                                v-model.number="form.credit_term_days"
                                type="number"
                                id="credit_term_days"
                                min="0"
                                max="365"
                                class="mt-1 block w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                placeholder="e.g., 30"
                            />
                            <div class="text-[11px] text-gray-500 mt-1">
                                Number of days until payment is due (default: 30 days).
                            </div>
                            <InputError :message="form.errors.credit_term_days" />
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
                            
                            <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                                <div>
                                    <Label :for="`category_id_${index}`" class="text-xs">Category</Label>
                                    <SearchSelect
                                        v-model="item.category_id"
                                        :options="categoryOptions"
                                        placeholder="Select category"
                                        search-placeholder="Search categories..."
                                        class="mt-1 h-8"
                                        @update:model-value="onCategoryChange(index)"
                                    />
                                    <InputError :message="getFormError(`invoice_items.${index}.category_id`)" />
                                </div>
                                
                                <div>
                                    <Label :for="`product_id_${index}`" class="text-xs">Product *</Label>
                                    <SearchSelect
                                        v-model="item.product_id"
                                        :options="getProductOptions(index)"
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
                    
                    <!-- Invoice Type -->
                    <div class="mt-4 pt-3 border-t">
                        <Label for="invoice_type">Invoice Type *</Label>
                        <Select
                            v-model="form.invoice_type"
                            :options="invoiceTypeOptions"
                            placeholder="Select invoice type"
                            class="mt-1"
                            :disabled="isWalkInCustomer"
                            required
                        />
                        <div class="text-[11px] text-gray-500 mt-1">
                            Walk-in: Customer purchases in-store. Delivery: Items will be delivered.
                            <span v-if="isWalkInCustomer" class="block text-blue-600 font-medium">(Locked to Walk-in for walk-in customers)</span>
                        </div>
                        <InputError :message="form.errors.invoice_type" />
                    </div>
                </CardContent>
            </Card>

            <!-- Form Actions -->
            <Card>
                <CardFooter class="flex gap-2 justify-end">
                    <Button type="submit" :disabled="!canSubmit" variant="default">Create Invoice</Button>
                    <Link :href="route('invoices.index')">
                        <Button type="button" variant="ghost">Cancel</Button>
                    </Link>
                </CardFooter>
            </Card>
        </form>
    </AppLayout>
</template> 