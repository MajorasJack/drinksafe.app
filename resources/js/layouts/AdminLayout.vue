<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { Toaster } from '@/components/ui/sonner';
import type { BreadcrumbItem } from '@/types';

const { breadcrumbs = [] } = defineProps<{
    breadcrumbs?: BreadcrumbItem[];
}>();
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <header
                class="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 px-4"
            >
                <SidebarTrigger
                    class="-ml-1"
                    data-test="sidebar-trigger"
                />
                <Breadcrumbs
                    v-if="breadcrumbs.length > 0"
                    :breadcrumbs="breadcrumbs"
                />
            </header>
            <slot />
        </AppContent>
        <Toaster />
    </AppShell>
</template>
