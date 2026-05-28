/**
 * Safety Score TypeScript types
 *
 * These types define the structure for venue safety scores
 * based on historical report data with time-decay weighting.
 */

/**
 * Safety tier classification based on score ranges
 *
 * - low: Few/no recent reports (Green)
 * - moderate: Some recent activity (Amber)
 * - high: Significant recent reports (Red)
 * - insufficient_data: New or rarely-visited venues (Grey)
 */
export type SafetyTier = 'low' | 'moderate' | 'high' | 'insufficient_data';

/**
 * Safety score response from the API
 * Matches backend VenueSafetyScoreResource structure
 */
export interface SafetyScore {
    venueUuid: string;
    score: number;
    tier: SafetyTier;
    reportCount30d: number;
    reportCount90d: number;
    lastUpdated: string;
}

/**
 * API response structure for safety score endpoint
 * GET /api/venues/{uuid}/safety-score
 */
export interface SafetyScoreResponse {
    venue_uuid: string;
    score: number;
    tier: SafetyTier;
    report_count_30d: number;
    report_count_90d: number;
    last_updated: string;
}

/**
 * Transform API response to frontend SafetyScore type
 */
export function transformSafetyScoreResponse(response: SafetyScoreResponse): SafetyScore {
    return {
        venueUuid: response.venue_uuid,
        score: response.score,
        tier: response.tier,
        reportCount30d: response.report_count_30d,
        reportCount90d: response.report_count_90d,
        lastUpdated: response.last_updated,
    };
}
