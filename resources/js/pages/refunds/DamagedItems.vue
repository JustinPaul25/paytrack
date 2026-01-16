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
    display_image_url?: string;
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

interface Stats {
    total_damaged_refunds: number;
    total_damaged_value: number;
}

const page = usePage();
const stats = (page.props.stats || {}) as Stats;
const refunds = (page.props.refunds || { data: [], current_page: 1, last_page: 1, total: 0, from: 0, to: 0, prev_page_url: null, next_page_url: null }) as Paginated<Refund>;

const showDetails = ref<Refund | null>(null);
const imageErrors = ref<Set<number>>(new Set());

function handleImageError(refundId: number) {
    imageErrors.value.add(refundId);
}

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

        <!-- Approved Damaged Items -->
        <Card>
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
                                <th class="px-4 py-2 text-left">Image</th>
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
                                <td class="px-4 py-2">
                                    <div class="w-16 h-16 flex items-center justify-center bg-gray-100 rounded-md overflow-hidden">
                                        <img 
                                            v-if="r.display_image_url && !imageErrors.has(r.id)" 
                                            :src="r.display_image_url" 
                                            :alt="r.product?.name || 'Product image'"
                                            class="w-full h-full object-cover"
                                            @error="handleImageError(r.id)"
                                        />
                                        <div v-else class="w-full h-full flex items-center justify-center text-gray-400 text-xs">
                                            No Image
                                        </div>
                                    </div>
                                </td>
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
