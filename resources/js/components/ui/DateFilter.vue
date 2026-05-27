<script setup lang="ts">
import { computed } from 'vue';
import { Calendar } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

interface Props {
    startDate?: string;
    endDate?: string;
}

const props = withDefaults(defineProps<Props>(), {
    startDate: '',
    endDate: '',
});

interface Emits {
    (e: 'update:startDate', value: string): void;
    (e: 'update:endDate', value: string): void;
    (e: 'apply'): void;
    (e: 'clear'): void;
}

const emit = defineEmits<Emits>();

const isValid = computed((): boolean => {
    if (!props.startDate || !props.endDate) return true;
    return props.endDate >= props.startDate;
});

const hasFilters = computed((): boolean => {
    return Boolean(props.startDate || props.endDate);
});

const handleStartDateChange = (event: Event): void => {
    const target = event.target as HTMLInputElement;
    emit('update:startDate', target.value);
};

const handleEndDateChange = (event: Event): void => {
    const target = event.target as HTMLInputElement;
    emit('update:endDate', target.value);
};

const handleApply = (): void => {
    if (isValid.value) {
        emit('apply');
    }
};

const handleClear = (): void => {
    emit('update:startDate', '');
    emit('update:endDate', '');
    emit('clear');
};
</script>

<template>
    <div class="space-y-3 rounded-xl bg-slate-50 p-4 shadow-sm dark:bg-gray-800/50">
        <div class="flex items-center gap-2">
            <Calendar class="size-4 text-brand-teal" />
            <h3 class="text-sm font-semibold text-slate-900 dark:text-white">
                Filter by Date
            </h3>
        </div>

        <div class="space-y-2">
            <div class="space-y-1.5">
                <label
                    for="start-date"
                    class="block text-xs font-medium text-slate-700 dark:text-gray-300"
                >
                    Start Date
                </label>
                <input
                    id="start-date"
                    type="date"
                    :value="startDate"
                    class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition-colors focus:border-brand-teal focus:ring-2 focus:ring-brand-teal/20 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-brand-teal"
                    @input="handleStartDateChange"
                />
            </div>

            <div class="space-y-1.5">
                <label
                    for="end-date"
                    class="block text-xs font-medium text-slate-700 dark:text-gray-300"
                >
                    End Date
                </label>
                <input
                    id="end-date"
                    type="date"
                    :value="endDate"
                    class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition-colors focus:border-brand-teal focus:ring-2 focus:ring-brand-teal/20 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-brand-teal"
                    @input="handleEndDateChange"
                />
            </div>
        </div>

        <div v-if="!isValid" class="rounded-md bg-red-50 p-2 dark:bg-red-900/20">
            <p class="text-xs text-red-600 dark:text-red-400">
                End date must be after start date
            </p>
        </div>

        <div class="flex gap-2">
            <Button
                type="button"
                size="sm"
                class="flex-1"
                :disabled="!isValid || !hasFilters"
                @click="handleApply"
            >
                Apply Filters
            </Button>
            <Button
                v-if="hasFilters"
                type="button"
                variant="outline"
                size="sm"
                @click="handleClear"
            >
                Clear
            </Button>
        </div>
    </div>
</template>
