<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import {
    Search,
    MapPin,
    ShieldCheck,
    Clock,
    ArrowRight,
} from 'lucide-vue-next';
import AppLayout from '@/components/layout/AppLayout.vue';
import DisclaimerBanner from '@/components/ui/DisclaimerBanner.vue';
import Button from '@/components/ui/button/Button.vue';
import type { Venue } from '@/types/venue';
import type { PopulatedReport } from '@/types/report';
import { formatDistanceToNow } from 'date-fns';

interface Props {
    stats: {
        total_venues: number;
        total_reports: number;
    };
    recent_reports: PopulatedReport[];
}

const props = defineProps<Props>();

const searchQuery = ref('');

const handleSearch = (e: Event): void => {
    e.preventDefault();
    if (searchQuery.value.trim()) {
        router.visit('/map', {
            data: { search: searchQuery.value },
        });
    }
};
</script>

<template>
    <AppLayout>
        <!-- Hero Section -->
        <section
            class="relative overflow-hidden bg-brand-teal py-16 text-white sm:py-24"
        >
            <div
                class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_var(--tw-gradient-stops))] from-white via-transparent to-transparent opacity-10"
            />
            <div class="relative z-10 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="max-w-3xl">
                    <h1
                        class="mb-6 text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl"
                    >
                        Community awareness for safer nights out.
                    </h1>
                    <p
                        class="mb-10 max-w-2xl text-lg leading-relaxed text-slate-200 sm:text-xl"
                    >
                        Drink Safe is an anonymous, informational platform to
                        share and view reports of venue spiking incidents. Stay
                        informed and help others make safer decisions.
                    </p>

                    <form
                        @submit="handleSearch"
                        class="relative mb-8 max-w-2xl"
                    >
                        <div class="relative flex items-center">
                            <Search class="absolute left-4 size-6 text-white" />
                            <input
                                v-model="searchQuery"
                                type="text"
                                name="search"
                                placeholder="Search by city, town, or venue name..."
                                class="focus:ring-brand-amber/30 w-full rounded-xl py-4 pr-32 pl-14 text-lg text-white shadow-lg focus:ring-4 focus:outline-none"
                            />

                            <Button
                                type="submit"
                                variant="default"
                                class="bg-brand-amber hover:bg-brand-amber-light absolute right-2 rounded-lg px-6 py-2 font-medium text-white transition-colors"
                            >
                                Search
                            </Button>
                        </div>
                    </form>

                    <div class="flex flex-wrap gap-4 text-sm font-medium">
                        <Link
                            href="/map"
                            class="flex items-center gap-2 rounded-lg bg-white/10 px-4 py-2 transition-colors hover:bg-white/20"
                        >
                            <MapPin class="size-4" /> Browse Map
                        </Link>
                        <Link
                            href="/submit-report"
                            class="flex items-center gap-2 rounded-lg bg-white/10 px-4 py-2 transition-colors hover:bg-white/20"
                        >
                            <ShieldCheck class="size-4" /> Share a Report
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <DisclaimerBanner class="mb-16" />

            <div class="grid grid-cols-1 gap-12 lg:grid-cols-3">
                <!-- Recent Reports -->
                <div class="lg:col-span-2">
                    <div class="mb-6 flex items-end justify-between">
                        <div>
                            <h2
                                class="mb-2 text-2xl font-bold text-slate-900 dark:text-white"
                            >
                                Recent Reports
                            </h2>
                            <p class="text-slate-600 dark:text-gray-300">
                                Latest anonymised submissions from the
                                community.
                            </p>
                        </div>
                        <Link
                            href="/map"
                            class="hover:text-brand-teal-light hidden items-center gap-1 font-medium text-brand-teal transition-colors sm:flex"
                        >
                            View all <ArrowRight class="size-4" />
                        </Link>
                    </div>

                    <div class="space-y-6">
                        <Link
                            v-for="report in recent_reports"
                            :key="report.uuid"
                            :href="`/venues/${report.venue?.slug}`"
                            class="group block rounded-xl border border-slate-200 bg-white p-5 transition-all hover:border-brand-teal/30 hover:shadow-md dark:border-gray-700 dark:bg-gray-800"
                        >
                            <div class="mb-3 flex items-start justify-between">
                                <div>
                                    <h3
                                        class="text-lg font-semibold text-slate-900 transition-colors group-hover:text-brand-teal dark:text-white"
                                    >
                                        {{ report.venue?.name }}
                                    </h3>
                                    <p
                                        class="mt-1 flex items-center gap-1 text-sm text-slate-500 dark:text-gray-400"
                                    >
                                        <MapPin class="size-3.5" />
                                        {{ report.venue?.city }}
                                    </p>
                                </div>
                                <span
                                    class="flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600 dark:bg-gray-700 dark:text-gray-300"
                                >
                                    <Clock class="size-3" />
                                    {{
                                        formatDistanceToNow(
                                            new Date(report.incident_date),
                                            {
                                                addSuffix: true,
                                            },
                                        )
                                    }}
                                </span>
                            </div>
                            <p
                                class="line-clamp-2 text-sm leading-relaxed text-slate-700 dark:text-gray-300"
                            >
                                "{{ report.description }}"
                            </p>
                        </Link>
                    </div>

                    <Link
                        href="/map"
                        class="mt-6 flex w-full items-center justify-center gap-2 rounded-xl bg-slate-100 py-3 font-medium text-slate-900 transition-colors hover:bg-slate-200 sm:hidden dark:bg-gray-800 dark:text-white dark:hover:bg-gray-700"
                    >
                        View all reports
                    </Link>
                </div>

                <!-- How it works -->
                <div>
                    <h2
                        class="mb-6 text-2xl font-bold text-slate-900 dark:text-white"
                    >
                        How it works
                    </h2>
                    <div
                        class="space-y-8 rounded-xl border border-slate-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"
                    >
                        <div class="flex gap-4">
                            <div
                                class="flex size-8 shrink-0 items-center justify-center rounded-full bg-brand-teal/10 font-bold text-brand-teal"
                            >
                                1
                            </div>
                            <div>
                                <h4
                                    class="mb-1 font-semibold text-slate-900 dark:text-white"
                                >
                                    Search or Browse
                                </h4>
                                <p
                                    class="text-sm text-slate-600 dark:text-gray-300"
                                >
                                    Look up venues or cities to see if there are
                                    any recent community reports.
                                </p>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div
                                class="flex size-8 shrink-0 items-center justify-center rounded-full bg-brand-teal/10 font-bold text-brand-teal"
                            >
                                2
                            </div>
                            <div>
                                <h4
                                    class="mb-1 font-semibold text-slate-900 dark:text-white"
                                >
                                    Stay Informed
                                </h4>
                                <p
                                    class="text-sm text-slate-600 dark:text-gray-300"
                                >
                                    Read anonymised accounts to understand
                                    potential risks at specific locations.
                                </p>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div
                                class="flex size-8 shrink-0 items-center justify-center rounded-full bg-brand-teal/10 font-bold text-brand-teal"
                            >
                                3
                            </div>
                            <div>
                                <h4
                                    class="mb-1 font-semibold text-slate-900 dark:text-white"
                                >
                                    Share Anonymously
                                </h4>
                                <p
                                    class="text-sm text-slate-600 dark:text-gray-300"
                                >
                                    If you've experienced an incident, share it
                                    to help protect others. No personal data is
                                    required.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        class="border-brand-amber/20 bg-brand-amber/10 mt-6 rounded-xl border p-5"
                    >
                        <h4
                            class="text-brand-amber-dark mb-2 flex items-center gap-2 font-semibold"
                        >
                            <ShieldCheck class="size-5" /> Need to report a
                            crime?
                        </h4>
                        <p class="text-brand-amber-dark/80 mb-3 text-sm">
                            This platform does not contact the police. If you
                            need authorities, please contact them directly.
                        </p>
                        <Link
                            href="/about#police"
                            class="text-brand-amber-dark hover:text-brand-amber text-sm font-medium underline transition-colors"
                        >
                            View police contact guide
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
