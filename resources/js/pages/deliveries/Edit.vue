<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, watch, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Select, SearchSelect } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardFooter from '@/components/ui/card/CardFooter.vue';
import Label from '@/components/ui/label/Label.vue';
import PhoneInput from '@/components/ui/input/PhoneInput.vue';
import Swal from 'sweetalert2';
import { type BreadcrumbItem } from '@/types';
import InputError from '@/components/InputError.vue';
import DeliveryRouteMap from '@/components/DeliveryRouteMap.vue';

interface Customer {
    id: number;
    name: string;
    company_name?: string;
    address?: string;
    location?: { lat: number; lng: number } | null;
}

interface Invoice {
    id: number;
    customer_id: number;
    total_amount: number;
    customer: Customer;
}

interface Delivery {
    id: number;
    customer_id: number;
    invoice_id?: number;
    delivery_address: string;
    contact_person: string;
    contact_phone: string;
    delivery_date: string | null;
    delivery_time: string;
    status: string;
    notes?: string;
    delivery_fee: number;
    proof_of_delivery_url?: string;
    customer: Customer;
    invoice?: Invoice;
}

const props = defineProps<{ 
    delivery: Delivery;
    customers: Customer[];
    invoices: Invoice[];
}>();

// Format date for HTML date input (YYYY-MM-DD)
function formatDateForInput(date: string | null | undefined): string {
    if (!date) return '';
    
    // If already in YYYY-MM-DD format, return as is
    if (typeof date === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(date)) {
        return date;
    }
    
    // Try to parse and format the date
    try {
        const dateObj = new Date(date);
        if (isNaN(dateObj.getTime())) {
            return '';
        }
        const year = dateObj.getFullYear();
        const month = String(dateObj.getMonth() + 1).padStart(2, '0');
        const day = String(dateObj.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    } catch (e) {
        return '';
    }
}

// Helper function to normalize phone number to +63XXXXXXXXXX format
function normalizePhoneNumber(phone: string | null | undefined): string {
    if (!phone) return '';
    
    // Remove all non-digit characters
    let digits = phone.replace(/[^0-9]/g, '');
    
    // Handle different formats:
    // - If starts with 63, remove it (country code)
    // - If starts with 0, remove it (local format)
    if (digits.startsWith('63')) {
        digits = digits.substring(2);
    } else if (digits.startsWith('0')) {
        digits = digits.substring(1);
    }
    
    // Ensure we have exactly 10 digits
    digits = digits.substring(0, 10);
    
    // Return in +63XXXXXXXXXX format if we have 10 digits
    if (digits.length === 10) {
        return '+63' + digits;
    }
    
    return phone; // Return original if can't normalize
}

const form = useForm({
    customer_id: props.delivery.customer_id,
    invoice_id: props.delivery.invoice_id || null,
    delivery_address: props.delivery.delivery_address,
    contact_person: props.delivery.contact_person,
    contact_phone: normalizePhoneNumber(props.delivery.contact_phone),
    delivery_date: formatDateForInput(props.delivery.delivery_date),
    delivery_time: props.delivery.delivery_time,
    status: props.delivery.status,
    notes: props.delivery.notes || '',
    delivery_fee: props.delivery.delivery_fee.toString(),
    proof_of_delivery: null as File | null,
});

// Set minimum date to today
const today = new Date().toISOString().split('T')[0];

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Deliveries',
        href: '/deliveries',
    },
    {
        title: 'Edit Delivery',
        href: `/deliveries/${props.delivery.id}/edit`,
    }
];


function submit() {
    // Normalize phone number before validation
    if (form.contact_phone) {
        form.contact_phone = normalizePhoneNumber(form.contact_phone);
    }
    
    // Validate Philippine mobile format on the client-side (10 digits after +63)
    const phMobileRegex = /^\+63\d{10}$/;
    if (form.contact_phone && !phMobileRegex.test(form.contact_phone)) {
        form.setError('contact_phone', 'Enter a valid 10-digit Philippine mobile number.');
        return;
    }
    
    form.put(route('deliveries.update', props.delivery.id), {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Delivery updated successfully',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
            });
        },
    });
}

// Customer options
const customerOptions = computed(() => [
    { value: null, label: 'Select customer' },
    ...props.customers.map(customer => ({
        value: customer.id,
        label: `${customer.name}${customer.company_name ? ` (${customer.company_name})` : ''}`
    }))
]);

// Invoice options (filtered by selected customer)
const invoiceOptions = computed(() => {
    if (!form.customer_id) {
        return [{ value: null, label: 'Select invoice (select customer first)' }];
    }
    
    const customerInvoices = props.invoices.filter(invoice => invoice.customer_id === form.customer_id);
    
    return [
        { value: null, label: 'Select invoice (optional)' },
        ...customerInvoices.map(invoice => ({
            value: invoice.id,
            label: `Invoice #${invoice.id} - ${formatCurrency(invoice.total_amount)}`
        }))
    ];
});

// Status options
const statusOptions = [
    { value: 'pending', label: 'Out for Delivery' },
    { value: 'completed', label: 'Delivered' },
    { value: 'cancelled', label: 'Cancelled' }
];

// Delivery time options
const deliveryTimeOptions = [
    { value: '09:00 AM - 12:00 PM', label: '09:00 AM - 12:00 PM' },
    { value: '12:00 PM - 03:00 PM', label: '12:00 PM - 03:00 PM' },
    { value: '03:00 PM - 06:00 PM', label: '03:00 PM - 06:00 PM' },
    { value: '06:00 PM - 09:00 PM', label: '06:00 PM - 09:00 PM' },
    { value: 'Custom', label: 'Custom' }
];

function formatCurrency(amount: number) {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2
    }).format(amount);
}

// Get selected customer location
const selectedCustomerLocation = computed(() => {
    if (!form.customer_id) return null;
    const customer = props.customers.find(c => c.id === form.customer_id);
    
    if (!customer?.location) return null;
    
    // Handle different location data formats
    let location = customer.location;
    
    // If it's a string, try to parse it
    if (typeof location === 'string') {
        try {
            location = JSON.parse(location);
        } catch (e) {
            console.warn('Failed to parse customer location string:', location);
            return null;
        }
    }
    
    // Validate the location object
    // Handle both number and string coordinates (convert strings to numbers)
    if (location) {
        let lat = location.lat;
        let lng = location.lng;
        
        // Convert strings to numbers if needed
        if (typeof lat === 'string') {
            lat = parseFloat(lat);
        }
        if (typeof lng === 'string') {
            lng = parseFloat(lng);
        }
        
        if (typeof lat === 'number' && 
            typeof lng === 'number' && 
            !isNaN(lat) && 
            !isNaN(lng) &&
            lat >= -90 && lat <= 90 &&
            lng >= -180 && lng <= 180 &&
            (lat != 0 || lng != 0)) {
            return { lat, lng };
        }
    }
    
    console.warn('Invalid customer location data:', customer.location);
    return null;
});

// Get selected customer for address
const selectedCustomer = computed(() => {
    if (!form.customer_id) return null;
    return props.customers.find(c => c.id === form.customer_id) || null;
});

// Auto-fill delivery address when customer is selected
watch(() => form.customer_id, (newCustomerId) => {
    if (newCustomerId) {
        const customer = props.customers.find(c => c.id === newCustomerId);
        if (customer?.address && !form.delivery_address) {
            form.delivery_address = customer.address;
        }
    }
});


// Image preview state
const imagePreview = ref<string | null>(null);
const fileInputRef = ref<HTMLInputElement | null>(null);

// Handle file selection
const handleFileSelect = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    
    if (file) {
        // Validate file type
        if (!file.type.startsWith('image/')) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid File Type',
                text: 'Please select an image file (JPEG, PNG, GIF, etc.)',
            });
            return;
        }
        
        // Validate file size (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
            Swal.fire({
                icon: 'error',
                title: 'File Too Large',
                text: 'Please select an image smaller than 5MB',
            });
            return;
        }
        
        form.proof_of_delivery = file;
        
        // Create preview
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
};

// Remove image
const removeImage = () => {
    form.proof_of_delivery = null;
    imagePreview.value = null;
    // Reset the file input
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Edit Delivery" />
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">Edit Delivery</h1>
        </div>
        
        <form @submit.prevent="submit()" class="space-y-6">
            <!-- Delivery Details -->
            <Card>
                <CardHeader>
                    <CardTitle>Delivery Details</CardTitle>
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
                            <Label for="invoice_id">Invoice (Optional)</Label>
                            <Select
                                v-model="form.invoice_id"
                                :options="invoiceOptions"
                                placeholder="Select invoice"
                                class="mt-1"
                            />
                            <InputError :message="form.errors.invoice_id" />
                        </div>
                    </div>
                    
                    <div>
                        <Label for="delivery_address">Delivery Address</Label>
                        <textarea
                            v-model="form.delivery_address"
                            id="delivery_address"
                            class="w-full rounded-md border border-input bg-transparent px-3 py-2 mt-1 text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none"
                            rows="3"
                            placeholder="Enter complete delivery address"
                            required
                        />
                        <InputError :message="form.errors.delivery_address" />
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <Label for="contact_person">Contact Person</Label>
                            <input
                                v-model="form.contact_person"
                                type="text"
                                id="contact_person"
                                class="w-full rounded-md border border-input bg-transparent px-3 py-2 mt-1 text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none"
                                placeholder="Enter contact person name"
                                required
                            />
                            <InputError :message="form.errors.contact_person" />
                        </div>
                        
                        <div>
                            <Label for="contact_phone">Contact Phone</Label>
                            <PhoneInput
                                v-model="form.contact_phone"
                                id="contact_phone"
                                placeholder="XXXXXXXXXX"
                                required
                                class="mt-1"
                            />
                            <InputError :message="form.errors.contact_phone" />
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <Label for="delivery_date">Delivery Date</Label>
                            <input
                                v-model="form.delivery_date"
                                type="date"
                                id="delivery_date"
                                :min="today"
                                class="w-full rounded-md border border-input bg-transparent px-3 py-2 mt-1 text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none"
                                required
                            />
                            <InputError :message="form.errors.delivery_date" />
                        </div>
                        
                        <div>
                            <Label for="delivery_time">Delivery Time</Label>
                            <Select
                                v-model="form.delivery_time"
                                :options="deliveryTimeOptions"
                                placeholder="Select delivery time"
                                class="mt-1"
                                required
                            />
                            <InputError :message="form.errors.delivery_time" />
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
                            <Label for="delivery_fee">Delivery Fee</Label>
                            <input
                                v-model="form.delivery_fee"
                                type="number"
                                id="delivery_fee"
                                min="0"
                                step="0.01"
                                class="w-full rounded-md border border-input bg-transparent px-3 py-2 mt-1 text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none"
                                placeholder="0.00"
                                required
                            />
                            <InputError :message="form.errors.delivery_fee" />
                        </div>
                        
                        <div>
                            <Label for="notes">Notes</Label>
                            <textarea
                                v-model="form.notes"
                                id="notes"
                                class="w-full rounded-md border border-input bg-transparent px-3 py-2 mt-1 text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none"
                                rows="3"
                                placeholder="Enter any additional notes"
                            />
                            <InputError :message="form.errors.notes" />
                        </div>
                    </div>
                    
                    <!-- Proof of Delivery -->
                    <div>
                        <Label for="proof_of_delivery">Proof of Delivery</Label>
                        <div class="mt-1 space-y-4">
                            <!-- File Input -->
                            <div class="flex items-center gap-4">
                                <input
                                    ref="fileInputRef"
                                    type="file"
                                    id="proof_of_delivery"
                                    accept="image/*"
                                    @change="handleFileSelect"
                                    class="hidden"
                                />
                                <Button 
                                    type="button" 
                                    variant="outline" 
                                    @click="fileInputRef?.click()"
                                    class="flex items-center gap-2"
                                >
                                    <Icon name="upload" class="h-4 w-4" />
                                    Choose Image
                                </Button>
                                <span class="text-sm text-muted-foreground">
                                    Accepted formats: JPEG, PNG, GIF (Max 5MB)
                                </span>
                            </div>
                            
                            <!-- Image Preview -->
                            <div v-if="imagePreview" class="relative inline-block">
                                <img 
                                    :src="imagePreview" 
                                    alt="Proof of delivery preview" 
                                    class="max-w-xs rounded-lg border border-border shadow-sm"
                                />
                                <Button 
                                    type="button" 
                                    variant="destructive" 
                                    size="sm"
                                    @click="removeImage"
                                    class="absolute -top-2 -right-2 h-6 w-6 rounded-full p-0"
                                >
                                    <Icon name="x" class="h-3 w-3" />
                                </Button>
                            </div>
                            
                            <!-- Existing Image (if any) -->
                            <div v-if="props.delivery.proof_of_delivery_url && !imagePreview" class="relative inline-block">
                                <img 
                                    :src="props.delivery.proof_of_delivery_url" 
                                    alt="Current proof of delivery" 
                                    class="max-w-xs rounded-lg border border-border shadow-sm"
                                />
                                <div class="mt-2 text-sm text-muted-foreground">
                                    Current proof of delivery image
                                </div>
                            </div>
                        </div>
                        <InputError :message="form.errors.proof_of_delivery" />
                    </div>
                </CardContent>
            </Card>

            <!-- Route Map -->
            <Card>
                <CardHeader>
                    <CardTitle>Delivery Route</CardTitle>
                </CardHeader>
                <CardContent>
                    <DeliveryRouteMap 
                        :customer-location="selectedCustomerLocation"
                        :delivery-address="form.delivery_address || selectedCustomer?.address"
                        map-height="400px"
                    />
                </CardContent>
            </Card>

            <!-- Form Actions -->
            <Card>
                <CardFooter class="flex gap-2 justify-end">
                    <Button type="submit" variant="default">Update Delivery</Button>
                    <Link :href="route('deliveries.index')">
                        <Button type="button" variant="ghost">Cancel</Button>
                    </Link>
                </CardFooter>
            </Card>
        </form>
    </AppLayout>
</template> 