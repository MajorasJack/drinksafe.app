<script setup lang="ts">
/**
 * HeatmapLayer Component
 *
 * Renders an incident heatmap overlay on a Leaflet map using leaflet.heat.
 * Integrates with the heatmap store for reactive data updates.
 */

import type { HeatLayer, Map as LeafletMap } from 'leaflet';
import type * as LeafletNamespace from 'leaflet';
import { onBeforeUnmount, watch } from 'vue';
import { useHeatmap } from '@/composables/useHeatmap';

interface Props {
    map: LeafletMap | null;
}

const props = defineProps<Props>();

const { leafletHeatData, isVisible, loading } = useHeatmap();

let heatLayer: HeatLayer | null = null;
let L: typeof LeafletNamespace | null = null;

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
 * Initialises or updates the heatmap layer with new data.
 */
const updateHeatLayer = async (): Promise<void> => {
    if (!props.map) {
        return;
    }

    // Dynamically import leaflet.heat
    if (!L) {
        L = await import('leaflet');
        await import('leaflet.heat');
    }

    if (!isVisible.value) {
        removeHeatLayer();

        return;
    }

    const data = leafletHeatData.value;

    if (heatLayer) {
        heatLayer.setLatLngs(data);
    } else {
        heatLayer = L.heatLayer(data, heatmapOptions);
        heatLayer.addTo(props.map);
    }
};

/**
 * Removes the heatmap layer from the map.
 */
const removeHeatLayer = (): void => {
    if (heatLayer && props.map) {
        props.map.removeLayer(heatLayer);
        heatLayer = null;
    }
};

// Watch for map changes
watch(
    () => props.map,
    (newMap) => {
        if (newMap && isVisible.value) {
            updateHeatLayer();
        }
    },
);

// Watch for visibility changes
watch(isVisible, (visible) => {
    if (visible) {
        updateHeatLayer();
    } else {
        removeHeatLayer();
    }
});

// Watch for data changes
watch(leafletHeatData, () => {
    if (isVisible.value) {
        updateHeatLayer();
    }
});

// Cleanup on unmount
onBeforeUnmount(() => {
    removeHeatLayer();
    L = null;
});

defineExpose({
    updateHeatLayer,
    removeHeatLayer,
});
</script>

<template>
    <!-- Loading indicator overlay when fetching heatmap data -->
    <div
        v-if="loading && isVisible"
        class="pointer-events-none absolute right-4 top-4 z-[1000]"
    >
        <div class="flex items-center gap-2 rounded-lg bg-white px-3 py-2 shadow-md dark:bg-gray-800">
            <svg
                class="size-4 animate-spin text-brand-teal"
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
            <span class="text-xs font-medium text-slate-600 dark:text-gray-300">
                Loading heatmap...
            </span>
        </div>
    </div>
</template>
