<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { BarChart3, AlertCircle, RefreshCw } from 'lucide-vue-next';
import { onMounted, ref, watch } from 'vue';
import AnalyticsFilters from '@/components/analytics/AnalyticsFilters.vue';
import AnalyticsSummary from '@/components/analytics/AnalyticsSummary.vue';
import InsightCards from '@/components/analytics/InsightCards.vue';
import TemporalGrid from '@/components/analytics/TemporalGrid.vue';
import AppLayout from '@/components/layout/AppLayout.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useAnalytics } from '@/composables/useAnalytics';
import type { AnalyticsFilters as AnalyticsFiltersType } from '@/types/analytics';

interface Props {
    cities: string[];
}

const props = defineProps<Props>();

const { data, loading, error, fetchTemporalAnalytics, clearError } =
    useAnalytics();

const filters = ref<AnalyticsFiltersType>({
    period: 90,
    city: undefined,
});

/**
 * Fetch analytics data with current filters
 */
const fetchData = async (): Promise<void> => {
    await fetchTemporalAnalytics(filters.value);
};

/**
 * Handle filter updates
 */
const handleFilterUpdate = (newFilters: AnalyticsFiltersType): void => {
    filters.value = newFilters;
};

/**
 * Retry fetching after an error
 */
const handleRetry = (): void => {
    clearError();
    fetchData();
};

// Fetch data on mount
onMounted(() => {
    fetchData();
});

// Refetch when filters change
watch(filters, () => {
    fetchData();
});
</script>

<template>
    <Head title="Analytics" />

    <AppLayout>
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <!-- Page header -->
            <div class="mb-8">
                <div class="flex items-center gap-3">
                    <div
                        class="flex size-10 items-center justify-center rounded-lg bg-brand-teal/10"
                    >
                        <BarChart3 class="size-5 text-brand-teal" />
                    </div>
                    <div>
                        <h1
                            class="text-2xl font-bold text-slate-900 sm:text-3xl dark:text-white"
                        >
                            Time-based Analytics
                        </h1>
                        <p class="mt-1 text-slate-600 dark:text-slate-400">
                            Discover when incidents are most frequently
                            reported.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <AnalyticsFilters
                :filters="filters"
                :cities="props.cities"
                :loading="loading"
                class="mb-8"
                @update:filters="handleFilterUpdate"
            />

            <!-- Error state -->
            <Alert
                v-if="error"
                variant="destructive"
                class="mb-8"
            >
                <AlertCircle class="size-4" />
                <AlertTitle>Error loading analytics</AlertTitle>
                <AlertDescription class="flex items-center justify-between">
                    <span>{{ error }}</span>
                    <Button
                        variant="outline"
                        size="sm"
                        @click="handleRetry"
                    >
                        <RefreshCw class="mr-2 size-4" />
                        Retry
                    </Button>
                </AlertDescription>
            </Alert>

            <!-- Main content -->
            <div
                v-else
                class="space-y-8"
            >
                <!-- Summary cards -->
                <AnalyticsSummary
                    :totals="data?.totals ?? { byDay: {}, byTime: {} }"
                    :meta="data?.meta ?? { periodDays: 0, totalReports: 0, city: null }"
                    :loading="loading"
                />

                <!-- Heatmap grid -->
                <Card>
                    <CardHeader>
                        <CardTitle>Report Patterns by Day and Time</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <TemporalGrid
                            :grid="data?.grid ?? []"
                            :loading="loading"
                        />
                    </CardContent>
                </Card>

                <!-- Insights -->
                <InsightCards
                    :insights="data?.insights ?? []"
                    :loading="loading"
                />

                <!-- Empty state when no data -->
                <Alert
                    v-if="!loading && data?.meta?.totalReports === 0"
                    class="border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800/50"
                >
                    <BarChart3 class="size-4 text-slate-400" />
                    <AlertTitle class="text-slate-700 dark:text-slate-300">
                        No data available
                    </AlertTitle>
                    <AlertDescription class="text-slate-600 dark:text-slate-400">
                        There are no reports matching your current filters. Try
                        adjusting the time period or removing the city filter.
                    </AlertDescription>
                </Alert>
            </div>

            <!-- Disclaimer -->
            <div
                class="mt-12 rounded-lg border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/50"
            >
                <p class="text-sm text-slate-600 dark:text-slate-400">
                    <strong class="text-slate-700 dark:text-slate-300">
                        Note:
                    </strong>
                    This data represents anonymised reports submitted by users
                    and may not reflect the complete picture of incidents. Use
                    this information as one factor when making decisions about
                    personal safety.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
