<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import { type BreadcrumbItem } from '@/types';
import { Filter, X, FileText, Calendar } from 'lucide-vue-next';
import Swal from 'sweetalert2';

interface PurchaseRecordItem {
    id: number;
    qty: string;
    unit: string | null;
    description: string;
    unit_price: string;
    amount: string;
}

interface PurchaseRecord {
    id: number;
    reference_number: string;
    supplier_name: string;
    receipt_number: string | null;
    date: string;
    payment_type: 'cash' | 'credit';
    buyer_name: string | null;
    total_amount_due: string;
    items: PurchaseRecordItem[];
    user: { id: number; name: string };
    created_at: string;
}

interface PaginatedRecords {
    data: PurchaseRecord[];
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
    links: { url: string | null; label: string; active: boolean }[];
}

const page = usePage();
const props = defineProps<{
    records: PaginatedRecords;
    filters: { search?: string; start_date?: string; end_date?: string; payment_type?: string };
    stats: { totalRecords: number; totalAmount: number; thisMonth: number };
}>();

const search      = ref(props.filters.search ?? '');
const startDate   = ref(props.filters.start_date ?? '');
const endDate     = ref(props.filters.end_date ?? '');
const paymentType = ref(props.filters.payment_type ?? '');
const showFilters = ref(false);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Purchase Records', href: '/purchase-records' },
];

const hasActiveFilters = computed(() =>
    !!(paymentType.value || startDate.value || endDate.value)
);

function applyFilters() {
    router.get('/purchase-records', {
        search: search.value || undefined,
        payment_type: paymentType.value || undefined,
        start_date: startDate.value || undefined,
        end_date: endDate.value || undefined,
    }, { preserveState: true, replace: true });
}

let searchTimer: ReturnType<typeof setTimeout>;
watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 400);
});
watch([paymentType, startDate, endDate], applyFilters);

function clearFilters() {
    search.value = '';
    paymentType.value = '';
    startDate.value = '';
    endDate.value = '';
    applyFilters();
}

async function deleteRecord(id: number, ref: string) {
    const result = await Swal.fire({
        title: 'Delete Record?',
        text: `Are you sure you want to delete ${ref}? This cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete it',
    });
    if (result.isConfirmed) {
        router.delete(`/purchase-records/${id}`, {
            onSuccess: () => Swal.fire({
                toast: true, position: 'top-end', icon: 'success',
                title: 'Record deleted.', showConfirmButton: false,
                timer: 3000, timerProgressBar: true,
            }),
        });
    }
}

function formatCurrency(val: number | string) {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency', currency: 'PHP', minimumFractionDigits: 2,
    }).format(Number(val));
}

function formatDate(d: string) {
    return new Date(d).toLocaleDateString('en-PH', {
        year: 'numeric', month: 'short', day: 'numeric',
    });
}

function paymentBadge(type: string) {
    return type === 'cash'
        ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
        : 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400';
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Purchase Records" />

        <!-- Stats Bar -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-6 mb-4">
            <Card>
                <CardContent class="p-5 flex items-center gap-4">
                    <div class="h-10 w-10 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center shrink-0">
                        <FileText class="h-5 w-5 text-indigo-600 dark:text-indigo-400" />
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Total Records</p>
                        <p class="text-xl font-bold">{{ props.stats.totalRecords }}</p>
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="p-5 flex items-center gap-4">
                    <div class="h-10 w-10 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center shrink-0">
                        <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Total Purchases</p>
                        <p class="text-xl font-bold text-emerald-600">{{ formatCurrency(props.stats.totalAmount) }}</p>
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="p-5 flex items-center gap-4">
                    <div class="h-10 w-10 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center shrink-0">
                        <Calendar class="h-5 w-5 text-amber-600 dark:text-amber-400" />
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">This Month</p>
                        <p class="text-xl font-bold text-amber-600">{{ formatCurrency(props.stats.thisMonth) }}</p>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Toolbar -->
        <div class="space-y-3 mb-3">
            <div class="flex items-center justify-between gap-2 flex-wrap">
                <div class="flex gap-2 items-center flex-1 min-w-[200px]">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search supplier, reference, or receipt no..."
                        class="flex-1 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    />
                    <Button
                        variant="outline"
                        @click="showFilters = !showFilters"
                        :class="hasActiveFilters ? 'border-blue-500 text-blue-600' : ''"
                    >
                        <Filter class="w-4 h-4 mr-2" />
                        Filters
                        <span v-if="hasActiveFilters" class="ml-2 bg-blue-500 text-white rounded-full w-5 h-5 text-xs flex items-center justify-center">
                            {{ (paymentType ? 1 : 0) + (startDate ? 1 : 0) + (endDate ? 1 : 0) }}
                        </span>
                    </Button>
                </div>
                <Link :href="route('purchase-records.create')">
                    <Button>
                        <span class="mr-1">+</span> Add Purchase Record
                    </Button>
                </Link>
            </div>

            <!-- Filter Panel -->
            <Transition
                enter-active-class="transition-all duration-200 ease-out"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition-all duration-150 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2"
            >
                <Card v-show="showFilters">
                    <CardContent class="p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-sm font-semibold flex items-center gap-2">
                                <Filter class="w-4 h-4" /> Filter Records
                            </h3>
                            <div class="flex gap-2">
                                <Button v-if="hasActiveFilters" variant="ghost" size="sm" @click="clearFilters" class="text-xs">
                                    <X class="w-3 h-3 mr-1" /> Clear All
                                </Button>
                                <Button variant="ghost" size="sm" @click="showFilters = false"><X class="w-3 h-3" /></Button>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="text-xs font-medium text-muted-foreground mb-1 block">Payment Type</label>
                                <select v-model="paymentType" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                    <option value="">All Types</option>
                                    <option value="cash">Cash</option>
                                    <option value="credit">Credit</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-muted-foreground mb-1 block">From Date</label>
                                <input v-model="startDate" type="date" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" />
                            </div>
                            <div>
                                <label class="text-xs font-medium text-muted-foreground mb-1 block">To Date</label>
                                <input v-model="endDate" type="date" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </Transition>
        </div>

        <!-- Table -->
        <Card>
            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border text-sm">
                        <thead class="bg-muted/50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Reference</th>
                                <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Supplier</th>
                                <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Receipt No.</th>
                                <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Date</th>
                                <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Payment</th>
                                <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Items</th>
                                <th class="px-4 py-3 text-right font-semibold text-muted-foreground">Total</th>
                                <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Recorded By</th>
                                <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Actions</th>
                            </tr>
                        </thead>
                        <tbody v-if="props.records.data.length" class="divide-y divide-border">
                            <tr v-for="rec in props.records.data" :key="rec.id" class="hover:bg-muted/30 transition-colors">
                                <td class="px-4 py-3">
                                    <Link :href="route('purchase-records.show', rec.id)" class="font-mono text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                                        {{ rec.reference_number }}
                                    </Link>
                                </td>
                                <td class="px-4 py-3 font-medium">{{ rec.supplier_name }}</td>
                                <td class="px-4 py-3 text-muted-foreground font-mono text-xs">
                                    {{ rec.receipt_number ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-muted-foreground">{{ formatDate(rec.date) }}</td>
                                <td class="px-4 py-3">
                                    <span :class="paymentBadge(rec.payment_type)" class="px-2 py-0.5 rounded-full text-xs font-medium capitalize">
                                        {{ rec.payment_type }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-muted-foreground">{{ rec.items.length }} item(s)</td>
                                <td class="px-4 py-3 text-right font-semibold">{{ formatCurrency(rec.total_amount_due) }}</td>
                                <td class="px-4 py-3 text-muted-foreground text-xs">{{ rec.user?.name ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex gap-1">
                                        <Link :href="route('purchase-records.show', rec.id)">
                                            <Button variant="ghost" size="sm" title="View">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            </Button>
                                        </Link>
                                        <Link :href="route('purchase-records.edit', rec.id)">
                                            <Button variant="ghost" size="sm" title="Edit">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </Button>
                                        </Link>
                                        <Button variant="ghost" size="sm" title="Delete" @click="deleteRecord(rec.id, rec.reference_number)">
                                            <svg class="h-4 w-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                        <tbody v-else>
                            <tr>
                                <td colspan="9" class="px-4 py-12 text-center text-muted-foreground">
                                    <div class="flex flex-col items-center gap-2">
                                        <FileText class="h-10 w-10 opacity-30" />
                                        <p>No purchase records found.</p>
                                        <button v-if="search" type="button" class="text-sm underline underline-offset-4" @click="search = ''">Clear search</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="props.records.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t border-border">
                    <p class="text-xs text-muted-foreground">
                        Showing {{ (props.records.current_page - 1) * props.records.per_page + 1 }}–{{ Math.min(props.records.current_page * props.records.per_page, props.records.total) }} of {{ props.records.total }}
                    </p>
                    <div class="flex gap-1">
                        <template v-for="link in props.records.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                class="px-3 py-1 rounded text-xs border"
                                :class="link.active ? 'bg-primary text-primary-foreground border-primary' : 'bg-background text-muted-foreground border-border hover:bg-muted'"
                                v-html="link.label"
                            />
                            <span v-else class="px-3 py-1 rounded text-xs border border-border text-muted-foreground opacity-40" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </CardContent>
        </Card>
    </AppLayout>
</template>
