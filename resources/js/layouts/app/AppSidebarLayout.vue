<script setup lang="ts">
import AppShell from '@/components/AppShell.vue';
import { Toaster } from '@/components/ui/sonner';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShell variant="header">
        <div class="flex min-h-screen w-full flex-col overflow-x-hidden">
            <slot />
        </div>
        <Toaster />
    </AppShell>
</template>
