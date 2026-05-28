<script setup lang="ts">
import { computed, watchEffect } from 'vue';
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
}

const props = withDefaults(defineProps<Props>(), {
    selectedVenue: null,
    center: () => [51.5074, -0.1278],
    zoom: 10,
});

const emit = defineEmits<Emits>();

const mapCenter = computed<[number, number]>(() => {
    if (props.selectedVenue) {
        return [props.selectedVenue.latitude, props.selectedVenue.longitude];
    }

    if (props.venues.length > 0) {
        return [props.venues[0].latitude, props.venues[0].longitude];
    }

    return props.center;
});

const handleMarkerClick = (venue: Venue): void => {
    emit('venue-click', venue);
};

const handleBoundsChange = (bounds: string): void => {
    emit('bounds-change', bounds);
};

// Debug venues
watchEffect(() => {
    console.log('VenueMap - Venues prop:', props.venues);
    console.log('VenueMap - Venues count:', props.venues?.length);
});
</script>

<template>
    <div class="size-full">
        <LeafletMap
            :center="mapCenter"
            :zoom="zoom"
            :venues="venues"
            @marker-click="handleMarkerClick"
            @bounds-change="handleBoundsChange"
        />
    </div>
</template>
