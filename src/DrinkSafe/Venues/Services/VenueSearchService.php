<?php

declare(strict_types=1);

namespace DrinkSafe\Venues\Services;

use DrinkSafe\Shared\Services\GeolocationService;
use DrinkSafe\Venues\Models\Venue;
use Illuminate\Support\Collection;

/**
 * VenueSearchService
 *
 * Handles searching and filtering venues by various criteria including
 * text search, city filtering, and geographical proximity.
 */
final class VenueSearchService
{
    /**
     * Whether to eager load report counts.
     */
    private bool $withReportCounts = false;

    public function __construct(
        private readonly GeolocationService $geolocationService
    ) {}

    /**
     * Search venues by name or city.
     *
     * Performs case-insensitive partial match on name and city fields.
     *
     * @param  string  $query  Search query
     * @param  int  $limit  Maximum number of results (default: 50)
     * @return Collection<int, Venue>
     */
    public function search(string $query, int $limit = 50): Collection
    {
        $queryBuilder = Venue::search($query);

        if ($this->withReportCounts) {
            $queryBuilder->withCount('reports');
        }

        return $queryBuilder->limit($limit)->get();
    }

    /**
     * Filter venues by city.
     *
     * @param  string  $city  City name
     * @param  int  $limit  Maximum number of results (default: 50)
     * @return Collection<int, Venue>
     */
    public function filterByCity(string $city, int $limit = 50): Collection
    {
        $queryBuilder = Venue::inCity($city);

        if ($this->withReportCounts) {
            $queryBuilder->withCount('reports');
        }

        return $queryBuilder->limit($limit)->get();
    }

    /**
     * Find venues within a specified radius of coordinates.
     *
     * @param  float  $lat  Centre latitude
     * @param  float  $lng  Centre longitude
     * @param  int  $radiusKm  Search radius in kilometres (default: 10)
     * @return Collection<int, Venue>
     */
    public function nearby(float $lat, float $lng, int $radiusKm = 10): Collection
    {
        $queryBuilder = Venue::nearby($lat, $lng, $radiusKm);

        if ($this->withReportCounts) {
            $queryBuilder->withCount('reports');
        }

        return $queryBuilder->get();
    }

    /**
     * Enable eager loading of report counts for subsequent queries.
     *
     * This method is chainable and affects the next query executed.
     */
    public function withReportCounts(): self
    {
        $this->withReportCounts = true;

        return $this;
    }
}
