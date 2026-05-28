<script setup lang="ts">
import { computed } from 'vue';
import { Skeleton } from '@/components/ui/skeleton';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import {
    DAYS_OF_WEEK,
    TIMES_OF_DAY,
    TIME_PERIOD_DESCRIPTIONS,
} from '@/types/analytics';
import type { TemporalGridCell } from '@/types/analytics';
import type { DayOfWeek, TimeOfDay } from '@/types/analytics';

interface Props {
    grid: TemporalGridCell[];
    loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    loading: false,
});

/**
 * Get cell data for a specific day and time
 */
const getCellData = (
    day: DayOfWeek,
    time: TimeOfDay,
): TemporalGridCell | null => {
    return (
        props.grid.find((cell) => cell.day === day && cell.time === time) ??
        null
    );
};

/**
 * Maximum count in the grid for normalising intensity
 */
const maxCount = computed((): number => {
    if (props.grid.length === 0) {
return 1;
}

    return Math.max(...props.grid.map((cell) => cell.count), 1);
});

/**
 * Get colour intensity class based on count
 * Uses a blue/purple gradient (not red to avoid implying danger)
 */
const getIntensityClass = (count: number): string => {
    if (count === 0) {
        return 'bg-slate-100 dark:bg-slate-800';
    }

    const intensity = count / maxCount.value;

    if (intensity < 0.2) {
        return 'bg-indigo-100 dark:bg-indigo-900/40';
    }

    if (intensity < 0.4) {
        return 'bg-indigo-200 dark:bg-indigo-800/60';
    }

    if (intensity < 0.6) {
        return 'bg-indigo-300 dark:bg-indigo-700/70';
    }

    if (intensity < 0.8) {
        return 'bg-indigo-400 dark:bg-indigo-600';
    }

    return 'bg-indigo-500 dark:bg-indigo-500';
};

/**
 * Get text colour class based on intensity
 */
const getTextClass = (count: number): string => {
    if (count === 0) {
        return 'text-slate-400 dark:text-slate-500';
    }

    const intensity = count / maxCount.value;

    return intensity >= 0.6
        ? 'text-white'
        : 'text-slate-700 dark:text-slate-200';
};

/**
 * Short day name for mobile display
 */
const shortDayName = (day: DayOfWeek): string => day.slice(0, 3);
</script>

<template>
    <div class="w-full overflow-x-auto">
        <!-- Loading skeleton -->
        <div
            v-if="loading"
            class="space-y-2"
        >
            <div class="flex gap-2">
                <Skeleton class="h-8 w-20" />
                <div class="grid flex-1 grid-cols-4 gap-2">
                    <Skeleton
                        v-for="time in TIMES_OF_DAY"
                        :key="time"
                        class="h-8"
                    />
                </div>
            </div>
            <div
                v-for="day in DAYS_OF_WEEK"
                :key="day"
                class="flex gap-2"
            >
                <Skeleton class="h-12 w-20" />
                <div class="grid flex-1 grid-cols-4 gap-2">
                    <Skeleton
                        v-for="time in TIMES_OF_DAY"
                        :key="`${day}-${time}`"
                        class="h-12"
                    />
                </div>
            </div>
        </div>

        <!-- Grid -->
        <div
            v-else
            class="min-w-[400px]"
        >
            <!-- Header row with time periods -->
            <div class="mb-2 flex gap-2">
                <div class="w-16 shrink-0 md:w-24" />
                <div class="grid flex-1 grid-cols-4 gap-2">
                    <TooltipProvider
                        v-for="time in TIMES_OF_DAY"
                        :key="time"
                    >
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <div
                                    class="rounded-md bg-slate-100 px-2 py-1.5 text-center text-xs font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300"
                                >
                                    {{ time }}
                                </div>
                            </TooltipTrigger>
                            <TooltipContent>
                                <p>{{ TIME_PERIOD_DESCRIPTIONS[time] }}</p>
                            </TooltipContent>
                        </Tooltip>
                    </TooltipProvider>
                </div>
            </div>

            <!-- Grid rows -->
            <div class="space-y-2">
                <div
                    v-for="day in DAYS_OF_WEEK"
                    :key="day"
                    class="flex gap-2"
                >
                    <!-- Day label -->
                    <div
                        class="flex w-16 shrink-0 items-center text-xs font-medium text-slate-600 md:w-24 md:text-sm dark:text-slate-300"
                    >
                        <span class="hidden md:inline">{{ day }}</span>
                        <span class="md:hidden">{{ shortDayName(day) }}</span>
                    </div>

                    <!-- Time cells -->
                    <div class="grid flex-1 grid-cols-4 gap-2">
                        <TooltipProvider
                            v-for="time in TIMES_OF_DAY"
                            :key="`${day}-${time}`"
                        >
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <div
                                        class="flex h-10 cursor-pointer items-center justify-center rounded-md transition-all hover:scale-105 hover:shadow-md md:h-12"
                                        :class="
                                            getIntensityClass(
                                                getCellData(day, time)
                                                    ?.count ?? 0,
                                            )
                                        "
                                        role="gridcell"
                                        :aria-label="`${day} ${time}: ${getCellData(day, time)?.count ?? 0} reports`"
                                    >
                                        <span
                                            class="text-sm font-semibold"
                                            :class="
                                                getTextClass(
                                                    getCellData(day, time)
                                                        ?.count ?? 0,
                                                )
                                            "
                                        >
                                            {{
                                                getCellData(day, time)?.count ??
                                                0
                                            }}
                                        </span>
                                    </div>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <div class="space-y-1">
                                        <p class="font-medium">
                                            {{ day }} {{ time }}
                                        </p>
                                        <p>
                                            {{
                                                getCellData(day, time)?.count ??
                                                0
                                            }}
                                            reports
                                        </p>
                                        <p
                                            v-if="
                                                getCellData(day, time)
                                                    ?.percentage
                                            "
                                            class="text-xs text-slate-400"
                                        >
                                            {{
                                                getCellData(
                                                    day,
                                                    time,
                                                )?.percentage.toFixed(1)
                                            }}% of total
                                        </p>
                                    </div>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                    </div>
                </div>
            </div>

            <!-- Legend -->
            <div
                class="mt-4 flex items-center justify-end gap-2 text-xs text-slate-500 dark:text-slate-400"
            >
                <span>Fewer</span>
                <div class="flex gap-0.5">
                    <div
                        class="size-4 rounded bg-slate-100 dark:bg-slate-800"
                    />
                    <div
                        class="size-4 rounded bg-indigo-100 dark:bg-indigo-900/40"
                    />
                    <div
                        class="size-4 rounded bg-indigo-200 dark:bg-indigo-800/60"
                    />
                    <div
                        class="size-4 rounded bg-indigo-300 dark:bg-indigo-700/70"
                    />
                    <div
                        class="size-4 rounded bg-indigo-400 dark:bg-indigo-600"
                    />
                    <div class="size-4 rounded bg-indigo-500" />
                </div>
                <span>More</span>
            </div>
        </div>
    </div>
</template>
