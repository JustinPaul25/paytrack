<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardFooter from '@/components/ui/card/CardFooter.vue';
import { Button } from '@/components/ui/button';
import { Select } from '@/components/ui/select';
import Label from '@/components/ui/label/Label.vue';
import InputError from '@/components/InputError.vue';
import Swal from 'sweetalert2';

interface Delivery {
    id: number;
    delivery_address: string;
    contact_person: string;
    contact_phone: string;
    delivery_date: string;
    delivery_time: string;
    status: string;
    delivery_fee: number;
    notes?: string;
    proof_of_delivery_url?: string | null;
    invoice?: { id:number; reference_number?:string; total_amount:number };
    customer?: { id:number; name:string; company_name?:string; email?:string; phone?:string; address?:string };
}

const props = defineProps<{ delivery: Delivery }>();

function formatCurrency(amount: number) {
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', minimumFractionDigits: 2 }).format(amount);
}

function formatDate(dateString: string) {
    return new Date(dateString).toLocaleString();
}

function getStatusLabel(status: string): string {
    switch (status) {
        case 'pending': return 'Out for Delivery';
        case 'completed': return 'Delivered';
        case 'cancelled': return 'Cancelled';
        default: return status.charAt(0).toUpperCase() + status.slice(1);
    }
}

const showRescheduleModal = ref(false);
const today = new Date().toISOString().split('T')[0];

// Format date for HTML date input (YYYY-MM-DD)
function formatDateForInput(date: string | null | undefined): string {
    if (!date) return '';
    
    if (typeof date === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(date)) {
        return date;
    }
    
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

// Delivery time options
const deliveryTimeOptions = [
    { value: '09:00 AM - 12:00 PM', label: '09:00 AM - 12:00 PM' },
    { value: '12:00 PM - 03:00 PM', label: '12:00 PM - 03:00 PM' },
    { value: '03:00 PM - 06:00 PM', label: '03:00 PM - 06:00 PM' },
    { value: '06:00 PM - 09:00 PM', label: '06:00 PM - 09:00 PM' },
    { value: 'Custom', label: 'Custom' }
];

const rescheduleForm = useForm({
    delivery_date: formatDateForInput(props.delivery.delivery_date),
    delivery_time: props.delivery.delivery_time,
    reason: '',
});

function openRescheduleModal() {
    rescheduleForm.delivery_date = formatDateForInput(props.delivery.delivery_date);
    rescheduleForm.delivery_time = props.delivery.delivery_time;
    rescheduleForm.reason = '';
    showRescheduleModal.value = true;
}

function closeRescheduleModal() {
    showRescheduleModal.value = false;
    rescheduleForm.reset();
}

function submitReschedule() {
    rescheduleForm.post(route('deliveries.reschedule', props.delivery.id), {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Delivery rescheduled successfully',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
            });
            closeRescheduleModal();
        },
        onError: () => {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to reschedule delivery. Please check the form for errors.',
            });
        },
    });
}
</script>

<template>
    <AppLayout>
        <Head :title="`Delivery #${props.delivery.id}`" />

        <div class="flex items-center justify-between my-6">
            <h1 class="text-2xl font-bold">Delivery #{{ props.delivery.id }}</h1>
            <div class="flex gap-2">
                <Link :href="route('deliveries.index')">
                    <Button variant="ghost">Back</Button>
                </Link>
                <Button 
                    v-if="props.delivery.status === 'pending'" 
                    variant="outline" 
                    @click="openRescheduleModal"
                >
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Reschedule
                </Button>
                <Link :href="route('deliveries.edit', props.delivery.id)">
                    <Button variant="default">Edit</Button>
                </Link>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Delivery Details</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <div class="text-sm text-gray-500">Status</div>
                                <div class="font-medium">{{ getStatusLabel(props.delivery.status) }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500">Delivery Date/Time</div>
                                <div class="font-medium">{{ formatDate(props.delivery.delivery_date) }} • {{ props.delivery.delivery_time }}</div>
                            </div>
                            <div class="md:col-span-2">
                                <div class="text-sm text-gray-500">Address</div>
                                <div class="font-medium">{{ props.delivery.delivery_address }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500">Contact Person</div>
                                <div class="font-medium">{{ props.delivery.contact_person }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500">Contact Phone</div>
                                <div class="font-medium">{{ props.delivery.contact_phone }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500">Delivery Fee</div>
                                <div class="font-medium">{{ formatCurrency(props.delivery.delivery_fee) }}</div>
                            </div>
                            <div class="md:col-span-2" v-if="props.delivery.notes">
                                <div class="text-sm text-gray-500">Notes</div>
                                <div class="whitespace-pre-wrap">{{ props.delivery.notes }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card v-if="props.delivery.proof_of_delivery_url">
                    <CardHeader>
                        <CardTitle>Proof of Delivery</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div class="relative rounded-lg overflow-hidden border border-gray-200">
                                <img 
                                    :src="props.delivery.proof_of_delivery_url" 
                                    alt="Proof of Delivery"
                                    class="w-full h-auto object-contain max-h-96"
                                />
                            </div>
                            <a 
                                :href="props.delivery.proof_of_delivery_url" 
                                target="_blank"
                                class="inline-flex items-center text-sm text-blue-600 hover:text-blue-800 hover:underline"
                            >
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                                View Full Image
                            </a>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="space-y-6">
                <Card v-if="props.delivery.invoice">
                    <CardHeader>
                        <CardTitle>Invoice</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-2">
                            <div>
                                <div class="text-sm text-gray-500">Reference</div>
                                <div class="font-medium">
                                    <Link :href="route('invoices.show', props.delivery.invoice!.id)" class="text-blue-500 underline">
                                        {{ props.delivery.invoice!.reference_number || ('#' + props.delivery.invoice!.id) }}
                                    </Link>
                                </div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500">Total</div>
                                <div class="font-medium">{{ formatCurrency(props.delivery.invoice!.total_amount) }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card v-if="props.delivery.customer">
                    <CardHeader>
                        <CardTitle>Customer</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-2">
                            <div class="font-medium">{{ props.delivery.customer!.name }}</div>
                            <div v-if="props.delivery.customer!.company_name" class="text-sm text-gray-500">{{ props.delivery.customer!.company_name }}</div>
                            <div v-if="props.delivery.customer!.email" class="text-sm">Email: {{ props.delivery.customer!.email }}</div>
                            <div v-if="props.delivery.customer!.phone" class="text-sm">Phone: {{ props.delivery.customer!.phone }}</div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Reschedule Modal -->
        <div v-if="showRescheduleModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="closeRescheduleModal">
            <Card class="w-full max-w-md mx-4">
                <CardHeader>
                    <CardTitle>Reschedule Delivery</CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submitReschedule" class="space-y-4">
                        <p class="text-sm text-muted-foreground mb-4">
                            Rescheduling delivery for <strong>{{ props.delivery.customer?.name }}</strong>
                            <br>
                            Current schedule: <strong>{{ formatDate(props.delivery.delivery_date) }} • {{ props.delivery.delivery_time }}</strong>
                        </p>
                        
                        <div>
                            <Label for="reschedule_delivery_date">New Delivery Date *</Label>
                            <input
                                v-model="rescheduleForm.delivery_date"
                                type="date"
                                id="reschedule_delivery_date"
                                :min="today"
                                class="w-full rounded-md border border-input bg-transparent px-3 py-2 mt-1 text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none"
                                required
                            />
                            <InputError :message="rescheduleForm.errors.delivery_date" />
                        </div>
                        
                        <div>
                            <Label for="reschedule_delivery_time">New Delivery Time *</Label>
                            <Select
                                v-model="rescheduleForm.delivery_time"
                                :options="deliveryTimeOptions"
                                placeholder="Select delivery time"
                                class="mt-1"
                                required
                            />
                            <InputError :message="rescheduleForm.errors.delivery_time" />
                        </div>
                        
                        <div>
                            <Label for="reschedule_reason">Reason for Rescheduling (Optional)</Label>
                            <textarea
                                v-model="rescheduleForm.reason"
                                id="reschedule_reason"
                                class="w-full rounded-md border border-input bg-transparent px-3 py-2 mt-1 text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none"
                                rows="3"
                                placeholder="e.g., Vehicle breakdown, driver unavailable, etc."
                            />
                            <InputError :message="rescheduleForm.errors.reason" />
                        </div>
                        
                        <CardFooter class="flex gap-2 justify-end pt-4">
                            <Button type="button" variant="ghost" @click="closeRescheduleModal">Cancel</Button>
                            <Button type="submit" variant="default" :disabled="rescheduleForm.processing">
                                {{ rescheduleForm.processing ? 'Rescheduling...' : 'Reschedule Delivery' }}
                            </Button>
                        </CardFooter>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>


