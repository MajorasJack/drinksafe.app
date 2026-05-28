/**
 * useVenues Composable
 *
 * Provides reactive access to venue store data and actions.
 * Uses storeToRefs for efficient reactivity without double-wrapping.
 */

import { storeToRefs } from 'pinia';
import { useVenueStore } from '@/stores/venueStore';

export function useVenues() {
    const store = useVenueStore();

    // Use storeToRefs for reactive state - avoids double-wrapping computed overhead
    const {
        venues,
        searchResults,
        currentVenue,
        loading,
        searchLoading,
        error,
        hasVenues,
        hasSearchResults,
    } = storeToRefs(store);

    return {
        // State (already reactive via storeToRefs)
        venues,
        searchResults,
        currentVenue,
        loading,
        searchLoading,
        error,

        // Getters (already reactive via storeToRefs)
        hasVenues,
        hasSearchResults,
        venuesByCity: store.venuesByCity,

        // Actions (not reactive, just functions)
        fetchVenues: store.fetchVenues,
        fetchVenueById: store.fetchVenueById,
        fetchVenuesByBounds: store.fetchVenuesByBounds,
        fetchVenuesByBoundsDebounced: store.fetchVenuesByBoundsDebounced,
        searchVenues: store.searchVenues,
        clearSearchResults: store.clearSearchResults,
        clearError: store.clearError,
    };
}
