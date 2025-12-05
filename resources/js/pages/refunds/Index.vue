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

function formatCurrency(amount: number) {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2
    }).format(amount);
}

interface RefundRequest {
    id: number;
    tracking_number: string;
    customer_name: string;
    email?: string;
    phone?: string;
    invoice_reference?: string;
    invoice_id?: number;
    invoice_item_id?: number;
    product_id?: number;
    product_name?: string;
    quantity: number;
    amount_requested?: number;
    reason?: string;
    notes?: string;
    media_link?: string;
    status: string;
    review_notes?: string;
    converted_refund_id?: number;
    created_at: string;
    updated_at: string;
    request_type?: 'refund';
    damaged_items_terms?: string;
    invoice?: {
        id: number;
        reference_number: string;
        total_amount: number;
        status: string;
        payment_status?: string;
        created_at: string;
    };
    product?: {
        id: number;
        name: string;
        selling_price: number;
        SKU?: string;
    };
    proof_images?: Array<{
        id: number;
        file_name: string;
        mime_type: string;
        size: number;
        url: string;
    }>;
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
                                <th class="px-4 py-2 text-left">Type</th>
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
                                    <span class="px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        Refund
                                    </span>
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
        <div v-if="showDetails" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50" @click="showDetails = null"></div>
            <div class="relative bg-background rounded-lg shadow-lg w-full max-w-3xl max-h-[90vh] overflow-y-auto p-6">
                <div class="flex items-center justify-between mb-6 border-b pb-4">
                    <h3 class="text-xl font-semibold">Refund Request Details</h3>
                    <button class="text-sm text-gray-500 hover:text-gray-700" @click="showDetails = null">Close</button>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Left Column -->
                    <div class="space-y-4">
                        <div>
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Tracking Number</div>
                            <div class="font-semibold text-lg">{{ showDetails.tracking_number }}</div>
                        </div>

                        <div>
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Status</div>
                            <span class="px-3 py-1 rounded-full text-sm font-medium inline-block"
                                :class="{
                                    'bg-yellow-100 text-yellow-800': showDetails.status === 'pending',
                                    'bg-green-100 text-green-800': showDetails.status === 'approved' || showDetails.status === 'converted',
                                    'bg-red-100 text-red-800': showDetails.status === 'rejected',
                                }"
                            >
                                {{ showDetails.status }}
                            </span>
                        </div>

                        <div v-if="showDetails.invoice">
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Invoice</div>
                            <div>
                                <Link v-if="showDetails.invoice_id" :href="route('invoices.show', showDetails.invoice_id)" class="text-blue-600 hover:underline font-medium">
                                    {{ showDetails.invoice.reference_number || showDetails.invoice_reference || `#${showDetails.invoice_id}` }}
                                </Link>
                                <span v-else class="font-medium">{{ showDetails.invoice_reference || 'N/A' }}</span>
                            </div>
                            <div v-if="showDetails.invoice" class="mt-1 text-sm text-gray-600">
                                <div>Amount: {{ formatCurrency(showDetails.invoice.total_amount) }}</div>
                                <div>Status: {{ showDetails.invoice.status }}</div>
                                <div v-if="showDetails.invoice.payment_status">Payment: {{ showDetails.invoice.payment_status }}</div>
                            </div>
                        </div>
                        <div v-else-if="showDetails.invoice_reference">
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Invoice Reference</div>
                            <div class="font-medium">{{ showDetails.invoice_reference }}</div>
                        </div>

                        <div>
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Customer Information</div>
                            <div class="space-y-1">
                                <div class="font-medium">{{ showDetails.customer_name }}</div>
                                <div v-if="showDetails.email" class="text-sm text-gray-600">{{ showDetails.email }}</div>
                                <div v-if="showDetails.phone" class="text-sm text-gray-600">{{ showDetails.phone }}</div>
                            </div>
                        </div>

                        <div>
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Submitted</div>
                            <div class="text-sm">{{ new Date(showDetails.created_at).toLocaleString() }}</div>
                            <div v-if="showDetails.updated_at && showDetails.updated_at !== showDetails.created_at" class="text-xs text-gray-500 mt-1">
                                Last updated: {{ new Date(showDetails.updated_at).toLocaleString() }}
                            </div>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="space-y-4">
                        <div v-if="showDetails.product || showDetails.product_name">
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Product</div>
                            <div>
                                <div class="font-medium">{{ showDetails.product?.name || showDetails.product_name || 'N/A' }}</div>
                                <div v-if="showDetails.product" class="mt-1 text-sm text-gray-600 space-y-1">
                                    <div v-if="showDetails.product.SKU">SKU: {{ showDetails.product.SKU }}</div>
                                    <div>Price: {{ formatCurrency(showDetails.product.selling_price) }}</div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Quantity</div>
                            <div class="font-medium text-lg">{{ showDetails.quantity }}</div>
                        </div>

                        <div v-if="showDetails.amount_requested">
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Amount Requested</div>
                            <div class="font-medium text-lg text-green-600">{{ formatCurrency(showDetails.amount_requested) }}</div>
                        </div>

                        <div v-if="showDetails.reason">
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Reason</div>
                            <div class="whitespace-pre-wrap text-sm bg-gray-50 p-3 rounded-md border">{{ showDetails.reason }}</div>
                        </div>

                        <div v-if="showDetails.notes">
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Additional Notes</div>
                            <div class="whitespace-pre-wrap text-sm bg-gray-50 p-3 rounded-md border">{{ showDetails.notes }}</div>
                        </div>

                        <div v-if="showDetails.review_notes">
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Review Notes</div>
                            <div class="whitespace-pre-wrap text-sm bg-blue-50 p-3 rounded-md border border-blue-200">{{ showDetails.review_notes }}</div>
                        </div>

                        <div v-if="showDetails.proof_images && showDetails.proof_images.length > 0">
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Proof Images</div>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-2 mt-2">
                                <a v-for="(media, index) in showDetails.proof_images" :key="media.id || index" :href="media.url" target="_blank" rel="noopener noreferrer" class="relative group block">
                                    <img 
                                        :src="media.url" 
                                        :alt="`Proof image ${index + 1}`" 
                                        class="w-full h-32 object-cover rounded-md border hover:opacity-75 transition-opacity bg-gray-100"
                                        @error="(e) => { e.target.style.display = 'none'; }"
                                        loading="lazy"
                                    />
                                    <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-10 transition-opacity rounded-md flex items-center justify-center pointer-events-none">
                                        <svg class="w-6 h-6 text-white opacity-0 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path>
                                        </svg>
                                    </div>
                                </a>
                            </div>
                        </div>

                        <div v-if="showDetails.media_link">
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Media Link (Legacy)</div>
                            <a :href="showDetails.media_link" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline break-all text-sm flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                </svg>
                                View Media
                            </a>
                        </div>

                        <div v-if="showDetails.damaged_items_terms">
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Damaged Items Terms</div>
                            <div class="whitespace-pre-wrap text-sm bg-yellow-50 p-3 rounded-md border border-yellow-200">{{ showDetails.damaged_items_terms }}</div>
                        </div>

                        <div v-if="showDetails.converted_refund_id">
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Converted to Refund</div>
                            <div class="text-sm font-medium text-green-600">Refund ID: #{{ showDetails.converted_refund_id }}</div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t flex justify-end gap-2">
                    <Button variant="outline" @click="showDetails = null">Close</Button>
                    <Link v-if="showDetails.invoice_id" :href="route('invoices.show', showDetails.invoice_id)">
                        <Button variant="default">View Invoice</Button>
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>


