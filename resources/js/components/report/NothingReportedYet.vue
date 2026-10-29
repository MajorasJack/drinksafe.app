<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ShieldCheck } from 'lucide-vue-next';
import { safetyTips } from '@/lib/safetyTips';
import { create } from '@/routes/reports';

withDefaults(defineProps<{ reportHref?: string }>(), {
    reportHref: () => create().url,
});
</script>

<template>
    <section
        data-testid="nothing-reported-yet"
        class="rounded-xl border border-slate-200 bg-white p-5 text-left dark:border-gray-700 dark:bg-gray-800"
    >
        <h3 class="mb-1 text-lg font-semibold text-slate-900 dark:text-white">
            Nothing reported here yet
        </h3>
        <p class="mb-4 text-sm text-slate-600 dark:text-gray-300">
            No one has shared a report here yet. That doesn't mean nothing has
            happened, so the tips below still apply.
        </p>

        <ol class="mb-5 space-y-2 text-sm text-slate-700 dark:text-gray-300">
            <li
                v-for="(tip, index) in safetyTips"
                :key="tip"
                class="flex gap-3"
            >
                <span
                    aria-hidden="true"
                    class="flex size-5 shrink-0 items-center justify-center rounded-full bg-brand-teal/10 text-xs font-medium text-brand-teal"
                >
                    {{ index + 1 }}
                </span>
                <span>{{ tip }}</span>
            </li>
        </ol>

        <p class="mb-3 text-sm text-slate-600 dark:text-gray-300">
            If something happened to you or a friend, you can report it
            anonymously. Every report is reviewed before it appears.
        </p>
        <Link
            :href="reportHref"
            class="inline-flex min-h-11 items-center gap-2 rounded-lg bg-brand-teal px-5 py-2 font-medium text-white transition-colors hover:bg-brand-teal/90"
        >
            <ShieldCheck class="size-4" /> Report it
        </Link>
    </section>
</template>
