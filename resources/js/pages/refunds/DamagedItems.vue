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
import { AlertTriangle } from 'lucide-vue-next';
import { type BreadcrumbItem } from '@/types';

function formatCurrency(amount: number) {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2
    }).format(amount);
}

interface Refund {
    id: number;
    refund_number: string;
    invoice_id: number;
    product?: { id: number; name: string };
    user?: { id: number; name: string };
    quantity_refunded: number;
    refund_amount: number;
    refund_method: string;
    status: string;
    reference_number?: string;
    created_at: string;
    is_damaged?: boolean;
    invoice?: {
        id: number;
        reference_number: string;
    };
}

interface RefundRequest {
    id: number;
    tracking_number: string;
    customer_name: string;
    email?: string;
    phone?: string;
    invoice_reference?: string;
    invoice_id?: number;
    product_id?: number;
    product_name?: string;
    quantity: number;
    amount_requested?: number;
    reason?: string;
    notes?: string;
    status: string;
    damaged_items_terms?: string;
    is_damaged?: boolean;
    invoice?: {
        id: number;
        reference_number: string;
    };
    product?: {
        id: number;
        name: string;
        selling_price: number;
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

interface StockMovement {
    id: number;
    product_id: number;
    refund_id?: number;
    invoice_id?: number;
    user_id?: number;
    type: string;
    quantity: number;
    quantity_before?: number;
    quantity_after?: number;
    notes?: string;
    created_at: string;
    product?: { id: number; name: string };
    refund?: { id: number; refund_number: string };
    invoice?: { id: number; reference_number: string };
    user?: { id: number; name: string };
}

interface Stats {
    total_damaged_refunds: number;
    total_damaged_requests: number;
    pending_requests: number;
    total_damaged_value: number;
    total_writeoffs: number;
}

const page = usePage();
const filters = (page.props.filters || {}) as { status?: string; request_status?: string };
const stats = (page.props.stats || {}) as Stats;
const refunds = (page.props.refunds || { data: [], current_page: 1, last_page: 1, total: 0, from: 0, to: 0, prev_page_url: null, next_page_url: null }) as Paginated<Refund>;
const refundRequests = (page.props.refundRequests || { data: [], current_page: 1, last_page: 1, total: 0, from: 0, to: 0, prev_page_url: null, next_page_url: null }) as Paginated<RefundRequest>;

const activeTab = ref<'refunds' | 'requests' | 'disposition'>('refunds');
const showDetails = ref<RefundRequest | null>(null);
const stockMovements = (page.props.stockMovements || []) as StockMovement[];

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Damaged Items', href: route('refunds.damaged-items') },
];

function processRefund(id: number) {
    Swal.fire({
        title: 'Mark as Processed',
        html: `
        <div style="text-align:left">
            <label style="display:block;margin-bottom:4px;font-size:12px">Method</label>
            <select id="method" class="swal2-input" style="width:100%">
                <option value="cash">cash</option>
                <option value="bank_transfer">bank_transfer</option>
                <option value="e-wallet">e-wallet</option>
                <option value="credit_note">credit_note</option>
            </select>
            <label style="display:block;margin:10px 0 4px;font-size:12px">Reference #</label>
            <input id="ref" class="swal2-input" placeholder="Reference number (optional)" />
            <label style="display:block;margin:10px 0 4px;font-size:12px">Notes</label>
            <textarea id="notes" class="swal2-textarea" placeholder="Notes (optional)"></textarea>
        </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Save',
        preConfirm: () => {
            const method = (document.getElementById('method') as HTMLSelectElement).value;
            const ref = (document.getElementById('ref') as HTMLInputElement).value;
            const notes = (document.getElementById('notes') as HTMLTextAreaElement).value;
            return { refund_method: method, reference_number: ref || undefined, notes: notes || undefined };
        },
    }).then((res) => {
        if (res.isConfirmed) {
            router.post(route('refunds.process', id), res.value, { preserveScroll: true });
        }
    });
}

function completeRefund(id: number) {
    const refund = refunds.data.find(r => r.id === id);
    const isDamaged = refund?.is_damaged ?? false;
    
    Swal.fire({
        title: 'Complete refund',
        html: `
        <div style="text-align:left">
            ${isDamaged ? '<div style="background:#fef3c7;border:1px solid #fbbf24;padding:8px;border-radius:4px;margin-bottom:12px;font-size:13px;color:#92400e;"><strong>⚠️ Damaged Items:</strong> These items will not be returned to inventory stock.</div>' : ''}
            <label style="display:flex;align-items:center;gap:8px;">
                <input id="return" type="checkbox" ${isDamaged ? '' : 'checked'} ${isDamaged ? 'disabled' : ''} />
                Return to stock
            </label>
            ${isDamaged ? '<p style="font-size:11px;color:#6b7280;margin-top:4px;margin-left:24px;">Disabled for damaged items</p>' : ''}
            <label style="display:block;margin:10px 0 4px;font-size:12px">Inspection notes</label>
            <textarea id="notes" class="swal2-textarea" placeholder="Notes (optional)"></textarea>
        </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Complete',
        confirmButtonColor: '#10b981',
        preConfirm: () => {
            const rtn = isDamaged ? false : (document.getElementById('return') as HTMLInputElement).checked;
            const notes = (document.getElementById('notes') as HTMLTextAreaElement).value;
            return { return_to_stock: rtn, notes: notes || undefined };
        }
    }).then((res) => {
        if (res.isConfirmed) {
            router.post(route('refunds.complete', id), res.value, { preserveScroll: true });
        }
    });
}

function cancelRefund(id: number) {
    Swal.fire({
        title: 'Cancel this refund?',
        text: 'This will mark the refund as cancelled. A short reason is required.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Cancel refund',
        confirmButtonColor: '#ef4444',
        input: 'textarea',
        inputLabel: 'Reason (required)',
        inputPlaceholder: 'Provide a brief reason...',
        inputAttributes: { 'aria-label': 'Reason (required)' },
        inputValidator: (value) => {
            if (!value || !value.trim()) {
                return 'Please provide a brief reason.';
            }
            return undefined as any;
        },
    }).then((res) => {
        if (res.isConfirmed) {
            router.post(route('refunds.cancel', id), { review_notes: res.value }, { preserveScroll: true });
        }
    });
}

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
        text: 'This will mark the request as rejected. You can\'t undo this.',
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

function goToRefundsPage(pageNum: number) {
    const params: any = { refunds_page: pageNum };
    if (filters.status && filters.status !== 'all') params.status = filters.status;
    if (filters.request_status && filters.request_status !== 'all') params.request_status = filters.request_status;
    router.get(route('refunds.damaged-items'), params, { preserveState: true, replace: true });
}

function goToRequestsPage(pageNum: number) {
    const params: any = { requests_page: pageNum };
    if (filters.status && filters.status !== 'all') params.status = filters.status;
    if (filters.request_status && filters.request_status !== 'all') params.request_status = filters.request_status;
    router.get(route('refunds.damaged-items'), params, { preserveState: true, replace: true });
}

function updateRefundFilter(status: string) {
    const params: any = {};
    if (status && status !== 'all') params.status = status;
    if (filters.request_status && filters.request_status !== 'all') params.request_status = filters.request_status;
    router.get(route('refunds.damaged-items'), params, { preserveState: true, replace: true });
}

function updateRequestFilter(status: string) {
    const params: any = {};
    if (filters.status && filters.status !== 'all') params.status = filters.status;
    if (status && status !== 'all') params.request_status = status;
    router.get(route('refunds.damaged-items'), params, { preserveState: true, replace: true });
}
</script>

<template>
    <AppLayout>
        <Head title="Damaged Items" />

        <div class="flex items-center justify-between my-6">
            <div class="flex items-center gap-3">
                <AlertTriangle class="h-8 w-8 text-orange-500" />
                <h1 class="text-2xl font-bold">Damaged Items</h1>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
            <Card>
                <CardContent class="pt-6">
                    <div class="text-sm font-medium text-gray-500">Total Damaged Refunds</div>
                    <div class="text-2xl font-bold mt-1">{{ stats.total_damaged_refunds || 0 }}</div>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="pt-6">
                    <div class="text-sm font-medium text-gray-500">Total Damaged Requests</div>
                    <div class="text-2xl font-bold mt-1">{{ stats.total_damaged_requests || 0 }}</div>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="pt-6">
                    <div class="text-sm font-medium text-gray-500">Pending Requests</div>
                    <div class="text-2xl font-bold mt-1 text-orange-600">{{ stats.pending_requests || 0 }}</div>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="pt-6">
                    <div class="text-sm font-medium text-gray-500">Total Writeoffs</div>
                    <div class="text-2xl font-bold mt-1 text-red-600">{{ stats.total_writeoffs || 0 }}</div>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="pt-6">
                    <div class="text-sm font-medium text-gray-500">Total Damaged Value</div>
                    <div class="text-2xl font-bold mt-1">{{ formatCurrency(stats.total_damaged_value || 0) }}</div>
                </CardContent>
            </Card>
        </div>

        <!-- Tabs -->
        <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex space-x-8">
                <button
                    @click="activeTab = 'refunds'"
                    :class="[
                        'py-4 px-1 border-b-2 font-medium text-sm',
                        activeTab === 'refunds'
                            ? 'border-orange-500 text-orange-600'
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                    ]"
                >
                    Refunds ({{ refunds.total }})
                </button>
                <button
                    @click="activeTab = 'requests'"
                    :class="[
                        'py-4 px-1 border-b-2 font-medium text-sm',
                        activeTab === 'requests'
                            ? 'border-orange-500 text-orange-600'
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                    ]"
                >
                    Refund Requests ({{ refundRequests.total }})
                </button>
                <button
                    @click="activeTab = 'disposition'"
                    :class="[
                        'py-4 px-1 border-b-2 font-medium text-sm',
                        activeTab === 'disposition'
                            ? 'border-orange-500 text-orange-600'
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                    ]"
                >
                    Where Products Went ({{ stockMovements.length }})
                </button>
            </nav>
        </div>

        <!-- Refunds Tab -->
        <Card v-if="activeTab === 'refunds'">
            <CardHeader>
                <div class="flex items-center justify-between">
                    <CardTitle>Damaged Refunds</CardTitle>
                    <select
                        class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                        :value="filters.status || 'all'"
                        @change="(e:any) => updateRefundFilter(e.target.value)"
                    >
                        <option value="all">All Status</option>
                        <option value="approved">Approved</option>
                        <option value="processed">Processed</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </CardHeader>
            <CardContent>
                <div v-if="!refunds.data.length" class="py-8 text-center text-sm text-gray-500">
                    No damaged refunds found.
                </div>
                <div v-else>
                    <table class="min-w-full divide-y divide-border">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 text-left">Refund #</th>
                                <th class="px-4 py-2 text-left">Invoice</th>
                                <th class="px-4 py-2 text-left">Product</th>
                                <th class="px-4 py-2 text-left">Qty</th>
                                <th class="px-4 py-2 text-left">Amount</th>
                                <th class="px-4 py-2 text-left">Status</th>
                                <th class="px-4 py-2 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="r in refunds.data" :key="r.id" class="hover:bg-muted">
                                <td class="px-4 py-2 font-medium">{{ r.refund_number }}</td>
                                <td class="px-4 py-2">
                                    <Link v-if="r.invoice_id" :href="route('invoices.show', r.invoice_id)" class="text-blue-500 underline">
                                        #{{ r.invoice_id }}
                                    </Link>
                                    <span v-else>—</span>
                                </td>
                                <td class="px-4 py-2">
                                    <div class="flex items-center gap-2">
                                        <span>{{ r.product?.name || '—' }}</span>
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                            ⚠️ Damaged
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-2">{{ r.quantity_refunded }}</td>
                                <td class="px-4 py-2 font-medium">{{ formatCurrency(r.refund_amount) }}</td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium"
                                        :class="{
                                            'bg-blue-100 text-blue-800': r.status === 'approved',
                                            'bg-yellow-100 text-yellow-800': r.status === 'processed',
                                            'bg-green-100 text-green-800': r.status === 'completed',
                                            'bg-red-100 text-red-800': r.status === 'cancelled',
                                        }"
                                    >{{ r.status }}</span>
                                </td>
                                <td class="px-4 py-2">
                                    <div class="flex gap-2">
                                        <Button v-if="r.status === 'approved' || r.status === 'processed'" size="sm" variant="outline" @click="processRefund(r.id)">
                                            Process
                                        </Button>
                                        <Button v-if="r.status === 'approved' || r.status === 'processed'" size="sm" variant="default" @click="completeRefund(r.id)">
                                            Complete
                                        </Button>
                                        <Button v-if="r.status !== 'completed' && r.status !== 'cancelled'" size="sm" variant="destructive" @click="cancelRefund(r.id)">
                                            Cancel
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div v-if="refunds.last_page > 1" class="mt-4 flex items-center justify-between">
                        <div class="text-sm text-gray-500">
                            Showing {{ refunds.from }} to {{ refunds.to }} of {{ refunds.total }} results
                        </div>
                        <div class="flex gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="refunds.current_page === 1"
                                @click="goToRefundsPage(refunds.current_page - 1)"
                            >
                                Previous
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="refunds.current_page === refunds.last_page"
                                @click="goToRefundsPage(refunds.current_page + 1)"
                            >
                                Next
                            </Button>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Refund Requests Tab -->
        <Card v-if="activeTab === 'requests'">
            <CardHeader>
                <div class="flex items-center justify-between">
                    <CardTitle>Damaged Refund Requests</CardTitle>
                    <select
                        class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                        :value="filters.request_status || 'all'"
                        @change="(e:any) => updateRequestFilter(e.target.value)"
                    >
                        <option value="all">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
            </CardHeader>
            <CardContent>
                <div v-if="!refundRequests.data.length" class="py-8 text-center text-sm text-gray-500">
                    No damaged refund requests found.
                </div>
                <div v-else>
                    <table class="min-w-full divide-y divide-border">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 text-left">Tracking #</th>
                                <th class="px-4 py-2 text-left">Customer</th>
                                <th class="px-4 py-2 text-left">Invoice</th>
                                <th class="px-4 py-2 text-left">Product</th>
                                <th class="px-4 py-2 text-left">Qty</th>
                                <th class="px-4 py-2 text-left">Status</th>
                                <th class="px-4 py-2 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="r in refundRequests.data" :key="r.id" class="hover:bg-muted">
                                <td class="px-4 py-2 font-medium">{{ r.tracking_number }}</td>
                                <td class="px-4 py-2">{{ r.customer_name }}</td>
                                <td class="px-4 py-2">
                                    <Link v-if="r.invoice_id" :href="route('invoices.show', r.invoice_id)" class="text-blue-500 underline">
                                        {{ r.invoice_reference || `#${r.invoice_id}` }}
                                    </Link>
                                    <span v-else>—</span>
                                </td>
                                <td class="px-4 py-2">
                                    <div class="flex items-center gap-2">
                                        <span>{{ r.product?.name || r.product_name || '—' }}</span>
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                            ⚠️ Damaged
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-2">{{ r.quantity }}</td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium"
                                        :class="{
                                            'bg-yellow-100 text-yellow-800': r.status === 'pending',
                                            'bg-green-100 text-green-800': r.status === 'approved',
                                            'bg-red-100 text-red-800': r.status === 'rejected',
                                            'bg-blue-100 text-blue-800': r.status === 'completed',
                                        }"
                                    >{{ r.status }}</span>
                                </td>
                                <td class="px-4 py-2">
                                    <div class="flex gap-2">
                                        <Button v-if="r.status === 'pending'" size="sm" variant="default" @click="approve(r.id)">
                                            Approve
                                        </Button>
                                        <Button v-if="r.status === 'pending'" size="sm" variant="destructive" @click="reject(r.id)">
                                            Reject
                                        </Button>
                                        <Button size="sm" variant="outline" @click="showDetails = r">
                                            View
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div v-if="refundRequests.last_page > 1" class="mt-4 flex items-center justify-between">
                        <div class="text-sm text-gray-500">
                            Showing {{ refundRequests.from }} to {{ refundRequests.to }} of {{ refundRequests.total }} results
                        </div>
                        <div class="flex gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="refundRequests.current_page === 1"
                                @click="goToRequestsPage(refundRequests.current_page - 1)"
                            >
                                Previous
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="refundRequests.current_page === refundRequests.last_page"
                                @click="goToRequestsPage(refundRequests.current_page + 1)"
                            >
                                Next
                            </Button>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Disposition Tab - Where Products Went -->
        <Card v-if="activeTab === 'disposition'">
            <CardHeader>
                <CardTitle>Where Damaged Products Went</CardTitle>
                <p class="text-sm text-gray-500 mt-1">Track the disposition of damaged items through stock movements</p>
            </CardHeader>
            <CardContent>
                <div v-if="!stockMovements.length" class="py-8 text-center text-sm text-gray-500">
                    No stock movements found for damaged items.
                </div>
                <div v-else>
                    <table class="min-w-full divide-y divide-border">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 text-left">Date</th>
                                <th class="px-4 py-2 text-left">Product</th>
                                <th class="px-4 py-2 text-left">Refund #</th>
                                <th class="px-4 py-2 text-left">Invoice</th>
                                <th class="px-4 py-2 text-left">Type</th>
                                <th class="px-4 py-2 text-left">Quantity</th>
                                <th class="px-4 py-2 text-left">Stock Before</th>
                                <th class="px-4 py-2 text-left">Stock After</th>
                                <th class="px-4 py-2 text-left">Processed By</th>
                                <th class="px-4 py-2 text-left">Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="movement in stockMovements" :key="movement.id" class="hover:bg-muted">
                                <td class="px-4 py-2 text-sm">
                                    {{ new Date(movement.created_at).toLocaleDateString() }}
                                    <div class="text-xs text-gray-500">
                                        {{ new Date(movement.created_at).toLocaleTimeString() }}
                                    </div>
                                </td>
                                <td class="px-4 py-2">
                                    <div class="font-medium">{{ movement.product?.name || '—' }}</div>
                                </td>
                                <td class="px-4 py-2">
                                    <Link v-if="movement.refund_id && movement.refund" :href="route('refunds.index')" class="text-blue-500 underline">
                                        {{ movement.refund.refund_number }}
                                    </Link>
                                    <span v-else class="text-gray-400">—</span>
                                </td>
                                <td class="px-4 py-2">
                                    <Link v-if="movement.invoice_id && movement.invoice" :href="route('invoices.show', movement.invoice_id)" class="text-blue-500 underline">
                                        {{ movement.invoice.reference_number || `#${movement.invoice_id}` }}
                                    </Link>
                                    <span v-else class="text-gray-400">—</span>
                                </td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium"
                                        :class="{
                                            'bg-red-100 text-red-800': movement.type === 'writeoff',
                                            'bg-orange-100 text-orange-800': movement.type === 'refund',
                                            'bg-blue-100 text-blue-800': movement.type === 'sale',
                                        }"
                                    >
                                        {{ movement.type === 'writeoff' ? '⚠️ Writeoff' : movement.type }}
                                    </span>
                                </td>
                                <td class="px-4 py-2">
                                    <span :class="movement.quantity < 0 ? 'text-red-600 font-medium' : 'text-green-600 font-medium'">
                                        {{ movement.quantity > 0 ? '+' : '' }}{{ movement.quantity }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-sm">{{ movement.quantity_before ?? '—' }}</td>
                                <td class="px-4 py-2 text-sm">{{ movement.quantity_after ?? '—' }}</td>
                                <td class="px-4 py-2 text-sm">{{ movement.user?.name || '—' }}</td>
                                <td class="px-4 py-2 text-sm text-gray-600 max-w-xs truncate" :title="movement.notes">
                                    {{ movement.notes || '—' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>

        <!-- Details Modal -->
        <div v-if="showDetails" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="showDetails = null">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold">Refund Request Details</h3>
                        <Button variant="ghost" size="sm" @click="showDetails = null">×</Button>
                    </div>
                    <div v-if="showDetails" class="space-y-4">
                        <div>
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Tracking Number</div>
                            <div class="text-sm font-medium">{{ showDetails.tracking_number }}</div>
                        </div>
                        <div>
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Customer</div>
                            <div class="text-sm">{{ showDetails.customer_name }}</div>
                            <div v-if="showDetails.email" class="text-xs text-gray-500">{{ showDetails.email }}</div>
                            <div v-if="showDetails.phone" class="text-xs text-gray-500">{{ showDetails.phone }}</div>
                        </div>
                        <div v-if="showDetails.invoice_id">
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Invoice</div>
                            <Link :href="route('invoices.show', showDetails.invoice_id)" class="text-sm text-blue-500 underline">
                                {{ showDetails.invoice_reference || `#${showDetails.invoice_id}` }}
                            </Link>
                        </div>
                        <div>
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Product</div>
                            <div class="text-sm">{{ showDetails.product?.name || showDetails.product_name || '—' }}</div>
                        </div>
                        <div>
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Quantity</div>
                            <div class="text-sm">{{ showDetails.quantity }}</div>
                        </div>
                        <div>
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Status</div>
                            <span class="px-2 py-1 rounded-full text-xs font-medium"
                                :class="{
                                    'bg-yellow-100 text-yellow-800': showDetails.status === 'pending',
                                    'bg-green-100 text-green-800': showDetails.status === 'approved',
                                    'bg-red-100 text-red-800': showDetails.status === 'rejected',
                                    'bg-blue-100 text-blue-800': showDetails.status === 'completed',
                                }"
                            >{{ showDetails.status }}</span>
                        </div>
                        <div v-if="showDetails.reason">
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Reason</div>
                            <div class="text-sm whitespace-pre-wrap">{{ showDetails.reason }}</div>
                        </div>
                        <div v-if="showDetails.damaged_items_terms">
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Damaged Items Terms</div>
                            <div class="text-sm whitespace-pre-wrap bg-yellow-50 p-3 rounded-md border border-yellow-200">{{ showDetails.damaged_items_terms }}</div>
                        </div>
                        <div v-if="showDetails.notes">
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Notes</div>
                            <div class="text-sm whitespace-pre-wrap">{{ showDetails.notes }}</div>
                        </div>
                        <div v-if="showDetails.review_notes">
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Review Notes</div>
                            <div class="text-sm whitespace-pre-wrap">{{ showDetails.review_notes }}</div>
                        </div>
                    </div>
                    <div class="mt-6 pt-4 border-t flex justify-end gap-2">
                        <Button variant="outline" @click="showDetails = null">Close</Button>
                        <Link v-if="showDetails?.invoice_id" :href="route('invoices.show', showDetails.invoice_id)">
                            <Button variant="default">View Invoice</Button>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
