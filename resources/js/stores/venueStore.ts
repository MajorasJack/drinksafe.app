/**
 * Venue Store
 *
 * Manages venue state and provides actions for fetching and searching venues.
 */

import { computed, ref } from 'vue';
import type { Ref } from 'vue';
import { defineStore } from 'pinia';
import axios from 'axios';
import type { Venue, VenueFilters } from '@/types';

export const useVenueStore = defineStore('venue', () => {
    // State
    const venues = ref<Venue[]>([]);
    const currentVenue = ref<Venue | null>(null);
    const loading = ref(false);
    const error = ref<string | null>(null);

    // Getters
    const hasVenues = computed(() => venues.value.length > 0);

    const venuesByCity = computed(() => (city: string) => {
        return venues.value.filter(
            (venue) => venue.city.toLowerCase() === city.toLowerCase(),
        );
    });

    // Actions
    async function fetchVenues(filters?: VenueFilters): Promise<void> {
        loading.value = true;
        error.value = null;

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

            const url = `/api/venues${params.toString() ? `?${params.toString()}` : ''}`;
            const response = await axios.get(url);

            venues.value = response.data.data;
        } catch (err) {
            error.value =
                err instanceof Error ? err.message : 'Failed to fetch venues';
            console.error('Failed to fetch venues:', err);
        } finally {
            loading.value = false;
        }
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

    async function searchVenues(
        query: string,
        filters?: VenueFilters,
    ): Promise<void> {
        await fetchVenues({ ...filters, search: query });
    }

    function clearError(): void {
        error.value = null;
    }

    return {
        // State
        venues,
        currentVenue,
        loading,
        error,

        // Getters
        hasVenues,
        venuesByCity,

        // Actions
        fetchVenues,
        fetchVenueById,
        searchVenues,
        clearError,
    };
});
