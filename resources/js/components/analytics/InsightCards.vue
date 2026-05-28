<script setup lang="ts">
import { Lightbulb } from 'lucide-vue-next';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Skeleton } from '@/components/ui/skeleton';

interface Props {
    insights: string[];
    loading?: boolean;
}

withDefaults(defineProps<Props>(), {
    loading: false,
});
</script>

<template>
    <div class="space-y-4">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
            Key Insights
        </h3>

        <!-- Loading state -->
        <div
            v-if="loading"
            class="grid gap-4 md:grid-cols-2"
        >
            <Skeleton class="h-20" />
            <Skeleton class="h-20" />
        </div>

        <!-- Empty state -->
        <Alert
            v-else-if="insights.length === 0"
            class="border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800/50"
        >
            <Lightbulb class="size-4 text-slate-400" />
            <AlertDescription class="text-slate-600 dark:text-slate-400">
                Not enough data to generate insights. More reports are needed to
                identify meaningful patterns.
            </AlertDescription>
        </Alert>

        <!-- Insight cards -->
        <div
            v-else
            class="grid gap-4 md:grid-cols-2"
        >
            <Alert
                v-for="(insight, index) in insights"
                :key="index"
                class="border-brand-teal/20 bg-brand-teal/5 dark:border-brand-teal/30 dark:bg-brand-teal/10"
            >
                <Lightbulb class="size-4 shrink-0 text-brand-teal" />
                <AlertDescription
                    class="text-slate-700 dark:text-slate-200"
                >
                    {{ insight }}
                </AlertDescription>
            </Alert>
        </div>
    </div>
</template>
