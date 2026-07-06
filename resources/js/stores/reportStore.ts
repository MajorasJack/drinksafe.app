/**
 * Report Store
 *
 * Manages report state and provides actions for fetching and submitting reports.
 */

import axios from 'axios';
import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import type {
    Report,
    ReportFilters,
    ReportSubmitData,
    TimeOfDay,
} from '@/types';

/**
 * Error thrown when a report submission fails, carrying any per-field
 * validation messages returned by the backend (HTTP 422) so the form can
 * surface them inline instead of only showing a generic toast.
 */
export class ReportSubmissionError extends Error {
    public readonly fieldErrors: Record<string, string>;

    constructor(message: string, fieldErrors: Record<string, string> = {}) {
        super(message);
        this.name = 'ReportSubmissionError';
        this.fieldErrors = fieldErrors;
    }
}

export const useReportStore = defineStore('report', () => {
    // State
    const reports = ref<Report[]>([]);
    const recentReports = ref<Report[]>([]);
    const loading = ref(false);
    const error = ref<string | null>(null);

    // Getters
    const reportsByVenue = computed(() => (venueUuid: string) => {
        return reports.value.filter(
            (report) => report.venue_uuid === venueUuid,
        );
    });

    const reportsByTimeOfDay = computed(() => (timeOfDay: TimeOfDay) => {
        return reports.value.filter(
            (report) => report.time_of_day === timeOfDay,
        );
    });

    // Actions
    async function fetchReports(filters?: ReportFilters): Promise<void> {
        loading.value = true;
        error.value = null;

        try {
            const params = new URLSearchParams();

            if (filters?.venue_uuid) {
                params.append('venue_uuid', filters.venue_uuid);
            }

            if (filters?.start_date) {
                params.append('start_date', filters.start_date);
            }

            if (filters?.end_date) {
                params.append('end_date', filters.end_date);
            }

            if (filters?.time_of_day) {
                params.append('time_of_day', filters.time_of_day);
            }

            if (filters?.limit) {
                params.append('limit', filters.limit.toString());
            }

            const url = `/api/reports${params.toString() ? `?${params.toString()}` : ''}`;
            const response = await axios.get(url);

            reports.value = response.data.data;
        } catch (err) {
            error.value =
                err instanceof Error ? err.message : 'Failed to fetch reports';
            console.error('Failed to fetch reports:', err);
        } finally {
            loading.value = false;
        }
    }

    async function fetchRecentReports(limit = 10): Promise<void> {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios.get(`/api/reports?limit=${limit}`);
            recentReports.value = response.data.data;
        } catch (err) {
            error.value =
                err instanceof Error
                    ? err.message
                    : 'Failed to fetch recent reports';
            console.error('Failed to fetch recent reports:', err);
        } finally {
            loading.value = false;
        }
    }

    async function submitReport(data: ReportSubmitData): Promise<Report> {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios.post('/api/reports', data);
            const newReport = response.data.data;

            // Add to reports array
            reports.value.unshift(newReport);

            return newReport;
        } catch (err) {
            if (axios.isAxiosError(err) && err.response) {
                const body = err.response.data as {
                    message?: string;
                    errors?: Record<string, string[]>;
                };

                const fieldErrors: Record<string, string> = {};

                for (const [field, messages] of Object.entries(
                    body.errors ?? {},
                )) {
                    fieldErrors[field] = messages[0];
                }

                error.value =
                    body.message ??
                    'Failed to submit report. Please try again.';

                throw new ReportSubmissionError(error.value, fieldErrors);
            }

            error.value =
                err instanceof Error ? err.message : 'Failed to submit report';
            console.error('Failed to submit report:', err);

            throw new ReportSubmissionError(error.value);
        } finally {
            loading.value = false;
        }
    }

    function clearError(): void {
        error.value = null;
    }

    return {
        // State
        reports,
        recentReports,
        loading,
        error,

        // Getters
        reportsByVenue,
        reportsByTimeOfDay,

        // Actions
        fetchReports,
        fetchRecentReports,
        submitReport,
        clearError,
    };
});
