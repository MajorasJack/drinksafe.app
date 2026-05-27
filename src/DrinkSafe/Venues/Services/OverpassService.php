<?php

declare(strict_types=1);

namespace DrinkSafe\Venues\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OverpassService
 *
 * Fetches venue data from OpenStreetMap's Overpass API.
 * Queries for bars, pubs, and nightclubs within specified cities.
 */
final class OverpassService
{
    /**
     * Available Overpass API mirrors.
     *
     * @var list<string>
     */
    private const array OVERPASS_API_MIRRORS = [
        'https://overpass.private.coffee/api/interpreter',
        'https://maps.mail.ru/osm/tools/overpass/api/interpreter',
        'https://overpass-api.de/api/interpreter',
    ];

    private const int TIMEOUT_SECONDS = 30;

    /**
     * Amenity types to fetch from OpenStreetMap.
     *
     * @var list<string>
     */
    private const array AMENITY_TYPES = [
        'bar',
        'pub',
        'nightclub',
    ];

    /**
     * Fetch venues for a specific city using coordinates and radius.
     *
     * Tries multiple Overpass API mirrors in sequence until one succeeds.
     *
     * @param  string  $cityName  Name of the city (for labelling)
     * @param  float  $latitude  City centre latitude
     * @param  float  $longitude  City centre longitude
     * @param  int  $radiusMetres  Search radius in metres (default 5km)
     * @return Collection<int, array{name: string, city: string, address: string|null, latitude: float, longitude: float, amenity: string, osm_id: int}>
     */
    public function fetchVenuesForCity(
        string $cityName,
        float $latitude,
        float $longitude,
        int $radiusMetres = 5000
    ): Collection {
        $query = $this->buildQuery($latitude, $longitude, $radiusMetres);

        foreach (self::OVERPASS_API_MIRRORS as $mirror) {
            try {
                $response = Http::timeout(self::TIMEOUT_SECONDS)
                    ->withUserAgent('DrinkSafe/1.0 (venue-seeder)')
                    ->asForm()
                    ->post($mirror, ['data' => $query]);

                if ($response->successful()) {
                    $data = $response->json();

                    if ($data !== null) {
                        return $this->parseResponse($data, $cityName);
                    }
                }

                Log::warning('Overpass mirror returned error, trying next', [
                    'mirror' => $mirror,
                    'city' => $cityName,
                    'status' => $response->status(),
                ]);
            } catch (ConnectionException $e) {
                Log::warning('Overpass mirror connection failed, trying next', [
                    'mirror' => $mirror,
                    'city' => $cityName,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::error('All Overpass API mirrors failed', ['city' => $cityName]);

        return collect();
    }

    /**
     * Fetch venues for multiple cities.
     *
     * @param  array<string, array{lat: float, lng: float}>  $cities  City name => coordinates mapping
     * @param  int  $radiusMetres  Search radius in metres
     * @return Collection<int, array{name: string, city: string, address: string|null, latitude: float, longitude: float, amenity: string, osm_id: int}>
     */
    public function fetchVenuesForCities(array $cities, int $radiusMetres = 5000): Collection
    {
        $allVenues = collect();

        foreach ($cities as $cityName => $coords) {
            $venues = $this->fetchVenuesForCity(
                $cityName,
                $coords['lat'],
                $coords['lng'],
                $radiusMetres
            );

            $allVenues = $allVenues->merge($venues);

            // Rate limiting - be nice to the free API
            usleep(500000); // 0.5 second delay between requests
        }

        return $allVenues;
    }

    /**
     * Build the Overpass QL query for fetching venues.
     */
    private function buildQuery(float $latitude, float $longitude, int $radiusMetres): string
    {
        $amenityFilter = implode('|', self::AMENITY_TYPES);

        return sprintf(
            '[out:json][timeout:%d];
            (
                node["amenity"~"%s"]["name"](around:%d,%f,%f);
                way["amenity"~"%s"]["name"](around:%d,%f,%f);
            );
            out center tags;',
            self::TIMEOUT_SECONDS,
            $amenityFilter,
            $radiusMetres,
            $latitude,
            $longitude,
            $amenityFilter,
            $radiusMetres,
            $latitude,
            $longitude
        );
    }

    /**
     * Parse the Overpass API response into venue data.
     *
     * @param  array<string, mixed>  $data
     * @return Collection<int, array{name: string, city: string, address: string|null, latitude: float, longitude: float, amenity: string, osm_id: int}>
     */
    private function parseResponse(array $data, string $cityName): Collection
    {
        $elements = $data['elements'] ?? [];

        return collect($elements)
            ->filter(fn (array $element): bool => isset($element['tags']['name'])
                && isset($element['tags']['amenity'])
                && in_array($element['tags']['amenity'], self::AMENITY_TYPES, true))
            ->map(function (array $element) use ($cityName): ?array {
                $tags = $element['tags'] ?? [];

                // For ways, use center coordinates
                $lat = $element['lat'] ?? $element['center']['lat'] ?? null;
                $lon = $element['lon'] ?? $element['center']['lon'] ?? null;

                if ($lat === null || $lon === null) {
                    return null;
                }

                return [
                    'name' => $tags['name'],
                    'city' => $cityName,
                    'address' => $this->buildAddress($tags),
                    'latitude' => (float) $lat,
                    'longitude' => (float) $lon,
                    'amenity' => $tags['amenity'],
                    'osm_id' => (int) $element['id'],
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * Build a formatted address from OSM tags.
     *
     * @param  array<string, string>  $tags
     */
    private function buildAddress(array $tags): ?string
    {
        $parts = array_filter([
            $tags['addr:housenumber'] ?? null,
            $tags['addr:street'] ?? null,
            $tags['addr:city'] ?? null,
            $tags['addr:postcode'] ?? null,
        ]);

        return $parts !== [] ? implode(', ', $parts) : null;
    }
}
