<script setup lang="ts">
import { Calendar, Clock, TrendingUp, FileText } from 'lucide-vue-next';
import { computed } from 'vue';
import { Card, CardContent } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { DAYS_OF_WEEK, TIMES_OF_DAY } from '@/types/analytics';
import type { AnalyticsTotals, AnalyticsMeta } from '@/types/analytics';
import type { DayOfWeek, TimeOfDay } from '@/types/analytics';

interface Props {
    totals: AnalyticsTotals;
    meta: AnalyticsMeta;
    loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    loading: false,
});

/**
 * Find the peak day (day with most reports)
 */
const peakDay = computed((): { day: DayOfWeek; count: number } | null => {
    if (!props.totals?.byDay) {
return null;
}

    let maxDay: DayOfWeek = 'Monday';
    let maxCount = 0;

    for (const day of DAYS_OF_WEEK) {
        const count = props.totals.byDay[day] ?? 0;

        if (count > maxCount) {
            maxDay = day;
            maxCount = count;
        }
    }

    return maxCount > 0 ? { day: maxDay, count: maxCount } : null;
});

/**
 * Find the peak time (time period with most reports)
 */
const peakTime = computed((): { time: TimeOfDay; count: number } | null => {
    if (!props.totals?.byTime) {
return null;
}

    let maxTime: TimeOfDay = 'Night';
    let maxCount = 0;

    for (const time of TIMES_OF_DAY) {
        const count = props.totals.byTime[time] ?? 0;

        if (count > maxCount) {
            maxTime = time;
            maxCount = count;
        }
    }

    return maxCount > 0 ? { time: maxTime, count: maxCount } : null;
});

/**
 * Calculate average reports per day in the period
 */
const averagePerDay = computed((): number => {
    if (!props.meta?.totalReports || !props.meta?.periodDays) {
return 0;
}

    return Math.round((props.meta.totalReports / props.meta.periodDays) * 10) / 10;
});

/**
 * Format period description
 */
const periodDescription = computed((): string => {
    if (!props.meta?.periodDays) {
return '';
}

    if (props.meta.periodDays === 30) {
return 'last 30 days';
}

    if (props.meta.periodDays === 90) {
return 'last 90 days';
}

    if (props.meta.periodDays === 365) {
return 'last year';
}

    return `last ${props.meta.periodDays} days`;
});
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Total Reports -->
        <Card>
            <CardContent class="flex items-center gap-4 pt-6">
                <div
                    class="flex size-12 shrink-0 items-center justify-center rounded-lg bg-brand-teal/10"
                >
                    <FileText class="size-6 text-brand-teal" />
                </div>
                <div v-if="loading">
                    <Skeleton class="mb-2 h-6 w-16" />
                    <Skeleton class="h-4 w-24" />
                </div>
                <div v-else>
                    <p
                        class="text-2xl font-bold text-slate-900 dark:text-white"
                    >
                        {{ meta.totalReports.toLocaleString() }}
                    </p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Total reports
                    </p>
                </div>
            </CardContent>
        </Card>

        <!-- Average per day -->
        <Card>
            <CardContent class="flex items-center gap-4 pt-6">
                <div
                    class="flex size-12 shrink-0 items-center justify-center rounded-lg bg-indigo-100 dark:bg-indigo-900/40"
                >
                    <TrendingUp class="size-6 text-indigo-600 dark:text-indigo-400" />
                </div>
                <div v-if="loading">
                    <Skeleton class="mb-2 h-6 w-16" />
                    <Skeleton class="h-4 w-24" />
                </div>
                <div v-else>
                    <p
                        class="text-2xl font-bold text-slate-900 dark:text-white"
                    >
                        {{ averagePerDay }}
                    </p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Avg per day
                    </p>
                </div>
            </CardContent>
        </Card>

        <!-- Peak Day -->
        <Card>
            <CardContent class="flex items-center gap-4 pt-6">
                <div
                    class="flex size-12 shrink-0 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-900/40"
                >
                    <Calendar class="size-6 text-amber-600 dark:text-amber-400" />
                </div>
                <div v-if="loading">
                    <Skeleton class="mb-2 h-6 w-16" />
                    <Skeleton class="h-4 w-24" />
                </div>
                <div v-else-if="peakDay">
                    <p
                        class="text-2xl font-bold text-slate-900 dark:text-white"
                    >
                        {{ peakDay.day }}
                    </p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Peak day ({{ peakDay.count }})
                    </p>
                </div>
                <div v-else>
                    <p class="text-lg font-semibold text-slate-400">N/A</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Peak day
                    </p>
                </div>
            </CardContent>
        </Card>

        <!-- Peak Time -->
        <Card>
            <CardContent class="flex items-center gap-4 pt-6">
                <div
                    class="flex size-12 shrink-0 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900/40"
                >
                    <Clock class="size-6 text-purple-600 dark:text-purple-400" />
                </div>
                <div v-if="loading">
                    <Skeleton class="mb-2 h-6 w-16" />
                    <Skeleton class="h-4 w-24" />
                </div>
                <div v-else-if="peakTime">
                    <p
                        class="text-2xl font-bold text-slate-900 dark:text-white"
                    >
                        {{ peakTime.time }}
                    </p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Peak time ({{ peakTime.count }})
                    </p>
                </div>
                <div v-else>
                    <p class="text-lg font-semibold text-slate-400">N/A</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Peak time
                    </p>
                </div>
            </CardContent>
        </Card>
    </div>

    <!-- Period info -->
    <p
        v-if="!loading && meta.periodDays"
        class="mt-2 text-sm text-slate-500 dark:text-slate-400"
    >
        Data from the {{ periodDescription }}{{ meta.city ? ` in ${meta.city}` : '' }}
    </p>
</template>
