<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
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

interface Expense {
    id: number;
    amount: number;
    expense_type: string;
    description: string;
    date: string;
    due_date?: string | null;
}

const props = defineProps<{
    expense: Expense;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Expenses',
        href: '/expenses',
    },
    {
        title: 'Edit Expense',
        href: `/expenses/${props.expense.id}/edit`,
    }
];

const expenseTypes = [
    { value: 'Cash Advance', label: 'Cash Advance' },
    { value: 'Salary', label: 'Salary' },
    { value: 'Tax', label: 'Tax' },
    { value: 'Bills', label: 'Bills' },
    { value: 'Transportation', label: 'Transportation' },
];

const form = useForm({
    amount: props.expense.amount.toString(),
    expense_type: props.expense.expense_type,
    description: props.expense.description,
    date: props.expense.date,
    due_date: props.expense.due_date || null,
});

function submit() {
    form.post(route('expenses.update', props.expense.id), {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Expense updated successfully',
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
        <Head title="Edit Expense" />
        
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">Edit Expense #{{ props.expense.id }}</h1>
        </div>
        
        <form @submit.prevent="submit()" class="space-y-6">
            <!-- Expense Details -->
            <Card>
                <CardHeader>
                    <CardTitle>Expense Details</CardTitle>
                </CardHeader>
                <CardContent class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <Label for="expense_type">Expense Type *</Label>
                            <Select
                                v-model="form.expense_type"
                                :options="expenseTypes"
                                placeholder="Select expense type"
                                class="mt-1"
                                required
                            />
                            <InputError :message="form.errors.expense_type" />
                        </div>
                        
                        <div>
                            <Label for="amount">Amount *</Label>
                            <input
                                v-model="form.amount"
                                type="number"
                                id="amount"
                                min="0"
                                step="0.01"
                                class="w-full rounded-md border border-input bg-transparent px-3 py-2 mt-1 text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none"
                                placeholder="e.g., 1500.00"
                                required
                            />
                            <div class="text-[11px] text-gray-500 mt-1">Numbers only, minimum 0.01.</div>
                            <InputError :message="form.errors.amount" />
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <Label for="date">Date *</Label>
                            <input
                                v-model="form.date"
                                type="date"
                                id="date"
                                class="w-full rounded-md border border-input bg-transparent px-3 py-2 mt-1 text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none"
                                required
                            />
                            <div class="text-[11px] text-gray-500 mt-1">Pick the date this expense occurred.</div>
                            <InputError :message="form.errors.date" />
                        </div>
                        
                        <div v-if="form.expense_type === 'Bills'">
                            <Label for="due_date">Due Date</Label>
                            <input
                                v-model="form.due_date"
                                type="date"
                                id="due_date"
                                class="w-full rounded-md border border-input bg-transparent px-3 py-2 mt-1 text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none"
                            />
                            <div class="text-[11px] text-gray-500 mt-1">When is this bill due? (e.g., water, electricity, internet)</div>
                            <InputError :message="form.errors.due_date" />
                        </div>
                    </div>
                    
                    <div>
                        <Label for="description">Description *</Label>
                        <input
                            v-model="form.description"
                            type="text"
                            id="description"
                            class="w-full rounded-md border border-input bg-transparent px-3 py-2 mt-1 text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none"
                            placeholder="e.g., Electricity bill for March"
                            required
                        />
                        <div class="text-[11px] text-gray-500 mt-1">Brief, clear note (e.g., vendor or bill name).</div>
                        <InputError :message="form.errors.description" />
                    </div>
                </CardContent>
            </Card>

            <!-- Form Actions -->
            <Card>
                <CardFooter class="flex gap-2 justify-end">
                    <Button type="submit" variant="default">Update Expense</Button>
                    <Link :href="route('expenses.index')">
                        <Button type="button" variant="ghost">Cancel</Button>
                    </Link>
                </CardFooter>
            </Card>
        </form>
    </AppLayout>
</template> 