/**
 * Analytics TypeScript types
 *
 * Types for time-based analytics data structures.
 * Matches backend TemporalAnalyticsResource response.
 */

/**
 * Days of the week
 */
export type DayOfWeek =
    | 'Monday'
    | 'Tuesday'
    | 'Wednesday'
    | 'Thursday'
    | 'Friday'
    | 'Saturday'
    | 'Sunday';

/**
 * Time periods (matches backend TimeOfDay enum)
 */
export type TimeOfDay = 'Morning' | 'Afternoon' | 'Evening' | 'Night';

/**
 * Single cell in the temporal analytics grid
 */
export interface TemporalGridCell {
    day: DayOfWeek;
    time: TimeOfDay;
    count: number;
    percentage: number;
}

/**
 * Aggregated totals by day and time
 */
export interface AnalyticsTotals {
    byDay: Record<DayOfWeek, number>;
    byTime: Record<TimeOfDay, number>;
}

/**
 * Metadata about the analytics query
 */
export interface AnalyticsMeta {
    periodDays: number;
    totalReports: number;
    city: string | null;
}

/**
 * Complete temporal analytics response
 */
export interface TemporalAnalytics {
    grid: TemporalGridCell[];
    totals: AnalyticsTotals;
    insights: string[];
    meta: AnalyticsMeta;
}

/**
 * Filters for analytics queries
 */
export interface AnalyticsFilters {
    period: 30 | 90 | 365;
    city?: string;
}

/**
 * Raw API response structure (snake_case)
 */
export interface TemporalAnalyticsApiResponse {
    data: {
        grid: Array<{
            day: DayOfWeek;
            time: TimeOfDay;
            count: number;
            percentage: number;
        }>;
        totals: {
            by_day: Record<DayOfWeek, number>;
            by_time: Record<TimeOfDay, number>;
        };
        insights: string[];
        meta: {
            period_days: number;
            total_reports: number;
            city: string | null;
        };
    };
}

/**
 * Chart data point for bar/line charts
 */
export interface ChartDataPoint {
    label: string;
    value: number;
    percentage?: number;
}

/**
 * All days of the week in order
 */
export const DAYS_OF_WEEK: DayOfWeek[] = [
    'Monday',
    'Tuesday',
    'Wednesday',
    'Thursday',
    'Friday',
    'Saturday',
    'Sunday',
];

/**
 * All time periods in order
 */
export const TIMES_OF_DAY: TimeOfDay[] = [
    'Morning',
    'Afternoon',
    'Evening',
    'Night',
];

/**
 * Time period descriptions
 */
export const TIME_PERIOD_DESCRIPTIONS: Record<TimeOfDay, string> = {
    Morning: '06:00 - 12:00',
    Afternoon: '12:00 - 18:00',
    Evening: '18:00 - 22:00',
    Night: '22:00 - 06:00',
};
