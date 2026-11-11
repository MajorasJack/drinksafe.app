<script setup lang="ts">
import { Form, Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminReportSearchController from '@/actions/DrinkSafe/Reports/Controllers/AdminReportSearchController';
import ReportModerationController from '@/actions/DrinkSafe/Reports/Controllers/ReportModerationController';
import AdminVenueController from '@/actions/DrinkSafe/Venues/Controllers/AdminVenueController';
import MoveReportDialog from '@/components/admin/MoveReportDialog.vue';
import ReportDecisionRow from '@/components/admin/ReportDecisionRow.vue';
import ReviewConfirmationDialog from '@/components/admin/ReviewConfirmationDialog.vue';
import VenueMiniMap from '@/components/admin/VenueMiniMap.vue';
import Heading from '@/components/Heading.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { formatDateTime } from '@/lib/utils';
import { ReportStatus } from '@/types/report';
import type {
    AffectedVenue,
    QueuedReport,
    ReportFlag,
    ReviewConfirmation,
} from '@/types/report';

type Props = {
    pending_reports: QueuedReport[];
    pending_count: number;
    pending_pagination: { current_page: number; last_page: number };
    reviewed_reports: QueuedReport[];
};

const props = defineProps<Props>();

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

const flagLabels: Record<ReportFlag, string> = {
    personal_details_removed: 'Personal details removed',
    new_venue_several_pending: 'New venue, several pending',
    venue_report_spike: 'Many reports in 24 hours',
    default_coordinates: 'Default location',
};

const page = usePage();
const statusError = computed(
    () => (page.props.errors as Record<string, string> | undefined)?.status,
);

const selectedUuids = ref<string[]>([]);
const confirmation = ref<ReviewConfirmation | null>(null);
const isSubmitting = ref(false);
const movingReport = ref<QueuedReport | null>(null);

const selectedReports = computed(() =>
    props.pending_reports.filter((report) =>
        selectedUuids.value.includes(report.uuid),
    ),
);

const allSelected = computed<boolean | 'indeterminate'>(() => {
    if (selectedUuids.value.length === 0) {
        return false;
    }

    return selectedUuids.value.length === props.pending_reports.length
        ? true
        : 'indeterminate';
});

const venueLabel = (report: QueuedReport): string =>
    `${report.venue.name}, ${report.venue.city}`;

const setSelected = (uuid: string, isSelected: boolean): void => {
    selectedUuids.value = isSelected
        ? [...selectedUuids.value, uuid]
        : selectedUuids.value.filter((selectedUuid) => selectedUuid !== uuid);
};

const setAllSelected = (isSelected: boolean | 'indeterminate'): void => {
    selectedUuids.value =
        isSelected === true
            ? props.pending_reports.map((report) => report.uuid)
            : [];
};

const affectedVenuesOf = (reports: QueuedReport[]): AffectedVenue[] => [
    ...new Map(
        reports
            .filter((report) => report.would_empty_venue)
            .map((report) => [
                report.venue_uuid,
                { uuid: report.venue_uuid, label: venueLabel(report) },
            ]),
    ).values(),
];

const visitOptions = {
    preserveScroll: true,
    onStart: (): void => {
        isSubmitting.value = true;
    },
    onFinish: (): void => {
        isSubmitting.value = false;
        confirmation.value = null;
    },
};

const submitBulk = (status: ReportStatus, hideVenueUuids: string[]): void =>
    router.post(
        ReportModerationController.bulk.url(),
        {
            uuids: selectedUuids.value,
            status,
            hide_venues: hideVenueUuids,
        },
        {
            ...visitOptions,
            onSuccess: (): void => {
                selectedUuids.value = [];
            },
        },
    );

const confirmBulkReject = (): void => {
    const affectedVenues = affectedVenuesOf(selectedReports.value);

    confirmation.value = {
        title: `Reject ${selectedReports.value.length} selected reports?`,
        description:
            affectedVenues.length > 0
                ? 'These venues would have no published reports left. Hide them from the site too?'
                : 'They stay off the site.',
        confirmLabel: 'Reject',
        affectedVenues,
        submit: (hideVenues) =>
            submitBulk(
                ReportStatus.Rejected,
                hideVenues ? affectedVenues.map((venue) => venue.uuid) : [],
            ),
    };
};
</script>

<template>
    <Head title="Review reports" />

    <div class="flex flex-col gap-8 p-4">
        <Alert v-if="statusError" variant="destructive" role="alert">
            <AlertTitle>That didn't work</AlertTitle>
            <AlertDescription>{{ statusError }}</AlertDescription>
        </Alert>

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

            <div
                v-else
                class="flex flex-wrap items-center gap-4 rounded-xl border p-4"
            >
                <label class="flex items-center gap-2 text-sm">
                    <Checkbox
                        :model-value="allSelected"
                        @update:model-value="setAllSelected"
                    />
                    Select all on this page
                </label>
                <span class="text-sm text-muted-foreground">
                    {{ selectedUuids.length }} selected
                </span>
                <div class="flex flex-wrap gap-2">
                    <Button
                        size="sm"
                        :disabled="selectedUuids.length === 0 || isSubmitting"
                        @click="submitBulk(ReportStatus.Published, [])"
                    >
                        Publish selected
                    </Button>
                    <Button
                        size="sm"
                        variant="destructive"
                        :disabled="selectedUuids.length === 0 || isSubmitting"
                        @click="confirmBulkReject"
                    >
                        Reject selected
                    </Button>
                </div>
            </div>

            <Card v-for="report in pending_reports" :key="report.uuid">
                <CardHeader>
                    <div class="flex items-start gap-3">
                        <Checkbox
                            class="mt-1"
                            :model-value="selectedUuids.includes(report.uuid)"
                            :aria-label="`Select the report about ${venueLabel(report)}`"
                            @update:model-value="
                                setSelected(report.uuid, $event === true)
                            "
                        />
                        <div class="min-w-0 space-y-1.5">
                            <CardTitle>{{ venueLabel(report) }}</CardTitle>
                            <p
                                v-if="report.venue.address"
                                class="text-sm text-muted-foreground"
                            >
                                {{ report.venue.address }}
                            </p>
                            <p
                                v-if="report.venue.unpublished_at"
                                class="text-sm font-medium"
                            >
                                This venue is unpublished. Publish the venue
                                first. Publishing selected reports skips this
                                one.
                            </p>
                            <p
                                v-else-if="report.venue.published_at === null"
                                class="text-sm font-medium"
                            >
                                New venue: publishing this report also publishes
                                this venue's name and address.
                            </p>
                            <CardDescription>
                                Incident on {{ report.incident_date }},
                                {{
                                    report.incident_time ?? report.time_of_day
                                }}. Submitted
                                {{ formatDateTime(report.created_at) }}.
                            </CardDescription>
                            <div
                                v-if="report.flags?.length"
                                class="flex flex-wrap gap-1"
                            >
                                <Badge
                                    v-for="flag in report.flags"
                                    :key="flag"
                                    variant="outline"
                                >
                                    {{ flagLabels[flag] }}
                                </Badge>
                            </div>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="space-y-4">
                    <p class="whitespace-pre-line">{{ report.description }}</p>

                    <div class="space-y-2">
                        <VenueMiniMap
                            :latitude="report.venue.latitude"
                            :longitude="report.venue.longitude"
                        />
                        <p class="text-sm text-muted-foreground">
                            {{ report.venue.latitude }},
                            {{ report.venue.longitude }}
                        </p>
                        <Alert v-if="report.venue.is_at_default_coordinates">
                            <AlertTitle>Location not given</AlertTitle>
                            <AlertDescription>
                                These are the default coordinates for a
                                submission with no location, in central London.
                                Correct the venue's location before you publish.
                                <Link
                                    :href="
                                        AdminVenueController.show(
                                            report.venue_uuid,
                                        )
                                    "
                                    class="font-medium underline underline-offset-4"
                                >
                                    Edit venue
                                </Link>
                            </AlertDescription>
                        </Alert>
                    </div>

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
                            <Button
                                type="submit"
                                :disabled="
                                    processing || !!report.venue.unpublished_at
                                "
                            >
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

                        <Button
                            variant="outline"
                            @click="movingReport = report"
                        >
                            Move to venue
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <nav
                v-if="pending_pagination.last_page > 1"
                class="flex items-center justify-between gap-2"
                aria-label="Pending reports pages"
            >
                <Button
                    v-if="pending_pagination.current_page > 1"
                    variant="outline"
                    size="sm"
                    as-child
                >
                    <Link
                        :href="
                            ReportModerationController.index({
                                query: {
                                    page: pending_pagination.current_page - 1,
                                },
                            })
                        "
                    >
                        Previous
                    </Link>
                </Button>
                <span v-else />
                <span class="text-sm text-muted-foreground">
                    Page {{ pending_pagination.current_page }} of
                    {{ pending_pagination.last_page }}
                </span>
                <Button
                    v-if="
                        pending_pagination.current_page <
                        pending_pagination.last_page
                    "
                    variant="outline"
                    size="sm"
                    as-child
                >
                    <Link
                        :href="
                            ReportModerationController.index({
                                query: {
                                    page: pending_pagination.current_page + 1,
                                },
                            })
                        "
                    >
                        Next
                    </Link>
                </Button>
                <span v-else />
            </nav>
        </section>

        <section class="space-y-4">
            <header>
                <h2 class="mb-0.5 text-base font-medium">
                    <Link
                        :href="AdminReportSearchController()"
                        class="underline underline-offset-4"
                    >
                        Recent decisions
                    </Link>
                </h2>
                <p class="text-sm text-muted-foreground">
                    The reports you published or rejected most recently.
                </p>
            </header>

            <p
                v-if="reviewed_reports.length === 0"
                class="text-sm text-muted-foreground"
            >
                No decisions yet.
            </p>

            <ul class="divide-y rounded-xl border">
                <ReportDecisionRow
                    v-for="report in reviewed_reports"
                    :key="report.uuid"
                    :report="report"
                />
            </ul>
        </section>

        <ReviewConfirmationDialog
            :confirmation="confirmation"
            :is-submitting="isSubmitting"
            @close="confirmation = null"
        />

        <MoveReportDialog :report="movingReport" @close="movingReport = null" />
    </div>
</template>
