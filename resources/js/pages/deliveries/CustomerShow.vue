<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import { Button } from '@/components/ui/button';
import { type BreadcrumbItem } from '@/types';

interface Delivery {
    id: number;
    delivery_address: string;
    contact_person: string;
    contact_phone: string;
    delivery_date: string;
    delivery_time: string;
    status: string;
    delivery_fee: number;
    type?: string;
    notes?: string;
    proof_of_delivery_url?: string | null;
    invoice?: { id: number; reference_number?: string; total_amount: number };
    customer?: { id: number; name: string; company_name?: string; email?: string; phone?: string; address?: string };
}

const props = defineProps<{ delivery: Delivery }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'My Deliveries', href: '/my-deliveries' },
    { title: `Delivery #${props.delivery.id}`, href: `/my-deliveries/${props.delivery.id}` },
];

function formatCurrency(amount: number) {
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', minimumFractionDigits: 2 }).format(amount);
}

function formatDate(dateString: string) {
    return new Date(dateString).toLocaleDateString();
}

function getStatusBadgeClass(status: string) {
    switch (status) {
        case 'pending': return 'bg-yellow-100 text-yellow-800';
        case 'completed': return 'bg-green-100 text-green-800';
        case 'cancelled': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

function getStatusLabel(status: string): string {
    switch (status) {
        case 'pending': return 'Out for Delivery';
        case 'completed': return 'Delivered';
        case 'cancelled': return 'Cancelled';
        default: return status.charAt(0).toUpperCase() + status.slice(1);
    }
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Delivery #${props.delivery.id}`" />

        <div class="flex items-center justify-between my-6">
            <h1 class="text-2xl font-bold">Delivery #{{ props.delivery.id }}</h1>
            <div class="flex gap-2">
                <Link :href="route('deliveries.customer')">
                    <Button variant="ghost">Back to Deliveries</Button>
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
                                <div class="mt-1">
                                    <span :class="['px-2 py-1 rounded-full text-xs font-medium', getStatusBadgeClass(props.delivery.status)]">
                                        {{ getStatusLabel(props.delivery.status) }}
                                    </span>
                                </div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500">Delivery Date</div>
                                <div class="font-medium">{{ formatDate(props.delivery.delivery_date) }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500">Delivery Time</div>
                                <div class="font-medium">{{ props.delivery.delivery_time }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500">Delivery Fee</div>
                                <div class="font-medium">{{ formatCurrency(props.delivery.delivery_fee) }}</div>
                            </div>
                            <div class="md:col-span-2">
                                <div class="text-sm text-gray-500">Delivery Address</div>
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
                            <div class="md:col-span-2" v-if="props.delivery.notes">
                                <div class="text-sm text-gray-500">Notes</div>
                                <div class="whitespace-pre-wrap">{{ props.delivery.notes }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Proof of Delivery - Prominent display for return deliveries -->
                <Card v-if="props.delivery.proof_of_delivery_url" class="border-2" :class="props.delivery.type === 'return' ? 'border-green-500 bg-green-50 dark:bg-green-950/20' : ''">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <svg v-if="props.delivery.type === 'return'" class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Proof of Delivery</span>
                            <span v-if="props.delivery.type === 'return'" class="ml-auto px-2 py-1 bg-green-100 text-green-800 text-xs font-medium rounded-full">
                                Available
                            </span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <p v-if="props.delivery.type === 'return'" class="text-sm text-green-700 dark:text-green-300 font-medium">
                                ✓ Your return delivery proof has been uploaded by our staff.
                            </p>
                            <div class="relative rounded-lg overflow-hidden border border-gray-200 bg-white">
                                <img 
                                    :src="props.delivery.proof_of_delivery_url" 
                                    alt="Proof of Delivery"
                                    class="w-full h-auto object-contain max-h-96 mx-auto"
                                />
                            </div>
                            <a 
                                :href="props.delivery.proof_of_delivery_url" 
                                target="_blank"
                                class="inline-flex items-center text-sm text-blue-600 hover:text-blue-800 hover:underline font-medium"
                            >
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                                Open Proof of Delivery in New Tab
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
                                    <Link :href="route('invoices.show', props.delivery.invoice!.id)" class="text-blue-500 hover:underline">
                                        {{ props.delivery.invoice!.reference_number || ('#' + props.delivery.invoice!.id) }}
                                    </Link>
                                </div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500">Total Amount</div>
                                <div class="font-medium">{{ formatCurrency(props.delivery.invoice!.total_amount) }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card v-if="props.delivery.customer">
                    <CardHeader>
                        <CardTitle>Customer Information</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-2">
                            <div class="font-medium">{{ props.delivery.customer!.name }}</div>
                            <div v-if="props.delivery.customer!.company_name" class="text-sm text-gray-500">{{ props.delivery.customer!.company_name }}</div>
                            <div v-if="props.delivery.customer!.email" class="text-sm">Email: {{ props.delivery.customer!.email }}</div>
                            <div v-if="props.delivery.customer!.phone" class="text-sm">Phone: {{ props.delivery.customer!.phone }}</div>
                            <div v-if="props.delivery.customer!.address" class="text-sm">Address: {{ props.delivery.customer!.address }}</div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>

