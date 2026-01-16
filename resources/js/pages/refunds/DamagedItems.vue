<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import { Button } from '@/components/ui/button';
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
    proof_images?: Array<{
        id: number;
        url: string;
        name: string;
    }>;
    refund_request?: {
        id: number;
        tracking_number: string;
        customer_name: string;
        reason?: string;
        damaged_items_terms?: string;
        notes?: string;
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
    total_damaged_value: number;
    total_writeoffs: number;
}

const page = usePage();
const stats = (page.props.stats || {}) as Stats;
const refunds = (page.props.refunds || { data: [], current_page: 1, last_page: 1, total: 0, from: 0, to: 0, prev_page_url: null, next_page_url: null }) as Paginated<Refund>;

const activeTab = ref<'refunds' | 'disposition'>('refunds');
const showDetails = ref<Refund | null>(null);
const stockMovements = (page.props.stockMovements || []) as StockMovement[];

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Damaged Items', href: route('refunds.damaged-items') },
];

function goToRefundsPage(pageNum: number) {
    router.get(route('refunds.damaged-items'), { refunds_page: pageNum }, { preserveState: true, replace: true });
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
                    Approved Damaged Items ({{ refunds.total }})
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

        <!-- Approved Damaged Items Tab -->
        <Card v-if="activeTab === 'refunds'">
            <CardHeader>
                <CardTitle>Approved Damaged Items</CardTitle>
                <p class="text-sm text-gray-500 mt-1">View-only listing of damaged items from approved refund requests</p>
            </CardHeader>
            <CardContent>
                <div v-if="!refunds.data.length" class="py-8 text-center text-sm text-gray-500">
                    No approved damaged items found.
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
                                    <Button size="sm" variant="outline" @click="showDetails = r">
                                        View
                                    </Button>
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
            <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold">Damaged Item Details</h3>
                        <Button variant="ghost" size="sm" @click="showDetails = null">×</Button>
                    </div>
                    <div v-if="showDetails" class="space-y-4">
                        <div>
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Refund Number</div>
                            <div class="text-sm font-medium">{{ showDetails.refund_number }}</div>
                        </div>
                        <div v-if="showDetails.refund_request?.tracking_number">
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Tracking Number</div>
                            <div class="text-sm">{{ showDetails.refund_request.tracking_number }}</div>
                        </div>
                        <div v-if="showDetails.refund_request?.customer_name">
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Customer</div>
                            <div class="text-sm">{{ showDetails.refund_request.customer_name }}</div>
                        </div>
                        <div v-if="showDetails.invoice_id">
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Invoice</div>
                            <Link :href="route('invoices.show', showDetails.invoice_id)" class="text-sm text-blue-500 underline">
                                {{ showDetails.invoice?.reference_number || `#${showDetails.invoice_id}` }}
                            </Link>
                        </div>
                        <div>
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Product</div>
                            <div class="text-sm">{{ showDetails.product?.name || '—' }}</div>
                        </div>
                        <div>
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Quantity</div>
                            <div class="text-sm">{{ showDetails.quantity_refunded }}</div>
                        </div>
                        <div>
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Refund Amount</div>
                            <div class="text-sm font-medium">{{ formatCurrency(showDetails.refund_amount) }}</div>
                        </div>
                        <div v-if="showDetails.refund_request?.reason">
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Reason</div>
                            <div class="text-sm whitespace-pre-wrap">{{ showDetails.refund_request.reason }}</div>
                        </div>
                        <div v-if="showDetails.refund_request?.damaged_items_terms">
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Damaged Items Terms</div>
                            <div class="text-sm whitespace-pre-wrap bg-yellow-50 p-3 rounded-md border border-yellow-200">{{ showDetails.refund_request.damaged_items_terms }}</div>
                        </div>
                        <div v-if="showDetails.refund_request?.notes">
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Notes</div>
                            <div class="text-sm whitespace-pre-wrap">{{ showDetails.refund_request.notes }}</div>
                        </div>
                        <div v-if="showDetails.proof_images && showDetails.proof_images.length > 0">
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Proof Images</div>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                <div v-for="image in showDetails.proof_images" :key="image.id" class="relative group">
                                    <img 
                                        :src="image.url" 
                                        :alt="image.name || 'Proof image'"
                                        class="w-full h-48 object-cover rounded-lg border border-gray-200 cursor-pointer hover:opacity-90 transition-opacity"
                                        @click="window.open(image.url, '_blank')"
                                    />
                                    <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-10 transition-all rounded-lg flex items-center justify-center">
                                        <span class="text-white text-sm opacity-0 group-hover:opacity-100">Click to enlarge</span>
                                    </div>
                                    <div class="mt-1 text-xs text-gray-500 truncate" :title="image.name">{{ image.name }}</div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-sm text-gray-400 italic">No proof images available</div>
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
