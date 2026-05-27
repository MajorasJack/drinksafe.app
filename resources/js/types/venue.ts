/**
 * Venue-related TypeScript types
 *
 * These types match the backend VenueResource structure.
 */

import type { Report } from './report';

/**
 * Venue interface matching backend VenueResource
 */
export interface Venue {
    uuid: string;
    slug: string;
    name: string;
    city: string;
    address: string | null;
    latitude: number;
    longitude: number;
    reports_count?: number;
    reports?: Report[];
    created_at: string;
}

/**
 * Venue filters for search and filtering
 */
export interface VenueFilters {
    city?: string;
    search?: string;
    latitude?: number;
    longitude?: number;
    radius?: number;
}

/**
 * Paginated venue collection
 */
export interface VenueCollection {
    data: Venue[];
    meta?: {
        total: number;
        per_page?: number;
        current_page?: number;
        last_page?: number;
    };
}
