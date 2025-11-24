<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import { Button } from '@/components/ui/button';
import { type BreadcrumbItem } from '@/types';
import { Users, UserCog, ShoppingBag } from 'lucide-vue-next';

interface UserStats {
    totalUsers: number;
    totalStaff: number;
    totalCustomers: number;
}

const page = usePage();
const stats = page.props.stats as UserStats;

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Users', href: '/users' },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Users" />
        
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">Users Management</h1>
            <Link :href="route('users.create')">
                <Button variant="default">
                    <span class="mr-2">+</span>
                    Add New User
                </Button>
            </Link>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Staff Card -->
            <Link :href="route('users.staff')">
                <Card class="hover:shadow-lg transition-shadow cursor-pointer h-full">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <UserCog class="h-5 w-5 text-blue-600" />
                            Staff
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-3xl font-bold text-blue-600">{{ stats?.totalStaff || 0 }}</p>
                                <p class="text-sm text-gray-500 mt-1">Admin & Staff Users</p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <p class="text-sm text-gray-600">View and manage staff members, admins, and employees.</p>
                        </div>
                    </CardContent>
                </Card>
            </Link>

            <!-- Customers Card -->
            <Link :href="route('users.customers')">
                <Card class="hover:shadow-lg transition-shadow cursor-pointer h-full">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <ShoppingBag class="h-5 w-5 text-purple-600" />
                            Customers
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-3xl font-bold text-purple-600">{{ stats?.totalCustomers || 0 }}</p>
                                <p class="text-sm text-gray-500 mt-1">Customer Accounts</p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <p class="text-sm text-gray-600">View customer details, addresses, and purchase history.</p>
                        </div>
                    </CardContent>
                </Card>
            </Link>

            <!-- Total Users Card -->
            <Card class="h-full">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Users class="h-5 w-5 text-green-600" />
                        Total Users
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-3xl font-bold text-green-600">{{ stats?.totalUsers || 0 }}</p>
                            <p class="text-sm text-gray-500 mt-1">All System Users</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <p class="text-sm text-gray-600">Combined count of all users in the system.</p>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
