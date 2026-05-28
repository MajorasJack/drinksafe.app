/**
 * Type declarations for leaflet.heat library
 *
 * Provides TypeScript support for Leaflet heatmap layer functionality.
 */

declare module 'leaflet' {
    /**
     * Configuration options for heatmap layer
     */
    interface HeatLayerOptions {
        /**
         * The radius of each point on the heatmap (default: 25)
         */
        radius?: number;

        /**
         * The amount of blur to apply (default: 15)
         */
        blur?: number;

        /**
         * Maximum zoom level for heatmap rendering
         */
        maxZoom?: number;

        /**
         * Maximum point intensity (default: 1.0)
         */
        max?: number;

        /**
         * Minimum opacity of the heatmap (default: 0.05)
         */
        minOpacity?: number;

        /**
         * Custom gradient colours (keys: 0.0-1.0, values: CSS colour strings)
         */
        gradient?: Record<number, string>;
    }

    /**
     * Heatmap layer class extending L.Layer
     */
    interface HeatLayer extends Layer {
        /**
         * Sets the heatmap data points
         */
        setLatLngs(latlngs: Array<[number, number, number?]> | Array<LatLng>): this;

        /**
         * Adds a single point to the heatmap
         */
        addLatLng(latlng: [number, number, number?] | LatLng): this;

        /**
         * Sets the heatmap options
         */
        setOptions(options: HeatLayerOptions): this;

        /**
         * Redraws the heatmap
         */
        redraw(): this;
    }

    /**
     * Creates a new heatmap layer
     *
     * @param latlngs Array of points as [lat, lng, intensity?]
     * @param options Heatmap configuration options
     */
    function heatLayer(
        latlngs: Array<[number, number, number?]> | Array<LatLng>,
        options?: HeatLayerOptions,
    ): HeatLayer;
}

declare module 'leaflet.heat' {
    // Side-effect import that extends L
}
