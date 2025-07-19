<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardFooter from '@/components/ui/card/CardFooter.vue';
import { type BreadcrumbItem } from '@/types';
import CustomerForm from './Form.vue';

const props = defineProps<{ customer: any, profile_image_url?: string }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Customers', href: '/customers' },
    { title: 'Edit Customer', href: `/customers/${props.customer.id}/edit` },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Edit Customer" />
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">Edit Customer</h1>
        </div>
        <Card>
            <CardHeader>
                <CardTitle>Edit Customer</CardTitle>
            </CardHeader>
            <CardContent>
                <CustomerForm :customer="customer" :profileImageUrl="profile_image_url" />
            </CardContent>
            <CardFooter class="flex gap-2 justify-end">
                <Link :href="route('customers.index')">
                    <Button type="button" variant="ghost">Cancel</Button>
                </Link>
            </CardFooter>
        </Card>
    </AppLayout>
</template> 