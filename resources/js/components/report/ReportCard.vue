<script setup lang="ts">
import { format } from 'date-fns';
import { Clock, Calendar } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import type { Report } from '@/types/report';

interface Props {
    report: Report;
}

const props = defineProps<Props>();

const isExpanded = ref(false);

const formattedDate = computed((): string => {
    return format(new Date(props.report.incident_date), 'dd MMM yyyy');
});

const isDescriptionLong = computed((): boolean => {
    return props.report.description.length > 250;
});

const displayDescription = computed((): string => {
    if (!isExpanded.value && isDescriptionLong.value) {
        return props.report.description.substring(0, 250) + '...';
    }

    return props.report.description;
});

const toggleExpanded = (): void => {
    isExpanded.value = !isExpanded.value;
};
</script>

<template>
    <Card class="transition-all hover:shadow-lg hover:border-brand-teal/20 dark:hover:border-brand-teal/30">
        <CardHeader>
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-center gap-4 text-sm text-slate-600 dark:text-gray-100">
                    <div class="flex items-center gap-1.5">
                        <Calendar class="size-4" />
                        <span>{{ formattedDate }}</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <Clock class="size-4" />
                        <span>{{ report.time_of_day }}</span>
                    </div>
                </div>
            </div>
        </CardHeader>
        <CardContent>
            <p class="text-slate-700 dark:text-gray-50 leading-relaxed">
                {{ displayDescription }}
            </p>
            <Button
                v-if="isDescriptionLong"
                variant="link"
                class="mt-2 px-0 h-auto text-brand-teal hover:text-brand-teal/80"
                @click="toggleExpanded"
            >
                {{ isExpanded ? 'Show Less' : 'Read More' }}
            </Button>
        </CardContent>
    </Card>
</template>
