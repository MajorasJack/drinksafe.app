<?php

declare(strict_types=1);

namespace DrinkSafe\Venues\Services;

use DateTimeInterface;
use DrinkSafe\Reports\Enums\ReportStatus;
use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Reports\Scopes\PublishedScope;
use DrinkSafe\Shared\Controllers\SitemapController;
use DrinkSafe\Shared\Services\GeolocationService;
use DrinkSafe\Shared\Services\PublicCacheVersion;
use DrinkSafe\Venues\Exceptions\VenueDuplicateException;
use DrinkSafe\Venues\Exceptions\VenueNotFoundException;
use DrinkSafe\Venues\Models\Venue;
use DrinkSafe\Venues\Scopes\PublishedVenueScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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
     * How close a venue with the same name and city must be to count as the same place, in kilometres (100 metres).
     */
    public const float SAME_VENUE_RADIUS_KM = 0.1;

    /**
     * Create a new VenueService instance.
     */
    public function __construct(
        private readonly SafetyScoreService $safetyScoreService,
        private readonly PublicCacheVersion $publicCacheVersion,
        private readonly GeolocationService $geolocationService,
    ) {}

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
     * Refuses a venue that already exists, which means the same name and city
     * within SAME_VENUE_RADIUS_KM of the given coordinates. Chains have many
     * branches in one city, so a match further away is a different branch.
     * The venue is created unpublished, because its details come from the public
     * and nobody has reviewed them yet. Publishing a report about it publishes it.
     * An unpublished match is returned as it is, rather than throwing, so the
     * caller can't tell that a submission is waiting for review, and no second
     * copy of the venue is created.
     *
     * @param  array{name: string, city: string, address?: string|null, latitude: float|int|string, longitude: float|int|string}  $data  Venue data
     *
     * @throws VenueDuplicateException If a published venue already exists
     */
    public function createVenue(array $data): Venue
    {
        $existingVenue = $this->findSameVenue(
            name: $data['name'],
            city: $data['city'],
            latitude: (float) $data['latitude'],
            longitude: (float) $data['longitude'],
        );

        throw_if($existingVenue?->published_at !== null, VenueDuplicateException::class, $data['name'], $data['city']);

        return $existingVenue ?? $this->createUnpublishedVenue($data);
    }

    /**
     * Find the venue, published or not, that a submission with this name, city and location means.
     *
     * With coordinates, it's a venue with the same name and city (ignoring
     * case and surrounding spaces) within SAME_VENUE_RADIUS_KM. A published
     * match wins, then the nearest. A placeholder at the default coordinates
     * never counts, as its pin isn't a real location. Without coordinates,
     * see findSameVenueByNameAndCity().
     *
     * @param  string  $name  Venue name
     * @param  string  $city  City name
     * @param  float|null  $latitude  Latitude of the submission, or null if it has none
     * @param  float|null  $longitude  Longitude of the submission, or null if it has none
     */
    public function findSameVenue(string $name, string $city, ?float $latitude, ?float $longitude): ?Venue
    {
        if ($latitude === null || $longitude === null) {
            return $this->findSameVenueByNameAndCity(name: $name, city: $city);
        }

        $boundingBox = $this->geolocationService->getBoundingBox($latitude, $longitude, self::SAME_VENUE_RADIUS_KM);

        return Venue::withoutGlobalScope(PublishedVenueScope::class)
            ->sameNameAndCity(name: $name, city: $city)
            ->whereBetween('latitude', [$boundingBox['minLat'], $boundingBox['maxLat']])
            ->whereBetween('longitude', [$boundingBox['minLng'], $boundingBox['maxLng']])
            ->get()
            ->reject(fn (Venue $venue): bool => $venue->isAtDefaultCoordinates())
            ->map(fn (Venue $venue): array => [
                'venue' => $venue,
                'distance' => $this->geolocationService->distance($latitude, $longitude, (float) $venue->latitude, (float) $venue->longitude),
            ])
            ->filter(fn (array $match): bool => $match['distance'] <= self::SAME_VENUE_RADIUS_KM)
            ->sortBy([
                fn (array $first, array $second): int => ($first['venue']->published_at === null) <=> ($second['venue']->published_at === null),
                fn (array $first, array $second): int => $first['distance'] <=> $second['distance'],
            ])
            ->first()['venue'] ?? null;
    }

    /**
     * Find the venue a submission with no coordinates means, from its name and city alone.
     *
     * Only published venues decide whether the name and city is a chain, so
     * unpublished copies (from `POST /api/venues`, or placeholders) can't turn
     * a single venue into one. One published match is the venue. Two or more
     * is a chain with no telling which branch was meant, so it's the chain's
     * existing placeholder at the default coordinates, if it has one. With no
     * published match, the oldest unpublished one, so a second report about
     * a new venue joins the first.
     *
     * @param  string  $name  Venue name
     * @param  string  $city  City name
     */
    private function findSameVenueByNameAndCity(string $name, string $city): ?Venue
    {
        $publishedMatches = Venue::sameNameAndCity(name: $name, city: $city)->limit(2)->get();
        $unpublishedMatches = Venue::withoutGlobalScope(PublishedVenueScope::class)
            ->sameNameAndCity(name: $name, city: $city)
            ->whereNull('published_at')
            ->oldest()
            ->oldest('uuid');

        return match ($publishedMatches->count()) {
            0 => $unpublishedMatches->first(),
            1 => $publishedMatches->first(),
            default => $unpublishedMatches->atDefaultCoordinates()->first(),
        };
    }

    /**
     * Create an unpublished venue without checking for an existing one.
     *
     * @param  array{name: string, city: string, address?: string|null, latitude: float|int|string, longitude: float|int|string}  $data  Venue data
     */
    public function createUnpublishedVenue(array $data): Venue
    {
        return tap(new Venue($data), fn (Venue $venue): bool => $venue->forceFill(['published_at' => null])->save());
    }

    /**
     * Update an existing venue, published or not.
     *
     * The slug is left as it is, so existing links keep working. Moving the
     * venue clears its cached safety score and the heatmap caches once the
     * change commits.
     *
     * @param  string  $uuid  The venue UUID
     * @param  array<string, mixed>  $data  Updated venue data
     *
     * @throws VenueNotFoundException If venue not found
     */
    public function updateVenue(string $uuid, array $data): Venue
    {
        $venue = Venue::withoutGlobalScope(PublishedVenueScope::class)->find($uuid);

        if ($venue === null) {
            throw new VenueNotFoundException($uuid);
        }

        $venue->update($data);

        if ($venue->wasChanged(['latitude', 'longitude'])) {
            $this->clearPublicCachesAfterCommit($venue);
        }

        return $venue->fresh();
    }

    /**
     * Get a page of venues, published or not, narrowed by the admin's filters, by name.
     *
     * Orphaned venues are unpublished ones with no pending report, which a
     * rejected submission or `POST /api/venues` leaves behind.
     *
     * @param  array{q?: string, city?: string, state?: string}  $filters  Name contains, city, and published, unpublished or orphaned
     * @param  int  $perPage  Venues per page (default: 25)
     * @return LengthAwarePaginator<int, Venue>
     */
    public function searchVenues(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        return Venue::withoutGlobalScope(PublishedVenueScope::class)
            ->withCount([
                'reports as published_reports_count' => fn (Builder $query): Builder => $query
                    ->withoutGlobalScope(PublishedScope::class)
                    ->where('status', ReportStatus::Published),
                'reports as pending_reports_count' => fn (Builder $query): Builder => $query
                    ->withoutGlobalScope(PublishedScope::class)
                    ->where('status', ReportStatus::Pending),
            ])
            ->when($filters['q'] ?? null, fn (Builder $query, string $name): Builder => $query->whereRaw(
                'name LIKE ? ESCAPE ?',
                [sprintf('%%%s%%', addcslashes($name, '\\%_')), '\\'],
            ))
            ->when($filters['city'] ?? null, fn (Builder $query, string $city): Builder => $query->where('city', $city))
            ->when(($filters['state'] ?? null) === 'published', fn (Builder $query): Builder => $query->whereNotNull('published_at'))
            ->when(($filters['state'] ?? null) === 'unpublished', fn (Builder $query): Builder => $query->whereNull('published_at'))
            ->when(($filters['state'] ?? null) === 'orphaned', fn (Builder $query): Builder => $query->orphaned())
            ->orderBy('name')
            ->orderBy('uuid')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Get a venue, published or not.
     *
     * @param  string  $uuid  The venue UUID
     *
     * @throws ModelNotFoundException If no venue has this UUID
     */
    public function getVenueForAdmin(string $uuid): Venue
    {
        return Venue::withoutGlobalScope(PublishedVenueScope::class)->findOrFail($uuid);
    }

    /**
     * Get a page of a venue's reports in every status, newest first.
     *
     * @param  Venue  $venue  The venue, published or not
     * @param  int  $perPage  Reports per page (default: 25)
     * @return LengthAwarePaginator<int, Report>
     */
    public function getVenueReportsForAdmin(Venue $venue, int $perPage = 25): LengthAwarePaginator
    {
        return tap(
            $venue->reports()->withoutGlobalScope(PublishedScope::class)->latest()->latest('uuid')->paginate($perPage),
            fn (LengthAwarePaginator $reports): Collection => $reports->getCollection()->each(fn (Report $report): Report => $report->setRelation('venue', $venue)),
        );
    }

    /**
     * Put a venue back on the site. Its reports keep their status.
     *
     * @param  string  $uuid  The venue UUID
     *
     * @throws ModelNotFoundException If no venue has this UUID
     */
    public function publishVenue(string $uuid): Venue
    {
        return $this->setPublishState(uuid: $uuid, publishedAt: now(), unpublishedAt: null);
    }

    /**
     * Take a venue off the site, along with all its reports.
     *
     * Marking it as unpublished by the admin stops a report about it from
     * publishing it again until the venue itself is published.
     *
     * @param  string  $uuid  The venue UUID
     *
     * @throws ModelNotFoundException If no venue has this UUID
     */
    public function unpublishVenue(string $uuid): Venue
    {
        return $this->setPublishState(uuid: $uuid, publishedAt: null, unpublishedAt: now());
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

    /**
     * Set when a venue was published and unpublished, then clear the caches that show it.
     *
     * A venue already in the requested state is left alone, so a repeated
     * publish or unpublish keeps its timestamps and the caches.
     *
     * @param  string  $uuid  The venue UUID
     * @param  DateTimeInterface|null  $publishedAt  When it went on the site, or null to hide it
     * @param  DateTimeInterface|null  $unpublishedAt  When the admin took it off, or null
     *
     * @throws ModelNotFoundException If no venue has this UUID
     */
    private function setPublishState(string $uuid, ?DateTimeInterface $publishedAt, ?DateTimeInterface $unpublishedAt): Venue
    {
        return DB::transaction(fn (): Venue => tap(
            Venue::withoutGlobalScope(PublishedVenueScope::class)->lockForUpdate()->findOrFail($uuid),
            function (Venue $venue) use ($publishedAt, $unpublishedAt): void {
                if (($venue->published_at === null) === ($publishedAt === null) && ($venue->unpublished_at === null) === ($unpublishedAt === null)) {
                    return;
                }

                $venue->forceFill(['published_at' => $publishedAt, 'unpublished_at' => $unpublishedAt])->save();
                $this->clearPublicCachesAfterCommit($venue);
            },
        ));
    }

    /**
     * Clear the venue's cached safety score, the heatmap caches and the sitemap once the transaction commits.
     *
     * @param  Venue  $venue  The venue that moved, or was published or unpublished
     */
    private function clearPublicCachesAfterCommit(Venue $venue): void
    {
        DB::afterCommit(function () use ($venue): void {
            $this->safetyScoreService->invalidateScore($venue->uuid);
            $this->publicCacheVersion->bump();
            Cache::forget(SitemapController::CACHE_KEY);
        });
    }
}
