<script setup lang="ts">
import type { HeatLayer, Icon, Map, Marker, MarkerClusterGroup } from 'leaflet';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { HeatmapControls } from '@/components/map';
import { useHeatmap } from '@/composables/useHeatmap';
import type { Venue } from '@/types/venue';

type LeafletModule = typeof import('leaflet');

interface Props {
    center?: [number, number];
    zoom?: number;
    venues?: Venue[];
    showHeatmapControls?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    center: () => [51.5074, -0.1278], // London
    zoom: 10,
    venues: () => [],
    showHeatmapControls: true,
});

interface Emits {
    (e: 'marker-click', venue: Venue): void;
    (e: 'bounds-change', bounds: string): void;
    (e: 'zoom-change', zoom: number): void;
}

const emit = defineEmits<Emits>();

// Heatmap composable
const {
    fetchHeatmapDataDebounced,
    isVisible: isHeatmapVisible,
    leafletHeatData,
} = useHeatmap();

const mapContainer = ref<HTMLDivElement | null>(null);
const isClient = ref(false);
const isLoadingMarkers = ref(false);

// Flag to prevent emitting bounds change during programmatic view updates
let isProgrammaticMove = false;

// These will be set after dynamic import
let L: LeafletModule | null = null;
let map: Map | null = null;
let markerClusterGroup: MarkerClusterGroup | null = null;
let markerIcon: Icon | null = null;
let heatLayer: HeatLayer | null = null;

/**
 * Default heatmap configuration for Leaflet.heat.
 * Gradient uses warm colours to indicate incident density.
 */
const heatmapOptions = {
    radius: 25,
    blur: 15,
    maxZoom: 17,
    minOpacity: 0.4,
    gradient: {
        0.0: '#3b82f6', // Blue - low
        0.25: '#22c55e', // Green
        0.5: '#eab308', // Yellow
        0.75: '#f97316', // Orange
        1.0: '#ef4444', // Red - high
    },
};

/**
 * Formats the current map bounds as a string for API consumption.
 * Format: "swLat,swLng,neLat,neLng"
 * Skips emission during programmatic view changes to prevent loops.
 */
const emitBoundsChange = (): void => {
    if (!map || isProgrammaticMove) return;

    const bounds = map.getBounds();
    const sw = bounds.getSouthWest();
    const ne = bounds.getNorthEast();
    const boundsString = `${sw.lat},${sw.lng},${ne.lat},${ne.lng}`;

    emit('bounds-change', boundsString);
    emit('zoom-change', map.getZoom());

    // Fetch heatmap data for new bounds (debounced)
    fetchHeatmapDataDebounced(boundsString);
};

/**
 * Updates or creates the heatmap layer with current data.
 */
const updateHeatLayer = async (): Promise<void> => {
    if (!map || !L) return;

    // Dynamically import leaflet.heat if not already loaded
    if (!heatLayer) {
        await import('leaflet.heat');
    }

    if (!isHeatmapVisible.value) {
        removeHeatLayer();
        return;
    }

    const data = leafletHeatData.value;

    if (heatLayer) {
        heatLayer.setLatLngs(data);
    } else {
        heatLayer = L.heatLayer(data, heatmapOptions);
        heatLayer.addTo(map);
    }
};

/**
 * Removes the heatmap layer from the map.
 */
const removeHeatLayer = (): void => {
    if (heatLayer && map) {
        map.removeLayer(heatLayer);
        heatLayer = null;
    }
};

/**
 * Creates popup content for a venue marker.
 * Called lazily only when popup is opened to avoid creating HTML for all markers upfront.
 */
const createPopupContent = (venue: Venue): string => `
    <div class="p-3">
        <div class="font-semibold text-lg mb-2 text-slate-900">${venue.name}</div>
        <div class="text-sm text-slate-600 mb-2">${venue.city}</div>
        ${venue.reports_count ? `<div class="mb-3 text-xs text-slate-500">${venue.reports_count} report(s)</div>` : ''}
        <a href="/venues/${venue.slug}"
           class="inline-flex items-center justify-center rounded-md bg-brand-teal px-4 py-2 text-sm font-semibold text-white hover:bg-brand-teal/90 transition-colors w-full shadow-sm"
           data-venue-uuid="${venue.uuid}"
           style="color: white !important;"
       >
            View Reports
        </a>
    </div>
`;

// Cache for venue data by marker - avoids storing large objects on markers
const venueDataCache = new Map<string, Venue>();

/**
 * Updates markers using MarkerClusterGroup for efficient rendering of large datasets.
 * Uses lazy popup binding - popup content is only created when clicked.
 * Uses requestAnimationFrame for non-blocking marker creation.
 */
const updateMarkers = (): void => {
    if (!L || !map || !props.venues || !markerClusterGroup) return;

    isLoadingMarkers.value = true;

    // Clear existing markers from cluster group
    markerClusterGroup.clearLayers();
    venueDataCache.clear();

    // Create markers array for batch adding
    const markers: Marker[] = [];
    const venues = props.venues;

    // Use a single loop without closure overhead for each venue
    for (let i = 0; i < venues.length; i++) {
        const venue = venues[i];

        if (!L || !markerIcon) continue;

        // Cache venue data for lazy popup creation
        venueDataCache.set(venue.uuid, venue);

        const marker = L.marker([venue.latitude, venue.longitude], {
            icon: markerIcon,
            interactive: true,
            riseOnHover: true,
        });

        // Store venue UUID on marker for lazy popup lookup
        (marker as Marker & { _venueUuid: string })._venueUuid = venue.uuid;

        // Lazy popup binding - content created only when popup is opened
        marker.bindPopup(() => {
            const cachedVenue = venueDataCache.get((marker as Marker & { _venueUuid: string })._venueUuid);

            return cachedVenue ? createPopupContent(cachedVenue) : '';
        }, {
            maxWidth: 280,
            className: 'venue-popup',
        });

        marker.on('click', () => {
            const clickedVenue = venueDataCache.get((marker as Marker & { _venueUuid: string })._venueUuid);

            if (clickedVenue) {
                emit('marker-click', clickedVenue);
            }
        });

        markers.push(marker);
    }

    // Add all markers to the cluster group at once
    // MarkerClusterGroup handles chunked loading internally
    markerClusterGroup.addLayers(markers);

    isLoadingMarkers.value = false;
};

onMounted(async () => {
    isClient.value = true;

    // Dynamic import of Leaflet and MarkerCluster - only runs on client
    const leaflet = await import('leaflet');
    await import('leaflet/dist/leaflet.css');

    // Import markercluster and its CSS
    await import('leaflet.markercluster');
    await import('leaflet.markercluster/dist/MarkerCluster.css');
    await import('leaflet.markercluster/dist/MarkerCluster.Default.css');

    L = leaflet.default;

    markerIcon = L.icon({
        iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
        iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
        shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41],
    });

    if (!mapContainer.value) return;

    map = L.map(mapContainer.value).setView(props.center, props.zoom);

    // Use CartoDB's Voyager tiles - faster CDN, cleaner design, better caching
    L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/attributions">CARTO</a>',
        subdomains: 'abcd',
        maxZoom: 20,
        // Reduce tile loading overhead
        updateWhenIdle: true, // Only load tiles when map stops moving
        updateWhenZooming: false, // Don't reload during zoom animation
        keepBuffer: 2, // Keep 2 tile buffer around viewport for smoother panning
    }).addTo(map);

    // Create MarkerClusterGroup with optimised options for large datasets
    markerClusterGroup = L.markerClusterGroup({
        chunkedLoading: true, // Prevents browser freeze during initial load
        chunkInterval: 200, // Process markers in chunks with 200ms intervals
        chunkDelay: 50, // Delay between chunks
        maxClusterRadius: 50, // Cluster markers within 50px of each other
        spiderfyOnMaxZoom: true, // Show individual markers when zoomed to max
        showCoverageOnHover: false, // Disable polygon coverage on hover for performance
        disableClusteringAtZoom: 16, // Show individual markers when zoomed in close
        removeOutsideVisibleBounds: true, // Optimisation: remove markers outside viewport
        animate: true, // Smooth animations when clustering/unclustering
        animateAddingMarkers: false, // Disable animation when adding markers for performance
        spiderfyDistanceMultiplier: 1.5, // Spread out spiderfied markers
        zoomToBoundsOnClick: true, // Zoom into cluster on click
    });

    if (map) {
        map.addLayer(markerClusterGroup);

        // Listen for map movement to emit bounds changes
        // Only use moveend - zoomend is redundant as zoom triggers moveend too
        map.on('moveend', emitBoundsChange);
    }

    // Initial markers and bounds
    updateMarkers();

    // Emit initial bounds after map is ready
    setTimeout(emitBoundsChange, 100);
});

onBeforeUnmount(() => {
    if (map) {
        map.off('moveend', emitBoundsChange);
    }

    if (markerClusterGroup) {
        markerClusterGroup.clearLayers();
    }

    // Clean up venue data cache to prevent memory leaks
    venueDataCache.clear();

    // Clean up heatmap layer
    removeHeatLayer();

    if (map) {
        map.remove();
        map = null;
    }

    markerClusterGroup = null;
    L = null;
});

// Watch for heatmap visibility changes
watch(isHeatmapVisible, () => {
    updateHeatLayer();
});

// Watch for heatmap data changes
watch(leafletHeatData, () => {
    if (isHeatmapVisible.value) {
        updateHeatLayer();
    }
});

// Track previous venue signature to avoid unnecessary marker updates
let previousVenueSignature = '';

// Watch for venue changes using shallow comparison via length and first/last venue IDs
// Only triggers updateMarkers if the computed signature actually changes
watch(
    () => {
        const venueList = props.venues;

        if (!venueList || venueList.length === 0) return '';

        // Include more IDs for better change detection without deep comparison
        const firstId = venueList[0]?.uuid ?? '';
        const lastId = venueList[venueList.length - 1]?.uuid ?? '';
        const midIdx = Math.floor(venueList.length / 2);
        const midId = venueList[midIdx]?.uuid ?? '';

        return `${venueList.length}-${firstId}-${midId}-${lastId}`;
    },
    (newSignature) => {
        // Only update if signature actually changed (prevents redundant updates)
        if (newSignature !== previousVenueSignature) {
            previousVenueSignature = newSignature;
            updateMarkers();
        }
    },
);

// Track previous center to avoid unnecessary map animations
let previousCenter: [number, number] | null = null;

watch(
    () => props.center,
    (newCenter) => {
        if (!map) return;

        // Compare by value, not reference - arrays are compared by reference by default
        // This prevents unnecessary map animations when the coordinates haven't changed
        if (
            previousCenter &&
            previousCenter[0] === newCenter[0] &&
            previousCenter[1] === newCenter[1]
        ) {
            return;
        }

        previousCenter = [...newCenter] as [number, number];

        // Set flag to prevent moveend from triggering a bounds change fetch
        isProgrammaticMove = true;

        // Use current zoom level to avoid resetting zoom when clicking markers
        const currentZoom = map.getZoom();
        map.setView(newCenter, currentZoom);

        // Reset flag after animation completes (use setTimeout to ensure moveend has fired)
        setTimeout(() => {
            isProgrammaticMove = false;
        }, 300);
    },
);
</script>

<template>
    <div class="relative size-full">
        <div
            v-if="isClient"
            ref="mapContainer"
            class="size-full [&_.leaflet-marker-icon]:cursor-pointer [&_.leaflet-popup-content-wrapper]:!bg-white [&_.leaflet-popup-tip]:!bg-white [&_.venue-popup_.leaflet-popup-content]:!p-0 [&_.venue-popup_.leaflet-popup-content-wrapper]:!rounded-lg [&_.venue-popup_.leaflet-popup-content-wrapper]:!shadow-xl [&_.marker-cluster]:!bg-brand-teal/20 [&_.marker-cluster]:!border-brand-teal/40 [&_.marker-cluster-small]:!bg-brand-teal/30 [&_.marker-cluster-medium]:!bg-brand-teal/50 [&_.marker-cluster-large]:!bg-brand-teal/70 [&_.marker-cluster_div]:!bg-brand-teal [&_.marker-cluster_div]:text-white [&_.marker-cluster_div]:font-semibold"
        />
        <div v-else class="flex size-full items-center justify-center bg-slate-100">
            <span class="text-slate-500">Loading map...</span>
        </div>

        <!-- Heatmap controls overlay -->
        <HeatmapControls v-if="isClient && showHeatmapControls" />

        <!-- Loading overlay for marker updates -->
        <div
            v-if="isLoadingMarkers && isClient"
            class="absolute inset-0 z-[1000] flex items-center justify-center bg-white/50 backdrop-blur-sm"
        >
            <div class="flex items-center gap-2 rounded-lg bg-white px-4 py-2 shadow-lg">
                <svg
                    class="size-5 animate-spin text-brand-teal"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                >
                    <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"
                    />
                    <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    />
                </svg>
                <span class="text-sm font-medium text-slate-700">Loading venues...</span>
            </div>
        </div>
    </div>
</template>
