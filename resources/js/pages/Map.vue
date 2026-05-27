<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Filter } from 'lucide-vue-next';
import { ref, computed, onMounted, watchEffect } from 'vue';
import AppLayout from '@/components/layout/AppLayout.vue';
import Button from '@/components/ui/button/Button.vue';
import DateFilter from '@/components/ui/DateFilter.vue';
import VenueMap from '@/components/venue/VenueMap.vue';
import VenueSearchInput from '@/components/venue/VenueSearchInput.vue';
import { useVenues } from '@/composables/useVenues';
import type { Venue } from '@/types/venue';

interface Props {
    venues?: Venue[];
    filters?: {
        search?: string | null;
        city?: string | null;
    };
}

const props = withDefaults(defineProps<Props>(), {
    venues: () => [],
    filters: () => ({}),
});

const { venues: storeVenues, loading, fetchVenues } = useVenues();
const selectedVenue = ref<Venue | null>(null);
const startDate = ref<string>('');
const endDate = ref<string>('');
const isMobileListOpen = ref(false);
const searchInputQuery = ref(props.filters?.search ?? '');

const filteredVenues = computed(() => {
    // Use store venues if fetched, otherwise use server-provided venues
    if (storeVenues.value && storeVenues.value.length > 0) {
        return storeVenues.value;
    }

    return props.venues;
});

// Debug venues
watchEffect(() => {
    console.log('Map.vue - venues from store:', storeVenues.value);
    console.log('Map.vue - venues from props:', props.venues);
    console.log('Map.vue - filteredVenues:', filteredVenues.value);
    console.log(
        'Map.vue - filteredVenues count:',
        filteredVenues.value?.length,
    );
});

const handleVenueSelect = (venue: Venue): void => {
    // Navigate to venue detail page to view reports
    router.visit(`/venues/${venue.slug}`);
};

const handleVenueClick = (venue: Venue): void => {
    // Just select the venue to show popup, don't auto-navigate
    selectedVenue.value = venue;
    isMobileListOpen.value = false;
};

const handleSearchClear = (): void => {
    selectedVenue.value = null;
};

const handleDateFilterApply = (): void => {
    console.log('Applying date filters:', startDate.value, endDate.value);
    fetchVenues();
};

const handleDateFilterClear = (): void => {
    startDate.value = '';
    endDate.value = '';
    fetchVenues();
};

const clearFilters = (): void => {
    searchInputQuery.value = '';
    startDate.value = '';
    endDate.value = '';
    selectedVenue.value = null;
    fetchVenues();
};

onMounted(() => {
    // Only fetch if no venues provided by server
    if (props.venues.length === 0) {
        fetchVenues();
    }
});
</script>

<template>
    <AppLayout>
        <div
            class="flex h-[calc(100vh-80px)] min-h-[600px] flex-col md:flex-row"
        >
            <!-- Sidebar / List View -->
            <div
                :class="[
                    'absolute top-0 left-0 z-10 flex h-full w-full flex-col border-r border-slate-200 bg-white md:relative md:w-96 lg:w-[400px] dark:border-gray-700 dark:bg-gray-900',
                    isMobileListOpen ? 'flex' : 'hidden md:flex',
                ]"
            >
                <!-- Filters Header -->
                <div
                    class="sticky top-0 z-20 border-b border-slate-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900"
                >
                    <div
                        class="mb-4 flex items-center justify-between md:hidden"
                    >
                        <h2 class="text-lg font-semibold dark:text-white">
                            Venues List
                        </h2>
                        <Button
                            variant="ghost"
                            @click="isMobileListOpen = false"
                        >
                            Show Map
                        </Button>
                    </div>

                    <div class="mb-3">
                        <VenueSearchInput
                            @select="handleVenueSelect"
                            @clear="handleSearchClear"
                        />
                    </div>

                    <DateFilter
                        v-model:start-date="startDate"
                        v-model:end-date="endDate"
                        @apply="handleDateFilterApply"
                        @clear="handleDateFilterClear"
                    />
                </div>

                <!-- Venues List -->
                <div
                    class="flex-grow overflow-y-auto bg-slate-50 p-4 dark:bg-gray-900"
                >
                    <div
                        v-if="loading"
                        class="py-10 text-center text-slate-500 dark:text-gray-400"
                    >
                        Loading venues...
                    </div>

                    <div
                        v-else-if="filteredVenues.length === 0"
                        class="py-10 text-center text-slate-500 dark:text-gray-400"
                    >
                        <p>No venues found matching your filters.</p>
                        <button
                            @click="clearFilters"
                            class="mt-2 font-medium text-brand-teal hover:underline"
                        >
                            Clear filters
                        </button>
                    </div>

                    <div v-else class="space-y-3">
                        <button
                            v-for="venue in filteredVenues"
                            :key="venue.uuid"
                            type="button"
                            class="block w-full rounded-xl border border-slate-200 bg-white p-4 text-left transition-all hover:border-brand-teal/40 hover:shadow-md dark:border-gray-700 dark:bg-gray-800 dark:hover:border-brand-teal/40"
                            :class="{
                                'border-brand-teal/60 shadow-md ring-2 ring-brand-teal/20':
                                    selectedVenue?.uuid === venue.uuid,
                            }"
                            @click="handleVenueSelect(venue)"
                        >
                            <h3
                                class="mb-1 font-semibold text-slate-900 dark:text-white"
                            >
                                {{ venue.name }}
                            </h3>
                            <div
                                class="mb-2 text-xs text-slate-500 dark:text-gray-400"
                            >
                                {{ venue.city }}
                                <span v-if="venue.address" class="ml-1">
                                    • {{ venue.address }}
                                </span>
                            </div>
                            <div
                                v-if="venue.reports_count"
                                class="bg-brand-amber/10 text-brand-amber-dark inline-block rounded px-2 py-0.5 text-xs font-semibold"
                            >
                                {{ venue.reports_count }} report{{
                                    venue.reports_count !== 1 ? 's' : ''
                                }}
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Map View -->
            <div
                :class="[
                    'relative flex-grow',
                    isMobileListOpen ? 'hidden md:block' : 'block',
                ]"
            >
                <Button
                    variant="default"
                    class="absolute bottom-6 left-1/2 z-[1000] flex -translate-x-1/2 items-center gap-2 rounded-full bg-brand-teal px-6 py-3 font-medium text-white shadow-lg md:hidden"
                    @click="isMobileListOpen = true"
                >
                    <Filter class="size-4" /> View
                    {{ filteredVenues.length }} Venues
                </Button>

                <VenueMap
                    :venues="filteredVenues"
                    :selected-venue="selectedVenue"
                    @venue-click="handleVenueClick"
                />
            </div>
        </div>
    </AppLayout>
</template>
