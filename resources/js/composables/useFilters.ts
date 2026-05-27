/**
 * useFilters Composable
 *
 * Manages report filtering state and applies filters.
 */

import { computed, ref } from 'vue';
import { useReportStore } from '@/stores/reportStore';
import type { ReportFilters } from '@/types';

export function useFilters() {
    const store = useReportStore();

    const filters = ref<ReportFilters>({});

    const activeFilters = computed(() => {
        return Object.entries(filters.value)
            .filter(([, value]) => value !== undefined && value !== null)
            .map(([key]) => key);
    });

    function updateFilter<K extends keyof ReportFilters>(
        key: K,
        value: ReportFilters[K],
    ): void {
        filters.value = {
            ...filters.value,
            [key]: value,
        };
    }

    function clearFilters(): void {
        filters.value = {};
    }

    async function applyFilters(): Promise<void> {
        await store.fetchReports(filters.value);
    }

    return {
        filters,
        activeFilters,
        updateFilter,
        clearFilters,
        applyFilters,
    };
}
