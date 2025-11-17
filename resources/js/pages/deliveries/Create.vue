<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
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
    reference_number: string;
    customer: Customer;
}

const props = defineProps<{ 
    customers: Customer[];
    invoices: Invoice[];
}>();

const form = useForm({
    customer_id: null as number | null,
    invoice_id: null as number | null,
    delivery_address: '',
    contact_person: '',
    contact_phone: '',
    delivery_date: '',
    delivery_time: '',
    status: 'pending',
    notes: '',
    delivery_fee: '',
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Deliveries',
        href: '/deliveries',
    },
    {
        title: 'Create Delivery',
        href: '/deliveries/create',
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
    // Require invoice selection on the client-side
    if (!form.invoice_id) {
        form.setError('invoice_id', 'Invoice is required.');
        return;
    }

    // Validate Philippine mobile format on the client-side
    const phMobileRegex = /^(?:\+?63|0)9\d{9}$/;
    if (!phMobileRegex.test(form.contact_phone || '')) {
        form.setError('contact_phone', 'Enter a valid PH mobile number (09XXXXXXXXX or +639XXXXXXXXX).');
        return;
    }
    
    form.post(route('deliveries.store'), {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Delivery created successfully',
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
        { value: null, label: 'Select invoice' },
        ...customerInvoices.map(invoice => ({
            value: invoice.id,
            label: `${invoice.reference_number}`
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

// Set minimum date to today
const today = new Date().toISOString().split('T')[0];

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
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Create Delivery" />
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">Create Delivery</h1>
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
                            <Label for="invoice_id">Invoice</Label>
                            <Select
                                v-model="form.invoice_id"
                                :options="invoiceOptions"
                                placeholder="Select invoice"
                                class="mt-1"
                                required
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
                    <Button type="submit" variant="default">Create Delivery</Button>
                    <Link :href="route('deliveries.index')">
                        <Button type="button" variant="ghost">Cancel</Button>
                    </Link>
                </CardFooter>
            </Card>
        </form>
    </AppLayout>
</template> 