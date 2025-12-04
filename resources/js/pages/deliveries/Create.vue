<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, watch, onMounted } from 'vue';
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
    purok?: string;
    barangay?: string;
    city_municipality?: string;
    province?: string;
    phone?: string;
    location?: { lat: number; lng: number } | null;
}

interface Invoice {
    id: number;
    customer_id: number;
    total_amount: number;
    reference_number: string;
    customer: Customer | null;
}

const props = defineProps<{ 
    customers: Customer[];
    invoices: Invoice[];
    preselectedInvoiceId?: number | null;
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


function submit() {
    // Require invoice selection on the client-side
    if (!form.invoice_id) {
        form.setError('invoice_id', 'Invoice is required.');
        return;
    }

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
        // If there's a preselected invoice, show it even without customer selected
        if (props.preselectedInvoiceId) {
            const preselectedInvoice = props.invoices.find(inv => inv.id === props.preselectedInvoiceId);
            if (preselectedInvoice) {
                return [
                    { value: null, label: 'Select invoice' },
                    {
                        value: preselectedInvoice.id,
                        label: `${preselectedInvoice.reference_number}`
                    }
                ];
            }
        }
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

// Set minimum date to today
const today = new Date().toISOString().split('T')[0];

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

// Helper function to concatenate address fields (purok, barangay, city_municipality, province)
function formatDeliveryAddress(customer: Customer | null | undefined): string {
    if (!customer) return '';
    
    const parts: string[] = [];
    
    if (customer.purok) {
        parts.push(customer.purok);
    }
    if (customer.barangay) {
        parts.push(customer.barangay);
    }
    if (customer.city_municipality) {
        parts.push(customer.city_municipality);
    }
    if (customer.province) {
        parts.push(customer.province);
    }
    
    return parts.length > 0 ? parts.join(', ') : '';
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

// Pre-fill form when invoice is preselected
onMounted(() => {
    if (props.preselectedInvoiceId) {
        const invoice = props.invoices.find(inv => inv.id === props.preselectedInvoiceId);
        if (invoice) {
            form.invoice_id = invoice.id;
            form.customer_id = invoice.customer_id;
            
            // Auto-fill customer details
            const customer = props.customers.find(c => c.id === invoice.customer_id);
            if (customer) {
                const deliveryAddress = formatDeliveryAddress(customer);
                if (deliveryAddress && !form.delivery_address) {
                    form.delivery_address = deliveryAddress;
                }
                if (customer.name && !form.contact_person) {
                    form.contact_person = customer.name;
                }
                if (customer.phone && !form.contact_phone) {
                    form.contact_phone = normalizePhoneNumber(customer.phone);
                }
            }
        }
    }
});

// Auto-fill delivery address and contact info when customer is selected
watch(() => form.customer_id, (newCustomerId) => {
    if (newCustomerId) {
        const customer = props.customers.find(c => c.id === newCustomerId);
        if (customer) {
            const deliveryAddress = formatDeliveryAddress(customer);
            if (deliveryAddress && !form.delivery_address) {
                form.delivery_address = deliveryAddress;
            }
            if (customer.name && !form.contact_person) {
                form.contact_person = customer.name;
            }
            if (customer.phone && !form.contact_phone) {
                form.contact_phone = normalizePhoneNumber(customer.phone);
            }
        }
    }
});

// Auto-fill customer details when invoice is selected
watch(() => form.invoice_id, (newInvoiceId) => {
    if (newInvoiceId) {
        const invoice = props.invoices.find(inv => inv.id === newInvoiceId);
        if (invoice?.customer) {
            form.customer_id = invoice.customer_id;
            // Find the full customer data from customers list to get address fields
            const customer = props.customers.find(c => c.id === invoice.customer_id);
            if (customer) {
                const deliveryAddress = formatDeliveryAddress(customer);
                if (deliveryAddress && !form.delivery_address) {
                    form.delivery_address = deliveryAddress;
                }
            }
            if (invoice.customer.name && !form.contact_person) {
                form.contact_person = invoice.customer.name;
            }
            if (invoice.customer.phone && !form.contact_phone) {
                form.contact_phone = normalizePhoneNumber(invoice.customer.phone);
            }
        }
    }
});

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
                        :delivery-address="form.delivery_address || formatDeliveryAddress(selectedCustomer)"
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