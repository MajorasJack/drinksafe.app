<?php

declare(strict_types=1);

namespace DrinkSafe\Venues\Services;

use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Venues\Exceptions\VenueDuplicateException;
use DrinkSafe\Venues\Exceptions\VenueNotFoundException;
use DrinkSafe\Venues\Models\Venue;
use DrinkSafe\Venues\Scopes\PublishedVenueScope;
use Illuminate\Support\Collection;

/**
 * VenueService
 *
 * Handles business logic for venue operations including CRUD operations
 * and venue-related queries.
 */
final class VenueService
{
    /**
     * Default maximum number of venues to return in bounds queries.
     */
    private const DEFAULT_BOUNDS_LIMIT = 300;

    /**
     * Retrieve all venues with report counts.
     *
     * @param  int|null  $limit  Maximum number of venues to return (null for no limit)
     * @return Collection<int, Venue>
     */
    public function getAllVenues(?int $limit = null): Collection
    {
        $query = Venue::withCount('reports');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Retrieve venues within geographic bounds with report counts.
     *
     * Uses the idx_venues_location index for efficient querying.
     * Results are limited to prevent performance issues with large viewports.
     * Only returns venues that have at least one report to avoid misleading users.
     *
     * @param  float  $swLat  Southwest corner latitude
     * @param  float  $swLng  Southwest corner longitude
     * @param  float  $neLat  Northeast corner latitude
     * @param  float  $neLng  Northeast corner longitude
     * @param  int  $limit  Maximum number of venues to return (default 300)
     * @return Collection<int, Venue>
     */
    public function getVenuesInBounds(
        float $swLat,
        float $swLng,
        float $neLat,
        float $neLng,
        int $limit = self::DEFAULT_BOUNDS_LIMIT
    ): Collection {
        return Venue::inBounds($swLat, $swLng, $neLat, $neLng)
            ->has('reports') // Only show venues with at least one report
            ->withCount('reports')
            ->limit($limit)
            ->get();
    }

    /**
     * Retrieve a single venue by UUID with reports relationship loaded.
     *
     * @param  string  $uuid  The venue UUID
     *
     * @throws VenueNotFoundException If venue not found
     */
    public function getVenueById(string $uuid): Venue
    {
        $venue = Venue::with('reports')->withCount('reports')->find($uuid);

        if ($venue === null) {
            throw new VenueNotFoundException($uuid);
        }

        return $venue;
    }

    /**
     * Create a new venue.
     *
     * Validates that a venue with the same name doesn't already exist in the same city.
     * The venue is created unpublished, because its details come from the public
     * and nobody has reviewed them yet. Publishing a report about it publishes it.
     * An unpublished venue with the same name and city is returned as it is,
     * rather than throwing, so the caller can't tell that a submission is
     * waiting for review, and no second copy of the venue is created.
     *
     * @param  array<string, mixed>  $data  Venue data
     *
     * @throws VenueDuplicateException If a published venue already exists
     */
    public function createVenue(array $data): Venue
    {
        $existingVenue = Venue::withoutGlobalScope(PublishedVenueScope::class)
            ->where('name', $data['name'])
            ->where('city', $data['city'])
            ->first();

        throw_if($existingVenue?->published_at !== null, VenueDuplicateException::class, $data['name'], $data['city']);

        return $existingVenue ?? tap(new Venue($data), fn (Venue $venue): bool => $venue->forceFill(['published_at' => null])->save());
    }

    /**
     * Update an existing venue.
     *
     * @param  string  $uuid  The venue UUID
     * @param  array<string, mixed>  $data  Updated venue data
     *
     * @throws VenueNotFoundException If venue not found
     */
    public function updateVenue(string $uuid, array $data): Venue
    {
        $venue = Venue::find($uuid);

        if ($venue === null) {
            throw new VenueNotFoundException($uuid);
        }

        $venue->update($data);

        return $venue->fresh();
    }

    /**
     * Soft delete a venue.
     *
     * This will cascade to related reports due to the database constraint.
     *
     * @param  string  $uuid  The venue UUID
     *
     * @throws VenueNotFoundException If venue not found
     */
    public function deleteVenue(string $uuid): bool
    {
        $venue = Venue::find($uuid);

        if ($venue === null) {
            throw new VenueNotFoundException($uuid);
        }

        return $venue->delete();
    }

    /**
     * Get paginated reports for a specific venue.
     *
     * @param  string  $venueUuid  The venue UUID
     * @param  int  $limit  Maximum number of reports to retrieve (default: 50)
     * @return Collection<int, Report>
     *
     * @throws VenueNotFoundException If venue not found
     */
    public function getVenueReports(string $venueUuid, int $limit = 50): Collection
    {
        $venue = Venue::find($venueUuid);

        if ($venue === null) {
            throw new VenueNotFoundException($venueUuid);
        }

        return $venue->reports()
            ->orderBy('incident_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
