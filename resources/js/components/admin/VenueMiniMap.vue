<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import type { Map } from 'leaflet';
import markerIconRetinaUrl from 'leaflet/dist/images/marker-icon-2x.png';
import markerIconUrl from 'leaflet/dist/images/marker-icon.png';
import markerShadowUrl from 'leaflet/dist/images/marker-shadow.png';
import { onBeforeUnmount, onMounted, useTemplateRef } from 'vue';

const props = defineProps<{
    latitude: number;
    longitude: number;
}>();

const mapContainer = useTemplateRef<HTMLDivElement>('mapContainer');

// the same CARTO tiles as LeafletMap, so the admin area adds no other host
const cartoApiKey = usePage().props.carto?.apiKey ?? '';
const cartoTileUrl = `https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png${cartoApiKey ? `?key=${encodeURIComponent(cartoApiKey)}` : ''}`;

let map: Map | null = null;
let isUnmounted = false;

onMounted(async (): Promise<void> => {
    const { default: L } = await import('leaflet');
    await import('leaflet/dist/leaflet.css');

    // the card can go before leaflet finishes loading, for example on the next page of the queue
    if (isUnmounted || !mapContainer.value) {
        return;
    }

    const position: [number, number] = [props.latitude, props.longitude];

    map = L.map(mapContainer.value, {
        dragging: false,
        touchZoom: false,
        scrollWheelZoom: false,
        doubleClickZoom: false,
        boxZoom: false,
        keyboard: false,
        zoomControl: false,
    }).setView(position, 15);

    L.tileLayer(cartoTileUrl, {
        attribution:
            '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/attributions">CARTO</a>',
        subdomains: 'abcd',
        maxZoom: 20,
    }).addTo(map);

    L.marker(position, {
        interactive: false,
        keyboard: false,
        icon: L.icon({
            iconUrl: markerIconUrl,
            iconRetinaUrl: markerIconRetinaUrl,
            shadowUrl: markerShadowUrl,
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            shadowSize: [41, 41],
        }),
    }).addTo(map);
});

onBeforeUnmount((): void => {
    isUnmounted = true;
    map?.remove();
    map = null;
});
</script>

<template>
    <div
        ref="mapContainer"
        class="z-0 h-40 w-full overflow-hidden rounded-md border"
        role="img"
        :aria-label="`Map of the venue at ${latitude}, ${longitude}`"
        data-test="venue-mini-map"
    />
</template>
