<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import { Button } from '@/components/ui/button';
import Swal from 'sweetalert2';

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

function approve(id: number) {
    Swal.fire({
        title: 'Approve this refund request?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Approve',
        confirmButtonColor: '#10b981',
    }).then((res) => {
        if (res.isConfirmed) {
            router.post(route('refundRequests.approve', id), {}, { preserveScroll: true });
        }
    });
}
</script>

<template>
    <AppLayout>
        <Head title="Refund Requests" />

        <div class="flex items-center justify-between my-6">
            <h1 class="text-2xl font-bold">Refund Requests</h1>
            <div class="flex gap-2">
                <select
                    class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                    :value="filters.status || 'pending'"
                    @change="(e:any) => router.get(route('refundRequests.index'), { status: e.target.value }, { preserveState: true, replace: true })"
                >
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
                    No refund requests.
                </div>
                <div v-else>
                    <table class="min-w-full divide-y divide-border">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 text-left">Tracking</th>
                                <th class="px-4 py-2 text-left">Invoice</th>
                                <th class="px-4 py-2 text-left">Customer</th>
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
                                    <div class="text-sm">{{ r.invoice_reference || ('#' + r.invoice_id) }}</div>
                                </td>
                                <td class="px-4 py-2">
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
                                        <Button v-if="r.status === 'pending'" size="sm" variant="default" @click="approve(r.id)">
                                            Approve
                                        </Button>
                                        <Link v-if="r.invoice_id" :href="route('invoices.show', r.invoice_id)">
                                            <Button size="sm" variant="ghost">View Invoice</Button>
                                        </Link>
                                        <a v-if="r.media_link" :href="r.media_link" target="_blank">
                                            <Button size="sm" variant="outline">Media</Button>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    </AppLayout>
</template>


