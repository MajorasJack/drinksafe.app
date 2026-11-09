<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ReportModerationController from '@/actions/DrinkSafe/Reports/Controllers/ReportModerationController';
import ReviewConfirmationDialog from '@/components/admin/ReviewConfirmationDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatDateTime } from '@/lib/utils';
import { show as venuePage } from '@/routes/venues';
import { ReportStatus } from '@/types/report';
import type { QueuedReport, ReviewConfirmation } from '@/types/report';

const props = defineProps<{ report: QueuedReport }>();

const statusLabels: Record<ReportStatus, string> = {
    [ReportStatus.Pending]: 'Pending',
    [ReportStatus.Published]: 'Published',
    [ReportStatus.Rejected]: 'Rejected',
};

const statusBadges: Record<ReportStatus, 'secondary' | 'default' | 'destructive'> = {
    [ReportStatus.Pending]: 'secondary',
    [ReportStatus.Published]: 'default',
    [ReportStatus.Rejected]: 'destructive',
};

const venueLabel = computed(
    () => `${props.report.venue.name}, ${props.report.venue.city}`,
);

const confirmation = ref<ReviewConfirmation | null>(null);
const isSubmitting = ref(false);

const review = (status: ReportStatus, hideVenue: boolean): void =>
    router.patch(
        ReportModerationController.update.url(props.report.uuid),
        { status, hide_venue: hideVenue },
        {
            preserveScroll: true,
            onStart: (): void => {
                isSubmitting.value = true;
            },
            onFinish: (): void => {
                isSubmitting.value = false;
                confirmation.value = null;
            },
        },
    );

const confirmTakedown = (): void => {
    confirmation.value = {
        title: 'Take down this report?',
        description: props.report.would_empty_venue
            ? `It's the last published report about ${venueLabel.value}. Hide the venue from the site too?`
            : 'It comes off the site straight away.',
        confirmLabel: 'Take down',
        affectedVenues: props.report.would_empty_venue
            ? [{ uuid: props.report.venue_uuid, label: venueLabel.value }]
            : [],
        submit: (hideVenue) => review(ReportStatus.Rejected, hideVenue),
    };
};
</script>

<template>
    <li class="flex flex-wrap items-center justify-between gap-2 p-4">
        <div class="min-w-0 space-y-1">
            <Link
                v-if="report.venue.published_at"
                :href="venuePage.url(report.venue.slug)"
                class="font-medium underline-offset-4 hover:underline"
            >
                {{ venueLabel }}
            </Link>
            <p v-else class="font-medium">{{ venueLabel }}</p>
            <p class="truncate text-sm text-muted-foreground">
                {{ report.description }}
            </p>
            <p class="text-sm text-muted-foreground">
                Incident on {{ report.incident_date }}. Submitted
                {{ formatDateTime(report.created_at) }}.
                <template v-if="report.reviewed_at">
                    Reviewed {{ formatDateTime(report.reviewed_at) }}.
                </template>
            </p>
            <p
                v-if="
                    report.status === ReportStatus.Rejected &&
                    report.venue.unpublished_at
                "
                class="text-sm font-medium"
            >
                This venue is unpublished. Publish the venue first.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <Badge :variant="statusBadges[report.status]">
                {{ statusLabels[report.status] }}
            </Badge>
            <Button
                v-if="report.status === ReportStatus.Published"
                size="sm"
                variant="outline"
                @click="confirmTakedown"
            >
                Take down
            </Button>
            <Button
                v-else-if="report.status === ReportStatus.Rejected"
                size="sm"
                variant="outline"
                :disabled="isSubmitting || !!report.venue.unpublished_at"
                @click="review(ReportStatus.Published, false)"
            >
                Publish
            </Button>
        </div>

        <ReviewConfirmationDialog
            :confirmation="confirmation"
            :is-submitting="isSubmitting"
            @close="confirmation = null"
        />
    </li>
</template>
