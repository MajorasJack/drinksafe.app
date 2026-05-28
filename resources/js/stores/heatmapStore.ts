/**
 * Heatmap Store
 *
 * Manages heatmap state and provides actions for fetching heatmap data.
 * Includes debounced fetching for map movement events, request cancellation,
 * and localStorage persistence.
 */

import axios from 'axios';
import type {CancelTokenSource} from 'axios';
import { defineStore } from 'pinia';
import { computed, ref, watch } from 'vue';
import type {
    HeatmapPeriod,
    HeatmapPoint,
    HeatmapResponse,
    ParsedBounds,
} from '@/types';

const HEATMAP_VISIBLE_KEY = 'drinksafe_heatmap_visible';
const HEATMAP_PERIOD_KEY = 'drinksafe_heatmap_period';
// Align with venue store debounce (300ms) so both requests fire together
const DEBOUNCE_DELAY_MS = 300;

export const useHeatmapStore = defineStore('heatmap', () => {
    // State
    const points = ref<HeatmapPoint[]>([]);
    const loading = ref(false);
    const error = ref<string | null>(null);
    const isVisible = ref(loadVisibilityFromStorage());
    const period = ref<HeatmapPeriod>(loadPeriodFromStorage());
    const currentBounds = ref<ParsedBounds | null>(null);
    const totalReports = ref(0);

    // Debounce timer and request cancellation
    let debounceTimer: ReturnType<typeof setTimeout> | null = null;
    let cancelTokenSource: CancelTokenSource | null = null;

    // Getters
    const hasData = computed(() => points.value.length > 0);

    const leafletHeatData = computed(() =>
        points.value.map((point) => [point.lat, point.lng, point.intensity] as [number, number, number]),
    );

    // Persist visibility preference
    watch(isVisible, (newValue) => {
        localStorage.setItem(HEATMAP_VISIBLE_KEY, JSON.stringify(newValue));
    });

    // Persist period preference
    watch(period, (newValue) => {
        localStorage.setItem(HEATMAP_PERIOD_KEY, newValue);
    });

    /**
     * Loads the visibility preference from localStorage.
     */
    function loadVisibilityFromStorage(): boolean {
        if (typeof window === 'undefined') {
            return false;
        }

        const stored = localStorage.getItem(HEATMAP_VISIBLE_KEY);

        return stored ? JSON.parse(stored) : false;
    }

    /**
     * Loads the period preference from localStorage.
     */
    function loadPeriodFromStorage(): HeatmapPeriod {
        if (typeof window === 'undefined') {
            return '30d';
        }

        const stored = localStorage.getItem(HEATMAP_PERIOD_KEY);

        if (stored && ['7d', '30d', '90d', 'all'].includes(stored)) {
            return stored as HeatmapPeriod;
        }

        return '30d';
    }

    /**
     * Parses a bounds string in format "swLat,swLng,neLat,neLng".
     */
    function parseBoundsString(boundsString: string): ParsedBounds | null {
        const parts = boundsString.split(',').map(Number);

        if (parts.length !== 4 || parts.some(isNaN)) {
            return null;
        }

        return {
            south: parts[0],
            west: parts[1],
            north: parts[2],
            east: parts[3],
        };
    }

    /**
     * Cancels any pending API request.
     */
    function cancelPendingRequest(): void {
        if (cancelTokenSource) {
            cancelTokenSource.cancel('Request cancelled due to new request');
            cancelTokenSource = null;
        }
    }

    /**
     * Fetches heatmap data from the API.
     * Cancels any pending request before starting a new one.
     */
    async function fetchHeatmapData(bounds: ParsedBounds): Promise<void> {
        cancelPendingRequest();

        loading.value = true;
        error.value = null;

        cancelTokenSource = axios.CancelToken.source();

        try {
            const params = new URLSearchParams({
                north: bounds.north.toString(),
                south: bounds.south.toString(),
                east: bounds.east.toString(),
                west: bounds.west.toString(),
                period: period.value,
            });

            const response = await axios.get<HeatmapResponse>(`/api/heatmap?${params.toString()}`, {
                cancelToken: cancelTokenSource.token,
            });

            points.value = response.data.data;
            totalReports.value = response.data.meta.total_reports;
            currentBounds.value = bounds;
        } catch (err) {
            if (axios.isCancel(err)) {
                return;
            }

            error.value = err instanceof Error ? err.message : 'Failed to fetch heatmap data';
            console.error('Failed to fetch heatmap data:', err);
        } finally {
            loading.value = false;
        }
    }

    /**
     * Fetches heatmap data with debouncing to prevent excessive API calls.
     */
    function fetchHeatmapDataDebounced(boundsString: string): void {
        if (!isVisible.value) {
            return;
        }

        const parsedBounds = parseBoundsString(boundsString);

        if (!parsedBounds) {
            console.error('Invalid bounds string:', boundsString);

            return;
        }

        if (debounceTimer) {
            clearTimeout(debounceTimer);
        }

        debounceTimer = setTimeout(() => {
            fetchHeatmapData(parsedBounds);
        }, DEBOUNCE_DELAY_MS);
    }

    /**
     * Toggles heatmap visibility.
     */
    function toggleVisibility(): void {
        isVisible.value = !isVisible.value;

        // Fetch data if becoming visible and we have bounds
        if (isVisible.value && currentBounds.value) {
            fetchHeatmapData(currentBounds.value);
        }
    }

    /**
     * Sets the time period filter and refetches data.
     */
    function setPeriod(newPeriod: HeatmapPeriod): void {
        period.value = newPeriod;

        // Refetch data if visible and we have bounds
        if (isVisible.value && currentBounds.value) {
            fetchHeatmapData(currentBounds.value);
        }
    }

    /**
     * Clears any error state.
     */
    function clearError(): void {
        error.value = null;
    }

    /**
     * Clears all heatmap data.
     */
    function clearData(): void {
        points.value = [];
        totalReports.value = 0;
        currentBounds.value = null;
    }

    return {
        // State
        points,
        loading,
        error,
        isVisible,
        period,
        currentBounds,
        totalReports,

        // Getters
        hasData,
        leafletHeatData,

        // Actions
        fetchHeatmapData,
        fetchHeatmapDataDebounced,
        toggleVisibility,
        setPeriod,
        clearError,
        clearData,
    };
});
