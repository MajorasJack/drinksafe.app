<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';
import AdminVenueController from '@/actions/DrinkSafe/Venues/Controllers/AdminVenueController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
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
import type { AdminVenue } from '@/types/venue';

type VenueState = 'published' | 'unpublished' | 'orphaned';

type Filters = {
    q?: string;
    city?: string;
    state?: VenueState;
};

const props = defineProps<{
    venues: AdminVenue[];
    total: number;
    pagination: { current_page: number; last_page: number };
    filters: Filters;
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

const anyState = 'any';

const page = usePage();
const errors = computed(
    () => (page.props.errors as Record<string, string> | undefined) ?? {},
);

const form = reactive({
    q: props.filters.q ?? '',
    city: props.filters.city ?? '',
    state: props.filters.state ?? anyState,
});

const search = (): void =>
    router.get(
        AdminVenueController.index.url(),
        Object.fromEntries(
            Object.entries(form).filter(
                ([, value]) => value !== '' && value !== anyState,
            ),
        ),
        { preserveState: true },
    );

const pageLink = (pageNumber: number) =>
    AdminVenueController.index({
        query: { ...props.filters, page: pageNumber },
    });
</script>

<template>
    <Head title="Venues" />

    <div class="flex flex-col gap-8 p-4">
        <section class="space-y-4">
            <Heading
                title="Venues"
                :description="`${total} ${total === 1 ? 'venue matches' : 'venues match'}, published or not, by name.`"
            />

            <form
                class="grid gap-4 rounded-xl border p-4 sm:grid-cols-3"
                @submit.prevent="search"
            >
                <div class="grid gap-2">
                    <Label for="q">Name</Label>
                    <Input id="q" v-model="form.q" type="search" />
                    <InputError :message="errors.q" />
                </div>
                <div class="grid gap-2">
                    <Label for="city">City</Label>
                    <Input id="city" v-model="form.city" />
                    <InputError :message="errors.city" />
                </div>
                <div class="grid gap-2">
                    <Label for="state">Shown</Label>
                    <Select v-model="form.state">
                        <SelectTrigger id="state" class="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="anyState">
                                Published or not
                            </SelectItem>
                            <SelectItem value="published">Published</SelectItem>
                            <SelectItem value="unpublished">
                                Unpublished
                            </SelectItem>
                            <SelectItem value="orphaned">
                                Unpublished, nothing pending
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="errors.state" />
                </div>
                <div class="flex gap-2 sm:col-span-3">
                    <Button type="submit">Search</Button>
                    <Button variant="outline" as-child>
                        <Link :href="AdminVenueController.index()">
                            Clear filters
                        </Link>
                    </Button>
                </div>
            </form>

            <p
                v-if="venues.length === 0"
                class="text-sm text-muted-foreground"
            >
                No venues match these filters.
            </p>

            <ul v-else class="divide-y rounded-xl border">
                <li
                    v-for="venue in venues"
                    :key="venue.uuid"
                    class="flex flex-wrap items-center justify-between gap-2 p-4"
                >
                    <div class="min-w-0 space-y-1">
                        <Link
                            :href="AdminVenueController.show(venue.uuid)"
                            class="font-medium underline-offset-4 hover:underline"
                        >
                            {{ venue.name }}, {{ venue.city }}
                        </Link>
                        <p class="text-sm text-muted-foreground">
                            {{ venue.published_reports_count }} published,
                            {{ venue.pending_reports_count }} pending.
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <Badge
                            v-if="venue.is_at_default_coordinates"
                            variant="outline"
                        >
                            Default location
                        </Badge>
                        <Badge v-if="venue.published_at">Published</Badge>
                        <Badge
                            v-else-if="venue.unpublished_at"
                            variant="destructive"
                        >
                            Unpublished
                        </Badge>
                        <Badge v-else variant="secondary">Never published</Badge>
                    </div>
                </li>
            </ul>

            <nav
                v-if="pagination.last_page > 1"
                class="flex items-center justify-between gap-2"
                aria-label="Venue pages"
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
