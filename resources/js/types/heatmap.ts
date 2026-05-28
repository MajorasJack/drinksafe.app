/**
 * Heatmap-related TypeScript types
 *
 * Types for incident heatmap layer visualisation.
 */

/**
 * Time period options for heatmap filtering
 */
export type HeatmapPeriod = '7d' | '30d' | '90d' | 'all';

/**
 * A single point on the heatmap with location and intensity
 */
export interface HeatmapPoint {
    lat: number;
    lng: number;
    intensity: number;
}

/**
 * Bounding box coordinates for geographic filtering
 */
export interface HeatmapBounds {
    north: number;
    south: number;
    east: number;
    west: number;
}

/**
 * Filters for requesting heatmap data
 */
export interface HeatmapFilters {
    north: number;
    south: number;
    east: number;
    west: number;
    period: HeatmapPeriod;
}

/**
 * Response metadata from heatmap API
 */
export interface HeatmapMeta {
    total_reports: number;
    period: string;
    bounds: HeatmapBounds;
}

/**
 * Full response from the heatmap API endpoint
 */
export interface HeatmapResponse {
    data: HeatmapPoint[];
    meta: HeatmapMeta;
}

/**
 * Heatmap configuration options for Leaflet.heat
 */
export interface HeatmapConfig {
    radius?: number;
    blur?: number;
    maxZoom?: number;
    minOpacity?: number;
    gradient?: Record<number, string>;
}

/**
 * Parsed bounds string format: "swLat,swLng,neLat,neLng"
 */
export interface ParsedBounds {
    south: number;
    west: number;
    north: number;
    east: number;
}
