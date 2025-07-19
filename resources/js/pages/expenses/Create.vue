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

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Expenses',
        href: '/expenses',
    },
    {
        title: 'Create Expense',
        href: '/expenses/create',
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
    amount: '',
    expense_type: '',
    description: '',
    date: new Date().toISOString().split('T')[0],
});

function submit() {
    form.post(route('expenses.store'), {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Expense created successfully (Demo - no data saved)',
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
        <Head title="Create Expense" />
        
        <!-- Demo Notice -->
        <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
            <div class="flex items-center">
                <svg class="h-5 w-5 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
                <div>
                    <h3 class="text-sm font-medium text-yellow-800">Demo Mode</h3>
                    <p class="text-sm text-yellow-700 mt-1">
                        This is a demo page with dummy data. No actual data will be saved to the database.
                    </p>
                </div>
            </div>
        </div>
        
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">Create Expense</h1>
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
                            <Label for="expense_type">Expense Type</Label>
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
                            <Label for="amount">Amount</Label>
                            <input
                                v-model="form.amount"
                                type="number"
                                id="amount"
                                min="0"
                                step="0.01"
                                class="w-full rounded-md border border-input bg-transparent px-3 py-2 mt-1 text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none"
                                placeholder="Enter amount"
                                required
                            />
                            <InputError :message="form.errors.amount" />
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <Label for="date">Date</Label>
                            <input
                                v-model="form.date"
                                type="date"
                                id="date"
                                class="w-full rounded-md border border-input bg-transparent px-3 py-2 mt-1 text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none"
                                required
                            />
                            <InputError :message="form.errors.date" />
                        </div>
                        
                        <div>
                            <Label for="description">Description</Label>
                            <input
                                v-model="form.description"
                                type="text"
                                id="description"
                                class="w-full rounded-md border border-input bg-transparent px-3 py-2 mt-1 text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none"
                                placeholder="Enter description"
                                required
                            />
                            <InputError :message="form.errors.description" />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Form Actions -->
            <Card>
                <CardFooter class="flex gap-2 justify-end">
                    <Button type="submit" variant="default">Create Expense</Button>
                    <Link :href="route('expenses.index')">
                        <Button type="button" variant="ghost">Cancel</Button>
                    </Link>
                </CardFooter>
            </Card>
        </form>
    </AppLayout>
</template> 