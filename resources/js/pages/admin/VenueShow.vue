<script setup lang="ts">
import { Form, Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminVenueController from '@/actions/DrinkSafe/Venues/Controllers/AdminVenueController';
import ReportDecisionRow from '@/components/admin/ReportDecisionRow.vue';
import ReviewConfirmationDialog from '@/components/admin/ReviewConfirmationDialog.vue';
import VenueMiniMap from '@/components/admin/VenueMiniMap.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { show as venuePage } from '@/routes/venues';
import type { QueuedReport, ReviewConfirmation } from '@/types/report';
import type { Venue } from '@/types/venue';

const props = defineProps<{
    venue: Venue;
    reports: QueuedReport[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Venues',
                href: AdminVenueController.index(),
            },
        ],
    },
});

const page = usePage();
const statusError = computed(
    () => (page.props.errors as Record<string, string> | undefined)?.status,
);

const confirmation = ref<ReviewConfirmation | null>(null);
const isSubmitting = ref(false);

const confirmUnpublish = (): void => {
    confirmation.value = {
        title: 'Unpublish this venue?',
        description:
            'The venue and all its reports come off the site straight away. Its reports keep their status, so publishing the venue again brings the published ones back.',
        confirmLabel: 'Unpublish the venue',
        affectedVenues: [],
        submit: (): void =>
            router.post(
                AdminVenueController.unpublish.url(props.venue.uuid),
                {},
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
            ),
    };
};
</script>

<template>
    <Head :title="venue.name" />

    <div class="flex flex-col gap-8 p-4">
        <Alert v-if="statusError" variant="destructive" role="alert">
            <AlertTitle>That didn't work</AlertTitle>
            <AlertDescription>{{ statusError }}</AlertDescription>
        </Alert>

        <section class="space-y-4">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <Heading
                    :title="`${venue.name}, ${venue.city}`"
                    :description="venue.address ?? 'No address given.'"
                />
                <div class="flex flex-wrap items-center gap-2">
                    <Badge v-if="venue.published_at">Published</Badge>
                    <Badge
                        v-else-if="venue.unpublished_at"
                        variant="destructive"
                    >
                        Unpublished
                    </Badge>
                    <Badge v-else variant="secondary">Never published</Badge>

                    <Button
                        v-if="venue.published_at"
                        variant="outline"
                        size="sm"
                        as-child
                    >
                        <Link :href="venuePage.url(venue.slug)">
                            View on the site
                        </Link>
                    </Button>
                    <Button
                        v-if="venue.published_at"
                        variant="destructive"
                        size="sm"
                        @click="confirmUnpublish"
                    >
                        Unpublish
                    </Button>
                    <Form
                        v-else
                        v-bind="AdminVenueController.publish.form(venue.uuid)"
                        :options="{ preserveScroll: true }"
                        #default="{ processing }"
                    >
                        <Button type="submit" size="sm" :disabled="processing">
                            Publish
                        </Button>
                    </Form>
                </div>
            </div>
            <p
                v-if="!venue.published_at"
                class="text-sm text-muted-foreground"
            >
                Publishing the venue doesn't publish its reports. Publish those
                one at a time below.
            </p>

            <div class="space-y-2">
                <VenueMiniMap
                    :key="`${venue.latitude},${venue.longitude}`"
                    :latitude="venue.latitude"
                    :longitude="venue.longitude"
                />
                <Alert v-if="venue.is_at_default_coordinates">
                    <AlertTitle>Location not given</AlertTitle>
                    <AlertDescription>
                        These are the default coordinates for a submission with
                        no location, in central London. Correct the location
                        below.
                    </AlertDescription>
                </Alert>
            </div>
        </section>

        <section class="space-y-4">
            <Heading
                variant="small"
                title="Edit venue"
                description="The web address stays the same, so existing links keep working."
            />

            <Form
                v-bind="AdminVenueController.update.form(venue.uuid)"
                :options="{ preserveScroll: true }"
                class="grid gap-4 rounded-xl border p-4 sm:grid-cols-2"
                #default="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input
                        id="name"
                        name="name"
                        :default-value="venue.name"
                        required
                    />
                    <InputError :message="errors.name" />
                </div>
                <div class="grid gap-2">
                    <Label for="city">City</Label>
                    <Input
                        id="city"
                        name="city"
                        :default-value="venue.city"
                        required
                    />
                    <InputError :message="errors.city" />
                </div>
                <div class="grid gap-2 sm:col-span-2">
                    <Label for="address">Address</Label>
                    <Input
                        id="address"
                        name="address"
                        :default-value="venue.address ?? ''"
                    />
                    <InputError :message="errors.address" />
                </div>
                <div class="grid gap-2">
                    <Label for="latitude">Latitude</Label>
                    <Input
                        id="latitude"
                        name="latitude"
                        type="number"
                        step="any"
                        :default-value="venue.latitude"
                        required
                    />
                    <InputError :message="errors.latitude" />
                </div>
                <div class="grid gap-2">
                    <Label for="longitude">Longitude</Label>
                    <Input
                        id="longitude"
                        name="longitude"
                        type="number"
                        step="any"
                        :default-value="venue.longitude"
                        required
                    />
                    <InputError :message="errors.longitude" />
                </div>
                <div class="sm:col-span-2">
                    <Button type="submit" :disabled="processing">
                        Save venue
                    </Button>
                </div>
            </Form>
        </section>

        <section class="space-y-4">
            <Heading
                variant="small"
                title="Reports"
                :description="`${reports.length} ${reports.length === 1 ? 'report' : 'reports'}, in any status, newest first.`"
            />

            <p
                v-if="reports.length === 0"
                class="text-sm text-muted-foreground"
            >
                No reports about this venue.
            </p>

            <ul v-else class="divide-y rounded-xl border">
                <ReportDecisionRow
                    v-for="report in reports"
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
    </div>
</template>
