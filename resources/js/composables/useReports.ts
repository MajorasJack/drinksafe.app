/**
 * useReports Composable
 *
 * Provides reactive access to report store data and actions.
 */

import { computed } from 'vue';
import { useReportStore } from '@/stores/reportStore';

export function useReports() {
    const store = useReportStore();

    return {
        // State
        reports: computed(() => store.reports),
        recentReports: computed(() => store.recentReports),
        loading: computed(() => store.loading),
        error: computed(() => store.error),

        // Getters
        reportsByVenue: store.reportsByVenue,
        reportsByTimeOfDay: store.reportsByTimeOfDay,

        // Actions
        fetchReports: store.fetchReports,
        fetchRecentReports: store.fetchRecentReports,
        submitReport: store.submitReport,
        clearError: store.clearError,
    };
}
