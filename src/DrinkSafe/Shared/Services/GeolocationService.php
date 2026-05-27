<?php

declare(strict_types=1);

namespace DrinkSafe\Shared\Services;

/**
 * GeolocationService
 *
 * Provides geographical calculations and utilities for coordinate-based operations.
 * Uses the Haversine formula for distance calculations.
 */
final class GeolocationService
{
    /**
     * Earth's radius in kilometres.
     */
    private const EARTH_RADIUS_KM = 6371;

    /**
     * Calculate the distance between two geographical points.
     *
     * Uses the Haversine formula to calculate the great-circle distance
     * between two points on Earth's surface.
     *
     * @param  float  $lat1  Latitude of first point
     * @param  float  $lng1  Longitude of first point
     * @param  float  $lat2  Latitude of second point
     * @param  float  $lng2  Longitude of second point
     * @return float Distance in kilometres
     */
    public function distance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::EARTH_RADIUS_KM * $c;
    }

    /**
     * Check if a point is within a specified radius of another point.
     *
     * @param  float  $lat1  Latitude of centre point
     * @param  float  $lng1  Longitude of centre point
     * @param  float  $lat2  Latitude of point to check
     * @param  float  $lng2  Longitude of point to check
     * @param  int  $radiusKm  Radius in kilometres
     * @return bool True if point 2 is within radius of point 1
     */
    public function isWithinRadius(float $lat1, float $lng1, float $lat2, float $lng2, int $radiusKm): bool
    {
        return $this->distance($lat1, $lng1, $lat2, $lng2) <= $radiusKm;
    }

    /**
     * Calculate a bounding box for query optimisation.
     *
     * Returns approximate min/max latitude and longitude values that form
     * a rectangular bounding box around a centre point with the given radius.
     * Useful for pre-filtering database queries before applying precise distance calculations.
     *
     * @param  float  $lat  Centre latitude
     * @param  float  $lng  Centre longitude
     * @param  int  $radiusKm  Radius in kilometres
     * @return array{minLat: float, maxLat: float, minLng: float, maxLng: float} Bounding box coordinates
     */
    public function getBoundingBox(float $lat, float $lng, int $radiusKm): array
    {
        // Approximation: 1 degree latitude ≈ 111km
        $latDelta = $radiusKm / 111.0;

        // Approximation: 1 degree longitude ≈ 111km * cos(latitude)
        $lngDelta = $radiusKm / (111.0 * cos(deg2rad($lat)));

        return [
            'minLat' => $lat - $latDelta,
            'maxLat' => $lat + $latDelta,
            'minLng' => $lng - $lngDelta,
            'maxLng' => $lng + $lngDelta,
        ];
    }
}
