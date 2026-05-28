<script setup lang="ts">
import { computed } from 'vue';
import LeafletMap from '@/components/ui/LeafletMap.vue';
import type { Venue } from '@/types/venue';

interface Props {
    venues: Venue[];
    selectedVenue?: Venue | null;
    center?: [number, number];
    zoom?: number;
}

interface Emits {
    (e: 'venue-click', venue: Venue): void;
    (e: 'bounds-change', bounds: string): void;
    (e: 'zoom-change', zoom: number): void;
}

const props = withDefaults(defineProps<Props>(), {
    selectedVenue: null,
    center: () => [51.5074, -0.1278],
    zoom: 10,
});

const emit = defineEmits<Emits>();

// Only recenter map when a venue is explicitly selected
// Do NOT recenter based on venues array changes - this causes infinite loops
const mapCenter = computed<[number, number]>(() => {
    if (props.selectedVenue) {
        return [props.selectedVenue.latitude, props.selectedVenue.longitude];
    }

    // Use the provided center prop, don't auto-center on first venue
    return props.center;
});

const handleMarkerClick = (venue: Venue): void => {
    emit('venue-click', venue);
};

const handleBoundsChange = (bounds: string): void => {
    emit('bounds-change', bounds);
};

const handleZoomChange = (zoom: number): void => {
    emit('zoom-change', zoom);
};
</script>

<template>
    <div class="size-full">
        <LeafletMap
            :center="mapCenter"
            :zoom="zoom"
            :venues="venues"
            @marker-click="handleMarkerClick"
            @bounds-change="handleBoundsChange"
            @zoom-change="handleZoomChange"
        />
    </div>
</template>
