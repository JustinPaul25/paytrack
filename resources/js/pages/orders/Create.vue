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
}

interface OrderItem {
    product_id: number | null;
    quantity: number;
}

const props = defineProps<{ 
    customer_id: number;
    products: Product[];
}>();

const form = useForm({
    customer_id: props.customer_id,
    delivery_type: 'delivery',
    notes: '',
    order_items: [
        {
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

// Computed total
const totalAmount = computed(() => {
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

// Computed VAT amount (12%)
const vatAmount = computed(() => {
    return totalAmount.value * 0.12;
});

// Computed grand total (subtotal + VAT)
const grandTotal = computed(() => {
    return totalAmount.value + vatAmount.value;
});

// Basic validation to ensure form is ready for submission
const canSubmit = computed(() => {
    if (!form.customer_id) return false;
    if (!form.order_items.length) return false;
    // At least one valid line item
    return form.order_items.some(it => !!it.product_id && it.quantity > 0);
});

// Add new order item
function addOrderItem() {
    form.order_items.push({
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

// Product options
function getProductOptions() {
    return [
        { value: null, label: 'Select product' },
        ...props.products.map(product => ({
            value: product.id,
            label: `${product.name} (Stock: ${product.stock})`
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
                    <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                        <p class="text-sm text-yellow-800">
                            <strong>Note:</strong> Your order will be reviewed by staff for product availability before approval. Once approved, an invoice will be automatically generated.
                        </p>
                    </div>
                    
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
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
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
                                    <InputError :message="getFormError(`order_items.${index}.product_id`)" />
                                </div>
                                
                                <div>
                                    <Label :for="`quantity_${index}`" class="text-xs">Quantity</Label>
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
                    <Button type="submit" :disabled="!canSubmit" variant="default">Submit Order</Button>
                    <Link :href="route('orders.index')">
                        <Button type="button" variant="ghost">Cancel</Button>
                    </Link>
                </CardFooter>
            </Card>
        </form>
    </AppLayout>
</template>

