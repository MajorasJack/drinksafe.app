/**
 * useVenues Composable
 *
 * Provides reactive access to venue store data and actions.
 */

import { computed } from 'vue';
import { useVenueStore } from '@/stores/venueStore';

export function useVenues() {
    const store = useVenueStore();

    return {
        // State
        venues: computed(() => store.venues),
        currentVenue: computed(() => store.currentVenue),
        loading: computed(() => store.loading),
        error: computed(() => store.error),

        // Getters
        hasVenues: computed(() => store.hasVenues),
        venuesByCity: store.venuesByCity,

        // Actions
        fetchVenues: store.fetchVenues,
        fetchVenueById: store.fetchVenueById,
        fetchVenuesByBounds: store.fetchVenuesByBounds,
        searchVenues: store.searchVenues,
        clearError: store.clearError,
    };
}
