<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import { Button } from '@/components/ui/button';
import Swal from 'sweetalert2';

interface Refund {
    id: number;
    refund_number: string;
    invoice_id: number;
    product?: { id:number; name:string };
    user?: { id:number; name:string };
    quantity_refunded: number;
    refund_amount: number;
    refund_method: string;
    status: string;
    reference_number?: string;
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
    Swal.fire({
        title: 'Complete refund',
        html: `
        <div style="text-align:left">
            <label style="display:flex;align-items:center;gap:8px;">
                <input id="return" type="checkbox" checked />
                Return to stock
            </label>
            <label style="display:block;margin:10px 0 4px;font-size:12px">Inspection notes</label>
            <textarea id="notes" class="swal2-textarea" placeholder="Notes (optional)"></textarea>
        </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Complete',
        confirmButtonColor: '#10b981',
        preConfirm: () => {
            const rtn = (document.getElementById('return') as HTMLInputElement).checked;
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

function formatCurrency(amount: number) {
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', minimumFractionDigits: 2 }).format(amount);
}
</script>

<template>
    <AppLayout>
        <Head title="Refunds" />

        <div class="flex items-center justify-between my-6">
            <h1 class="text-2xl font-bold">Refunds</h1>
            <div class="flex gap-2">
                <select
                    class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                    :value="filters.status || 'approved'"
                    @change="(e:any) => router.get(route('refunds.index'), { status: e.target.value }, { preserveState: true, replace: true })"
                >
                    <option value="approved">Approved</option>
                    <option value="processed">Processed</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Refunds</CardTitle>
            </CardHeader>
            <CardContent>
                <div v-if="!(page.props.refunds as Paginated<Refund>).data.length" class="py-8 text-center text-sm text-gray-500">
                    <div v-if="(filters.status && filters.status !== '')">
                        No refunds found for the selected filter.
                        <div class="mt-3">
                            <Button variant="outline" @click="router.get(route('refunds.index'), { status: 'approved' }, { preserveState: true, replace: true })">
                                Clear filters
                            </Button>
                        </div>
                    </div>
                    <div v-else>
                        No refunds found.
                    </div>
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
                            <tr v-for="r in (page.props.refunds as Paginated<Refund>).data" :key="r.id" class="hover:bg-muted">
                                <td class="px-4 py-2 font-medium">{{ r.refund_number }}</td>
                                <td class="px-4 py-2">
                                    <Link :href="route('invoices.show', r.invoice_id)" class="text-blue-500 underline">#{{ r.invoice_id }}</Link>
                                </td>
                                <td class="px-4 py-2">{{ (r as any).product?.name || '—' }}</td>
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
                </div>
            </CardContent>
        </Card>
    </AppLayout>
</template>


