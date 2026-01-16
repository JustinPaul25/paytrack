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

interface Product {
    id: number;
    name: string;
    selling_price: number;
    stock: number;
    unit: string;
    category_id: number;
}

interface Category {
    id: number;
    name: string;
}

interface OrderItem {
    category_id: number | null;
    product_id: number | null;
    quantity: number;
}

const props = withDefaults(defineProps<{ 
    customer_id: number;
    products: Product[];
    categories?: Category[];
    baseDeliveryFee?: number;
}>(), {
    categories: () => [],
    baseDeliveryFee: 50.00
});

const form = useForm({
    customer_id: props.customer_id,
    delivery_type: 'delivery',
    payment_method: 'cash',
    credit_term_days: null as number | null,
    notes: '',
    order_items: [
        {
            category_id: null,
            product_id: null,
            quantity: 1,
        }
    ],
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Orders',
        href: '/orders',
    },
    {
        title: 'Create Order',
        href: '/orders/create',
    }
];

// Subtotal (sum of all items)
const subtotal = computed(() => {
    return form.order_items.reduce((sum, item) => {
        if (item.product_id && item.quantity) {
            const product = props.products.find(p => p.id === item.product_id);
            if (product) {
                return sum + (item.quantity * product.selling_price);
            }
        }
        return sum;
    }, 0);
});

// VAT calculation (12% of subtotal)
const vatRate = 12.00;
const vatAmount = computed(() => {
    return subtotal.value * (vatRate / 100);
});

// Amount Net of VAT (Subtotal + VAT)
const amountNetOfVat = computed(() => {
    return subtotal.value + vatAmount.value;
});

// Withholding Tax calculation (1% of Amount Net of VAT)
const withholdingTaxRate = 1.00;
const withholdingTaxAmount = computed(() => {
    return amountNetOfVat.value * (withholdingTaxRate / 100);
});

// Estimated delivery fee (from admin settings)
const estimatedDeliveryFee = computed(() => {
    return form.delivery_type === 'delivery' ? props.baseDeliveryFee : 0;
});

// Total Amount Due = Subtotal + VAT - Withholding Tax (for order validation)
// Note: This is used for minimum order validation only
const totalAmountDue = computed(() => {
    return subtotal.value + vatAmount.value - withholdingTaxAmount.value;
});

// Estimated Total with Delivery Fee (for customer information)
const estimatedTotalWithDelivery = computed(() => {
    return totalAmountDue.value + estimatedDeliveryFee.value;
});

// Legacy computed for backward compatibility (used in minimum check)
const totalAmount = computed(() => {
    return subtotal.value;
});

// Minimum order amount for delivery
const MINIMUM_DELIVERY_AMOUNT = 500.00;

// Check if delivery order meets minimum amount requirement
// Uses totalAmountDue which matches backend calculation (subtotal + VAT - withholding tax)
const meetsDeliveryMinimum = computed(() => {
    if (form.delivery_type !== 'delivery') return true;
    return totalAmountDue.value >= MINIMUM_DELIVERY_AMOUNT;
});

// Basic validation to ensure form is ready for submission
const canSubmit = computed(() => {
    if (!form.customer_id) return false;
    if (!form.order_items.length) return false;
    // At least one valid line item
    if (!form.order_items.some(it => !!it.product_id && it.quantity > 0)) return false;
    // Check minimum order amount for delivery
    if (!meetsDeliveryMinimum.value) return false;
    return true;
});

// Add new order item
function addOrderItem() {
    form.order_items.push({
        category_id: null,
        product_id: null,
        quantity: 1,
    });
}

// Remove order item
function removeOrderItem(index: number) {
    if (form.order_items.length > 1) {
        form.order_items.splice(index, 1);
    }
}

// Handle category change - reset product when category changes
function onCategoryChange(index: number) {
    const item = form.order_items[index];
    // Reset product when category changes
    item.product_id = null;
}

// Auto-adjust quantity when product is selected
function onProductChange(index: number) {
    const item = form.order_items[index];
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
}

// Update quantity validation
function onQuantityChange(index: number) {
    const item = form.order_items[index];
    
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
}

function submit() {
    // Double-check if any item quantity exceeds stock (as a safety measure)
    for (let i = 0; i < form.order_items.length; i++) {
        const item = form.order_items[i];
        if (item.product_id && item.quantity) {
            const product = props.products.find(p => p.id === item.product_id);
            if (product && item.quantity > product.stock) {
                // Auto-adjust to maximum available stock as a final safety measure
                item.quantity = product.stock;
                
                Swal.fire({
                    icon: 'warning',
                    title: 'Quantity Auto-Adjusted',
                    text: `Quantity for "${product.name}" was automatically adjusted to ${product.stock} units due to stock limitations.`,
                    confirmButtonText: 'OK'
                });
            }
        }
    }
    
    form.post(route('orders.store'), {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Order created successfully',
                text: 'Your order is pending approval from staff.',
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

// Format currency helper
function formatCurrency(amount: number): string {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2
    }).format(amount);
}

// Payment method options
const paymentMethodOptions = [
    { value: 'cash', label: 'Cash on delivery' },
    { value: 'credit', label: 'Credit' }
];

// Category options
const categoryOptions = computed(() => [
    { value: null, label: 'Select category' },
    ...(props.categories || []).map(category => ({
        value: category.id,
        label: category.name
    }))
]);

// Product options - filtered by selected category
function getProductOptions(index: number) {
    const item = form.order_items[index];
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

// Get item total
function getItemTotal(index: number): number {
    const item = form.order_items[index];
    if (item.product_id && item.quantity) {
        const product = props.products.find(p => p.id === item.product_id);
        if (product) {
            return item.quantity * product.selling_price;
        }
    }
    return 0;
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Create Order" />
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">Create Order</h1>
        </div>
        
        <form @submit.prevent="submit()" class="space-y-6">
            <!-- Order Details -->
            <Card>
                <CardHeader>
                    <CardTitle>Order Details</CardTitle>
                </CardHeader>
                <CardContent class="space-y-6">
                    <div>
                        <Label for="delivery_type">Delivery Type *</Label>
                        <Select
                            id="delivery_type"
                            v-model="form.delivery_type"
                            :options="[
                                { value: 'pickup', label: 'Pickup' },
                                { value: 'delivery', label: 'Delivery' }
                            ]"
                            class="mt-1"
                            required
                        />
                        <InputError :message="form.errors.delivery_type" />
                        <div v-if="form.delivery_type === 'delivery' && !meetsDeliveryMinimum" class="mt-2 p-3 bg-orange-50 dark:bg-orange-950/20 border border-orange-200 dark:border-orange-800 rounded-md">
                            <p class="text-sm text-orange-800 dark:text-orange-200">
                                <strong>Minimum order amount for delivery:</strong> ₱{{ MINIMUM_DELIVERY_AMOUNT.toFixed(2) }}
                            </p>
                            <p class="text-sm text-orange-700 dark:text-orange-300 mt-1">
                                Current total: ₱{{ totalAmountDue.toFixed(2) }}. 
                                <span class="font-semibold">Add ₱{{ (MINIMUM_DELIVERY_AMOUNT - totalAmountDue).toFixed(2) }} more to proceed with delivery.</span>
                            </p>
                        </div>
                    </div>
                    
                    <div>
                        <Label for="payment_method">Payment Method *</Label>
                        <Select
                            id="payment_method"
                            v-model="form.payment_method"
                            :options="paymentMethodOptions"
                            class="mt-1"
                            required
                        />
                        <InputError :message="form.errors.payment_method" />
                    </div>
                    
                    <div v-if="form.payment_method === 'credit'">
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
                    
                    <div>
                        <Label for="notes">Notes (Optional)</Label>
                        <textarea 
                            id="notes" 
                            v-model="form.notes" 
                            class="w-full rounded-md border border-input bg-transparent px-3 py-2 mt-1 text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none" 
                            rows="3" 
                            placeholder="Add any special instructions or notes for your order..."
                        />
                        <InputError :message="form.errors.notes" />
                    </div>
                </CardContent>
            </Card>

            <!-- Order Items -->
            <Card>
                <CardHeader>
                    <div class="flex justify-between items-center">
                        <CardTitle>Order Items</CardTitle>
                        <Button type="button" variant="outline" @click="addOrderItem">
                            <span class="mr-2">+</span>
                            Add Item
                        </Button>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="space-y-3">
                        <div v-for="(item, index) in form.order_items" :key="index" class="border rounded-md p-3">
                            <div class="flex justify-between items-center mb-3">
                                <h4 class="font-medium text-sm">Item {{ index + 1 }}</h4>
                                <Button 
                                    v-if="form.order_items.length > 1" 
                                    type="button" 
                                    variant="ghost" 
                                    size="sm"
                                    class="h-7 w-7 p-0 text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/20"
                                    @click="removeOrderItem(index)"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
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
                                    <InputError :message="getFormError(`order_items.${index}.category_id`)" />
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
                                    <InputError :message="getFormError(`order_items.${index}.product_id`)" />
                                </div>
                                
                                <div>
                                    <Label :for="`quantity_${index}`" class="text-xs">Quantity *</Label>
                                    <input 
                                        :id="`quantity_${index}`" 
                                        v-model.number="item.quantity" 
                                        type="number" 
                                        min="1"
                                        :max="item.product_id ? (props.products.find(p => p.id === item.product_id) || { stock: 0 }).stock : undefined"
                                        class="w-full rounded-md border border-input bg-transparent px-2 py-1 mt-1 text-sm text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none h-8"
                                        @input="onQuantityChange(index)"
                                        required
                                    />
                                    <div v-if="item.product_id" class="text-[11px] text-gray-500 mt-1">
                                        In stock: {{ (props.products.find(p => p.id === item.product_id) || { stock: 0 }).stock }}
                                    </div>
                                    <InputError :message="getFormError(`order_items.${index}.quantity`)" />
                                </div>
                                
                                <div>
                                    <Label class="text-xs">Total</Label>
                                    <div class="w-full rounded-md border border-input bg-transparent px-2 py-1 mt-1 text-sm text-foreground dark:bg-input/30 h-8 flex items-center">
                                        ₱{{ getItemTotal(index).toFixed(2) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Cost Breakdown -->
                    <div class="mt-6 pt-4 border-t">
                        <h3 class="text-sm font-semibold mb-3">Order Summary</h3>
                        <div class="space-y-2">
                            <!-- Subtotal -->
                            <div class="flex justify-between text-sm">
                                <span class="text-muted-foreground">Subtotal:</span>
                                <span class="font-medium">₱{{ subtotal.toFixed(2) }}</span>
                            </div>
                            
                            <!-- VAT -->
                            <div class="flex justify-between text-sm">
                                <span class="text-muted-foreground">VAT ({{ vatRate.toFixed(2) }}%):</span>
                                <span class="font-medium">₱{{ vatAmount.toFixed(2) }}</span>
                            </div>
                            
                            <!-- Amount Net of VAT -->
                            <div class="flex justify-between text-sm">
                                <span class="text-muted-foreground">Amount Net of VAT:</span>
                                <span class="font-medium">₱{{ amountNetOfVat.toFixed(2) }}</span>
                            </div>
                            
                            <!-- Withholding Tax -->
                            <div class="flex justify-between text-sm">
                                <span class="text-muted-foreground">Less: W/Holding Tax ({{ withholdingTaxRate.toFixed(2) }}%):</span>
                                <span class="font-medium text-red-600 dark:text-red-400">-₱{{ withholdingTaxAmount.toFixed(2) }}</span>
                            </div>
                            
                            <!-- Subtotal (before delivery) -->
                            <div class="flex justify-between pt-2 mt-2 border-t text-sm">
                                <span class="text-muted-foreground">Subtotal:</span>
                                <span class="font-medium">₱{{ totalAmountDue.toFixed(2) }}</span>
                            </div>
                            
                            <!-- Estimated Delivery Fee (only if delivery) -->
                            <div v-if="form.delivery_type === 'delivery'" class="flex justify-between text-sm">
                                <span class="text-muted-foreground">Estimated Delivery Fee:</span>
                                <span class="font-medium">₱{{ estimatedDeliveryFee.toFixed(2) }}</span>
                            </div>
                            
                            <!-- Estimated Total Amount Due -->
                            <div class="flex justify-between pt-2 mt-2 border-t font-semibold text-base">
                                <span>Estimated Total Amount Due:</span>
                                <span class="text-lg">₱{{ estimatedTotalWithDelivery.toFixed(2) }}</span>
                            </div>
                            
                            <!-- Delivery Fee Note -->
                            <div v-if="form.delivery_type === 'delivery'" class="mt-2 p-2 bg-blue-50 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-800 rounded text-xs text-blue-700 dark:text-blue-300">
                                <strong>Note:</strong> The actual delivery fee may vary based on delivery distance and will be confirmed when your order is processed.
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
                        :disabled="!canSubmit" 
                        variant="default"
                        :title="!meetsDeliveryMinimum ? `Minimum order amount for delivery is ₱${MINIMUM_DELIVERY_AMOUNT.toFixed(2)}` : ''"
                    >
                        Submit Order
                    </Button>
                    <Link :href="route('orders.index')">
                        <Button type="button" variant="ghost">Cancel</Button>
                    </Link>
                </CardFooter>
            </Card>
        </form>
    </AppLayout>
</template>

