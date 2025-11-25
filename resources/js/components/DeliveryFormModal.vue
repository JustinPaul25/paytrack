<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, watch, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Select, SearchSelect } from '@/components/ui/select';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import Label from '@/components/ui/label/Label.vue';
import PhoneInput from '@/components/ui/input/PhoneInput.vue';
import Swal from 'sweetalert2';
import InputError from '@/components/InputError.vue';
import DeliveryRouteMap from '@/components/DeliveryRouteMap.vue';

interface Customer {
    id: number;
    name: string;
    company_name?: string;
    address?: string;
    phone?: string;
    location?: { lat: number; lng: number } | null;
}

interface Invoice {
    id: number;
    customer_id: number;
    total_amount: number;
    reference_number: string;
    customer: Customer;
}

interface Props {
    open: boolean;
    invoice: Invoice;
    customers: Customer[];
}

const props = defineProps<Props>();
const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const form = useForm({
    customer_id: props.invoice.customer_id,
    invoice_id: props.invoice.id,
    delivery_address: props.invoice.customer?.address || '',
    contact_person: props.invoice.customer?.name || '',
    contact_phone: normalizePhoneNumber(props.invoice.customer?.phone) || '',
    delivery_date: '',
    delivery_time: '',
    status: 'pending',
    notes: '',
    delivery_fee: '',
});

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

// Reset form when modal opens
watch(() => props.open, (isOpen) => {
    if (isOpen) {
        form.reset();
        form.customer_id = props.invoice.customer_id;
        form.invoice_id = props.invoice.id;
        form.delivery_address = props.invoice.customer?.address || '';
        form.contact_person = props.invoice.customer?.name || '';
        form.contact_phone = normalizePhoneNumber(props.invoice.customer?.phone) || '';
        form.clearErrors();
    }
});



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
            emit('update:open', false);
            form.reset();
        },
    });
}

function closeModal() {
    emit('update:open', false);
    form.clearErrors();
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-w-4xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>Out for Delivery</DialogTitle>
                <DialogDescription>
                    Create a delivery for invoice {{ invoice.reference_number }}
                </DialogDescription>
            </DialogHeader>
            
            <form @submit.prevent="submit()" class="space-y-6">
                <!-- Delivery Details -->
                <div class="space-y-4">
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

                <!-- Route Map -->
                <div v-if="selectedCustomerLocation || form.delivery_address || selectedCustomer?.address">
                    <Label class="mb-2 block">Delivery Route</Label>
                    <DeliveryRouteMap 
                        :customer-location="selectedCustomerLocation"
                        :delivery-address="form.delivery_address || selectedCustomer?.address"
                        map-height="300px"
                    />
                </div>

                <DialogFooter class="gap-2">
                    <Button type="button" variant="ghost" @click="closeModal">Cancel</Button>
                    <Button type="submit" variant="default" :disabled="form.processing">
                        Create Delivery
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>


