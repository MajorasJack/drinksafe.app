<script setup lang="ts">
import { computed } from 'vue';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { AnalyticsFilters } from '@/types/analytics';

interface Props {
    filters: AnalyticsFilters;
    cities: string[];
    loading?: boolean;
}

interface Emits {
    (e: 'update:filters', filters: AnalyticsFilters): void;
}

const props = withDefaults(defineProps<Props>(), {
    loading: false,
});
const emit = defineEmits<Emits>();

const periods = [
    { value: '30', label: 'Last 30 days' },
    { value: '90', label: 'Last 90 days' },
    { value: '365', label: 'Last year' },
] as const;

/**
 * Sorted cities for the dropdown
 */
const sortedCities = computed((): string[] =>
    [...props.cities].sort((a, b) => a.localeCompare(b)),
);

/**
 * Handle period change
 */
const handlePeriodChange = (value: string): void => {
    emit('update:filters', {
        ...props.filters,
        period: parseInt(value, 10) as 30 | 90 | 365,
    });
};

/**
 * Handle city change
 */
const handleCityChange = (value: string): void => {
    emit('update:filters', {
        ...props.filters,
        city: value === 'all' ? undefined : value,
    });
};

/**
 * Current city value for the select
 */
const currentCityValue = computed((): string => props.filters.city ?? 'all');
</script>

<template>
    <div
        class="flex flex-col gap-4 rounded-lg border border-slate-200 bg-slate-50 p-4 sm:flex-row sm:items-end dark:border-slate-700 dark:bg-slate-800/50"
    >
        <!-- Period selector -->
        <div class="flex flex-col gap-1.5">
            <Label
                for="period"
                class="text-sm font-medium text-slate-700 dark:text-slate-300"
            >
                Time Period
            </Label>
            <Select
                :model-value="String(filters.period)"
                :disabled="loading"
                @update:model-value="handlePeriodChange"
            >
                <SelectTrigger
                    id="period"
                    class="w-full sm:w-[160px]"
                    aria-label="Select time period"
                >
                    <SelectValue placeholder="Select period" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="p in periods"
                        :key="p.value"
                        :value="p.value"
                    >
                        {{ p.label }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <!-- City selector -->
        <div class="flex flex-col gap-1.5">
            <Label
                for="city"
                class="text-sm font-medium text-slate-700 dark:text-slate-300"
            >
                City
            </Label>
            <Select
                :model-value="currentCityValue"
                :disabled="loading || cities.length === 0"
                @update:model-value="handleCityChange"
            >
                <SelectTrigger
                    id="city"
                    class="w-full sm:w-[200px]"
                    aria-label="Select city"
                >
                    <SelectValue placeholder="All cities" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">All cities</SelectItem>
                    <SelectItem
                        v-for="city in sortedCities"
                        :key="city"
                        :value="city"
                    >
                        {{ city }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <!-- Filter summary for screen readers -->
        <span class="sr-only">
            Currently showing data for {{ filters.city ?? 'all cities' }} over
            the last {{ filters.period }} days
        </span>
    </div>
</template>
