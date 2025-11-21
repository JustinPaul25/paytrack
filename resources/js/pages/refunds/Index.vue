<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import { Button } from '@/components/ui/button';
import Swal from 'sweetalert2';
import { ref } from 'vue';

interface RefundRequest {
    id: number;
    tracking_number: string;
    customer_name: string;
    email?: string;
    invoice_reference?: string;
    invoice_id?: number;
    product_id?: number;
    quantity: number;
    amount_requested?: number;
    reason?: string;
    media_link?: string;
    status: string;
    created_at: string;
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

const page = usePage();
const filters = (page.props.filters || {}) as { status?: string };
const isCustomer = Array.isArray((page.props as any).auth?.userRoles) && (page.props as any).auth.userRoles.includes('Customer');
const isAdmin = Array.isArray((page.props as any).auth?.userRoles) && (page.props as any).auth.userRoles.includes('Admin');
const isStaff = Array.isArray((page.props as any).auth?.userRoles) && (page.props as any).auth.userRoles.includes('Staff');
const canProcessRefunds = isAdmin || isStaff;

const showDetails = ref<RefundRequest | null>(null);

function approve(id: number) {
    Swal.fire({
        title: 'Approve this refund request?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Approve',
        confirmButtonColor: '#10b981',
        input: 'textarea',
        inputLabel: 'Approval notes (optional)',
        inputPlaceholder: 'Notes for this approval...',
        inputAttributes: { 'aria-label': 'Approval notes (optional)' },
    }).then((res) => {
        if (res.isConfirmed) {
            router.post(route('refundRequests.approve', id), { review_notes: res.value || undefined }, { preserveScroll: true });
        }
    });
}

function reject(id: number) {
    Swal.fire({
        title: 'Decline this refund request?',
        text: 'This will mark the request as rejected. You can’t undo this.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Decline',
        confirmButtonColor: '#ef4444',
        input: 'textarea',
        inputLabel: 'Reason (required)',
        inputPlaceholder: 'Provide a reason for declining...',
        inputAttributes: { 'aria-label': 'Reason (required)' },
        inputValidator: (value) => {
            if (!value || !value.trim()) {
                return 'Please provide a brief reason.';
            }
            return undefined as any;
        },
    }).then((res) => {
        if (res.isConfirmed) {
            router.post(route('refundRequests.reject', id), { review_notes: res.value }, { preserveScroll: true });
        }
    });
}
</script>

<template>
    <AppLayout>
        <Head title="Refund Requests" />

        <div class="flex items-center justify-between my-6">
            <h1 class="text-2xl font-bold">{{ isCustomer ? 'My Refund Requests' : 'Refund Requests' }}</h1>
            <div class="flex gap-2">
                <select
                    class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                    :value="filters.status || 'all'"
                    @change="(e:any) => {
                        const status = e.target.value === 'all' ? '' : e.target.value;
                        router.get(route('refundRequests.index'), status ? { status } : {}, { preserveState: true, replace: true });
                    }"
                >
                    <option value="all">All</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                    <option value="converted">Converted</option>
                </select>
            </div>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Requests</CardTitle>
            </CardHeader>
            <CardContent>
                <div v-if="!(page.props.refundRequests as Paginated<RefundRequest>).data.length" class="py-8 text-center text-sm text-gray-500">
                    <div v-if="(filters.status && filters.status !== '' && filters.status !== 'all')">
                        No refund requests for the selected filter.
                        <div class="mt-3">
                            <Button variant="outline" @click="router.get(route('refundRequests.index'), {}, { preserveState: true, replace: true })">
                                Clear filters
                            </Button>
                        </div>
                    </div>
                    <div v-else>
                        No refund requests.
                    </div>
                </div>
                <div v-else>
                    <table class="min-w-full divide-y divide-border">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 text-left">Tracking</th>
                                <th class="px-4 py-2 text-left">Invoice</th>
                                <th v-if="!isCustomer" class="px-4 py-2 text-left">Customer</th>
                                <th class="px-4 py-2 text-left">Qty</th>
                                <th class="px-4 py-2 text-left">Status</th>
                                <th class="px-4 py-2 text-left">Submitted</th>
                                <th class="px-4 py-2 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="r in (page.props.refundRequests as Paginated<RefundRequest>).data" :key="r.id" class="hover:bg-muted">
                                <td class="px-4 py-2">
                                    <div class="font-medium">{{ r.tracking_number }}</div>
                                    <div v-if="r.reason" class="text-xs text-gray-500 truncate max-w-64">{{ r.reason }}</div>
                                </td>
                                <td class="px-4 py-2">
                                    <Link v-if="r.invoice_id" :href="route('invoices.show', r.invoice_id)" class="text-sm text-blue-600 hover:underline">
                                        {{ r.invoice_reference || ('#' + r.invoice_id) }}
                                    </Link>
                                    <div v-else class="text-sm">{{ r.invoice_reference || ('#' + r.invoice_id) }}</div>
                                </td>
                                <td v-if="!isCustomer" class="px-4 py-2">
                                    <div class="text-sm">{{ r.customer_name }}</div>
                                    <div v-if="r.email" class="text-xs text-gray-500">{{ r.email }}</div>
                                </td>
                                <td class="px-4 py-2">{{ r.quantity }}</td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium"
                                        :class="{
                                            'bg-yellow-100 text-yellow-800': r.status === 'pending',
                                            'bg-green-100 text-green-800': r.status === 'approved' || r.status === 'converted',
                                            'bg-red-100 text-red-800': r.status === 'rejected',
                                        }"
                                    >
                                        {{ r.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ new Date(r.created_at).toLocaleString() }}</td>
                                <td class="px-4 py-2">
                                    <div class="flex gap-2">
                                        <Button v-if="canProcessRefunds && r.status === 'pending'" size="sm" variant="default" @click="approve(r.id)">
                                            Approve
                                        </Button>
                                        <Button v-if="canProcessRefunds && r.status === 'pending'" size="sm" variant="destructive" @click="reject(r.id)">
                                            Decline
                                        </Button>
                                        <Button size="sm" variant="outline" @click="showDetails = r">View details</Button>
                                        <Link v-if="r.invoice_id && !isCustomer" :href="route('invoices.show', r.invoice_id)">
                                            <Button size="sm" variant="ghost">View Invoice</Button>
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>

        <!-- Details Modal -->
        <div v-if="showDetails" class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="absolute inset-0 bg-black/50" @click="showDetails = null"></div>
            <div class="relative bg-background rounded-lg shadow-lg w-[90%] max-w-lg p-6">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-lg font-semibold">Refund Request Details</h3>
                    <button class="text-sm text-gray-500" @click="showDetails = null">Close</button>
                </div>
                <div class="space-y-3">
                    <div>
                        <div class="text-xs text-gray-500">Tracking</div>
                        <div class="font-medium">{{ showDetails.tracking_number }}</div>
                    </div>
                    <div v-if="showDetails.product_name">
                        <div class="text-xs text-gray-500">Product</div>
                        <div>{{ showDetails.product_name }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Quantity</div>
                        <div>{{ showDetails.quantity }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Description</div>
                        <div class="whitespace-pre-wrap">{{ (showDetails as any).reason || '—' }}</div>
                    </div>
                    <div v-if="showDetails.media_link">
                        <div class="text-xs text-gray-500">Media Link</div>
                        <a :href="showDetails.media_link" target="_blank" class="text-blue-500 underline break-all">{{ showDetails.media_link }}</a>
                    </div>
                </div>
                <div class="mt-6 flex justify-end">
                    <Button variant="default" @click="showDetails = null">Done</Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>


