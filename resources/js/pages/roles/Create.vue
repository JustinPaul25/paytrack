<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardFooter from '@/components/ui/card/CardFooter.vue';
import Checkbox from '@/components/ui/checkbox/Checkbox.vue';
import Label from '@/components/ui/label/Label.vue';
import Swal from 'sweetalert2';
import { type BreadcrumbItem } from '@/types';

const props = defineProps<{ groupedPermissions: Record<string, Array<any>> }>();

const form = useForm({
    name: '',
    guard_name: 'web',
    permissions: [] as number[],
    create_another: undefined as number | undefined,
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Roles',
        href: '/roles',
    },
    {
        title: 'Create Role',
        href: '/roles/create',
    }
]

function togglePermission(id: number) {
    if (form.permissions.includes(id)) {
        form.permissions = form.permissions.filter(pid => pid !== id);
    } else {
        form.permissions.push(id);
    }
}

function selectAll(resource: string) {
    const ids = props.groupedPermissions[resource].map(p => p.id);
    const allSelected = ids.every(id => form.permissions.includes(id));
    if (allSelected) {
        form.permissions = form.permissions.filter(pid => !ids.includes(pid));
    } else {
        form.permissions = [...new Set([...form.permissions, ...ids])];
    }
}

function submit(createAnother = false) {
    if (createAnother) {
        form.create_another = 1;
    } else {
        delete form.create_another;
    }
    form.post(route('roles.store'), {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Role created successfully',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
            });
            if (createAnother) {
                form.reset();
                form.permissions = [];
            }
        },
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Create Role" />
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">Create Role</h1>
        </div>
        <Card>
            <CardHeader>
                <CardTitle>Create Role</CardTitle>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="submit()" class="space-y-6">
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <Label for="name">Name</Label>
                            <input id="name" v-model="form.name" class="w-full rounded border px-3 py-2 mt-1" required />
                        </div>
                        <div class="flex-1">
                            <Label for="guard_name">Guard Name</Label>
                            <input id="guard_name" v-model="form.guard_name" class="w-full rounded border px-3 py-2 mt-1" required />
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div v-for="(permissions, resource) in props.groupedPermissions" :key="resource" class="rounded border p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-semibold">{{ resource }}</span>
                                <button type="button" class="text-xs text-primary underline" @click="selectAll(resource)">
                                    Select all
                                </button>
                            </div>
                            <div class="grid gap-2">
                                <div v-for="permission in permissions" :key="permission.id" class="flex items-center gap-2">
                                    <Checkbox :id="'perm-' + permission.id" :checked="form.permissions.includes(permission.id)" @update:checked="() => togglePermission(permission.id)" />
                                    <Label :for="'perm-' + permission.id">{{ permission.name }}</Label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <CardFooter class="flex gap-2 justify-end">
                        <Button type="submit" variant="default">Create</Button>
                        <Button type="button" variant="secondary" @click="submit(true)">Create & create another</Button>
                        <Link :href="route('roles.index')">
                            <Button type="button" variant="ghost">Cancel</Button>
                        </Link>
                    </CardFooter>
                </form>
            </CardContent>
        </Card>
    </AppLayout>
</template> 