<script setup lang="ts">
import { AlertCircle } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import type { Report, TimeOfDay } from '@/types/report';
import ReportCard from './ReportCard.vue';

interface Props {
    reports: Report[];
    loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    loading: false,
});

const selectedTimeOfDay = ref<TimeOfDay | 'All'>('All');

const timeOfDayOptions = [
    { value: 'All', label: 'All Times' },
    { value: 'Morning', label: 'Morning' },
    { value: 'Afternoon', label: 'Afternoon' },
    { value: 'Evening', label: 'Evening' },
    { value: 'Night', label: 'Night' },
    { value: 'Unknown', label: 'Unknown' },
];

const filteredReports = computed((): Report[] => {
    let filtered = [...props.reports];

    if (selectedTimeOfDay.value !== 'All') {
        filtered = filtered.filter(
            (report) => report.time_of_day === selectedTimeOfDay.value,
        );
    }

    // Sort by date descending (newest first)
    filtered.sort((a, b) => {
        return (
            new Date(b.incident_date).getTime() -
            new Date(a.incident_date).getTime()
        );
    });

    return filtered;
});

const hasReports = computed((): boolean => {
    return props.reports.length > 0;
});

const hasFilteredReports = computed((): boolean => {
    return filteredReports.value.length > 0;
});
</script>

<template>
    <div class="space-y-6">
        <div v-if="hasReports || loading" class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
                Reports
                <span class="text-slate-500 dark:text-gray-400">
                    ({{ filteredReports.length }})
                </span>
            </h3>
            <Select v-model="selectedTimeOfDay">
                <SelectTrigger class="w-[180px]">
                    <SelectValue placeholder="Filter by time" />
                </SelectTrigger>
                <SelectContent>
                    <SelectGroup>
                        <SelectItem
                            v-for="option in timeOfDayOptions"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </SelectItem>
                    </SelectGroup>
                </SelectContent>
            </Select>
        </div>

        <div v-if="loading" class="space-y-4">
            <div v-for="i in 3" :key="i" class="rounded-xl border p-6">
                <div class="space-y-3">
                    <div class="flex gap-4">
                        <Skeleton class="h-4 w-24" />
                        <Skeleton class="h-4 w-24" />
                    </div>
                    <Skeleton class="h-4 w-full" />
                    <Skeleton class="h-4 w-3/4" />
                </div>
            </div>
        </div>

        <div v-else-if="!hasReports" class="text-center py-12">
            <AlertCircle class="size-12 mx-auto text-slate-400 dark:text-gray-500 mb-4" />
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">
                No Reports Yet
            </h3>
            <p class="text-slate-600 dark:text-gray-300">
                Be the first to report an incident at this venue.
            </p>
        </div>

        <div
            v-else-if="!hasFilteredReports"
            class="text-center py-12"
        >
            <AlertCircle class="size-12 mx-auto text-slate-400 dark:text-gray-500 mb-4" />
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">
                No Reports Found
            </h3>
            <p class="text-slate-600 dark:text-gray-300">
                Try adjusting your filters to see more results.
            </p>
        </div>

        <div v-else class="space-y-4">
            <ReportCard
                v-for="report in filteredReports"
                :key="report.uuid"
                :report="report"
            />
        </div>
    </div>
</template>
