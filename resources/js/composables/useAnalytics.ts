/**
 * useAnalytics Composable
 *
 * Provides functionality for fetching and managing temporal analytics data.
 */

import axios from 'axios';
import { ref } from 'vue';
import type {
    AnalyticsFilters,
    TemporalAnalytics,
    TemporalAnalyticsApiResponse,
} from '@/types/analytics';

/**
 * Transform snake_case API response to camelCase frontend types
 */
const transformApiResponse = (
    response: TemporalAnalyticsApiResponse,
): TemporalAnalytics => ({
    grid: response.data.grid,
    totals: {
        byDay: response.data.totals.by_day,
        byTime: response.data.totals.by_time,
    },
    insights: response.data.insights,
    meta: {
        periodDays: response.data.meta.period_days,
        totalReports: response.data.meta.total_reports,
        city: response.data.meta.city,
    },
});

export function useAnalytics() {
    const data = ref<TemporalAnalytics | null>(null);
    const loading = ref(false);
    const error = ref<string | null>(null);

    /**
     * Fetch temporal analytics data from the API
     */
    const fetchTemporalAnalytics = async (
        filters: AnalyticsFilters,
    ): Promise<TemporalAnalytics | null> => {
        loading.value = true;
        error.value = null;

        try {
            const params = new URLSearchParams();
            params.append('period', filters.period.toString());

            if (filters.city) {
                params.append('city', filters.city);
            }

            const url = `/api/analytics/temporal?${params.toString()}`;
            const response =
                await axios.get<TemporalAnalyticsApiResponse>(url);

            data.value = transformApiResponse(response);

            return data.value;
        } catch (err) {
            if (axios.isAxiosError(err)) {
                if (err.response?.status === 422) {
                    error.value = 'Invalid filter parameters';
                } else if (err.response?.status === 404) {
                    error.value = 'Analytics data not found';
                } else {
                    error.value =
                        err.response?.data?.message ||
                        'Failed to fetch analytics data';
                }
            } else {
                error.value =
                    err instanceof Error
                        ? err.message
                        : 'An unexpected error occurred';
            }

            console.error('Failed to fetch temporal analytics:', err);

            return null;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Clear any existing error
     */
    const clearError = (): void => {
        error.value = null;
    };

    /**
     * Reset all state
     */
    const reset = (): void => {
        data.value = null;
        loading.value = false;
        error.value = null;
    };

    return {
        // State
        data,
        loading,
        error,

        // Actions
        fetchTemporalAnalytics,
        clearError,
        reset,
    };
}
