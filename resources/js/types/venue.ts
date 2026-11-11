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
    /** Only sent to admins */
    published_at?: string | null;
    /** Only sent to admins: when an admin took the venue off the site */
    unpublished_at?: string | null;
    /** Only sent to admins: whether the venue sits on the default for submissions with no location */
    is_at_default_coordinates?: boolean;
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
    bounds?: string;
    limit?: number;
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

/**
 * Venue as the "Move to venue" search shows it, matching AdminVenueController::lookup
 */
export type AdminVenueMatch = Required<
    Pick<Venue, 'published_at' | 'unpublished_at' | 'is_at_default_coordinates'>
> &
    Venue;

/**
 * Venue as the admin's venue list shows it, matching AdminVenueController::index
 */
export type AdminVenue = AdminVenueMatch & {
    published_reports_count: number;
    pending_reports_count: number;
};
