<script setup lang="ts">
import { ref, onMounted, watch, onBeforeUnmount } from 'vue';
import type { Map, Icon, Marker, MarkerClusterGroup } from 'leaflet';
import type {} from 'leaflet.markercluster';
import type { Venue } from '@/types/venue';

type LeafletModule = typeof import('leaflet');

interface Props {
    center?: [number, number];
    zoom?: number;
    venues?: Venue[];
}

const props = withDefaults(defineProps<Props>(), {
    center: () => [51.5074, -0.1278], // London
    zoom: 10,
    venues: () => [],
});

interface Emits {
    (e: 'marker-click', venue: Venue): void;
    (e: 'bounds-change', bounds: string): void;
}

const emit = defineEmits<Emits>();

const mapContainer = ref<HTMLDivElement | null>(null);
const isClient = ref(false);
const isLoadingMarkers = ref(false);

// These will be set after dynamic import
let L: LeafletModule | null = null;
let map: Map | null = null;
let markerClusterGroup: MarkerClusterGroup | null = null;
let markerIcon: Icon | null = null;

/**
 * Formats the current map bounds as a string for API consumption.
 * Format: "swLat,swLng,neLat,neLng"
 */
const emitBoundsChange = (): void => {
    if (!map) return;

    const bounds = map.getBounds();
    const sw = bounds.getSouthWest();
    const ne = bounds.getNorthEast();
    const boundsString = `${sw.lat},${sw.lng},${ne.lat},${ne.lng}`;

    emit('bounds-change', boundsString);
};

/**
 * Creates popup content for a venue marker.
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

/**
 * Updates markers using MarkerClusterGroup for efficient rendering of large datasets.
 * Uses chunked loading to prevent browser freeze with thousands of markers.
 */
const updateMarkers = (): void => {
    if (!L || !map || !props.venues || !markerClusterGroup) return;

    isLoadingMarkers.value = true;

    // Clear existing markers from cluster group
    markerClusterGroup.clearLayers();

    // Create markers array for batch adding
    const markers: Marker[] = [];

    props.venues.forEach((venue) => {
        if (!L || !markerIcon) return;

        const marker = L.marker([venue.latitude, venue.longitude], {
            icon: markerIcon,
            interactive: true,
            riseOnHover: true,
        });

        marker.bindPopup(createPopupContent(venue), {
            maxWidth: 280,
            className: 'venue-popup',
        });

        marker.on('click', () => {
            emit('marker-click', venue);
        });

        markers.push(marker);
    });

    // Add all markers to the cluster group at once
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

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
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
        map.on('moveend', emitBoundsChange);
        map.on('zoomend', emitBoundsChange);
    }

    // Initial markers and bounds
    updateMarkers();

    // Emit initial bounds after map is ready
    setTimeout(emitBoundsChange, 100);
});

onBeforeUnmount(() => {
    if (map) {
        map.off('moveend', emitBoundsChange);
        map.off('zoomend', emitBoundsChange);
    }

    if (markerClusterGroup) {
        markerClusterGroup.clearLayers();
    }

    if (map) {
        map.remove();
        map = null;
    }

    markerClusterGroup = null;
    L = null;
});

watch(() => props.venues, updateMarkers, { deep: true });

watch(
    () => props.center,
    (newCenter) => {
        if (map) {
            map.setView(newCenter, props.zoom);
        }
    },
);
</script>

<template>
    <div class="size-full relative">
        <div
            v-if="isClient"
            ref="mapContainer"
            class="size-full [&_.leaflet-marker-icon]:cursor-pointer [&_.leaflet-popup-content-wrapper]:!bg-white [&_.leaflet-popup-tip]:!bg-white [&_.venue-popup_.leaflet-popup-content]:!p-0 [&_.venue-popup_.leaflet-popup-content-wrapper]:!rounded-lg [&_.venue-popup_.leaflet-popup-content-wrapper]:!shadow-xl [&_.marker-cluster]:!bg-brand-teal/20 [&_.marker-cluster]:!border-brand-teal/40 [&_.marker-cluster-small]:!bg-brand-teal/30 [&_.marker-cluster-medium]:!bg-brand-teal/50 [&_.marker-cluster-large]:!bg-brand-teal/70 [&_.marker-cluster_div]:!bg-brand-teal [&_.marker-cluster_div]:text-white [&_.marker-cluster_div]:font-semibold"
        />
        <div v-else class="flex size-full items-center justify-center bg-slate-100">
            <span class="text-slate-500">Loading map...</span>
        </div>
        <!-- Loading overlay for marker updates -->
        <div
            v-if="isLoadingMarkers && isClient"
            class="absolute inset-0 flex items-center justify-center bg-white/50 backdrop-blur-sm z-[1000]"
        >
            <div class="flex items-center gap-2 rounded-lg bg-white px-4 py-2 shadow-lg">
                <svg
                    class="h-5 w-5 animate-spin text-brand-teal"
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
