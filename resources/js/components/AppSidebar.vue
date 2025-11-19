<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem, SidebarMenuSub, SidebarMenuSubButton, SidebarMenuSubItem } from '@/components/ui/sidebar';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { BookOpen, ChevronDown, Folder, LayoutGrid, Package, Shield, ShoppingCart, Tag, Users, Receipt, Truck, BarChart3, CreditCard, RotateCcw, TrendingDown, FileSpreadsheet } from 'lucide-vue-next';
import { ref } from 'vue';
import AppLogo from './AppLogo.vue';

const page = usePage();
const isCustomer = !!((page.props.auth as any)?.userRoles && (page.props.auth as any).userRoles.includes('Customer'));
const isAdmin = !!((page.props.auth as any)?.userRoles && (page.props.auth as any).userRoles.includes('Admin'));
const isStaff = !!((page.props.auth as any)?.userRoles && 
    ((page.props.auth as any).userRoles.includes('Admin') || (page.props.auth as any).userRoles.includes('Staff')));
const pendingOrderCount = (page.props.pendingOrderCount as number) || 0;

const mainNavItems: NavItem[] = isCustomer
    ? [
        {
            title: 'Dashboard',
            href: '/dashboard',
            icon: LayoutGrid,
        },
        {
            title: 'My Deliveries',
            href: '/my-deliveries',
            icon: Truck,
        },
    ]
    : [
        {
            title: 'Dashboard',
            href: '/dashboard',
            icon: LayoutGrid,
        },
        {
            title: 'Customers',
            href: '/customers',
            icon: Folder,
        },
    ];

const footerNavItems: NavItem[] = [
    // {
    //     title: 'Github Repo',
    //     href: 'https://github.com/laravel/vue-starter-kit',
    //     icon: Folder,
    // },
    // {
    //     title: 'Documentation',
    //     href: 'https://laravel.com/docs/starter-kits#vue',
    //     icon: BookOpen,
    // },
];

const isProductsOpen = ref(true);
const isUsersOpen = ref(true);
const isSalesOpen = ref(true);
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />

            <!-- Sales Group -->
            <SidebarMenu class="px-2">
                <SidebarMenuItem>
                    <Collapsible v-model:open="isSalesOpen">
                        <CollapsibleTrigger as-child>
                            <SidebarMenuButton tooltip="Sales">
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 119.43 122.88" aria-hidden="true" focusable="false">
                                    <path d="M118.45,51l1,1-.74,9.11H99A40.52,40.52,0,0,1,81.88,78.43q-11.44,6.28-27.71,7h-15l.5,37.43H21.42l.74-36.94-.24-24.87H1L0,59.84.74,51H21.92l-.25-15.26H1l-1-1,.74-9.11H21.67L21.42.25,63.29,0Q78.8,0,88.65,6.53T102,25.61h16.5l1,1.23-.74,8.87h-15v3.94A53.17,53.17,0,0,1,102.44,51ZM39.65,25.61H81.26Q74.85,14,58.61,13.3L39.89,14l-.24,11.57ZM39.4,51H83.23a39.51,39.51,0,0,0,1.23-9.6,46.17,46.17,0,0,0-.24-5.66H39.65L39.4,51ZM58.61,71.91q12.56-2.72,19.21-10.84H39.4l-.25,10.1,19.46.74Z"/>
                                </svg>
                                <span>Sales</span>
                                <ChevronDown class="ml-auto h-4 w-4 transition-transform duration-200" :class="{ 'rotate-180': isSalesOpen }" />
                            </SidebarMenuButton>
                        </CollapsibleTrigger>
                        <CollapsibleContent>
                            <SidebarMenuSub>
                                <SidebarMenuSubItem v-if="isCustomer">
                                    <SidebarMenuSubButton as-child>
                                        <Link href="/orders" class="flex items-center justify-between w-full">
                                            <div class="flex items-center gap-2">
                                                <ShoppingCart />
                                                <span>Orders</span>
                                            </div>
                                            <span 
                                                v-if="pendingOrderCount > 0"
                                                class="ml-auto flex h-5 min-w-[20px] items-center justify-center rounded-full bg-yellow-500 px-1.5 text-xs font-semibold text-white"
                                            >
                                                {{ pendingOrderCount > 99 ? '99+' : pendingOrderCount }}
                                            </span>
                                        </Link>
                                    </SidebarMenuSubButton>
                                </SidebarMenuSubItem>
                                <SidebarMenuSubItem>
                                    <SidebarMenuSubButton as-child>
                                        <Link href="/invoices">
                                            <Receipt />
                                            <span>Invoices</span>
                                        </Link>
                                    </SidebarMenuSubButton>
                                </SidebarMenuSubItem>
                                <SidebarMenuSubItem v-if="!isCustomer">
                                    <SidebarMenuSubButton as-child>
                                        <Link href="/orders" class="flex items-center justify-between w-full">
                                            <div class="flex items-center gap-2">
                                                <ShoppingCart />
                                                <span>Orders</span>
                                            </div>
                                            <span 
                                                v-if="isStaff && pendingOrderCount > 0"
                                                class="ml-auto flex h-5 min-w-[20px] items-center justify-center rounded-full bg-yellow-500 px-1.5 text-xs font-semibold text-white"
                                            >
                                                {{ pendingOrderCount > 99 ? '99+' : pendingOrderCount }}
                                            </span>
                                        </Link>
                                    </SidebarMenuSubButton>
                                </SidebarMenuSubItem>
                                <SidebarMenuSubItem v-if="!isCustomer">
                                    <SidebarMenuSubButton as-child>
                                        <Link href="/deliveries">
                                            <Truck />
                                            <span>Deliveries</span>
                                        </Link>
                                    </SidebarMenuSubButton>
                                </SidebarMenuSubItem>
                                <SidebarMenuSubItem v-if="!isCustomer">
                                    <SidebarMenuSubButton as-child>
                                        <Link href="/expenses">
                                            <TrendingDown />
                                            <span>Expenses</span>
                                        </Link>
                                    </SidebarMenuSubButton>
                                </SidebarMenuSubItem>

                                <SidebarMenuSubItem v-if="!isCustomer">
                                    <SidebarMenuSubButton as-child>
                                        <Link href="/sales/transactions">
                                            <CreditCard />
                                            <span>Transactions</span>
                                        </Link>
                                    </SidebarMenuSubButton>
                                </SidebarMenuSubItem>
                                <SidebarMenuSubItem v-if="!isCustomer">
                                    <SidebarMenuSubButton as-child>
                                        <Link :href="route('finance.cash-flow')">
                                            <BarChart3 />
                                            <span>Cash Flow</span>
                                        </Link>
                                    </SidebarMenuSubButton>
                                </SidebarMenuSubItem>
                                <SidebarMenuSubItem v-if="!isCustomer">
                                    <SidebarMenuSubButton as-child>
                                        <Link :href="route('finance.reports')">
                                            <FileSpreadsheet />
                                            <span>Financial Reports</span>
                                        </Link>
                                    </SidebarMenuSubButton>
                                </SidebarMenuSubItem>
                                <SidebarMenuSubItem v-if="!isCustomer && isAdmin">
                                    <SidebarMenuSubButton as-child>
                                        <Link :href="route('refundRequests.index')">
                                            <RotateCcw />
                                            <span>Refund Requests</span>
                                        </Link>
                                    </SidebarMenuSubButton>
                                </SidebarMenuSubItem>
                                <SidebarMenuSubItem v-if="!isCustomer && isAdmin">
                                    <SidebarMenuSubButton as-child>
                                        <Link :href="route('refunds.index')">
                                            <RotateCcw />
                                            <span>Refunds</span>
                                        </Link>
                                    </SidebarMenuSubButton>
                                </SidebarMenuSubItem>
                            </SidebarMenuSub>
                        </CollapsibleContent>
                    </Collapsible>
                </SidebarMenuItem>
            </SidebarMenu>

            <!-- Products Group -->
            <SidebarMenu v-if="!isCustomer" class="px-2">
                <SidebarMenuItem>
                    <Collapsible v-model:open="isProductsOpen">
                        <CollapsibleTrigger as-child>
                            <SidebarMenuButton tooltip="Products">
                                <Package />
                                <span>Products</span>
                                <ChevronDown class="ml-auto h-4 w-4 transition-transform duration-200" :class="{ 'rotate-180': isProductsOpen }" />
                            </SidebarMenuButton>
                        </CollapsibleTrigger>
                        <CollapsibleContent>
                            <SidebarMenuSub>
                                <SidebarMenuSubItem>
                                    <SidebarMenuSubButton as-child>
                                        <Link href="/categories">
                                            <Tag />
                                            <span>Categories</span>
                                        </Link>
                                    </SidebarMenuSubButton>
                                </SidebarMenuSubItem>
                                <SidebarMenuSubItem>
                                    <SidebarMenuSubButton as-child>
                                        <Link href="/products">
                                            <ShoppingCart />
                                            <span>Products</span>
                                        </Link>
                                    </SidebarMenuSubButton>
                                </SidebarMenuSubItem>
                            </SidebarMenuSub>
                        </CollapsibleContent>
                    </Collapsible>
                </SidebarMenuItem>
            </SidebarMenu>
            
            <!-- Users Group (Admin only) -->
            <SidebarMenu v-if="isAdmin" class="px-2">
                <SidebarMenuItem>
                    <Collapsible v-model:open="isUsersOpen">
                        <CollapsibleTrigger as-child>
                            <SidebarMenuButton tooltip="Users">
                                <Users />
                                <span>Users</span>
                                <ChevronDown class="ml-auto h-4 w-4 transition-transform duration-200" :class="{ 'rotate-180': isUsersOpen }" />
                            </SidebarMenuButton>
                        </CollapsibleTrigger>
                        <CollapsibleContent>
                            <SidebarMenuSub>
                                <SidebarMenuSubItem>
                                    <SidebarMenuSubButton as-child>
                                        <Link href="/users">
                                            <Folder />
                                            <span>Users</span>
                                        </Link>
                                    </SidebarMenuSubButton>
                                </SidebarMenuSubItem>
                                <SidebarMenuSubItem>
                                    <SidebarMenuSubButton as-child>
                                        <Link href="/roles">
                                            <Shield />
                                            <span>Roles</span>
                                        </Link>
                                    </SidebarMenuSubButton>
                                </SidebarMenuSubItem>
                            </SidebarMenuSub>
                        </CollapsibleContent>
                    </Collapsible>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarContent>

        <SidebarFooter>
            <!-- Quick Deliveries Shortcut -->
            <SidebarMenu v-if="!isCustomer" class="px-2">
                <SidebarMenuItem>
                    <SidebarMenuButton as-child class="bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200">
                        <Link href="/deliveries/shortcut">
                            <Truck class="h-4 w-4" />
                            <span>Deliveries Hub</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
            
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
