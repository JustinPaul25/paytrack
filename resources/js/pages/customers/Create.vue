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

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Customers', href: '/customers' },
    { title: 'Create Customer', href: '/customers/create' },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Create Customer" />
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">Create Customer</h1>
        </div>
        <Card>
            <CardHeader>
                <CardTitle>Create Customer</CardTitle>
            </CardHeader>
            <CardContent>
				<CustomerForm>
					<template #footer>
						<Link :href="route('customers.index')">
							<Button type="button" variant="ghost">Cancel</Button>
						</Link>
					</template>
				</CustomerForm>
            </CardContent>
        </Card>
    </AppLayout>
</template> 