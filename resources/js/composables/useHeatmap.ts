/**
 * useHeatmap Composable
 *
 * Provides reactive access to heatmap store data and actions.
 * Handles debounced API calls on map movement and period filtering.
 * Uses storeToRefs for efficient reactivity without double-wrapping.
 */

import { storeToRefs } from 'pinia';
import { useHeatmapStore } from '@/stores/heatmapStore';

export function useHeatmap() {
    const store = useHeatmapStore();

    // Use storeToRefs for reactive state - avoids double-wrapping computed overhead
    const {
        points,
        loading,
        error,
        isVisible,
        period,
        totalReports,
        hasData,
        leafletHeatData,
    } = storeToRefs(store);

    return {
        // State (already reactive via storeToRefs)
        points,
        loading,
        error,
        isVisible,
        period,
        totalReports,

        // Getters (already reactive via storeToRefs)
        hasData,
        leafletHeatData,

        // Actions (not reactive, just functions)
        fetchHeatmapDataDebounced: store.fetchHeatmapDataDebounced,
        toggleVisibility: store.toggleVisibility,
        setPeriod: store.setPeriod,
        clearError: store.clearError,
        clearData: store.clearData,
    };
}
