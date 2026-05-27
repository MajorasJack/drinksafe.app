<script setup lang="ts">
import PoliceStrip from './PoliceStrip.vue';
import AppHeader from './AppHeader.vue';
import AppFooter from './AppFooter.vue';
</script>

<template>
    <div class="min-h-screen bg-white dark:bg-gray-900">
        <PoliceStrip />
        <AppHeader />
        <main class="flex flex-grow flex-col">
            <slot />
        </main>
        <AppFooter />
    </div>
</template>
