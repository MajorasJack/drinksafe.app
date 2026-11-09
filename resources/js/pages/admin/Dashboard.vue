<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminPartnerController from '@/actions/DrinkSafe/Partners/Controllers/AdminPartnerController';
import ReportModerationController from '@/actions/DrinkSafe/Reports/Controllers/ReportModerationController';
import AdminDashboardController from '@/actions/DrinkSafe/Shared/Controllers/AdminDashboardController';
import AdminVenueController from '@/actions/DrinkSafe/Venues/Controllers/AdminVenueController';
import Heading from '@/components/Heading.vue';
import TextLink from '@/components/TextLink.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes/admin';

type PartnerTotal = {
    code: string;
    label: string;
    destination: string;
    active: boolean;
    total: number;
};

type Summary = {
    pending_count: number;
    oldest_pending_at: string | null;
    received_per_day: Record<string, number>;
    published_30d: number;
    rejected_30d: number;
    top_partners: PartnerTotal[];
    orphaned_venues: number;
};

const props = defineProps<{
    summary: Summary;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});

const days = computed(() =>
    Object.entries(props.summary.received_per_day).map(([date, count]) => ({
        date,
        count,
    })),
);

const busiestDay = computed(() =>
    Math.max(1, ...days.value.map((day) => day.count)),
);

const receivedTotal = computed(() =>
    days.value.reduce((total, day) => total + day.count, 0),
);

const oldestPendingDays = computed(() =>
    props.summary.oldest_pending_at === null
        ? null
        : Math.floor(
              (Date.now() - Date.parse(props.summary.oldest_pending_at)) /
                  86_400_000,
          ),
);

const formatDay = (date: string): string =>
    new Date(`${date}T12:00:00Z`).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
    });

const plural = (count: number, word: string): string =>
    `${count} ${count === 1 ? word : `${word}s`}`;
</script>

<template>
    <Head title="Admin dashboard" />

    <div class="flex flex-col gap-8 p-4">
        <Heading
            title="Admin dashboard"
            description="The last 30 days, in UK dates."
        />

        <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="space-y-1 rounded-xl border p-4">
                <p class="text-sm text-muted-foreground">Waiting for review</p>
                <p class="text-3xl font-semibold" data-test="pending-count">
                    {{ summary.pending_count }}
                </p>
                <p
                    v-if="oldestPendingDays !== null"
                    class="text-sm text-muted-foreground"
                >
                    Oldest
                    {{
                        oldestPendingDays === 0
                            ? 'from today'
                            : `${plural(oldestPendingDays, 'day')} old`
                    }}
                </p>
                <TextLink
                    :href="ReportModerationController.index()"
                    class="text-sm"
                >
                    Go to the review queue
                </TextLink>
            </div>

            <div class="space-y-1 rounded-xl border p-4">
                <p class="text-sm text-muted-foreground">Published</p>
                <p class="text-3xl font-semibold">
                    {{ summary.published_30d }}
                </p>
            </div>

            <div class="space-y-1 rounded-xl border p-4">
                <p class="text-sm text-muted-foreground">
                    Rejected or taken down
                </p>
                <p class="text-3xl font-semibold">
                    {{ summary.rejected_30d }}
                </p>
            </div>

            <div class="space-y-1 rounded-xl border p-4">
                <p class="text-sm text-muted-foreground">
                    Unpublished venues with no pending report
                </p>
                <p class="text-3xl font-semibold">
                    {{ summary.orphaned_venues }}
                </p>
                <TextLink
                    :href="
                        AdminVenueController.index({
                            query: { state: 'orphaned' },
                        })
                    "
                    class="text-sm"
                >
                    See these venues
                </TextLink>
            </div>
        </section>

        <section class="space-y-4">
            <Heading
                variant="small"
                title="Reports received per day"
                :description="`${plural(receivedTotal, 'report')} in every status.`"
            />

            <ol
                class="flex h-40 items-end gap-0.5 rounded-xl border p-4"
                aria-label="Reports received per day"
            >
                <li
                    v-for="day in days"
                    :key="day.date"
                    class="flex h-full flex-1 items-end"
                    :title="`${formatDay(day.date)}: ${plural(day.count, 'report')}`"
                >
                    <span class="sr-only">
                        {{ formatDay(day.date) }}:
                        {{ plural(day.count, 'report') }}
                    </span>
                    <span
                        aria-hidden="true"
                        class="w-full rounded-t-sm bg-primary"
                        :class="day.count === 0 ? 'bg-muted' : ''"
                        :style="{
                            height: `${Math.max(2, (day.count / busiestDay) * 100)}%`,
                        }"
                    ></span>
                </li>
            </ol>
            <div
                class="flex justify-between text-xs text-muted-foreground"
                aria-hidden="true"
            >
                <span>{{ days.length > 0 ? formatDay(days[0].date) : '' }}</span>
                <span>Today</span>
            </div>
        </section>

        <section class="space-y-4">
            <Heading
                variant="small"
                title="Top partner codes"
                description="Scans over the last 30 days. Links that are switched off still count scans."
            />

            <p
                v-if="summary.top_partners.length === 0"
                class="text-sm text-muted-foreground"
            >
                There are no partner links yet.
            </p>

            <ul v-else class="divide-y rounded-xl border">
                <li
                    v-for="partner in summary.top_partners"
                    :key="partner.code"
                    class="flex flex-wrap items-center justify-between gap-2 p-4"
                >
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-medium">{{ partner.code }}</span>
                        <span class="text-sm text-muted-foreground">
                            {{ partner.label }}
                        </span>
                        <Badge v-if="!partner.active" variant="secondary">
                            Off
                        </Badge>
                    </div>
                    <span class="text-sm">
                        {{ plural(partner.total, 'scan') }}
                    </span>
                </li>
            </ul>

            <p class="text-sm">
                <TextLink :href="AdminPartnerController.index()">
                    See every partner link
                </TextLink>
            </p>
        </section>

        <section class="space-y-4">
            <Heading
                variant="small"
                title="Public caches"
                description="The heatmap and analytics rebuild their caches on the next request. Reviewing a report or editing a venue already does this, so you only need it if the public pages look out of date."
            />

            <Form
                v-bind="AdminDashboardController.refreshCaches.form()"
                :options="{ preserveScroll: true }"
                #default="{ processing }"
            >
                <Button type="submit" variant="outline" :disabled="processing">
                    Refresh public caches
                </Button>
            </Form>
        </section>
    </div>
</template>
