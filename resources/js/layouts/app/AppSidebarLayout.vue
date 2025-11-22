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
// Show notification bell only on dashboard for all authenticated users
const isDashboard = page.url === '/dashboard' || page.url.startsWith('/dashboard?');
const shouldShowNotificationBell = isAuthenticated && isDashboard;
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>
        <!-- Floating Notification Bell (only shown on dashboard for all user roles) -->
        <NotificationBell v-if="shouldShowNotificationBell" />
    </AppShell>
</template>
