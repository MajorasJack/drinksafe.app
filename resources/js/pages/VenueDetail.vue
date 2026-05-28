<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { MapPin, AlertTriangle, ChevronRight } from 'lucide-vue-next';
import { computed, onMounted } from 'vue';
import AppLayout from '@/components/layout/AppLayout.vue';
import ReportList from '@/components/report/ReportList.vue';
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import LeafletMap from '@/components/ui/LeafletMap.vue';
import SafetyBadge from '@/components/venue/SafetyBadge.vue';
import { useSafetyScore } from '@/composables/useSafetyScore';
import type { Venue } from '@/types/venue';

interface Props {
    venue: Venue;
}

const props = defineProps<Props>();

const { score, loading: safetyLoading, fetchScore } = useSafetyScore();

const mapCenter = computed((): [number, number] => {
    return [props.venue.latitude, props.venue.longitude];
});

const venueReports = computed(() => {
    return props.venue.reports || [];
});

const hasReports = computed((): boolean => {
    return venueReports.value.length > 0;
});

onMounted(() => {
    fetchScore(props.venue.uuid);
});
</script>

<template>
    <AppLayout>
        <div class="container mx-auto max-w-7xl px-4 pt-4 pb-8">
            <Breadcrumb class="mb-4">
                <BreadcrumbList>
                    <BreadcrumbItem>
                        <BreadcrumbLink as-child>
                            <Link href="/">Home</Link>
                        </BreadcrumbLink>
                    </BreadcrumbItem>
                    <BreadcrumbSeparator>
                        <ChevronRight class="size-4" />
                    </BreadcrumbSeparator>
                    <BreadcrumbItem>
                        <BreadcrumbLink as-child>
                            <Link href="/map">Map</Link>
                        </BreadcrumbLink>
                    </BreadcrumbItem>
                    <BreadcrumbSeparator>
                        <ChevronRight class="size-4" />
                    </BreadcrumbSeparator>
                    <BreadcrumbItem>
                        <BreadcrumbPage>{{ venue.name }}</BreadcrumbPage>
                    </BreadcrumbItem>
                </BreadcrumbList>
            </Breadcrumb>

            <div class="grid gap-8 lg:grid-cols-2">
                <div class="space-y-6">
                    <div class="space-y-2">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <h1
                                class="text-4xl font-bold text-slate-900 dark:text-white"
                            >
                                {{ venue.name }}
                            </h1>
                            <SafetyBadge
                                :score="score"
                                :loading="safetyLoading"
                                size="lg"
                            />
                        </div>
                        <div
                            class="flex items-center gap-2 text-slate-600 dark:text-gray-100"
                        >
                            <MapPin class="size-5" />
                            <span class="text-lg">{{ venue.city }}</span>
                        </div>
                        <p
                            v-if="venue.address"
                            class="text-slate-600 dark:text-gray-100"
                        >
                            {{ venue.address }}
                        </p>
                    </div>

                    <Card
                        v-if="hasReports"
                        class="border-amber-200 bg-amber-50 dark:border-amber-700 dark:bg-amber-900/30"
                    >
                        <CardContent class="py-1">
                            <div class="flex items-start gap-3">
                                <AlertTriangle
                                    class="mt-0.5 size-5 shrink-0 text-amber-600 dark:text-amber-400"
                                />
                                <div>
                                    <p
                                        class="font-semibold text-amber-900 dark:text-white"
                                    >
                                        {{ venue.reports_count }}
                                        {{
                                            venue.reports_count === 1
                                                ? 'report'
                                                : 'reports'
                                        }}
                                        at this venue
                                    </p>
                                    <p
                                        class="text-sm text-amber-800 dark:text-amber-50"
                                    >
                                        Review the reports below for more
                                        information about incidents at this
                                        location.
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Location</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="h-64 overflow-hidden rounded-lg">
                                <LeafletMap
                                    :center="mapCenter"
                                    :zoom="15"
                                    :venues="[venue]"
                                />
                            </div>
                            <div
                                class="mt-4 space-y-1 text-sm text-slate-600 dark:text-gray-100"
                            >
                                <p>
                                    <strong>Coordinates:</strong>
                                    {{ venue.latitude.toFixed(6) }},
                                    {{ venue.longitude.toFixed(6) }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="bg-brand-teal text-white">
                        <CardContent class="py-1">
                            <div class="space-y-3">
                                <div>
                                    <h3 class="mb-1.5 text-lg font-semibold">
                                        Experienced something here?
                                    </h3>
                                    <p class="text-teal-50">
                                        Help others stay safe by submitting an
                                        anonymous report about this venue.
                                    </p>
                                </div>
                                <Button
                                    as-child
                                    variant="secondary"
                                    class="w-full"
                                >
                                    <Link :href="`/report?venue=${venue.uuid}`">
                                        Submit Report
                                    </Link>
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div>
                    <ReportList :reports="venueReports" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
