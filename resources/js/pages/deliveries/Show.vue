<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import { Button } from '@/components/ui/button';

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
    </AppLayout>
</template>


