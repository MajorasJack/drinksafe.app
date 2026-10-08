<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Filter } from 'lucide-vue-next';
import { ref, computed, onMounted } from 'vue';
import AppLayout from '@/components/layout/AppLayout.vue';
import Button from '@/components/ui/button/Button.vue';
import DateFilter from '@/components/ui/DateFilter.vue';
import VenueMap from '@/components/venue/VenueMap.vue';
import VenueSearchInput from '@/components/venue/VenueSearchInput.vue';
import { useSearch } from '@/composables/useSearch';
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

const {
    venues: storeVenues,
    loading,
    fetchVenues,
    fetchVenuesByBoundsDebounced,
} = useVenues();
const {
    query: searchQuery,
    results: searchResults,
    isSearching,
    clearResults: clearSearchResults,
} = useSearch();
const selectedVenue = ref<Venue | null>(null);
const startDate = ref<string>('');
const endDate = ref<string>('');
const isMobileListOpen = ref(false);
const currentBounds = ref<string | null>(null);
const currentZoom = ref(10); // Default zoom level

// Minimum zoom level to show venue list (individual markers visible around zoom 12+)
const MIN_ZOOM_FOR_LIST = 12;
const isZoomedInEnough = computed(() => currentZoom.value >= MIN_ZOOM_FOR_LIST);

// Only show venues when searching OR zoomed in enough
const shouldShowVenueList = computed(
    () => searchQuery.value.trim() || isZoomedInEnough.value,
);

const filteredVenues = computed(() => {
    // When searching, show search results in sidebar
    if (searchQuery.value.trim() && searchResults.value.length > 0) {
        return searchResults.value;
    }

    // Otherwise show bounds-based venues
    const venuesFromStore = storeVenues.value;

    return venuesFromStore.length > 0 ? venuesFromStore : props.venues;
});

// Combined loading state for search and bounds fetching
const isLoading = computed(() => loading.value || isSearching.value);

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
    fetchVenues();
};

const handleDateFilterClear = (): void => {
    startDate.value = '';
    endDate.value = '';
    fetchVenues();
};

const clearFilters = (): void => {
    clearSearchResults();
    startDate.value = '';
    endDate.value = '';
    selectedVenue.value = null;

    if (currentBounds.value) {
        fetchVenuesByBoundsDebounced(currentBounds.value);
    } else {
        fetchVenues();
    }
};

const handleBoundsChange = (bounds: string): void => {
    currentBounds.value = bounds;
    fetchVenuesByBoundsDebounced(bounds);
};

const handleZoomChange = (zoom: number): void => {
    currentZoom.value = zoom;
};

onMounted(() => {
    // Venues will be loaded via bounds-change event from the map
    // No need to fetch all venues on mount - this is now viewport-based
});
</script>

<template>
    <AppLayout>
        <div
            class="relative flex min-h-[28rem] flex-1 flex-col md:min-h-0 md:flex-row"
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
                            v-model="searchQuery"
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
                    <!-- Zoom in prompt when not searching and zoomed out -->
                    <div
                        v-if="!shouldShowVenueList"
                        class="py-10 text-center text-slate-500 dark:text-gray-400"
                    >
                        <p class="mb-2">Zoom in to see venues in this area</p>
                        <p class="text-sm">
                            Or use the search above to find a specific venue
                        </p>
                    </div>

                    <div
                        v-else-if="isLoading"
                        class="py-10 text-center text-slate-500 dark:text-gray-400"
                    >
                        {{ isSearching ? 'Searching...' : 'Loading venues...' }}
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
                    class="absolute bottom-[calc(1.5rem+env(safe-area-inset-bottom))] left-1/2 z-[1000] flex min-h-12 -translate-x-1/2 items-center gap-2 rounded-full bg-brand-teal px-6 py-3 font-medium text-white shadow-lg md:hidden"
                    @click="isMobileListOpen = true"
                >
                    <Filter class="size-4" /> View
                    {{ filteredVenues.length }}
                    {{ filteredVenues.length === 1 ? 'Venue' : 'Venues' }}
                </Button>

                <VenueMap
                    :venues="filteredVenues"
                    :selected-venue="selectedVenue"
                    @venue-click="handleVenueClick"
                    @bounds-change="handleBoundsChange"
                    @zoom-change="handleZoomChange"
                />
            </div>
        </div>
    </AppLayout>
</template>
