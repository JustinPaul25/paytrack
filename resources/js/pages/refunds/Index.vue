<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import { Button } from '@/components/ui/button';
import Icon from '@/components/Icon.vue';
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
    completed_refund_id?: number;
    created_at: string;
    updated_at: string;
    request_type?: 'refund';
    damaged_items_terms?: string;
    is_damaged?: boolean;
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

interface Refund {
    id: number;
    refund_number: string;
    invoice_id: number;
    product?: { id:number; name:string };
    user?: { id:number; name:string };
    quantity_refunded: number;
    refund_amount: number;
    refund_method: string;
    refund_type?: string;
    status: string;
    reference_number?: string;
    created_at: string;
    is_damaged?: boolean;
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
            // Close all modals
            Swal.close();
            showDetails.value = null;
            // Submit the approval
            router.post(route('refundRequests.approve', id), { review_notes: res.value || undefined }, { preserveScroll: true });
        }
    });
}

function reject(id: number) {
    Swal.fire({
        title: 'Decline this refund request?',
        text: "This will mark the request as rejected. You can't undo this.",
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
            // Close all modals
            Swal.close();
            showDetails.value = null;
            // Submit the rejection
            router.post(route('refundRequests.reject', id), { review_notes: res.value }, { preserveScroll: true });
        }
    });
}

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
    const refund = (page.props.refunds as Paginated<Refund>).data.find(r => r.id === id);
    const isDamaged = refund?.is_damaged ?? false;
    const isExchange = refund?.refund_type === 'exchange';
    const today = new Date().toISOString().split('T')[0];
    
    // Delivery time options
    const deliveryTimeOptions = [
        '09:00 AM - 12:00 PM',
        '12:00 PM - 03:00 PM',
        '03:00 PM - 06:00 PM',
        '06:00 PM - 09:00 PM',
        'Custom'
    ];
    
    const timeOptionsHtml = deliveryTimeOptions.map(opt => 
        `<option value="${opt}">${opt}</option>`
    ).join('');
    
    Swal.fire({
        title: 'Complete refund',
        html: `
        <div style="text-align:left">
            ${isDamaged ? '<div style="background:#fef3c7;border:1px solid #fbbf24;padding:8px;border-radius:4px;margin-bottom:12px;font-size:13px;color:#92400e;"><strong>⚠️ Damaged Items:</strong> These items will not be returned to inventory stock.</div>' : ''}
            <label style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
                <input id="return" type="checkbox" ${isDamaged ? '' : 'checked'} ${isDamaged ? 'disabled' : ''} />
                Return to stock
            </label>
            ${isDamaged ? '<p style="font-size:11px;color:#6b7280;margin-top:4px;margin-left:24px;margin-bottom:12px;">Disabled for damaged items</p>' : ''}
            <label style="display:block;margin:10px 0 4px;font-size:12px;font-weight:500;">Inspection notes</label>
            <textarea id="notes" class="swal2-textarea" placeholder="Notes (optional)" style="margin-bottom:12px;"></textarea>
            
            <div style="border-top:1px solid #e5e7eb;padding-top:12px;margin-top:12px;">
                <div style="font-weight:600;margin-bottom:8px;color:#1f2937;">Schedule Delivery ${isExchange ? 'for Exchange Product' : 'for Refunded Item'}</div>
                <div style="background:#eff6ff;border:1px solid #bfdbfe;padding:8px;border-radius:4px;margin-bottom:12px;font-size:12px;color:#1e40af;">
                    Set the delivery date and time to notify the customer when ${isExchange ? 'the exchange product' : 'the refunded item'} will be delivered.
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:12px;">
                    <div>
                        <label style="display:block;margin-bottom:4px;font-size:12px;font-weight:500;">Delivery Date</label>
                        <input id="delivery_date" type="date" class="swal2-input" style="width:100%;margin:0;" min="${today}" />
                    </div>
                    <div>
                        <label style="display:block;margin-bottom:4px;font-size:12px;font-weight:500;">Delivery Time</label>
                        <select id="delivery_time" class="swal2-input" style="width:100%;margin:0;">
                            <option value="">Select time (optional)</option>
                            ${timeOptionsHtml}
                        </select>
                    </div>
                </div>
                <div id="custom_time_container" style="display:none;margin-bottom:12px;">
                    <label style="display:block;margin-bottom:4px;font-size:12px;font-weight:500;">Custom Time</label>
                    <input id="custom_time" type="text" class="swal2-input" placeholder="e.g., 10:00 AM - 2:00 PM" style="width:100%;margin:0;" />
                </div>
                <div>
                    <label style="display:block;margin-bottom:4px;font-size:12px;font-weight:500;">Delivery Fee (PHP)</label>
                    <input id="delivery_fee" type="number" class="swal2-input" placeholder="0.00" min="0" step="0.01" value="0" style="width:100%;margin:0;" />
                    <p style="font-size:11px;color:#6b7280;margin-top:4px;">Enter the delivery fee for this refund delivery</p>
                </div>
            </div>
        </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Complete',
        confirmButtonColor: '#10b981',
        didOpen: () => {
            // Show custom time input when "Custom" is selected
            const deliveryTimeSelect = document.getElementById('delivery_time') as HTMLSelectElement;
            const customTimeContainer = document.getElementById('custom_time_container');
            const customTimeInput = document.getElementById('custom_time') as HTMLInputElement;
            
            if (deliveryTimeSelect && customTimeContainer && customTimeInput) {
                deliveryTimeSelect.addEventListener('change', () => {
                    if (deliveryTimeSelect.value === 'Custom') {
                        customTimeContainer.style.display = 'block';
                    } else {
                        customTimeContainer.style.display = 'none';
                        customTimeInput.value = '';
                    }
                });
            }
        },
        preConfirm: () => {
            const rtn = isDamaged ? false : (document.getElementById('return') as HTMLInputElement).checked;
            const notes = (document.getElementById('notes') as HTMLTextAreaElement).value;
            
            const data: any = { return_to_stock: rtn, notes: notes || undefined };
            
            // Add delivery info if provided
            const deliveryDate = (document.getElementById('delivery_date') as HTMLInputElement).value;
            const deliveryTime = (document.getElementById('delivery_time') as HTMLSelectElement).value;
            const customTime = (document.getElementById('custom_time') as HTMLInputElement)?.value;
            
            // If date is provided, time is required (and vice versa)
            if (deliveryDate || deliveryTime) {
                if (!deliveryDate) {
                    Swal.showValidationMessage('Please select a delivery date if you want to schedule delivery');
                    return false;
                }
                
                if (!deliveryTime) {
                    Swal.showValidationMessage('Please select a delivery time if you want to schedule delivery');
                    return false;
                }
                
                data.delivery_date = deliveryDate;
                data.delivery_time = deliveryTime === 'Custom' ? (customTime || '') : deliveryTime;
                
                if (deliveryTime === 'Custom' && !customTime) {
                    Swal.showValidationMessage('Please enter a custom delivery time');
                    return false;
                }
                
                // Get delivery fee
                const deliveryFeeInput = document.getElementById('delivery_fee') as HTMLInputElement;
                if (deliveryFeeInput) {
                    const deliveryFee = parseFloat(deliveryFeeInput.value) || 0;
                    data.delivery_fee = deliveryFee;
                }
            }
            
            return data;
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
</script>

<template>
    <AppLayout>
        <Head title="Refunds" />

        <div class="flex items-center justify-between my-6">
            <h1 class="text-2xl font-bold">{{ isCustomer ? 'My Refunds' : 'Refunds' }}</h1>
        </div>

        <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <CardTitle>Refund Requests</CardTitle>
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
                            </select>
                        </div>
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
                                            <div class="flex items-center gap-2">
                                                <span class="font-medium">{{ r.tracking_number }}</span>
                                                <span v-if="r.is_damaged" class="px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800" title="Damaged items">
                                                    ⚠️
                                                </span>
                                            </div>
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
                                                    'bg-green-100 text-green-800': r.status === 'approved',
                                                    'bg-red-100 text-red-800': r.status === 'rejected',
                                                }"
                                            >
                                                {{ r.status === 'completed' ? 'approved' : r.status }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-500">{{ new Date(r.created_at).toLocaleString() }}</td>
                                        <td class="px-4 py-2">
                                            <div class="flex gap-2">
                                                <Button size="sm" variant="ghost" @click="showDetails = r" title="View details">
                                                    <Icon name="eye" class="h-4 w-4" />
                                                </Button>
                                                <Link v-if="r.invoice_id && !isCustomer" :href="route('invoices.show', r.invoice_id)">
                                                    <Button size="sm" variant="ghost" title="View Invoice">
                                                        <Icon name="file-text" class="h-4 w-4" />
                                                    </Button>
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
                                    'bg-green-100 text-green-800': showDetails.status === 'approved' || showDetails.status === 'completed',
                                    'bg-red-100 text-red-800': showDetails.status === 'rejected',
                                }"
                            >
                                {{ showDetails.status === 'completed' ? 'approved' : showDetails.status }}
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
                                <a v-for="(media, index) in showDetails.proof_images" :key="media.id || index" :href="media.url" target="_blank" rel="noopener noreferrer" class="relative group block overflow-hidden rounded-md border border-gray-300 hover:border-blue-500 transition-all">
                                    <img 
                                        :src="media.url" 
                                        :alt="`Proof image ${index + 1}`" 
                                        class="w-full h-32 object-cover transition-transform group-hover:scale-105 bg-gray-100"
                                        @error="(e) => { e.target.src = 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%22 height=%22100%22%3E%3Crect fill=%22%23f3f4f6%22 width=%22100%22 height=%22100%22/%3E%3Ctext fill=%22%239ca3af%22 font-family=%22sans-serif%22 font-size=%2214%22 x=%2250%%22 y=%2250%%22 text-anchor=%22middle%22 dominant-baseline=%22middle%22%3EImage%3C/text%3E%3C/svg%3E'; }"
                                        loading="lazy"
                                    />
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <div class="bg-blue-600 text-white px-3 py-1.5 rounded-md text-sm font-medium opacity-0 group-hover:opacity-100 transform translate-y-2 group-hover:translate-y-0 transition-all flex items-center gap-1.5 shadow-lg">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                            View
                                        </div>
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

                        <div v-if="showDetails.is_damaged">
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Item Status</div>
                            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-md bg-orange-100 text-orange-800 text-sm font-medium">
                                ⚠️ Damaged Items
                            </div>
                            <p class="text-xs text-gray-600 mt-2">These items will not be returned to inventory stock when the refund is completed.</p>
                        </div>

                        <div v-if="showDetails.damaged_items_terms">
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Damaged Items Terms</div>
                            <div class="whitespace-pre-wrap text-sm bg-yellow-50 p-3 rounded-md border border-yellow-200">{{ showDetails.damaged_items_terms }}</div>
                        </div>

                        <div v-if="showDetails.completed_refund_id">
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Completed to Refund</div>
                            <div class="text-sm font-medium text-green-600">Refund ID: #{{ showDetails.completed_refund_id }}</div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t flex justify-end items-center">
                    <div class="flex gap-2">
                        <Button v-if="canProcessRefunds && showDetails.status === 'pending'" variant="default" @click="approve(showDetails.id)">
                            <Icon name="check" class="h-4 w-4 mr-2" />
                            Approve
                        </Button>
                        <Button v-if="canProcessRefunds && showDetails.status === 'pending'" variant="destructive" @click="reject(showDetails.id)">
                            <Icon name="x" class="h-4 w-4 mr-2" />
                            Decline
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
