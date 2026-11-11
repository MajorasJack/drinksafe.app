<script setup lang="ts">
import { router, useHttp, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import ReportModerationController from '@/actions/DrinkSafe/Reports/Controllers/ReportModerationController';
import AdminVenueController from '@/actions/DrinkSafe/Venues/Controllers/AdminVenueController';
import VenueMiniMap from '@/components/admin/VenueMiniMap.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { QueuedReport } from '@/types/report';
import type { AdminVenueMatch } from '@/types/venue';

const props = defineProps<{ report: QueuedReport | null }>();

const emit = defineEmits<{ close: [] }>();

const search = useHttp({ q: '', city: '' });
const matches = ref<AdminVenueMatch[]>([]);
const hasSearched = ref(false);
const selectedVenueUuid = ref<string | null>(null);
const isMoving = ref(false);

const page = usePage();
const moveError = computed(
    () => (page.props.errors as Record<string, string> | undefined)?.venue_uuid,
);

const selectedVenue = computed(() =>
    matches.value.find((venue) => venue.uuid === selectedVenueUuid.value),
);

const findVenues = (): void => {
    search.get(AdminVenueController.lookup.url(), {
        onSuccess: (response): void => {
            matches.value = (response as { data: AdminVenueMatch[] }).data;
            hasSearched.value = true;
        },
    });
};

// start from the report's own name and city, which lists every branch of a chain
watch(
    () => props.report,
    (report) => {
        if (report === null) {
            return;
        }

        search.q = report.venue.name;
        search.city = report.venue.city;
        matches.value = [];
        hasSearched.value = false;
        selectedVenueUuid.value = null;
        findVenues();
    },
);

const moveReport = (): void => {
    if (props.report === null || selectedVenueUuid.value === null) {
        return;
    }

    router.patch(
        ReportModerationController.move.url(props.report.uuid),
        { venue_uuid: selectedVenueUuid.value },
        {
            preserveScroll: true,
            onStart: (): void => {
                isMoving.value = true;
            },
            onFinish: (): void => {
                isMoving.value = false;
            },
            onSuccess: (): void => emit('close'),
        },
    );
};
</script>

<template>
    <Dialog
        :open="report !== null"
        @update:open="(isOpen) => !isOpen && emit('close')"
    >
        <DialogContent v-if="report" class="max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>Move this report to another venue</DialogTitle>
                <DialogDescription>
                    It's on {{ report.venue.name }}, {{ report.venue.city }}.
                    Find the venue it belongs to, published or not.
                </DialogDescription>
            </DialogHeader>

            <form
                class="flex flex-wrap items-end gap-2"
                @submit.prevent="findVenues"
            >
                <div class="grid min-w-40 flex-1 gap-1.5">
                    <Label for="move-venue-name">Name contains</Label>
                    <Input id="move-venue-name" v-model="search.q" />
                </div>
                <div class="grid min-w-32 flex-1 gap-1.5">
                    <Label for="move-venue-city">City</Label>
                    <Input id="move-venue-city" v-model="search.city" />
                </div>
                <Button
                    type="submit"
                    variant="outline"
                    :disabled="search.processing"
                >
                    Search
                </Button>
            </form>

            <p
                v-if="hasSearched && matches.length === 0"
                class="text-sm text-muted-foreground"
            >
                No venues match.
            </p>

            <fieldset v-if="matches.length > 0" class="space-y-2">
                <legend class="sr-only">Venues</legend>
                <label
                    v-for="venue in matches"
                    :key="venue.uuid"
                    class="flex cursor-pointer items-start gap-3 rounded-lg border p-3 text-sm has-[:checked]:border-primary has-[:disabled]:cursor-not-allowed has-[:disabled]:opacity-60"
                >
                    <input
                        v-model="selectedVenueUuid"
                        type="radio"
                        name="venue_uuid"
                        class="mt-1"
                        :value="venue.uuid"
                        :disabled="venue.uuid === report.venue_uuid"
                    />
                    <span class="min-w-0 space-y-1">
                        <span class="flex flex-wrap items-center gap-2">
                            <span class="font-medium">
                                {{ venue.name }}, {{ venue.city }}
                            </span>
                            <Badge variant="outline">
                                {{
                                    venue.published_at
                                        ? 'Published'
                                        : 'Unpublished'
                                }}
                            </Badge>
                            <Badge
                                v-if="venue.uuid === report.venue_uuid"
                                variant="secondary"
                            >
                                Current venue
                            </Badge>
                        </span>
                        <span
                            v-if="venue.address"
                            class="block text-muted-foreground"
                        >
                            {{ venue.address }}
                        </span>
                        <span class="block text-muted-foreground">
                            {{ venue.latitude }}, {{ venue.longitude }}
                            <template v-if="venue.is_at_default_coordinates">
                                (default location)
                            </template>
                        </span>
                    </span>
                </label>
            </fieldset>

            <VenueMiniMap
                v-if="selectedVenue"
                :key="selectedVenue.uuid"
                :latitude="selectedVenue.latitude"
                :longitude="selectedVenue.longitude"
            />

            <InputError :message="moveError" />

            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <Button variant="outline">Cancel</Button>
                </DialogClose>
                <Button
                    :disabled="selectedVenueUuid === null || isMoving"
                    @click="moveReport"
                >
                    Move report
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
