<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import ReportModerationController from '@/actions/DrinkSafe/Reports/Controllers/ReportModerationController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { ReportStatus } from '@/types/report';
import type { PopulatedReport } from '@/types/report';

type Props = {
    pending_reports: PopulatedReport[];
    pending_count: number;
    reviewed_reports: PopulatedReport[];
};

defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Review reports',
                href: ReportModerationController.index(),
            },
        ],
    },
});

const formatDateTime = (isoDate: string | null): string =>
    isoDate === null
        ? ''
        : new Date(isoDate).toLocaleString('en-GB', {
              dateStyle: 'medium',
              timeStyle: 'short',
          });
</script>

<template>
    <Head title="Review reports" />

    <div class="flex flex-col gap-8 p-4">
        <section class="space-y-4">
            <Heading
                title="Waiting for review"
                :description="`${pending_count} pending. Nothing here is on the site until you publish it.`"
            />

            <p
                v-if="pending_reports.length === 0"
                class="text-sm text-muted-foreground"
            >
                No reports are waiting for review.
            </p>

            <Card v-for="report in pending_reports" :key="report.uuid">
                <CardHeader>
                    <CardTitle>
                        {{ report.venue.name }}, {{ report.venue.city }}
                    </CardTitle>
                    <CardDescription>
                        Incident on {{ report.incident_date }},
                        {{ report.incident_time ?? report.time_of_day }}.
                        Submitted {{ formatDateTime(report.created_at) }}.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <p class="whitespace-pre-line">{{ report.description }}</p>

                    <div class="flex flex-wrap gap-2">
                        <Form
                            v-bind="
                                ReportModerationController.update.form(
                                    report.uuid,
                                )
                            "
                            :options="{ preserveScroll: true }"
                            #default="{ processing }"
                        >
                            <input
                                type="hidden"
                                name="status"
                                :value="ReportStatus.Published"
                            />
                            <Button type="submit" :disabled="processing">
                                Publish
                            </Button>
                        </Form>

                        <Form
                            v-bind="
                                ReportModerationController.update.form(
                                    report.uuid,
                                )
                            "
                            :options="{ preserveScroll: true }"
                            #default="{ processing }"
                        >
                            <input
                                type="hidden"
                                name="status"
                                :value="ReportStatus.Rejected"
                            />
                            <Button
                                type="submit"
                                variant="destructive"
                                :disabled="processing"
                            >
                                Reject
                            </Button>
                        </Form>
                    </div>
                </CardContent>
            </Card>
        </section>

        <section class="space-y-4">
            <Heading
                variant="small"
                title="Recent decisions"
                description="The last 20 reports you published or rejected."
            />

            <p
                v-if="reviewed_reports.length === 0"
                class="text-sm text-muted-foreground"
            >
                No decisions yet.
            </p>

            <ul class="divide-y rounded-xl border">
                <li
                    v-for="report in reviewed_reports"
                    :key="report.uuid"
                    class="flex flex-wrap items-center justify-between gap-2 p-4"
                >
                    <div class="min-w-0">
                        <p class="font-medium">
                            {{ report.venue.name }}, {{ report.venue.city }}
                        </p>
                        <p class="truncate text-sm text-muted-foreground">
                            {{ report.description }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-muted-foreground">
                            {{ formatDateTime(report.reviewed_at) }}
                        </span>
                        <Badge
                            :variant="
                                report.status === ReportStatus.Published
                                    ? 'default'
                                    : 'destructive'
                            "
                        >
                            {{
                                report.status === ReportStatus.Published
                                    ? 'Published'
                                    : 'Rejected'
                            }}
                        </Badge>
                        <Form
                            v-if="report.status === ReportStatus.Published"
                            v-bind="
                                ReportModerationController.update.form(
                                    report.uuid,
                                )
                            "
                            :options="{ preserveScroll: true }"
                            #default="{ processing }"
                        >
                            <input
                                type="hidden"
                                name="status"
                                :value="ReportStatus.Rejected"
                            />
                            <Button
                                type="submit"
                                size="sm"
                                variant="outline"
                                :disabled="processing"
                            >
                                Take down
                            </Button>
                        </Form>
                    </div>
                </li>
            </ul>
        </section>
    </div>
</template>
