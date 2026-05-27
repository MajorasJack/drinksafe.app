/**
 * Report-related TypeScript types
 *
 * These types match the backend ReportResource and TimeOfDay enum.
 */

import type { Venue } from './venue';

/**
 * Time of day enum matching backend DrinkSafe\Reports\Enums\TimeOfDay
 */
export enum TimeOfDay {
    Morning = 'Morning',
    Afternoon = 'Afternoon',
    Evening = 'Evening',
    Night = 'Night',
    Unknown = 'Unknown',
}

/**
 * Report interface matching backend ReportResource
 */
export interface Report {
    uuid: string;
    venue_uuid: string;
    incident_date: string;
    time_of_day: TimeOfDay;
    description: string;
    formatted_date: string;
    venue?: Venue;
    created_at: string;
}

/**
 * Report filters for filtering and searching reports
 */
export interface ReportFilters {
    venue_uuid?: string;
    start_date?: string;
    end_date?: string;
    time_of_day?: TimeOfDay;
    limit?: number;
}

/**
 * Report submission data for creating new reports
 */
export interface ReportSubmitData {
    venue_uuid?: string;
    venue_name?: string;
    venue_city?: string;
    venue_address?: string;
    latitude?: number;
    longitude?: number;
    incident_date: string;
    time_of_day: TimeOfDay;
    description: string;
    'cf-turnstile-response': string;
}

/**
 * Report with venue relationship loaded
 */
export type PopulatedReport = Required<Pick<Report, 'venue'>> & Report;
