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
    delivery_date: string;
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

const form = useForm({
    customer_id: props.delivery.customer_id,
    invoice_id: props.delivery.invoice_id || null,
    delivery_address: props.delivery.delivery_address,
    contact_person: props.delivery.contact_person,
    contact_phone: props.delivery.contact_phone,
    delivery_date: props.delivery.delivery_date,
    delivery_time: props.delivery.delivery_time,
    status: props.delivery.status,
    notes: props.delivery.notes || '',
    delivery_fee: props.delivery.delivery_fee.toString(),
    proof_of_delivery: null as File | null,
});

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

function onContactPhoneInput(e: Event) {
    const target = e.target as HTMLInputElement;
    let value = target.value;
    
    // Remove all non-digit and non-plus characters
    value = value.replace(/[^0-9+]/g, '');
    
    // If starts with +, ensure it's +63
    if (value.startsWith('+')) {
        if (value.length > 1 && !value.startsWith('+63')) {
            value = '+63' + value.substring(1).replace(/[^0-9]/g, '');
        }
        // Limit to +639XXXXXXXXX (13 chars: +639 + 9 digits)
        if (value.length > 13) {
            value = value.substring(0, 13);
        }
    } else {
        // If starts with 0, ensure it's 09
        if (value.length > 0 && value[0] === '0' && value.length > 1 && value[1] !== '9') {
            value = '09' + value.substring(2).replace(/[^0-9]/g, '');
        }
        // If starts with 63, convert to +63
        if (value.startsWith('63')) {
            value = '+' + value;
        }
        // Limit to 11 digits for 09XXXXXXXXX format
        if (value.length > 11 && !value.startsWith('+')) {
            value = value.substring(0, 11);
        }
    }
    
    // Update the input and form
    if (value !== target.value) {
        target.value = value;
    }
    form.contact_phone = value;
}

function submit() {
    // Validate Philippine mobile format on the client-side
    const phMobileRegex = /^(?:\+?63|0)9\d{9}$/;
    if (!phMobileRegex.test(form.contact_phone || '')) {
        form.setError('contact_phone', 'Enter a valid PH mobile number (09XXXXXXXXX or +639XXXXXXXXX).');
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
    { value: 'pending', label: 'Pending' },
    { value: 'completed', label: 'Completed' },
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
    if (location && 
        typeof location.lat === 'number' && 
        typeof location.lng === 'number' && 
        !isNaN(location.lat) && 
        !isNaN(location.lng) &&
        location.lat >= -90 && location.lat <= 90 &&
        location.lng >= -180 && location.lng <= 180) {
        return location;
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

// Get current location address using reverse geocoding
async function getCurrentLocationAddress() {
    try {
        if (!navigator.geolocation) {
            Swal.fire({
                icon: 'error',
                title: 'Geolocation not supported',
                text: 'Your browser does not support geolocation.',
            });
            return;
        }

        const position = await new Promise<GeolocationPosition>((resolve, reject) => {
            navigator.geolocation.getCurrentPosition(resolve, reject, {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 60000
            });
        });

        const { latitude, longitude } = position.coords;
        
        // Use OpenStreetMap Nominatim for reverse geocoding
        const response = await fetch(
            `https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}&addressdetails=1`
        );
        
        if (!response.ok) {
            throw new Error('Failed to get address');
        }
        
        const data = await response.json();
        
        if (data.display_name) {
            form.delivery_address = data.display_name;
            Swal.fire({
                icon: 'success',
                title: 'Location Retrieved',
                text: 'Current location address has been filled in.',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
            });
        }
    } catch (error) {
        console.error('Error getting location address:', error);
        Swal.fire({
            icon: 'error',
            title: 'Location Error',
            text: 'Failed to get your current location address. Please enter it manually.',
        });
    }
}

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
                        <div class="flex gap-2 mt-1">
                            <textarea
                                v-model="form.delivery_address"
                                id="delivery_address"
                                class="flex-1 rounded-md border border-input bg-transparent px-3 py-2 text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none"
                                rows="3"
                                placeholder="Enter complete delivery address"
                                required
                            />
                            <Button 
                                type="button" 
                                variant="outline" 
                                @click="getCurrentLocationAddress"
                                class="h-fit px-3 py-2"
                            >
                                📍 Get Current Location
                            </Button>
                        </div>
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
                            <input
                                v-model="form.contact_phone"
                                type="tel"
                                id="contact_phone"
                                inputmode="numeric"
                                pattern="^(?:\+?63|0)9\d{9}$"
                                maxlength="13"
                                class="w-full rounded-md border border-input bg-transparent px-3 py-2 mt-1 text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none"
                                placeholder="09XXXXXXXXX or +639XXXXXXXXX"
                                @input="onContactPhoneInput"
                                required
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