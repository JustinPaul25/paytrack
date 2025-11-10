<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem, SidebarMenuSub, SidebarMenuSubButton, SidebarMenuSubItem } from '@/components/ui/sidebar';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { BookOpen, ChevronDown, Folder, LayoutGrid, Package, Shield, ShoppingCart, Tag, Users, Receipt, Truck, DollarSign, BarChart3, CreditCard, RotateCcw, TrendingDown } from 'lucide-vue-next';
import { ref } from 'vue';
import AppLogo from './AppLogo.vue';

const mainNavItems: NavItem[] = [
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
                                <DollarSign />
                                <span>Sales</span>
                                <ChevronDown class="ml-auto h-4 w-4 transition-transform duration-200" :class="{ 'rotate-180': isSalesOpen }" />
                            </SidebarMenuButton>
                        </CollapsibleTrigger>
                        <CollapsibleContent>
                            <SidebarMenuSub>
                                <SidebarMenuSubItem>
                                    <SidebarMenuSubButton as-child>
                                        <Link href="/invoices">
                                            <Receipt />
                                            <span>Invoices</span>
                                        </Link>
                                    </SidebarMenuSubButton>
                                </SidebarMenuSubItem>
                                <SidebarMenuSubItem>
                                    <SidebarMenuSubButton as-child>
                                        <Link href="/refunds">
                                            <RotateCcw />
                                            <span>Refunds</span>
                                        </Link>
                                    </SidebarMenuSubButton>
                                </SidebarMenuSubItem>
                                <SidebarMenuSubItem>
                                    <SidebarMenuSubButton as-child>
                                        <Link href="/deliveries">
                                            <Truck />
                                            <span>Deliveries</span>
                                        </Link>
                                    </SidebarMenuSubButton>
                                </SidebarMenuSubItem>
                                <SidebarMenuSubItem>
                                    <SidebarMenuSubButton as-child>
                                        <Link href="/expenses">
                                            <TrendingDown />
                                            <span>Expenses</span>
                                        </Link>
                                    </SidebarMenuSubButton>
                                </SidebarMenuSubItem>

                                <SidebarMenuSubItem>
                                    <SidebarMenuSubButton as-child>
                                        <Link href="/sales/transactions">
                                            <CreditCard />
                                            <span>Transactions</span>
                                        </Link>
                                    </SidebarMenuSubButton>
                                </SidebarMenuSubItem>
                            </SidebarMenuSub>
                        </CollapsibleContent>
                    </Collapsible>
                </SidebarMenuItem>
            </SidebarMenu>

            <!-- Products Group -->
            <SidebarMenu class="px-2">
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
            
            <!-- Users Group -->
            <SidebarMenu class="px-2">
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
            <!-- Quick Create Invoice Button -->
            <SidebarMenu class="px-2">
                <SidebarMenuItem>
                    <SidebarMenuButton as-child class="bg-green-50 hover:bg-green-100 text-green-700 border border-green-200">
                        <Link :href="route('invoices.create')">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            <span>New Invoice</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>

            <!-- Quick Deliveries Shortcut -->
            <SidebarMenu class="px-2">
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
