<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Select } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardFooter from '@/components/ui/card/CardFooter.vue';
import Label from '@/components/ui/label/Label.vue';
import InputError from '@/components/InputError.vue';
import Swal from 'sweetalert2';
import { type BreadcrumbItem } from '@/types';
import { Calendar } from 'lucide-vue-next';

interface Customer {
    id: number;
    name: string;
    company_name?: string;
}

interface Expense {
    id: number;
    expense_type: string;
    amount: number;
    date: string;
}

const props = defineProps<{
    customers: Customer[];
    expenses: Expense[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Reminders',
        href: '/reminders',
    },
    {
        title: 'Create Reminder',
        href: '/reminders/create',
    }
];

const typeOptions = [
    { value: 'bill_payment', label: 'Bill Payment' },
    { value: 'customer_due', label: 'Customer Due' },
    { value: 'credit_term', label: 'Credit Term' },
];

const priorityOptions = [
    { value: 'low', label: 'Low' },
    { value: 'medium', label: 'Medium' },
    { value: 'high', label: 'High' },
];

const form = useForm({
    type: 'bill_payment',
    title: '',
    description: '',
    due_date: '',
    amount: '',
    currency: 'USD',
    priority: 'medium',
    customer_id: '',
    expense_id: '',
});

// Auto-generate title based on type and selected expense
watch([() => form.type, () => form.expense_id], ([type, expenseId]) => {
    if (type === 'bill_payment' && expenseId) {
        const expense = props.expenses.find(e => e.id === Number(expenseId));
        if (expense) {
            form.title = `Bill Payment: ${expense.expense_type}`;
            form.amount = expense.amount.toString();
            form.description = `Monthly payment for ${expense.expense_type}`;
        }
    } else if (type === 'customer_due' && form.customer_id) {
        const customer = props.customers.find(c => c.id === Number(form.customer_id));
        if (customer) {
            form.title = `Customer Payment Due: ${customer.name}`;
        }
    } else if (type === 'credit_term' && form.customer_id) {
        const customer = props.customers.find(c => c.id === Number(form.customer_id));
        if (customer) {
            form.title = `Credit Term Payment Due: ${customer.name}`;
        }
    }
});

// Auto-calculate priority based on due date
watch(() => form.due_date, (dueDate) => {
    if (dueDate) {
        const today = new Date();
        const due = new Date(dueDate);
        const diffTime = due.getTime() - today.getTime();
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        if (diffDays <= 7) {
            form.priority = 'high';
        } else if (diffDays <= 14) {
            form.priority = 'medium';
        } else {
            form.priority = 'low';
        }
    }
});

const customerOptions = computed(() => {
    return props.customers.map(c => ({
        value: c.id.toString(),
        label: c.company_name ? `${c.name} (${c.company_name})` : c.name
    }));
});

const expenseOptions = computed(() => {
    return props.expenses.map(e => ({
        value: e.id.toString(),
        label: `${e.expense_type} - $${e.amount.toFixed(2)} (${new Date(e.date).toLocaleDateString()})`
    }));
});

function submit() {
    form.post(route('reminders.store'), {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Reminder created successfully',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
            });
        },
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Create Reminder" />
        
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">Create Reminder</h1>
        </div>
        
        <form @submit.prevent="submit()" class="space-y-6">
            <!-- Reminder Details -->
            <Card>
                <CardHeader>
                    <CardTitle>Reminder Details</CardTitle>
                </CardHeader>
                <CardContent class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <Label for="type">Reminder Type</Label>
                            <Select
                                v-model="form.type"
                                :options="typeOptions"
                                placeholder="Select reminder type"
                                class="mt-1"
                                required
                            />
                            <div class="text-[11px] text-gray-500 mt-1">
                                Bill Payment: For recurring bills like electricity, water, etc.
                            </div>
                            <InputError :message="form.errors.type" />
                        </div>
                        
                        <div>
                            <Label for="priority">Priority</Label>
                            <Select
                                v-model="form.priority"
                                :options="priorityOptions"
                                placeholder="Select priority"
                                class="mt-1"
                                required
                            />
                            <div class="text-[11px] text-gray-500 mt-1">
                                Priority is auto-calculated based on due date, but you can override it.
                            </div>
                            <InputError :message="form.errors.priority" />
                        </div>
                    </div>
                    
                    <div>
                        <Label for="title">Title</Label>
                        <input
                            v-model="form.title"
                            type="text"
                            id="title"
                            class="w-full rounded-md border border-input bg-transparent px-3 py-2 mt-1 text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none"
                            placeholder="e.g., Electricity Bill Payment"
                            required
                        />
                        <div class="text-[11px] text-gray-500 mt-1">
                            A clear, descriptive title for this reminder.
                        </div>
                        <InputError :message="form.errors.title" />
                    </div>
                    
                    <div>
                        <Label for="description">Description</Label>
                        <textarea
                            v-model="form.description"
                            id="description"
                            rows="3"
                            class="w-full rounded-md border border-input bg-transparent px-3 py-2 mt-1 text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none"
                            placeholder="e.g., Monthly electricity bill payment for main office"
                        ></textarea>
                        <div class="text-[11px] text-gray-500 mt-1">
                            Additional details about this reminder (optional).
                        </div>
                        <InputError :message="form.errors.description" />
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <Label for="due_date">Due Date</Label>
                            <div class="relative">
                                <input
                                    v-model="form.due_date"
                                    type="date"
                                    id="due_date"
                                    class="w-full rounded-md border border-input bg-transparent px-3 py-2 mt-1 text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none"
                                    required
                                />
                                <Calendar class="absolute right-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400 pointer-events-none" />
                            </div>
                            <div class="text-[11px] text-gray-500 mt-1">
                                When is this reminder due?
                            </div>
                            <InputError :message="form.errors.due_date" />
                        </div>
                        
                        <div>
                            <Label for="amount">Amount (Optional)</Label>
                            <input
                                v-model="form.amount"
                                type="number"
                                id="amount"
                                min="0"
                                step="0.01"
                                class="w-full rounded-md border border-input bg-transparent px-3 py-2 mt-1 text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none"
                                placeholder="e.g., 1500.00"
                            />
                            <div class="text-[11px] text-gray-500 mt-1">
                                The amount due (if applicable).
                            </div>
                            <InputError :message="form.errors.amount" />
                        </div>
                    </div>
                    
                    <!-- Conditional Fields Based on Type -->
                    <div v-if="form.type === 'bill_payment'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <Label for="expense_id">Link to Expense (Optional)</Label>
                            <Select
                                v-model="form.expense_id"
                                :options="expenseOptions"
                                placeholder="Select an expense"
                                class="mt-1"
                            />
                            <div class="text-[11px] text-gray-500 mt-1">
                                Link this reminder to an existing expense to auto-fill details.
                            </div>
                            <InputError :message="form.errors.expense_id" />
                        </div>
                    </div>
                    
                    <div v-if="form.type === 'customer_due' || form.type === 'credit_term'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <Label for="customer_id">Customer (Optional)</Label>
                            <Select
                                v-model="form.customer_id"
                                :options="customerOptions"
                                placeholder="Select a customer"
                                class="mt-1"
                            />
                            <div class="text-[11px] text-gray-500 mt-1">
                                Link this reminder to a customer.
                            </div>
                            <InputError :message="form.errors.customer_id" />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Form Actions -->
            <Card>
                <CardFooter class="flex gap-2 justify-end">
                    <Button type="submit" variant="default">Create Reminder</Button>
                    <Link :href="route('reminders.index')">
                        <Button type="button" variant="ghost">Cancel</Button>
                    </Link>
                </CardFooter>
            </Card>
        </form>
    </AppLayout>
</template>

