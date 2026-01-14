<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import { type BreadcrumbItem } from '@/types';
import Swal from 'sweetalert2';
import Icon from '@/components/Icon.vue';
import Avatar from '@/components/ui/avatar/Avatar.vue';
import AvatarImage from '@/components/ui/avatar/AvatarImage.vue';
import AvatarFallback from '@/components/ui/avatar/AvatarFallback.vue';

const props = defineProps<{ customer: any, profile_image_url?: string }>();

const page = usePage();

// Check if we're coming from the users management page
const fromUsers = computed(() => {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('from') === 'users';
});

const breadcrumbs = computed<BreadcrumbItem[]>(() => {
    if (fromUsers.value) {
        return [
            { title: 'Users', href: '/admin/users' },
            { title: 'View User', href: `/customers/${props.customer.id}?from=users` },
        ];
    }
    return [
        { title: 'Customers', href: '/customers' },
        { title: 'View Customer', href: `/customers/${props.customer.id}` },
    ];
});

function getCustomerProfileImage(customer: any) {
    if (props.profile_image_url) {
        return props.profile_image_url;
    }
    if (customer.media && customer.media.length > 0) {
        const profileImage = customer.media.find((media: any) => 
            media.mime_type && media.mime_type.startsWith('image/')
        );
        return profileImage ? (profileImage.thumb_url || profileImage.original_url) : null;
    }
    return null;
}

function getCustomerInitials(customer: any) {
    return customer.name
        .split(' ')
        .map((word: string) => word.charAt(0))
        .join('')
        .toUpperCase()
        .slice(0, 2);
}

function formatAddress(customer: any): string {
    const parts: string[] = [];
    
    if (customer.address) {
        parts.push(customer.address);
    }
    if (customer.purok) {
        parts.push(`Purok ${customer.purok}`);
    }
    if (customer.barangay) {
        parts.push(`Barangay ${customer.barangay}`);
    }
    if (customer.city_municipality) {
        parts.push(customer.city_municipality);
    }
    if (customer.province) {
        parts.push(customer.province);
    }
    
    return parts.length > 0 ? parts.join(', ') : '';
}

async function verifyCustomer() {
    const result = await Swal.fire({
        title: 'Verify Customer?',
        text: `Are you sure you want to verify ${props.customer.name}? They will receive an email notification and be able to log in.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#8f5be8',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, verify',
        cancelButtonText: 'Cancel',
    });
    
    if (result.isConfirmed) {
        router.post(`/customers/${props.customer.id}/approve`, {}, {
            onSuccess: () => {
                Swal.fire('Customer Verified', 'The customer has been verified and notified via email.', 'success');
            },
            onError: () => {
                const flash = (page.props as unknown as { flash?: { error?: string } }).flash;
                const message = flash?.error ?? 'Failed to verify customer.';
                Swal.fire('Error', message, 'error');
            },
        });
    }
}

</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="fromUsers ? 'View User' : 'View Customer'" />
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">{{ fromUsers ? 'View User' : 'View Customer' }}</h1>
            <div class="flex gap-2">
                <Button 
                    v-if="!customer.verified_at" 
                    variant="default" 
                    @click="verifyCustomer"
                    class="bg-green-600 hover:bg-green-700"
                >
                    <Icon name="Check" class="h-4 w-4 mr-2" />
                    Verify Customer
                </Button>
                <Link :href="route('customers.logs.show', customer.id)">
                    <Button variant="outline">
                        <Icon name="file-text" class="h-4 w-4 mr-2" />
                        View Logs
                    </Button>
                </Link>
            </div>
        </div>
        <Card>
            <CardHeader>
                <CardTitle>{{ fromUsers ? 'User Information' : 'Customer Information' }}</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="space-y-6">
                    <!-- Profile Image and Basic Info -->
                    <div class="flex items-start gap-4">
                        <Avatar class="h-20 w-20">
                            <AvatarImage 
                                v-if="getCustomerProfileImage(customer)"
                                :src="getCustomerProfileImage(customer)!"
                                :alt="customer.name"
                            />
                            <AvatarFallback v-else>
                                {{ getCustomerInitials(customer) }}
                            </AvatarFallback>
                        </Avatar>
                        <div class="flex-1">
                            <h2 class="text-xl font-semibold">{{ customer.name }}</h2>
                            <p v-if="customer.company_name" class="text-sm text-gray-600 mt-1">{{ customer.company_name }}</p>
                            <div v-if="customer.verified_at" class="mt-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Verified
                                </span>
                            </div>
                            <div v-else class="mt-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    Unverified
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-700 mb-3">Contact Information</h3>
                            <div class="space-y-3">
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Email</label>
                                    <p class="text-sm mt-1">{{ customer.email }}</p>
                                </div>
                                <div v-if="customer.phone">
                                    <label class="text-sm font-medium text-gray-500">Phone</label>
                                    <p class="text-sm mt-1">{{ customer.phone }}</p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-sm font-semibold text-gray-700 mb-3">Address</h3>
                            <div class="space-y-3">
                                <div v-if="formatAddress(customer)">
                                    <p class="text-sm text-gray-600">{{ formatAddress(customer) }}</p>
                                </div>
                                <div v-else>
                                    <p class="text-sm text-gray-400 italic">No address provided</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Location (if available) -->
                    <div v-if="customer.location" class="border-t pt-4">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Location Coordinates</h3>
                        <p class="text-sm text-gray-600">
                            Latitude: {{ typeof customer.location === 'object' ? customer.location.lat : 'N/A' }}, 
                            Longitude: {{ typeof customer.location === 'object' ? customer.location.lng : 'N/A' }}
                        </p>
                    </div>

                    <!-- Additional Details -->
                    <div class="border-t pt-4">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Additional Details</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div v-if="customer.created_at">
                                <label class="text-sm font-medium text-gray-500">Created At</label>
                                <p class="text-sm mt-1">{{ new Date(customer.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' }) }}</p>
                            </div>
                            <div v-if="customer.updated_at">
                                <label class="text-sm font-medium text-gray-500">Last Updated</label>
                                <p class="text-sm mt-1">{{ new Date(customer.updated_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' }) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Back Button -->
        <div class="mt-4 flex justify-end">
            <Link :href="fromUsers ? route('admin.users') : route('customers.index')">
                <Button variant="outline">
                    Back
                </Button>
            </Link>
        </div>
    </AppLayout>
</template>