<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import Icon from '@/components/Icon.vue';
import { type BreadcrumbItem } from '@/types';

const props = defineProps<{
  branch: any;
  branch_image_url: string;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Branches', href: '/branches' },
    { title: props.branch.name, href: `/branches/${props.branch.id}` },
];

function getStatusBadgeClass(status: string) {
    switch (status) {
        case 'active': return 'bg-green-100 text-green-800';
        case 'inactive': return 'bg-red-100 text-red-800';
        case 'maintenance': return 'bg-yellow-100 text-yellow-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

function formatTime(timeString: string) {
    if (!timeString) return '-';
    return new Date(`2000-01-01T${timeString}`).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Branch - ${branch.name}`" />
        
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">{{ branch.name }}</h1>
            <div class="flex gap-2">
                <Link :href="route('branches.edit', branch.id)">
                    <Button variant="default">
                        <Icon name="edit" class="h-4 w-4 mr-2" />
                        Edit Branch
                    </Button>
                </Link>
                <Link :href="route('branches.index')">
                    <Button variant="outline">
                        <Icon name="arrow-left" class="h-4 w-4 mr-2" />
                        Back to Branches
                    </Button>
                </Link>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Information -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Basic Information -->
                <Card>
                    <CardHeader>
                        <CardTitle>Basic Information</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm font-medium text-gray-500">Branch Code</label>
                                <p class="text-lg font-semibold">{{ branch.code }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Status</label>
                                <div class="mt-1">
                                    <span :class="['px-2 py-1 rounded-full text-xs font-medium', getStatusBadgeClass(branch.status)]">
                                        {{ branch.status }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <label class="text-sm font-medium text-gray-500">Address</label>
                            <p class="text-lg">{{ branch.address }}</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm font-medium text-gray-500">Phone</label>
                                <p class="text-lg">{{ branch.phone || '-' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Email</label>
                                <p class="text-lg">{{ branch.email || '-' }}</p>
                            </div>
                        </div>

                        <div v-if="branch.description">
                            <label class="text-sm font-medium text-gray-500">Description</label>
                            <p class="text-lg">{{ branch.description }}</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Manager Information -->
                <Card v-if="branch.manager_name">
                    <CardHeader>
                        <CardTitle>Manager Information</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="text-sm font-medium text-gray-500">Manager Name</label>
                                <p class="text-lg font-semibold">{{ branch.manager_name }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Manager Phone</label>
                                <p class="text-lg">{{ branch.manager_phone || '-' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Manager Email</label>
                                <p class="text-lg">{{ branch.manager_email || '-' }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Operating Hours -->
                <Card v-if="branch.opening_time || branch.closing_time">
                    <CardHeader>
                        <CardTitle>Operating Hours</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm font-medium text-gray-500">Opening Time</label>
                                <p class="text-lg">{{ formatTime(branch.opening_time) }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Closing Time</label>
                                <p class="text-lg">{{ formatTime(branch.closing_time) }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Branch Image -->
                <Card v-if="branch_image_url">
                    <CardHeader>
                        <CardTitle>Branch Image</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <img :src="branch_image_url" alt="Branch Image" class="w-full h-48 object-cover rounded-lg" />
                    </CardContent>
                </Card>

                <!-- Location -->
                <Card v-if="branch.location">
                    <CardHeader>
                        <CardTitle>Location</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="bg-gray-100 p-4 rounded-lg">
                            <p class="text-sm text-gray-600">
                                <strong>Latitude:</strong> {{ branch.location.lat }}<br>
                                <strong>Longitude:</strong> {{ branch.location.lng }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Timestamps -->
                <Card>
                    <CardHeader>
                        <CardTitle>Timestamps</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <div>
                            <label class="text-sm font-medium text-gray-500">Created</label>
                            <p class="text-sm">{{ new Date(branch.created_at).toLocaleDateString() }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Last Updated</label>
                            <p class="text-sm">{{ new Date(branch.updated_at).toLocaleDateString() }}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template> 