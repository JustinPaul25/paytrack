<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import NotificationBell from '@/components/NotificationBell.vue';
import { usePage } from '@inertiajs/vue3';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const isAuthenticated = !!((page.props.auth as any)?.user);
const isAdmin = Array.isArray((page.props.auth as any)?.userRoles) && (page.props.auth as any).userRoles.includes('Admin');
const shouldShowNotificationBell = isAuthenticated && !isAdmin;
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>
        <!-- Floating Notification Bell (hidden for Admin users) -->
        <NotificationBell v-if="shouldShowNotificationBell" />
    </AppShell>
</template>
