/**
 * useMap Composable
 *
 * Provides Leaflet map functionality for displaying venues.
 */

import L from 'leaflet';
import { ref } from 'vue';
import type { Venue } from '@/types';

export function useMap() {
    const map = ref<L.Map | null>(null);
    const markers = ref<L.Marker[]>([]);

    function initMap(
        elementId: string,
        center: [number, number],
        zoom: number,
    ): void {
        if (map.value) {
            return;
        }

        map.value = L.map(elementId).setView(center, zoom);

        const tileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution:
                '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19,
        });

        if (map.value) {
            tileLayer.addTo(map.value as L.Map);
        }
    }

    function addMarker(venue: Venue): L.Marker {
        if (!map.value) {
            throw new Error('Map not initialised');
        }

        const marker = L.marker([venue.latitude, venue.longitude]);

        if (map.value) {
            marker.addTo(map.value as L.Map);
        }

        marker.bindPopup(
            `
            <div>
                <h3 class="font-semibold">${venue.name}</h3>
                <p class="text-sm">${venue.city}</p>
                ${venue.address ? `<p class="text-sm text-gray-600">${venue.address}</p>` : ''}
                ${venue.reports_count ? `<p class="text-sm mt-1">${venue.reports_count} report${venue.reports_count > 1 ? 's' : ''}</p>` : ''}
            </div>
        `,
        );

        markers.value.push(marker);

        return marker;
    }

    function clearMarkers(): void {
        if (map.value) {
            markers.value.forEach((marker) => {
                (map.value as L.Map).removeLayer(marker as unknown as L.Layer);
            });
        }

        markers.value = [];
    }

    function fitBounds(venues: Venue[]): void {
        if (!map.value || venues.length === 0) {
            return;
        }

        const bounds = L.latLngBounds(
            venues.map((venue) => [venue.latitude, venue.longitude]),
        );

        map.value.fitBounds(bounds, {
            padding: [50, 50],
        });
    }

    return {
        map,
        markers,
        initMap,
        addMarker,
        clearMarkers,
        fitBounds,
    };
}
