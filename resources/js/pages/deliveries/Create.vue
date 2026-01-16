<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, watch, onMounted, ref } from 'vue';
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

interface RefundRequest {
    id: number;
    tracking_number: string;
    invoice_id: number;
    customer_id: number;
    product_name: string;
    quantity: number;
    delivery_address: string;
    contact_person: string;
    contact_phone: string;
    notes: string;
}

interface Location {
    lat: number;
    lng: number;
}

const props = withDefaults(defineProps<{ 
    customers: Customer[];
    invoices: Invoice[];
    preselectedInvoiceId?: number | null;
    refundRequest?: RefundRequest | null;
    refundType?: string | null;
    baseDeliveryFee?: number;
    ratePerKm?: number;
}>(), {
    baseDeliveryFee: 50.00,
    ratePerKm: 10.00
});

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

// Check if user is staff (Staff or Admin)
const page = usePage();
const isStaff = computed(() => {
    const userRoles = (page.props as any).auth?.userRoles || [];
    return Array.isArray(userRoles) && (userRoles.includes('Admin') || userRoles.includes('Staff'));
});

// Delivery fee calculation
const routeDistance = ref<number | null>(null);

// Get delivery origin location from page props (set in admin settings)
const deliveryOriginLocation = computed(() => {
    const origin = (page.props as any).deliveryOriginLocation;
    if (origin && typeof origin.lat === 'number' && typeof origin.lng === 'number') {
        return { lat: origin.lat, lng: origin.lng };
    }
    return null;
});

// Calculate distance between two coordinates using Haversine formula (straight-line)
// This matches the calculation used in order forms
function calculateStraightLineDistance(lat1: number, lon1: number, lat2: number, lon2: number): number {
    const R = 6371; // Radius of the Earth in kilometers
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = 
        Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
        Math.sin(dLon / 2) * Math.sin(dLon / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    const distance = R * c; // Distance in kilometers
    return distance;
}

// Get selected customer's location
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

// Calculate initial delivery fee using straight-line distance (same as order forms)
const straightLineDistance = computed(() => {
    if (!selectedCustomerLocation.value || !deliveryOriginLocation.value) {
        return null;
    }
    
    const loc1 = deliveryOriginLocation.value;
    const loc2 = selectedCustomerLocation.value;
    
    if (!loc1.lat || !loc1.lng || !loc2.lat || !loc2.lng) {
        return null;
    }
    
    return calculateStraightLineDistance(
        loc1.lat,
        loc1.lng,
        loc2.lat,
        loc2.lng
    );
});

// Calculate initial delivery fee based on straight-line distance (same as order forms)
const initialDeliveryFee = computed(() => {
    const baseFee = props.baseDeliveryFee || 50.00;
    const ratePerKm = props.ratePerKm || 10.00;
    
    if (!straightLineDistance.value || straightLineDistance.value <= 0) {
        return baseFee;
    }
    
    const calculatedFee = baseFee + (straightLineDistance.value * ratePerKm);
    return Math.max(calculatedFee, baseFee); // Ensure minimum fee
});

// Delivery fee rates (using base fee and rate from settings)
const BASE_DELIVERY_FEE = props.baseDeliveryFee;
const MINIMUM_FEE = props.baseDeliveryFee;

// Calculate delivery fee based on distance (for route distance updates)
function calculateDeliveryFee(distance: number | null): number {
    if (!distance || distance <= 0) {
        // If no route distance, use straight-line distance calculation
        return initialDeliveryFee.value;
    }
    
    // Use route distance for calculation
    const calculatedFee = BASE_DELIVERY_FEE + (distance * props.ratePerKm);
    return Math.max(calculatedFee, MINIMUM_FEE); // Ensure minimum fee
}

// Watch for initial delivery fee changes (when customer location changes)
watch(() => initialDeliveryFee.value, (newFee) => {
    // Only update if customer is selected and fee field is empty or matches base fee
    // This allows manual overrides
    if (form.customer_id && newFee != null) {
        const currentFee = parseFloat(form.delivery_fee) || 0;
        const baseFee = props.baseDeliveryFee || 0;
        if (!form.delivery_fee || currentFee === baseFee || currentFee === 0) {
            form.delivery_fee = newFee.toFixed(2);
        }
    }
});

// Handle distance calculated from map (route distance - optional override)
function handleDistanceCalculated(distance: number | null) {
    routeDistance.value = distance;
    
    // Route distance is only used as an optional override
    // The initial fee is calculated using straight-line distance (same as order forms)
    // Staff can manually adjust the fee if needed based on actual route distance
}

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
    
    // Delivery fee should be auto-calculated or manually set before submission
    // For refund requests, the fee will be auto-calculated from route distance if not manually set
    
    // Transform form data to include refund_request_id if present
    form.transform((data) => {
        if (props.refundRequest) {
            return {
                ...data,
                refund_request_id: props.refundRequest.id,
            };
        }
        return data;
    }).post(route('deliveries.store'), {
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

// Get selected customer for address
const selectedCustomer = computed(() => {
    if (!form.customer_id) return null;
    return props.customers.find(c => c.id === form.customer_id) || null;
});

// Pre-fill form when invoice is preselected or refund request is provided
onMounted(() => {
    // Handle refund request context first (takes priority)
    if (props.refundRequest) {
        const refundReq = props.refundRequest;
        form.invoice_id = refundReq.invoice_id;
        form.customer_id = refundReq.customer_id;
        form.delivery_address = refundReq.delivery_address;
        form.contact_person = refundReq.contact_person;
        form.contact_phone = normalizePhoneNumber(refundReq.contact_phone);
        form.notes = refundReq.notes;
        // Delivery fee will be auto-calculated when route distance is calculated
        
        // Set default delivery date to tomorrow
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        form.delivery_date = tomorrow.toISOString().split('T')[0];
        
        // Set default delivery time
        form.delivery_time = '09:00 AM - 12:00 PM';
    } else if (props.preselectedInvoiceId) {
        // Handle regular invoice preselection
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
// Also initialize delivery fee using straight-line distance (same as order forms)
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
            
            // Initialize delivery fee using straight-line distance (same as order forms)
            // This ensures consistency with the order creation form
            if (initialDeliveryFee.value != null) {
                form.delivery_fee = initialDeliveryFee.value.toFixed(2);
            } else if (props.baseDeliveryFee != null) {
                // Fallback to base fee if distance cannot be calculated
                form.delivery_fee = props.baseDeliveryFee.toFixed(2);
            } else {
                form.delivery_fee = '50.00'; // Default fallback
            }
        }
    } else {
        // Reset to base fee if no customer selected
        if (props.baseDeliveryFee != null) {
            form.delivery_fee = props.baseDeliveryFee.toFixed(2);
        } else {
            form.delivery_fee = '50.00'; // Default fallback
        }
    }
}, { immediate: true });

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
        
        <!-- Refund Request Banner -->
        <div v-if="props.refundRequest" class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-sm font-semibold text-blue-900 mb-1">Return Pickup for Refund Request</h3>
                    <p class="text-sm text-blue-700">
                        Scheduling delivery for return pickup of refund request <strong>{{ props.refundRequest.tracking_number }}</strong>.
                        Product: <strong>{{ props.refundRequest.product_name }}</strong> (Qty: {{ props.refundRequest.quantity }})
                    </p>
                </div>
            </div>
        </div>

        <form @submit.prevent="submit()" class="space-y-6">
            <!-- Delivery Details -->
            <Card>
                <CardHeader>
                    <CardTitle>{{ props.refundRequest ? 'Return Pickup Details' : 'Delivery Details' }}</CardTitle>
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
                            <Label for="invoice_id">Invoice *</Label>
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
                        <Label for="delivery_address">Delivery Address *</Label>
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
                            <Label for="contact_person">Contact Person *</Label>
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
                            <Label for="contact_phone">Contact Phone *</Label>
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
                            <Label for="delivery_date">Delivery Date *</Label>
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
                            <Label for="delivery_time">Delivery Time *</Label>
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
                            <Label for="status">Status *</Label>
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
                    
                    <div :class="props.refundRequest ? 'grid grid-cols-1 gap-4' : 'grid grid-cols-1 md:grid-cols-2 gap-4'">
                        <!-- Delivery fee field - now available for refund requests too -->
                        <div>
                            <Label for="delivery_fee">Delivery Fee <span v-if="!props.refundRequest">*</span></Label>
                            <input
                                v-model="form.delivery_fee"
                                type="number"
                                id="delivery_fee"
                                min="0"
                                step="0.01"
                                :readonly="isStaff"
                                :disabled="isStaff"
                                class="w-full rounded-md border border-input bg-transparent px-3 py-2 mt-1 text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none disabled:opacity-50 disabled:cursor-not-allowed"
                                placeholder="0.00"
                                :required="!props.refundRequest"
                            />
                            <div v-if="props.refundRequest" class="text-xs text-muted-foreground mt-1">
                                Optional: Set delivery fee for refund return pickup
                            </div>
                            <div v-else-if="isStaff && routeDistance !== null" class="text-xs text-muted-foreground mt-1">
                                Distance: {{ routeDistance }} km • Fee: ₱{{ BASE_DELIVERY_FEE.toFixed(2) }} base + ₱{{ RATE_PER_KM.toFixed(2) }}/km
                            </div>
                            <div v-else-if="isStaff && routeDistance === null" class="text-xs text-muted-foreground mt-1">
                                Fee will be calculated automatically based on delivery distance
                            </div>
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
                        @distance-calculated="handleDistanceCalculated"
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