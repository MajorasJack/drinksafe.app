/**
 * useSearch Composable
 *
 * Provides debounced search functionality for venues.
 */

import { ref } from 'vue';
import type { Ref } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import { useVenueStore } from '@/stores/venueStore';

export function useSearch() {
    const store = useVenueStore();

    const query = ref('');
    const results = ref<typeof store.venues>([]);
    const isSearching = ref(false);

    const performSearch = async (searchQuery: string): Promise<void> => {
        if (!searchQuery.trim()) {
            results.value = [];
            return;
        }

        isSearching.value = true;

        try {
            await store.searchVenues(searchQuery);
            results.value = store.venues;
        } catch (err) {
            console.error('Search failed:', err);
        } finally {
            isSearching.value = false;
        }
    };

    const search = useDebounceFn(async (searchQuery: string) => {
        await performSearch(searchQuery);
    }, 300);

    return {
        query,
        results,
        isSearching,
        search,
    };
}
