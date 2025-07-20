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
    product_id: number | null;
    quantity: number;
    price: number;
    total: number;
}

const props = defineProps<{ 
    customers: Customer[];
    products: Product[];
}>();

const form = useForm({
    customer_id: null as number | null,
    status: 'draft',
    payment_method: 'cash',
    notes: '',
    invoice_items: [
        {
            product_id: null,
            quantity: 1,
            price: 0,
            total: 0
        }
    ],
    create_another: undefined as number | undefined,
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

// Computed total
const totalAmount = computed(() => {
    return form.invoice_items.reduce((sum, item) => sum + item.total, 0);
});

// Computed VAT amount (12%)
const vatAmount = computed(() => {
    return totalAmount.value * 0.12;
});

// Computed grand total (subtotal + VAT)
const grandTotal = computed(() => {
    return totalAmount.value + vatAmount.value;
});

// Add new invoice item
function addInvoiceItem() {
    form.invoice_items.push({
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

function submit(createAnother = false) {
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

    if (createAnother) {
        form.create_another = 1;
    } else {
        delete form.create_another;
    }
    
    form.post(route('invoices.store'), {
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
            if (createAnother) {
                form.reset();
                // Reset to one empty item
                form.invoice_items = [{
                    product_id: null,
                    quantity: 1,
                    price: 0,
                    total: 0
                }];
            }
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
    { value: 'bank_transfer', label: 'Bank Transfer' },
    { value: 'e-wallet', label: 'E-Wallet' },
    { value: 'other', label: 'Other' }
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
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <Label for="customer_id">Customer</Label>
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
                            <Label for="status">Status</Label>
                            <Select
                                v-model="form.status"
                                :options="statusOptions"
                                placeholder="Select status"
                                class="mt-1"
                                required
                            />
                            <InputError :message="form.errors.status" />
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <Label for="payment_method">Payment Method</Label>
                            <Select
                                v-model="form.payment_method"
                                :options="paymentMethodOptions"
                                placeholder="Select payment method"
                                class="mt-1"
                                required
                            />
                            <InputError :message="form.errors.payment_method" />
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
                                    <Label :for="`product_id_${index}`" class="text-xs">Product</Label>
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
                                    <Label :for="`quantity_${index}`" class="text-xs">Quantity</Label>
                                    <input 
                                        :id="`quantity_${index}`" 
                                        v-model.number="item.quantity" 
                                        type="number" 
                                        min="1" 
                                        class="w-full rounded-md border border-input bg-transparent px-2 py-1 mt-1 text-sm text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none h-8"
                                        @input="onQuantityChange(index)"
                                        required
                                    />
                                    <InputError :message="getFormError(`invoice_items.${index}.quantity`)" />
                                </div>
                                
                                <div>
                                    <Label :for="`price_${index}`" class="text-xs">Price</Label>
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
                    
                    <!-- Total Amount -->
                    <div class="mt-4 pt-3 border-t">
                        <div class="flex justify-end">
                            <div class="text-right space-y-1">
                                <div class="text-sm text-muted-foreground">Subtotal: ₱{{ totalAmount.toFixed(2) }}</div>
                                <div class="text-sm text-muted-foreground">VAT (12%): ₱{{ vatAmount.toFixed(2) }}</div>
                                <div class="text-base font-medium">Total Amount: ₱{{ grandTotal.toFixed(2) }}</div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Form Actions -->
            <Card>
                <CardFooter class="flex gap-2 justify-end">
                    <Button type="submit" variant="default">Create Invoice</Button>
                    <Button type="button" variant="secondary" @click="submit(true)">Create & create another</Button>
                    <Link :href="route('invoices.index')">
                        <Button type="button" variant="ghost">Cancel</Button>
                    </Link>
                </CardFooter>
            </Card>
        </form>
    </AppLayout>
</template> 