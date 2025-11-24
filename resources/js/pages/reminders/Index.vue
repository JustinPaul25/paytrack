<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Select } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import Swal from 'sweetalert2';
import { type BreadcrumbItem } from '@/types';
import { Bell, AlertCircle, CheckCircle, XCircle, Calendar, DollarSign, Clock, Plus } from 'lucide-vue-next';

interface Reminder {
    id: number;
    type: 'bill_payment' | 'customer_due' | 'credit_term';
    title: string;
    description: string | null;
    due_date: string;
    amount: number | null;
    currency: string;
    priority: 'low' | 'medium' | 'high';
    status: 'pending' | 'completed' | 'dismissed';
    is_read: boolean;
    customer?: { id: number; name: string; company_name?: string } | null;
    invoice?: { id: number; reference_number: string } | null;
    order?: { id: number; reference_number: string } | null;
    expense?: { id: number; expense_type: string } | null;
    created_at: string;
}

interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}

interface ReminderStats {
    total: number;
    overdue: number;
    upcoming: number;
    today: number;
    byType: {
        bill_payment: number;
        customer_due: number;
        credit_term: number;
    };
}

const page = usePage();
const filters = ref<{ type?: string; status?: string; priority?: string; search?: string; filter?: string }>(
    page.props.filters ? (page.props.filters as any) : {}
);
const type = ref(filters.value.type || 'all');
const status = ref(filters.value.status || 'pending');
const priority = ref(filters.value.priority || 'all');
const search = ref(filters.value.search || '');
const filter = ref(filters.value.filter || 'all');
const showStats = ref(false);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Reminders', href: '/reminders' },
];

const typeOptions = [
    { value: 'all', label: 'All Types' },
    { value: 'bill_payment', label: 'Bill Payment' },
    { value: 'customer_due', label: 'Customer Due' },
    { value: 'credit_term', label: 'Credit Term' },
];

const statusOptions = [
    { value: 'all', label: 'All Status' },
    { value: 'pending', label: 'Pending' },
    { value: 'completed', label: 'Completed' },
    { value: 'dismissed', label: 'Dismissed' },
];

const priorityOptions = [
    { value: 'all', label: 'All Priorities' },
    { value: 'high', label: 'High' },
    { value: 'medium', label: 'Medium' },
    { value: 'low', label: 'Low' },
];

const filterOptions = [
    { value: 'all', label: 'All Reminders' },
    { value: 'overdue', label: 'Overdue' },
    { value: 'upcoming', label: 'Upcoming (7 days)' },
    { value: 'today', label: 'Due Today' },
];

let searchTimeout: ReturnType<typeof setTimeout> | null = null;
watch(search, (val) => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        updateFilters();
    }, 400);
});

watch([type, status, priority, filter], () => {
    updateFilters();
});

function updateFilters() {
    router.get('/reminders', {
        type: type.value !== 'all' ? type.value : undefined,
        status: status.value !== 'all' ? status.value : undefined,
        priority: priority.value !== 'all' ? priority.value : undefined,
        search: search.value || undefined,
        filter: filter.value !== 'all' ? filter.value : undefined,
    }, { preserveState: true, replace: true });
}

function goToPage(pageNum: number) {
    router.get('/reminders', {
        type: type.value !== 'all' ? type.value : undefined,
        status: status.value !== 'all' ? status.value : undefined,
        priority: priority.value !== 'all' ? priority.value : undefined,
        search: search.value || undefined,
        filter: filter.value !== 'all' ? filter.value : undefined,
        page: pageNum,
    }, { preserveState: true, replace: true });
}

function markAsRead(reminderId: number) {
    router.post(`/reminders/${reminderId}/mark-read`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Reminder marked as read',
                showConfirmButton: false,
                timer: 2000,
            });
        },
    });
}

function markAsCompleted(reminderId: number) {
    router.post(`/reminders/${reminderId}/mark-completed`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Reminder marked as completed',
                showConfirmButton: false,
                timer: 2000,
            });
        },
    });
}

function dismiss(reminderId: number) {
    Swal.fire({
        title: 'Dismiss this reminder?',
        text: 'This will mark the reminder as dismissed.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#8f5be8',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, dismiss it',
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(`/reminders/${reminderId}/dismiss`, {}, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Reminder dismissed',
                        showConfirmButton: false,
                        timer: 2000,
                    });
                },
            });
        }
    });
}

function markAllAsRead() {
    router.post('/reminders/mark-all-read', {}, {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'All reminders marked as read',
                showConfirmButton: false,
                timer: 2000,
            });
        },
    });
}

function getTypeLabel(type: string) {
    switch (type) {
        case 'bill_payment': return 'Bill Payment';
        case 'customer_due': return 'Customer Due';
        case 'credit_term': return 'Credit Term';
        default: return type;
    }
}

function getTypeBadgeClass(type: string) {
    switch (type) {
        case 'bill_payment': return 'bg-blue-100 text-blue-800';
        case 'customer_due': return 'bg-purple-100 text-purple-800';
        case 'credit_term': return 'bg-orange-100 text-orange-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

function getPriorityBadgeClass(priority: string) {
    switch (priority) {
        case 'high': return 'bg-red-100 text-red-800';
        case 'medium': return 'bg-yellow-100 text-yellow-800';
        case 'low': return 'bg-green-100 text-green-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

function formatCurrency(amount: number | null) {
    if (amount === null) return 'N/A';
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2
    }).format(amount);
}

function formatDate(dateString: string) {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function isOverdue(dueDate: string) {
    return new Date(dueDate) < new Date() && new Date(dueDate).toDateString() !== new Date().toDateString();
}

function isDueToday(dueDate: string) {
    return new Date(dueDate).toDateString() === new Date().toDateString();
}

function getDaysUntilDue(dueDate: string) {
    const today = new Date();
    const due = new Date(dueDate);
    const diffTime = due.getTime() - today.getTime();
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays;
}

const stats = computed(() => page.props.stats as ReminderStats);
const reminders = computed(() => page.props.reminders as Paginated<Reminder>);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Reminders" />
        
        <!-- Stats Widget -->
        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 transform -translate-y-4"
            enter-to-class="opacity-100 transform translate-y-0"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 transform translate-y-0"
            leave-to-class="opacity-0 transform -translate-y-4"
        >
            <div v-show="showStats" class="grid grid-cols-1 lg:grid-cols-4 gap-6 mt-8 mb-6">
                <Card class="relative overflow-hidden">
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Total Pending</p>
                                <p class="text-xl font-bold text-blue-600">{{ stats.total }}</p>
                            </div>
                            <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center">
                                <Bell class="h-6 w-6 text-blue-600" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="relative overflow-hidden">
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Overdue</p>
                                <p class="text-xl font-bold text-red-600">{{ stats.overdue }}</p>
                            </div>
                            <div class="h-12 w-12 rounded-full bg-red-100 flex items-center justify-center">
                                <AlertCircle class="h-6 w-6 text-red-600" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="relative overflow-hidden">
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Upcoming</p>
                                <p class="text-xl font-bold text-yellow-600">{{ stats.upcoming }}</p>
                            </div>
                            <div class="h-12 w-12 rounded-full bg-yellow-100 flex items-center justify-center">
                                <Clock class="h-6 w-6 text-yellow-600" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="relative overflow-hidden">
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Due Today</p>
                                <p class="text-xl font-bold text-orange-600">{{ stats.today }}</p>
                            </div>
                            <div class="h-12 w-12 rounded-full bg-orange-100 flex items-center justify-center">
                                <Calendar class="h-6 w-6 text-orange-600" />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </Transition>

        <!-- Filters and Actions -->
        <div class="flex flex-col md:flex-row gap-4 items-start md:items-center justify-between mt-4 mb-2">
            <div class="flex flex-wrap gap-2 items-center w-full md:w-auto">
                <input 
                    v-model="search" 
                    type="text" 
                    placeholder="Search reminders..." 
                    class="rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 flex-1 md:flex-initial min-w-[200px]" 
                />
                <Select
                    v-model="type"
                    :options="typeOptions"
                    placeholder="Type"
                    class="w-full md:w-[150px]"
                />
                <Select
                    v-model="status"
                    :options="statusOptions"
                    placeholder="Status"
                    class="w-full md:w-[150px]"
                />
                <Select
                    v-model="priority"
                    :options="priorityOptions"
                    placeholder="Priority"
                    class="w-full md:w-[150px]"
                />
                <Select
                    v-model="filter"
                    :options="filterOptions"
                    placeholder="Filter"
                    class="w-full md:w-[150px]"
                />
            </div>
            <div class="flex items-center gap-2 w-full md:w-auto">
                <Button
                    variant="outline"
                    @click="showStats = !showStats"
                >
                    {{ showStats ? 'Hide' : 'Show' }} Stats
                </Button>
                <Button
                    variant="outline"
                    @click="markAllAsRead"
                >
                    Mark All Read
                </Button>
                <Link :href="route('reminders.create')">
                    <Button variant="default">
                        <Plus class="h-4 w-4 mr-2" />
                        Create Reminder
                    </Button>
                </Link>
            </div>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Reminders</CardTitle>
            </CardHeader>
            <CardContent>
                <div v-if="reminders.data.length === 0" class="py-12 text-center">
                    <div class="text-xl font-semibold mb-2">No reminders found</div>
                    <p class="text-sm text-gray-600 mb-6">
                        <span v-if="search || type !== 'all' || status !== 'pending' || priority !== 'all' || filter !== 'all'">
                            No reminders match your current filters.
                        </span>
                        <span v-else>
                            You're all caught up! No pending reminders at the moment.
                        </span>
                    </p>
                    <Button v-if="search || type !== 'all' || status !== 'pending' || priority !== 'all' || filter !== 'all'" variant="outline" @click="type = 'all'; status = 'pending'; priority = 'all'; filter = 'all'; search = ''">
                        Clear Filters
                    </Button>
                </div>

                <div v-else>
                    <table class="min-w-full divide-y divide-border">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-border">
                            <tr
                                v-for="reminder in reminders.data"
                                :key="reminder.id"
                                :class="[
                                    'hover:bg-muted transition-colors',
                                    reminder.is_read ? 'opacity-60' : '',
                                    isOverdue(reminder.due_date) ? 'bg-red-50' : '',
                                    isDueToday(reminder.due_date) ? 'bg-orange-50' : ''
                                ]"
                            >
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span :class="['px-2 py-1 text-xs font-semibold rounded', getTypeBadgeClass(reminder.type)]">
                                        {{ getTypeLabel(reminder.type) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div>
                                        <div :class="['font-medium', reminder.is_read ? 'text-gray-500' : 'text-gray-900']">
                                            {{ reminder.title }}
                                        </div>
                                        <div v-if="reminder.description" class="text-sm text-gray-500 mt-1">
                                            {{ reminder.description }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div v-if="reminder.customer">
                                        <div class="font-medium">{{ reminder.customer.name }}</div>
                                        <div v-if="reminder.customer.company_name" class="text-sm text-gray-500">
                                            {{ reminder.customer.company_name }}
                                        </div>
                                    </div>
                                    <span v-else class="text-gray-400">—</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span v-if="reminder.amount" class="font-medium">
                                        {{ formatCurrency(reminder.amount) }}
                                    </span>
                                    <span v-else class="text-gray-400">—</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <Calendar class="h-4 w-4 text-gray-400" />
                                        <span :class="[
                                            'text-sm',
                                            isOverdue(reminder.due_date) ? 'text-red-600 font-semibold' : '',
                                            isDueToday(reminder.due_date) ? 'text-orange-600 font-semibold' : 'text-gray-700'
                                        ]">
                                            {{ formatDate(reminder.due_date) }}
                                        </span>
                                    </div>
                                    <div v-if="!isOverdue(reminder.due_date) && !isDueToday(reminder.due_date)" class="text-xs text-gray-500 mt-1">
                                        {{ getDaysUntilDue(reminder.due_date) }} days remaining
                                    </div>
                                    <div v-else-if="isOverdue(reminder.due_date)" class="text-xs text-red-600 font-semibold mt-1">
                                        OVERDUE
                                    </div>
                                    <div v-else-if="isDueToday(reminder.due_date)" class="text-xs text-orange-600 font-semibold mt-1">
                                        DUE TODAY
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span :class="['px-2 py-1 text-xs font-semibold rounded', getPriorityBadgeClass(reminder.priority)]">
                                        {{ reminder.priority.toUpperCase() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span :class="[
                                        'px-2 py-1 text-xs font-semibold rounded',
                                        reminder.status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '',
                                        reminder.status === 'completed' ? 'bg-green-100 text-green-800' : '',
                                        reminder.status === 'dismissed' ? 'bg-gray-100 text-gray-800' : ''
                                    ]">
                                        {{ reminder.status.charAt(0).toUpperCase() + reminder.status.slice(1) }}
                                    </span>
                                    <div v-if="!reminder.is_read" class="mt-1">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                            Unread
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <Button
                                            v-if="!reminder.is_read"
                                            variant="ghost"
                                            size="sm"
                                            @click="markAsRead(reminder.id)"
                                        >
                                            Mark Read
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            @click="markAsCompleted(reminder.id)"
                                        >
                                            <CheckCircle class="h-4 w-4 mr-1" />
                                            Complete
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            @click="dismiss(reminder.id)"
                                        >
                                            <XCircle class="h-4 w-4 mr-1" />
                                            Dismiss
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="reminders.last_page > 1" class="mt-6 flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Showing {{ reminders.from }} to {{ reminders.to }} of {{ reminders.total }} results
                    </div>
                    <div class="flex gap-2">
                        <Button
                            v-if="reminders.prev_page_url"
                            variant="outline"
                            size="sm"
                            @click="goToPage(reminders.current_page - 1)"
                        >
                            Previous
                        </Button>
                        <Button
                            v-if="reminders.next_page_url"
                            variant="outline"
                            size="sm"
                            @click="goToPage(reminders.current_page + 1)"
                        >
                            Next
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>
    </AppLayout>
</template>

