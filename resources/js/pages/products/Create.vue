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
import { ref } from 'vue';

const props = defineProps<{ categories: Array<{ id: number; name: string }> }>();

const imagePreview = ref<string | null>(null);

const form = useForm({
    name: '',
    description: '',
    category_id: null as number | null,
    purchase_price: '',
    selling_price: '',
    stock: '',
    SKU: '',
    image: null as File | null,
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Products',
        href: '/products',
    },
    {
        title: 'Create Product',
        href: '/products/create',
    }
];

function handleImageChange(e: Event) {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        form.image = target.files[0];
		imagePreview.value = URL.createObjectURL(form.image);
    } else {
        form.image = null;
		imagePreview.value = null;
    }
}

function submit() {
    form.post(route('products.store'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Product created successfully',
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
        <Head title="Create Product" />
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">Create Product</h1>
        </div>
        <Card>
            <CardHeader>
                <CardTitle>Create Product</CardTitle>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="submit()" class="space-y-6" enctype="multipart/form-data">
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <Label for="name">Name</Label>
							<input id="name" v-model="form.name" class="w-full rounded border px-3 py-2 mt-1" required placeholder="e.g., Premium Bond Paper A4" />
                            <InputError :message="form.errors.name" />
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <Label for="description">Description</Label>
							<textarea id="description" v-model="form.description" class="w-full rounded border px-3 py-2 mt-1" rows="3" placeholder="Optional details to help identify the product" />
                            <InputError :message="form.errors.description" />
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <Label for="category_id">Category</Label>
                            <select id="category_id" v-model="form.category_id" class="w-full rounded border px-3 py-2 mt-1">
                                <option :value="null">Select category</option>
                                <option v-for="cat in props.categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                            </select>
                            <InputError :message="form.errors.category_id" />
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <Label for="purchase_price">Purchase Price</Label>
							<input id="purchase_price" v-model="form.purchase_price" type="number" min="0" step="0.01" class="w-full rounded border px-3 py-2 mt-1" required placeholder="e.g., 120.00" />
                            <InputError :message="form.errors.purchase_price" />
                        </div>
                        <div class="flex-1">
                            <Label for="selling_price">Selling Price</Label>
							<input id="selling_price" v-model="form.selling_price" type="number" min="0" step="0.01" class="w-full rounded border px-3 py-2 mt-1" required placeholder="e.g., 150.00" />
                            <InputError :message="form.errors.selling_price" />
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <Label for="stock">Stock</Label>
							<input id="stock" v-model="form.stock" type="number" min="0" class="w-full rounded border px-3 py-2 mt-1" required placeholder="e.g., 100" />
                            <InputError :message="form.errors.stock" />
                        </div>
                        <div class="flex-1">
                            <Label for="SKU">SKU</Label>
							<input id="SKU" v-model="form.SKU" class="w-full rounded border px-3 py-2 mt-1" required placeholder="e.g., BP-A4-80G-500" />
                            <InputError :message="form.errors.SKU" />
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <Label for="image">Image</Label>
                            <input id="image" type="file" accept="image/*" @change="handleImageChange" class="w-full rounded border px-3 py-2 mt-1" />
                            <InputError :message="form.errors.image" />
							<div v-if="imagePreview" class="mt-2">
								<img :src="imagePreview" alt="Selected Image Preview" class="h-24 rounded border" />
							</div>
                        </div>
                    </div>
                    <CardFooter class="flex gap-2 justify-end">
                        <Button type="submit" variant="default">Create</Button>
                        <Link :href="route('products.index')">
                            <Button type="button" variant="ghost">Cancel</Button>
                        </Link>
                    </CardFooter>
                </form>
            </CardContent>
        </Card>
    </AppLayout>
</template> 