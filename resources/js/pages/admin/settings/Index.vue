<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardFooter from '@/components/ui/card/CardFooter.vue';
import Label from '@/components/ui/label/Label.vue';
import LocationInput from '@/components/ui/input/LocationInput.vue';
import Swal from 'sweetalert2';
import { type BreadcrumbItem } from '@/types';

interface Props {
    deliveryOriginAddress: string | null;
    deliveryOriginLocation: { lat: number; lng: number } | null;
    baseDeliveryFee: string | number;
}

const props = defineProps<Props>();

const form = useForm({
    delivery_origin_address: props.deliveryOriginAddress || '',
    delivery_origin_location: props.deliveryOriginLocation || null,
    base_delivery_fee: props.baseDeliveryFee || '50.00',
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Settings',
        href: '/admin/settings',
    },
];

function submit() {
    form.post(route('admin.settings.update'), {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Settings updated successfully',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
            });
        },
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Admin Settings" />
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">Admin Settings</h1>
        </div>
        <Card>
            <CardHeader>
                <CardTitle>Delivery Origin Address</CardTitle>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="submit()" class="space-y-6">
                    <div>
                        <Label for="delivery_origin_address">Business/Store Address</Label>
                        <textarea 
                            id="delivery_origin_address" 
                            v-model="form.delivery_origin_address" 
                            class="w-full rounded border px-3 py-2 mt-1" 
                            rows="3"
                            placeholder="Enter the business/store address that will be used as the origin for deliveries"
                        />
                        <div v-if="form.errors.delivery_origin_address" class="text-red-500 text-xs mt-1">
                            {{ form.errors.delivery_origin_address }}
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            This address will be used as the starting point for calculating delivery routes and distances.
                        </p>
                    </div>
                    
                    <div>
                        <Label>Location on Map (Optional)</Label>
                        <p class="text-xs text-gray-500 mb-2">
                            Pinpoint the exact location on the map for more accurate delivery calculations.
                        </p>
                        <LocationInput 
                            v-model="form.delivery_origin_location"
                            :map-height="'400px'"
                            :map-zoom="13"
                        />
                        <div v-if="form.errors.delivery_origin_location" class="text-red-500 text-xs mt-1">
                            {{ form.errors.delivery_origin_location }}
                        </div>
                    </div>
                    
                    <div>
                        <Label for="base_delivery_fee">Base Delivery Fee (₱)</Label>
                        <input 
                            id="base_delivery_fee" 
                            v-model.number="form.base_delivery_fee" 
                            type="number"
                            step="0.01"
                            min="0"
                            max="99999.99"
                            class="w-full rounded border px-3 py-2 mt-1" 
                            placeholder="50.00"
                        />
                        <div v-if="form.errors.base_delivery_fee" class="text-red-500 text-xs mt-1">
                            {{ form.errors.base_delivery_fee }}
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            This is the estimated delivery fee shown to customers when creating orders. The actual fee can be adjusted when creating the delivery.
                        </p>
                    </div>

                    <CardFooter class="flex gap-2 justify-end">
                        <Button type="submit" variant="default" :disabled="form.processing">
                            {{ form.processing ? 'Saving...' : 'Save Settings' }}
                        </Button>
                    </CardFooter>
                </form>
            </CardContent>
        </Card>
    </AppLayout>
</template>

