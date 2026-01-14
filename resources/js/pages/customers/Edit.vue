<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardFooter from '@/components/ui/card/CardFooter.vue';
import { type BreadcrumbItem } from '@/types';
import CustomerForm from './Form.vue';
import Swal from 'sweetalert2';
import Icon from '@/components/Icon.vue';

const props = defineProps<{ customer: any, profile_image_url?: string }>();

const page = usePage();
const isAdmin = computed(() => Array.isArray((page.props as any).auth?.userRoles) && 
    (page.props as any).auth.userRoles.includes('Admin'));

// Check if we're coming from the users management page
const fromUsers = computed(() => {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('from') === 'users';
});

const breadcrumbs = computed<BreadcrumbItem[]>(() => {
    if (fromUsers.value) {
        return [
            { title: 'Users', href: '/users' },
            { title: 'Edit User', href: `/customers/${props.customer.id}/edit?from=users` },
        ];
    }
    return [
        { title: 'Customers', href: '/customers' },
        { title: 'Edit Customer', href: `/customers/${props.customer.id}/edit` },
    ];
});

// Redirect staff users who try to access edit page
onMounted(() => {
    if (!isAdmin.value) {
        Swal.fire({
            title: 'Access Denied',
            text: 'You do not have permission to edit customer data.',
            icon: 'error',
            confirmButtonText: 'Go Back',
            confirmButtonColor: '#8f5be8',
        }).then(() => {
            router.visit('/customers');
        });
    }
});

async function approveCustomer() {
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
    <AppLayout v-if="isAdmin" :breadcrumbs="breadcrumbs">
        <Head :title="fromUsers ? 'Edit User' : 'Edit Customer'" />
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">{{ fromUsers ? 'Edit User' : 'Edit Customer' }}</h1>
            <div class="flex gap-2">
                <Button 
                    v-if="!customer.verified_at" 
                    variant="default" 
                    @click="approveCustomer"
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
                <CardTitle>{{ fromUsers ? 'Edit User' : 'Edit Customer' }}</CardTitle>
            </CardHeader>
            <CardContent>
				<CustomerForm :customer="customer" :profileImageUrl="profile_image_url">
					<template #footer>
						<Link :href="fromUsers ? route('users.index') : route('customers.index')">
							<Button type="button" variant="ghost">Cancel</Button>
						</Link>
					</template>
				</CustomerForm>
            </CardContent>
        </Card>
    </AppLayout>
    <AppLayout v-else :breadcrumbs="breadcrumbs">
        <Head title="Access Denied" />
        <Card>
            <CardContent class="py-12 text-center">
                <h3 class="text-lg font-semibold mb-2">Access Denied</h3>
                <p class="text-sm text-muted-foreground mb-6">
                    You do not have permission to edit customer data.
                </p>
                <Link :href="route('customers.index')">
                    <Button variant="default">Go Back</Button>
                </Link>
            </CardContent>
        </Card>
    </AppLayout>
</template> 