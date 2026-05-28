<script setup lang="ts">
/**
 * HeatmapToggle Component
 *
 * Toggle button to show/hide the incident heatmap layer on the map.
 * Displays current state with visual indicator.
 */

import { Flame, FlameKindling } from 'lucide-vue-next';
import Button from '@/components/ui/button/Button.vue';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useHeatmap } from '@/composables/useHeatmap';

const { isVisible, toggleVisibility, totalReports } = useHeatmap();
</script>

<template>
    <TooltipProvider>
        <Tooltip>
            <TooltipTrigger as-child>
                <Button
                    variant="outline"
                    size="icon"
                    :class="[
                        'relative transition-colors',
                        isVisible
                            ? 'border-brand-amber bg-brand-amber/10 text-brand-amber hover:bg-brand-amber/20 dark:border-brand-amber/60'
                            : 'bg-white dark:bg-gray-800',
                    ]"
                    @click="toggleVisibility"
                >
                    <Flame v-if="isVisible" class="size-5" />
                    <FlameKindling v-else class="size-5" />

                    <!-- Active indicator dot -->
                    <span
                        v-if="isVisible"
                        class="absolute -right-0.5 -top-0.5 size-2 rounded-full bg-brand-amber"
                    />
                </Button>
            </TooltipTrigger>
            <TooltipContent side="left">
                <p>
                    {{ isVisible ? 'Hide' : 'Show' }} incident heatmap
                    <span v-if="isVisible && totalReports > 0" class="text-muted-foreground">
                        ({{ totalReports }} reports)
                    </span>
                </p>
            </TooltipContent>
        </Tooltip>
    </TooltipProvider>
</template>
