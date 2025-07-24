<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardFooter from '@/components/ui/card/CardFooter.vue';
import { type BreadcrumbItem } from '@/types';
import BranchForm from './Form.vue';
import Swal from 'sweetalert2';
import { onMounted } from 'vue';

const page = usePage();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Branches', href: '/branches' },
    { title: 'Create Branch', href: '/branches/create' },
];

// Handle flash messages
onMounted(() => {
    const flash = page.props.flash as any;
    if (flash?.error) {
        Swal.fire({
            title: 'Error!',
            text: flash.error,
            icon: 'error',
            confirmButtonColor: '#8f5be8',
        });
    }
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Create Branch" />
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">Create Branch</h1>
        </div>
        <Card>
            <CardHeader>
                <CardTitle>Create Branch</CardTitle>
            </CardHeader>
            <CardContent>
                <BranchForm />
            </CardContent>
            <CardFooter class="flex gap-2 justify-end">
                <Link :href="route('branches.index')">
                    <Button type="button" variant="ghost">Cancel</Button>
                </Link>
            </CardFooter>
        </Card>
    </AppLayout>
</template> 