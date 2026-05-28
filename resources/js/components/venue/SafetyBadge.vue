<script setup lang="ts">
import { Shield, ShieldAlert, ShieldCheck, ShieldQuestion } from 'lucide-vue-next';
import { computed  } from 'vue';
import type {Component} from 'vue';
import { Skeleton } from '@/components/ui/skeleton';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import type { SafetyScore, SafetyTier } from '@/types/safety';

interface Props {
    score: SafetyScore | null;
    loading?: boolean;
    size?: 'sm' | 'md' | 'lg';
}

const props = withDefaults(defineProps<Props>(), {
    loading: false,
    size: 'md',
});

interface TierConfig {
    label: string;
    description: string;
    bgClass: string;
    textClass: string;
    borderClass: string;
    icon: Component;
}

const tierConfigs: Record<SafetyTier, TierConfig> = {
    low: {
        label: 'Low Concern',
        description: 'Few or no recent reports at this venue',
        bgClass: 'bg-green-100 dark:bg-green-900/30',
        textClass: 'text-green-700 dark:text-green-300',
        borderClass: 'border-green-200 dark:border-green-800',
        icon: ShieldCheck,
    },
    moderate: {
        label: 'Moderate Concern',
        description: 'Some recent activity reported at this venue',
        bgClass: 'bg-amber-100 dark:bg-amber-900/30',
        textClass: 'text-amber-700 dark:text-amber-300',
        borderClass: 'border-amber-200 dark:border-amber-800',
        icon: Shield,
    },
    high: {
        label: 'High Concern',
        description: 'Significant recent reports at this venue',
        bgClass: 'bg-red-100 dark:bg-red-900/30',
        textClass: 'text-red-700 dark:text-red-300',
        borderClass: 'border-red-200 dark:border-red-800',
        icon: ShieldAlert,
    },
    insufficient_data: {
        label: 'Insufficient Data',
        description: 'Not enough reports to determine safety level',
        bgClass: 'bg-slate-100 dark:bg-slate-800',
        textClass: 'text-slate-600 dark:text-slate-300',
        borderClass: 'border-slate-200 dark:border-slate-700',
        icon: ShieldQuestion,
    },
};

const currentConfig = computed((): TierConfig | null => {
    if (!props.score) {
        return null;
    }

    return tierConfigs[props.score.tier];
});

const sizeClasses = computed(() => {
    const sizes = {
        sm: {
            badge: 'px-2 py-0.5 text-xs gap-1',
            icon: 'size-3',
            skeleton: 'h-5 w-20',
        },
        md: {
            badge: 'px-2.5 py-1 text-sm gap-1.5',
            icon: 'size-4',
            skeleton: 'h-6 w-24',
        },
        lg: {
            badge: 'px-3 py-1.5 text-base gap-2',
            icon: 'size-5',
            skeleton: 'h-8 w-28',
        },
    };

    return sizes[props.size];
});

const tooltipContent = computed((): string => {
    if (!props.score || !currentConfig.value) {
        return '';
    }

    const parts = [currentConfig.value.description];

    if (props.score.tier !== 'insufficient_data') {
        if (props.score.reportCount30d > 0) {
            const reportText =
                props.score.reportCount30d === 1 ? 'report' : 'reports';
            parts.push(`${props.score.reportCount30d} ${reportText} in the last 30 days`);
        }

        if (props.score.reportCount90d > props.score.reportCount30d) {
            const totalReportText =
                props.score.reportCount90d === 1 ? 'report' : 'reports';
            parts.push(`${props.score.reportCount90d} ${totalReportText} in the last 90 days`);
        }
    }

    return parts.join('. ');
});

const ariaLabel = computed((): string => {
    if (props.loading) {
        return 'Loading safety score';
    }

    if (!currentConfig.value) {
        return 'Safety score unavailable';
    }

    return `Safety level: ${currentConfig.value.label}`;
});
</script>

<template>
    <div
        role="status"
        :aria-label="ariaLabel"
        :aria-busy="loading"
    >
        <Skeleton
            v-if="loading"
            :class="['rounded-full', sizeClasses.skeleton]"
        />

        <TooltipProvider v-else-if="score && currentConfig">
            <Tooltip>
                <TooltipTrigger as-child>
                    <span
                        :class="[
                            'inline-flex items-center rounded-full border font-medium',
                            currentConfig.bgClass,
                            currentConfig.textClass,
                            currentConfig.borderClass,
                            sizeClasses.badge,
                        ]"
                    >
                        <component
                            :is="currentConfig.icon"
                            :class="sizeClasses.icon"
                            aria-hidden="true"
                        />
                        <span>{{ currentConfig.label }}</span>
                    </span>
                </TooltipTrigger>
                <TooltipContent side="top" :side-offset="4">
                    <p class="max-w-xs">{{ tooltipContent }}</p>
                </TooltipContent>
            </Tooltip>
        </TooltipProvider>
    </div>
</template>
