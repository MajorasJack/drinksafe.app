<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import type { Venue } from '@/types/venue';

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
}

const emit = defineEmits<Emits>();

const mapContainer = ref<HTMLDivElement | null>(null);
const isClient = ref(false);

// These will be set after dynamic import
let L: typeof import('leaflet').default | null = null;
let map: import('leaflet').Map | null = null;
let markers: import('leaflet').Marker[] = [];
let markerIcon: import('leaflet').Icon | null = null;

const updateMarkers = (): void => {
    if (!L || !map || !props.venues) return;

    // Clear existing markers
    markers.forEach((marker) => marker.remove());
    markers = [];

    // Add new markers
    props.venues.forEach((venue) => {
        if (!L || !map || !markerIcon) return;

        const marker = L.marker([venue.latitude, venue.longitude], {
            icon: markerIcon,
            interactive: true,
            riseOnHover: true,
        });

        const popupContent = `
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
        marker.bindPopup(popupContent, {
            maxWidth: 280,
            className: 'venue-popup',
        });

        marker.on('click', () => {
            emit('marker-click', venue);
        });

        marker.addTo(map);
        markers.push(marker);
    });
};

onMounted(async () => {
    isClient.value = true;

    // Dynamic import of Leaflet - only runs on client
    const leaflet = await import('leaflet');
    await import('leaflet/dist/leaflet.css');
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

    updateMarkers();
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
    <div class="size-full">
        <div
            v-if="isClient"
            ref="mapContainer"
            class="size-full [&_.leaflet-marker-icon]:cursor-pointer [&_.leaflet-popup-content-wrapper]:!bg-white [&_.leaflet-popup-tip]:!bg-white [&_.venue-popup_.leaflet-popup-content]:!p-0 [&_.venue-popup_.leaflet-popup-content-wrapper]:!rounded-lg [&_.venue-popup_.leaflet-popup-content-wrapper]:!shadow-xl"
        />
        <div v-else class="flex size-full items-center justify-center bg-slate-100">
            <span class="text-slate-500">Loading map...</span>
        </div>
    </div>
</template>
