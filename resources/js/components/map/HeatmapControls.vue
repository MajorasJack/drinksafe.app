<script setup lang="ts">
/**
 * HeatmapControls Component
 *
 * Container component that combines the heatmap toggle and period selector.
 * Positioned as a floating overlay on the map.
 */

import HeatmapPeriodSelector from './HeatmapPeriodSelector.vue';
import HeatmapToggle from './HeatmapToggle.vue';
import { useHeatmap } from '@/composables/useHeatmap';

const { isVisible, totalReports, hasData } = useHeatmap();
</script>

<template>
    <div class="absolute right-4 top-4 z-[1000] flex flex-col items-end gap-2">
        <!-- Main controls row -->
        <div
            class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white/95 p-2 shadow-md backdrop-blur-sm dark:border-gray-700 dark:bg-gray-800/95"
        >
            <HeatmapPeriodSelector />
            <div class="h-6 w-px bg-slate-200 dark:bg-gray-700" />
            <HeatmapToggle />
        </div>

        <!-- Report count indicator (shows when heatmap is visible and has data) -->
        <Transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="opacity-0 translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-1"
        >
            <div
                v-if="isVisible && hasData"
                class="rounded-md bg-slate-900/80 px-2.5 py-1 text-xs font-medium text-white shadow-md dark:bg-gray-900/90"
            >
                {{ totalReports }} incident{{ totalReports !== 1 ? 's' : '' }} shown
            </div>
        </Transition>

        <!-- Empty state (shows when heatmap is visible but no data) -->
        <Transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="opacity-0 translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-1"
        >
            <div
                v-if="isVisible && !hasData && totalReports === 0"
                class="rounded-md bg-slate-600/80 px-2.5 py-1 text-xs font-medium text-white shadow-md dark:bg-gray-700/90"
            >
                No incidents in this area
            </div>
        </Transition>
    </div>
</template>
