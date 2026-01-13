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
import { type BreadcrumbItem } from '@/types';
import { ref } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps<{ user: any, roles: Array<{ id: number; name: string }>, userRoles: number[], profileImageUrl?: string }>();

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    password: '',
    password_confirmation: '',
    roles: [...props.userRoles],
    profile_image: null as File | null, // Add profile_image to form state
});

const profileImage = ref<File|null>(null);
const profileImageUrl = ref<string|null>(props.profileImageUrl || null);

function onProfileImageChange(e: Event) {
    const files = (e.target as HTMLInputElement).files;
    if (files && files[0]) {
        form.profile_image = files[0];
        profileImageUrl.value = URL.createObjectURL(files[0]);
    } else {
        form.profile_image = null;
        profileImageUrl.value = props.profileImageUrl || null;
    }
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Users',
        href: '/users',
    },
    {
        title: 'Edit User',
        href: `/users/${props.user.id}/edit`,
    }
]

function submit() {
    form.post(route('users.update', props.user.id), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'User updated successfully',
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
        <Head title="Edit User" />
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">Edit User</h1>
        </div>
        <Card>
            <CardHeader>
                <CardTitle>Edit User</CardTitle>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <Label for="name">Name *</Label>
                            <input id="name" v-model="form.name" class="w-full rounded border px-3 py-2 mt-1" required />
                            <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</div>
                        </div>
                        <div class="flex-1">
                            <Label for="email">Email *</Label>
                            <input id="email" v-model="form.email" class="w-full rounded border px-3 py-2 mt-1" required />
                            <div v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</div>
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <Label for="password">Password</Label>
                            <input id="password" type="password" v-model="form.password" class="w-full rounded border px-3 py-2 mt-1" />
                            <div v-if="form.errors.password" class="text-red-500 text-xs mt-1">{{ form.errors.password }}</div>
                        </div>
                        <div class="flex-1">
                            <Label for="password_confirmation">Confirm Password</Label>
                            <input id="password_confirmation" type="password" v-model="form.password_confirmation" class="w-full rounded border px-3 py-2 mt-1" />
                            <div v-if="form.errors.password_confirmation" class="text-red-500 text-xs mt-1">{{ form.errors.password_confirmation }}</div>
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <Label for="profile_image">Profile Image</Label>
                            <input id="profile_image" type="file" accept="image/*" @change="onProfileImageChange" class="w-full rounded border px-3 py-2 mt-1" />
                            <div v-if="profileImageUrl" class="mt-2">
                                <img :src="profileImageUrl" alt="Profile Preview" class="h-20 w-20 object-cover rounded-full border" />
                            </div>
                            <div v-if="form.errors.profile_image" class="text-red-500 text-xs mt-1">{{ form.errors.profile_image }}</div>
                        </div>
                    </div>
                    <div>
                        <Label>Roles</Label>
                        <div class="flex flex-wrap gap-2 mt-2">
                            <label v-for="role in props.roles" :key="role.id" class="flex items-center gap-2">
                                <input type="checkbox" :value="role.id" v-model="form.roles" />
                                {{ role.name }}
                            </label>
                        </div>
                        <div v-if="form.errors.roles" class="text-red-500 text-xs mt-1">{{ form.errors.roles }}</div>
                    </div>
                    <CardFooter class="flex gap-2 justify-end">
                        <Button type="submit" variant="default">Update</Button>
                        <Link :href="route('users.index')">
                            <Button type="button" variant="ghost">Cancel</Button>
                        </Link>
                    </CardFooter>
                </form>
            </CardContent>
        </Card>
    </AppLayout>
</template> 