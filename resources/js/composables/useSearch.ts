/**
 * useSearch Composable
 *
 * Provides debounced search functionality for venues.
 * Uses separate search results state to avoid mutating the main venue store.
 */

import { useDebounceFn } from '@vueuse/core';
import { computed, ref } from 'vue';
import { useVenueStore } from '@/stores/venueStore';
import type { Venue } from '@/types';

export function useSearch() {
    const store = useVenueStore();

    const query = ref('');
    const isSearching = computed(() => store.searchLoading);

    // Use the store's separate search results instead of main venues
    const results = computed(() => store.searchResults);

    const performSearch = async (searchQuery: string): Promise<Venue[]> => {
        if (!searchQuery.trim()) {
            store.clearSearchResults();

            return [];
        }

        return await store.searchVenues(searchQuery);
    };

    const search = useDebounceFn(async (searchQuery: string) => {
        await performSearch(searchQuery);
    }, 300);

    const clearResults = (): void => {
        store.clearSearchResults();
    };

    return {
        query,
        results,
        isSearching,
        search,
        clearResults,
    };
}
