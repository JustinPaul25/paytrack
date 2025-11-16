<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardFooter from '@/components/ui/card/CardFooter.vue';
import Label from '@/components/ui/label/Label.vue';
import SearchSelect from '@/components/ui/select/SearchSelect.vue';
import { type BreadcrumbItem } from '@/types';
import Swal from 'sweetalert2';

const props = defineProps<{ category: any, categories: Array<{ id: number; name: string }> }>();

const form = useForm({
    name: props.category.name,
    description: props.category.description,
    parent_id: props.category.parent_id ?? null,
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Categories',
        href: '/categories',
    },
    {
        title: 'Edit Category',
        href: `/categories/${props.category.id}/edit`,
    }
];

// Transform categories for SearchSelect component
const categoryOptions = computed(() => {
    return [
        { value: null, label: 'None' },
        ...props.categories.map(cat => ({
            value: cat.id,
            label: cat.name
        }))
    ];
});

function submit() {
    form.put(route('categories.update', props.category.id), {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Category updated successfully',
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
        <Head title="Edit Category" />
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">Edit Category</h1>
        </div>
        <Card>
            <CardHeader>
                <CardTitle>Edit Category</CardTitle>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <Label for="name">Name</Label>
							<input id="name" v-model="form.name" class="w-full rounded border px-3 py-2 mt-1" required placeholder="Category name" />
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <Label for="description">Description</Label>
							<textarea id="description" v-model="form.description" class="w-full rounded border px-3 py-2 mt-1" rows="3" placeholder="Optional. Short summary of this category" />
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <Label for="parent_id">Parent Category</Label>
                            <SearchSelect
                                v-model="form.parent_id"
                                :options="categoryOptions"
                                placeholder="Select parent category"
                                search-placeholder="Search categories..."
                                class="mt-1"
                            />
                        </div>
                    </div>
                    <CardFooter class="flex gap-2 justify-end">
                        <Button type="submit" variant="default">Update</Button>
                        <Link :href="route('categories.index')">
                            <Button type="button" variant="ghost">Cancel</Button>
                        </Link>
                    </CardFooter>
                </form>
            </CardContent>
        </Card>
    </AppLayout>
</template> 