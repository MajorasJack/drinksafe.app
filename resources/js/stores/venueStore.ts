/**
 * Venue Store
 *
 * Manages venue state and provides actions for fetching and searching venues.
 * Includes debounced fetching for map movement events and request cancellation.
 */

import axios from 'axios';
import type {CancelTokenSource} from 'axios';
import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import type { Venue, VenueFilters } from '@/types';

const DEBOUNCE_DELAY_MS = 300;

export const useVenueStore = defineStore('venue', () => {
    // State - Map venues (from bounds)
    const venues = ref<Venue[]>([]);
    // State - Search results (separate from map venues)
    const searchResults = ref<Venue[]>([]);
    const currentVenue = ref<Venue | null>(null);
    const loading = ref(false);
    const searchLoading = ref(false);
    const error = ref<string | null>(null);

    // Debounce and cancellation for bounds fetching
    let debounceTimer: ReturnType<typeof setTimeout> | null = null;
    let cancelTokenSource: CancelTokenSource | null = null;
    let lastBounds: string | null = null;

    // Getters
    const hasVenues = computed(() => venues.value.length > 0);
    const hasSearchResults = computed(() => searchResults.value.length > 0);

    const venuesByCity = computed(() => (city: string) => {
        return venues.value.filter(
            (venue) => venue.city.toLowerCase() === city.toLowerCase(),
        );
    });

    /**
     * Cancels any pending API request.
     */
    function cancelPendingRequest(): void {
        if (cancelTokenSource) {
            cancelTokenSource.cancel('Request cancelled due to new request');
            cancelTokenSource = null;
        }
    }

    /**
     * Fetches venues with optional filters.
     * Does not use debouncing - use fetchVenuesByBoundsDebounced for map movements.
     */
    async function fetchVenues(filters?: VenueFilters): Promise<void> {
        cancelPendingRequest();

        loading.value = true;
        error.value = null;

        cancelTokenSource = axios.CancelToken.source();

        try {
            const params = new URLSearchParams();

            if (filters?.city) {
                params.append('city', filters.city);
            }

            if (filters?.search) {
                params.append('q', filters.search);
            }

            if (filters?.latitude && filters?.longitude) {
                params.append('lat', filters.latitude.toString());
                params.append('lng', filters.longitude.toString());
            }

            if (filters?.radius) {
                params.append('radius', filters.radius.toString());
            }

            if (filters?.bounds) {
                params.append('bounds', filters.bounds);
            }

            if (filters?.limit) {
                params.append('limit', filters.limit.toString());
            }

            const url = `/api/venues${params.toString() ? `?${params.toString()}` : ''}`;
            const response = await axios.get(url, {
                cancelToken: cancelTokenSource.token,
            });

            venues.value = response.data.data;
        } catch (err) {
            if (axios.isCancel(err)) {
                return;
            }

            error.value =
                err instanceof Error ? err.message : 'Failed to fetch venues';
            console.error('Failed to fetch venues:', err);
        } finally {
            loading.value = false;
        }
    }

    /**
     * Fetches venues by bounds without debouncing (immediate).
     * Limit of 100 provides good coverage without excessive data transfer.
     */
    async function fetchVenuesByBounds(bounds: string): Promise<void> {
        await fetchVenues({ bounds, limit: 100 });
    }

    /**
     * Fetches venues by bounds with debouncing to prevent excessive API calls.
     * Skips fetch if bounds haven't changed.
     */
    function fetchVenuesByBoundsDebounced(bounds: string): void {
        // Skip if bounds haven't changed
        if (bounds === lastBounds) {
            return;
        }

        // Clear any pending debounce timer
        if (debounceTimer) {
            clearTimeout(debounceTimer);
        }

        debounceTimer = setTimeout(() => {
            lastBounds = bounds;
            fetchVenues({ bounds, limit: 100 });
        }, DEBOUNCE_DELAY_MS);
    }

    async function fetchVenueById(uuid: string): Promise<void> {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios.get(`/api/venues/${uuid}`);
            currentVenue.value = response.data.data;
        } catch (err) {
            error.value =
                err instanceof Error ? err.message : 'Failed to fetch venue';
            console.error('Failed to fetch venue:', err);
        } finally {
            loading.value = false;
        }
    }

    /**
     * Searches venues and stores results separately from map venues.
     * Does not overwrite the main venues array.
     */
    async function searchVenues(
        query: string,
        filters?: VenueFilters,
    ): Promise<Venue[]> {
        cancelPendingRequest();

        searchLoading.value = true;
        error.value = null;

        cancelTokenSource = axios.CancelToken.source();

        try {
            const params = new URLSearchParams();

            if (query) {
                params.append('q', query);
            }

            if (filters?.city) {
                params.append('city', filters.city);
            }

            if (filters?.limit) {
                params.append('limit', filters.limit.toString());
            }

            const url = `/api/venues${params.toString() ? `?${params.toString()}` : ''}`;
            const response = await axios.get(url, {
                cancelToken: cancelTokenSource.token,
            });

            searchResults.value = response.data.data;

            return searchResults.value;
        } catch (err) {
            if (axios.isCancel(err)) {
                return [];
            }

            error.value =
                err instanceof Error ? err.message : 'Failed to search venues';
            console.error('Failed to search venues:', err);

            return [];
        } finally {
            searchLoading.value = false;
        }
    }

    /**
     * Clears search results.
     */
    function clearSearchResults(): void {
        searchResults.value = [];
    }

    function clearError(): void {
        error.value = null;
    }

    return {
        // State
        venues,
        searchResults,
        currentVenue,
        loading,
        searchLoading,
        error,

        // Getters
        hasVenues,
        hasSearchResults,
        venuesByCity,

        // Actions
        fetchVenues,
        fetchVenueById,
        fetchVenuesByBounds,
        fetchVenuesByBoundsDebounced,
        searchVenues,
        clearSearchResults,
        clearError,
    };
});
