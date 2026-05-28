<script setup lang="ts">
/**
 * HeatmapPeriodSelector Component
 *
 * Dropdown selector for filtering heatmap data by time period.
 * Options: 7 days, 30 days, 90 days, or all time.
 */

import { computed } from 'vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useHeatmap } from '@/composables/useHeatmap';
import type { HeatmapPeriod } from '@/types';

const { period, setPeriod, isVisible } = useHeatmap();

interface PeriodOption {
    value: HeatmapPeriod;
    label: string;
}

const periodOptions: PeriodOption[] = [
    { value: '7d', label: 'Last 7 days' },
    { value: '30d', label: 'Last 30 days' },
    { value: '90d', label: 'Last 90 days' },
    { value: 'all', label: 'All time' },
];

const selectedPeriodLabel = computed(
    () => periodOptions.find((option) => option.value === period.value)?.label ?? 'Last 30 days',
);

const handlePeriodChange = (value: unknown): void => {
    if (typeof value === 'string' && ['7d', '30d', '90d', 'all'].includes(value)) {
        setPeriod(value as HeatmapPeriod);
    }
};
</script>

<template>
    <Select
        :model-value="period"
        :disabled="!isVisible"
        @update:model-value="handlePeriodChange"
    >
        <SelectTrigger
            size="sm"
            :class="[
                'w-[140px] text-xs',
                !isVisible && 'cursor-not-allowed opacity-50',
            ]"
        >
            <SelectValue :placeholder="selectedPeriodLabel" />
        </SelectTrigger>
        <SelectContent>
            <SelectItem
                v-for="option in periodOptions"
                :key="option.value"
                :value="option.value"
            >
                {{ option.label }}
            </SelectItem>
        </SelectContent>
    </Select>
</template>
