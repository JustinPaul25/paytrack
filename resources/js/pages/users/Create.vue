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
import { ref } from 'vue';

const props = defineProps<{ roles: Array<{ id: number; name: string }> }>();

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    roles: [] as number[],
    create_another: undefined as number | undefined,
    profile_image: null as File | null, // Allow File or null
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Users',
        href: '/users',
    },
    {
        title: 'Create User',
        href: '/users/create',
    }
]

const profileImage = ref<File|null>(null);
const profileImageUrl = ref<string|null>(null);

function onProfileImageChange(e: Event) {
    const files = (e.target as HTMLInputElement).files;
    if (files && files[0]) {
        form.profile_image = files[0];
        profileImageUrl.value = URL.createObjectURL(files[0]);
    } else {
        form.profile_image = null;
        profileImageUrl.value = null;
    }
}

function submit(createAnother = false) {
    if (createAnother) {
        form.create_another = 1;
    } else {
        delete form.create_another;
    }
    form.post(route('users.store'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'User created successfully',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
            });
            if (createAnother) {
                form.reset();
                profileImage.value = null;
                profileImageUrl.value = null;
            }
        },
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Create User" />
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">Create User</h1>
        </div>
        <Card>
            <CardHeader>
                <CardTitle>Create User</CardTitle>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="submit()" class="space-y-6">
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <Label for="name">Name</Label>
                            <input id="name" v-model="form.name" class="w-full rounded border px-3 py-2 mt-1" required />
                            <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</div>
                        </div>
                        <div class="flex-1">
                            <Label for="email">Email</Label>
                            <input id="email" v-model="form.email" class="w-full rounded border px-3 py-2 mt-1" required />
                            <div v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</div>
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <Label for="password">Password</Label>
                            <input id="password" type="password" v-model="form.password" class="w-full rounded border px-3 py-2 mt-1" required />
                            <div v-if="form.errors.password" class="text-red-500 text-xs mt-1">{{ form.errors.password }}</div>
                        </div>
                        <div class="flex-1">
                            <Label for="password_confirmation">Confirm Password</Label>
                            <input id="password_confirmation" type="password" v-model="form.password_confirmation" class="w-full rounded border px-3 py-2 mt-1" required />
                            <div v-if="form.errors.password_confirmation" class="text-red-500 text-xs mt-1">{{ form.errors.password_confirmation }}</div>
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <Label for="profile_image">Profile Image</Label>
                            <input id="profile_image" name="profile_image" type="file" accept="image/*" @change="onProfileImageChange" class="w-full rounded border px-3 py-2 mt-1" />
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
                        <Button type="submit" variant="default">Create</Button>
                        <Button type="button" variant="secondary" @click="submit(true)">Create & create another</Button>
                        <Link :href="route('users.index')">
                            <Button type="button" variant="ghost">Cancel</Button>
                        </Link>
                    </CardFooter>
                </form>
            </CardContent>
        </Card>
    </AppLayout>
</template> 