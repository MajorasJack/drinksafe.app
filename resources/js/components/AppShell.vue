<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { SidebarProvider } from '@/components/ui/sidebar';
import type { AppVariant } from '@/types';

type Props = {
    variant?: AppVariant;
};

withDefaults(defineProps<Props>(), {
    variant: 'sidebar',
});

const isOpen = usePage().props.sidebarOpen;
</script>

<template>
    <SidebarProvider :default-open="isOpen">
        <div v-if="variant === 'header'" class="flex min-h-screen w-full flex-col">
            <slot />
        </div>
        <template v-else>
            <slot />
        </template>
    </SidebarProvider>
</template>
