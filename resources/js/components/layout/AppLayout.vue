<script setup lang="ts">
import AppFooter from './AppFooter.vue';
import AppHeader from './AppHeader.vue';
import PoliceStrip from './PoliceStrip.vue';
</script>

<template>
    <div
        class="flex min-h-[100dvh] flex-col bg-white pt-[env(safe-area-inset-top)] dark:bg-gray-900"
    >
        <PoliceStrip />
        <AppHeader />
        <main class="flex min-h-0 flex-1 flex-col">
            <slot />
        </main>
        <AppFooter />
    </div>
</template>
