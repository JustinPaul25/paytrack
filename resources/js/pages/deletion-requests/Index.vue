<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import Icon from '@/components/Icon.vue';
import Swal from 'sweetalert2';
import { AlertCircle, CheckCircle, XCircle, Clock } from 'lucide-vue-next';

interface DeletionRequest {
    id: number;
    requested_by: number;
    deletable_type: string;
    deletable_id: number;
    deletable_name: string;
    reason?: string;
    status: 'pending' | 'approved' | 'rejected';
    reviewed_by?: number;
    review_notes?: string;
    reviewed_at?: string;
    created_at: string;
    updated_at: string;
    requester?: {
        id: number;
        name: string;
        email: string;
    };
    reviewer?: {
        id: number;
        name: string;
        email: string;
    };
}

interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    from: number;
    to: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}

const props = defineProps<{
    requests: Paginated<DeletionRequest>;
    filters: {
        status?: string;
    };
}>();

const statusFilter = ref(props.filters.status || 'all');
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

watch(statusFilter, (val) => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        updateFilters();
    }, 300);
});

function updateFilters() {
    const params: any = {};
    if (statusFilter.value && statusFilter.value !== 'all') {
        params.status = statusFilter.value;
    }
    router.get('/deletion-requests', params, { preserveState: true, replace: true });
}

function goToPage(pageNum: number) {
    const params: any = { page: pageNum };
    if (statusFilter.value && statusFilter.value !== 'all') {
        params.status = statusFilter.value;
    }
    router.get('/deletion-requests', params, { preserveState: true, replace: true });
}

function getStatusIcon(status: string) {
    switch (status) {
        case 'pending':
            return Clock;
        case 'approved':
            return CheckCircle;
        case 'rejected':
            return XCircle;
        default:
            return AlertCircle;
    }
}

function getStatusColor(status: string) {
    switch (status) {
        case 'pending':
            return 'text-yellow-600 bg-yellow-50 border-yellow-200';
        case 'approved':
            return 'text-green-600 bg-green-50 border-green-200';
        case 'rejected':
            return 'text-red-600 bg-red-50 border-red-200';
        default:
            return 'text-gray-600 bg-gray-50 border-gray-200';
    }
}

function getDeletableTypeLabel(type: string): string {
    const parts = type.split('\\');
    return parts[parts.length - 1];
}

async function approveRequest(request: DeletionRequest) {
    const result = await Swal.fire({
        title: 'Approve Deletion Request?',
        html: `
            <p>Are you sure you want to approve this deletion request?</p>
            <p class="mt-2 text-sm text-gray-600"><strong>Item:</strong> ${request.deletable_name}</p>
            ${request.reason ? `<p class="mt-2 text-sm text-gray-600"><strong>Reason:</strong> ${request.reason}</p>` : ''}
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, approve',
        cancelButtonText: 'Cancel',
        input: 'textarea',
        inputLabel: 'Review Notes (optional)',
        inputPlaceholder: 'Add any notes about this approval...',
        inputAttributes: {
            'aria-label': 'Review notes'
        },
        showLoaderOnConfirm: true,
        preConfirm: (notes) => {
            return router.post(`/deletion-requests/${request.id}/approve`, {
                review_notes: notes || null
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire('Approved', 'Deletion request approved and item deleted successfully.', 'success');
                },
                onError: (errors) => {
                    Swal.fire('Error', errors.message || 'Failed to approve deletion request.', 'error');
                }
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    });
}

async function rejectRequest(request: DeletionRequest) {
    const result = await Swal.fire({
        title: 'Reject Deletion Request?',
        html: `
            <p>Are you sure you want to reject this deletion request?</p>
            <p class="mt-2 text-sm text-gray-600"><strong>Item:</strong> ${request.deletable_name}</p>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, reject',
        cancelButtonText: 'Cancel',
        input: 'textarea',
        inputLabel: 'Rejection Notes',
        inputPlaceholder: 'Please provide a reason for rejection...',
        inputAttributes: {
            'aria-label': 'Rejection notes'
        },
        inputValidator: (value) => {
            if (!value) {
                return 'Please provide a reason for rejection';
            }
        },
        showLoaderOnConfirm: true,
        preConfirm: (notes) => {
            return router.post(`/deletion-requests/${request.id}/reject`, {
                review_notes: notes
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire('Rejected', 'Deletion request rejected.', 'success');
                },
                onError: (errors) => {
                    Swal.fire('Error', errors.message || 'Failed to reject deletion request.', 'error');
                }
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    });
}

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}
</script>

<template>
    <AppLayout>
        <Head title="Deletion Requests" />

        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Deletion Requests</h1>
                    <p class="mt-1 text-sm text-gray-500">Review and manage deletion requests from staff members</p>
                </div>
            </div>

            <!-- Filters -->
            <Card>
                <CardHeader>
                    <CardTitle>Filters</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex gap-4">
                        <div class="w-48">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <Select v-model="statusFilter">
                                <SelectTrigger>
                                    <SelectValue placeholder="All statuses" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Statuses</SelectItem>
                                    <SelectItem value="pending">Pending</SelectItem>
                                    <SelectItem value="approved">Approved</SelectItem>
                                    <SelectItem value="rejected">Rejected</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Requests List -->
            <Card>
                <CardHeader>
                    <CardTitle>Requests ({{ requests.total }})</CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="requests.data.length === 0" class="text-center py-12">
                        <AlertCircle class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No deletion requests</h3>
                        <p class="mt-1 text-sm text-gray-500">There are no deletion requests matching your filters.</p>
                    </div>

                    <div v-else class="space-y-4">
                        <div
                            v-for="request in requests.data"
                            :key="request.id"
                            class="border rounded-lg p-4 hover:bg-gray-50 transition-colors"
                        >
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <component
                                            :is="getStatusIcon(request.status)"
                                            :class="`h-5 w-5 ${getStatusColor(request.status).split(' ')[0]}`"
                                        />
                                        <span
                                            :class="`px-2 py-1 text-xs font-medium rounded border ${getStatusColor(request.status)}`"
                                        >
                                            {{ request.status.charAt(0).toUpperCase() + request.status.slice(1) }}
                                        </span>
                                        <span class="text-sm text-gray-500">
                                            {{ formatDate(request.created_at) }}
                                        </span>
                                    </div>

                                    <h3 class="text-lg font-semibold text-gray-900 mb-1">
                                        {{ request.deletable_name }}
                                    </h3>

                                    <div class="text-sm text-gray-600 space-y-1">
                                        <p>
                                            <span class="font-medium">Type:</span>
                                            {{ getDeletableTypeLabel(request.deletable_type) }}
                                        </p>
                                        <p>
                                            <span class="font-medium">Requested by:</span>
                                            {{ request.requester?.name || 'Unknown' }}
                                            <span class="text-gray-400">({{ request.requester?.email }})</span>
                                        </p>
                                        <p v-if="request.reason">
                                            <span class="font-medium">Reason:</span>
                                            {{ request.reason }}
                                        </p>
                                        <p v-if="request.reviewer">
                                            <span class="font-medium">Reviewed by:</span>
                                            {{ request.reviewer.name }}
                                            <span v-if="request.reviewed_at" class="text-gray-400">
                                                on {{ formatDate(request.reviewed_at) }}
                                            </span>
                                        </p>
                                        <p v-if="request.review_notes">
                                            <span class="font-medium">Review Notes:</span>
                                            {{ request.review_notes }}
                                        </p>
                                    </div>
                                </div>

                                <div v-if="request.status === 'pending'" class="flex gap-2 ml-4">
                                    <Button
                                        @click="approveRequest(request)"
                                        variant="default"
                                        class="bg-green-600 hover:bg-green-700"
                                    >
                                        <CheckCircle class="h-4 w-4 mr-2" />
                                        Approve
                                    </Button>
                                    <Button
                                        @click="rejectRequest(request)"
                                        variant="destructive"
                                    >
                                        <XCircle class="h-4 w-4 mr-2" />
                                        Reject
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="requests.last_page > 1" class="mt-6 flex items-center justify-between">
                        <div class="text-sm text-gray-700">
                            Showing {{ requests.from }} to {{ requests.to }} of {{ requests.total }} results
                        </div>
                        <div class="flex gap-2">
                            <Button
                                v-if="requests.prev_page_url"
                                @click="goToPage(requests.current_page - 1)"
                                variant="outline"
                                size="sm"
                            >
                                Previous
                            </Button>
                            <Button
                                v-if="requests.next_page_url"
                                @click="goToPage(requests.current_page + 1)"
                                variant="outline"
                                size="sm"
                            >
                                Next
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
