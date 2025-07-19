<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardFooter from '@/components/ui/card/CardFooter.vue';
import Label from '@/components/ui/label/Label.vue';
import Swal from 'sweetalert2';
import { type BreadcrumbItem } from '@/types';
import InputError from '@/components/InputError.vue';

const props = defineProps<{ categories: Array<{ id: number; name: string }> }>();

const form = useForm({
    name: '',
    description: '',
    parent_id: null as number | null,
    create_another: undefined as number | undefined,
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Categories',
        href: '/categories',
    },
    {
        title: 'Create Category',
        href: '/categories/create',
    }
];

function submit(createAnother = false) {
    if (createAnother) {
        form.create_another = 1;
    } else {
        delete form.create_another;
    }
    form.post(route('categories.store'), {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Category created successfully',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
            });
            if (createAnother) {
                form.reset();
            }
        },
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Create Category" />
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">Create Category</h1>
        </div>
        <Card>
            <CardHeader>
                <CardTitle>Create Category</CardTitle>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="submit()" class="space-y-6">
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <Label for="name">Name</Label>
                            <input id="name" v-model="form.name" class="w-full rounded border px-3 py-2 mt-1" required />
                            <InputError :message="form.errors.name" />
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <Label for="description">Description</Label>
                            <textarea id="description" v-model="form.description" class="w-full rounded border px-3 py-2 mt-1" rows="3" />
                            <InputError :message="form.errors.description" />
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <Label for="parent_id">Parent Category</Label>
                            <select id="parent_id" v-model="form.parent_id" class="w-full rounded border px-3 py-2 mt-1">
                                <option :value="null">None</option>
                                <option v-for="cat in props.categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                            </select>
                            <InputError :message="form.errors.parent_id" />
                        </div>
                    </div>
                    <CardFooter class="flex gap-2 justify-end">
                        <Button type="submit" variant="default">Create</Button>
                        <Button type="button" variant="secondary" @click="submit(true)">Create & create another</Button>
                        <Link :href="route('categories.index')">
                            <Button type="button" variant="ghost">Cancel</Button>
                        </Link>
                    </CardFooter>
                </form>
            </CardContent>
        </Card>
    </AppLayout>
</template> 