<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';
import AdminReportSearchController from '@/actions/DrinkSafe/Reports/Controllers/AdminReportSearchController';
import ReportDecisionRow from '@/components/admin/ReportDecisionRow.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { ReportStatus } from '@/types/report';
import type { QueuedReport } from '@/types/report';

type Filters = {
    venue?: string;
    city?: string;
    status?: ReportStatus;
    from?: string;
    to?: string;
    q?: string;
};

const props = defineProps<{
    reports: QueuedReport[];
    total: number;
    pagination: { current_page: number; last_page: number };
    filters: Filters;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Reports',
                href: AdminReportSearchController(),
            },
        ],
    },
});

const anyStatus = 'any';

const page = usePage();
const errors = computed(
    () => (page.props.errors as Record<string, string> | undefined) ?? {},
);

const form = reactive({
    venue: props.filters.venue ?? '',
    city: props.filters.city ?? '',
    status: props.filters.status ?? anyStatus,
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
    q: props.filters.q ?? '',
});

const search = (): void =>
    router.get(
        AdminReportSearchController.url(),
        Object.fromEntries(
            Object.entries(form).filter(
                ([, value]) => value !== '' && value !== anyStatus,
            ),
        ),
        { preserveState: true },
    );

const pageLink = (pageNumber: number) =>
    AdminReportSearchController({
        query: { ...props.filters, page: pageNumber },
    });
</script>

<template>
    <Head title="Reports" />

    <div class="flex flex-col gap-8 p-4">
        <Alert v-if="errors.status" variant="destructive" role="alert">
            <AlertTitle>That didn't work</AlertTitle>
            <AlertDescription>{{ errors.status }}</AlertDescription>
        </Alert>

        <section class="space-y-4">
            <Heading
                title="Reports"
                :description="`${total} ${total === 1 ? 'report matches' : 'reports match'}, in any status, newest first.`"
            />

            <form
                class="grid gap-4 rounded-xl border p-4 sm:grid-cols-2 lg:grid-cols-3"
                @submit.prevent="search"
            >
                <div class="grid gap-2">
                    <Label for="venue">Venue</Label>
                    <Input id="venue" v-model="form.venue" type="search" />
                    <InputError :message="errors.venue" />
                </div>
                <div class="grid gap-2">
                    <Label for="city">City</Label>
                    <Input id="city" v-model="form.city" />
                    <InputError :message="errors.city" />
                </div>
                <div class="grid gap-2">
                    <Label for="status">Status</Label>
                    <Select v-model="form.status">
                        <SelectTrigger id="status" class="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="anyStatus">
                                Any status
                            </SelectItem>
                            <SelectItem :value="ReportStatus.Pending">
                                Pending
                            </SelectItem>
                            <SelectItem :value="ReportStatus.Published">
                                Published
                            </SelectItem>
                            <SelectItem :value="ReportStatus.Rejected">
                                Rejected
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="grid gap-2">
                    <Label for="from">Incident from</Label>
                    <Input id="from" v-model="form.from" type="date" />
                    <InputError :message="errors.from" />
                </div>
                <div class="grid gap-2">
                    <Label for="to">Incident to</Label>
                    <Input id="to" v-model="form.to" type="date" />
                    <InputError :message="errors.to" />
                </div>
                <div class="grid gap-2">
                    <Label for="q">Description contains</Label>
                    <Input id="q" v-model="form.q" type="search" />
                    <InputError :message="errors.q" />
                </div>
                <div class="flex gap-2 sm:col-span-2 lg:col-span-3">
                    <Button type="submit">Search</Button>
                    <Button variant="outline" as-child>
                        <Link :href="AdminReportSearchController()">
                            Clear filters
                        </Link>
                    </Button>
                </div>
            </form>

            <p
                v-if="reports.length === 0"
                class="text-sm text-muted-foreground"
            >
                No reports match these filters.
            </p>

            <ul v-else class="divide-y rounded-xl border">
                <ReportDecisionRow
                    v-for="report in reports"
                    :key="report.uuid"
                    :report="report"
                />
            </ul>

            <nav
                v-if="pagination.last_page > 1"
                class="flex items-center justify-between gap-2"
                aria-label="Report pages"
            >
                <Button
                    v-if="pagination.current_page > 1"
                    variant="outline"
                    size="sm"
                    as-child
                >
                    <Link :href="pageLink(pagination.current_page - 1)">
                        Previous
                    </Link>
                </Button>
                <span v-else />
                <span class="text-sm text-muted-foreground">
                    Page {{ pagination.current_page }} of
                    {{ pagination.last_page }}
                </span>
                <Button
                    v-if="pagination.current_page < pagination.last_page"
                    variant="outline"
                    size="sm"
                    as-child
                >
                    <Link :href="pageLink(pagination.current_page + 1)">
                        Next
                    </Link>
                </Button>
                <span v-else />
            </nav>
        </section>
    </div>
</template>
