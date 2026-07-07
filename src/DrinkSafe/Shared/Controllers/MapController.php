<?php

declare(strict_types=1);

namespace DrinkSafe\Shared\Controllers;

use App\Http\Controllers\Controller;
use DrinkSafe\Shared\Seo\Seo;
use DrinkSafe\Venues\Models\Venue;
use DrinkSafe\Venues\Resources\VenueMapResource;
use DrinkSafe\Venues\Services\VenueSearchService;
use DrinkSafe\Venues\Services\VenueService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * MapController
 *
 * Displays the map page with venues.
 * Supports viewport-based loading via bounds parameter for performance.
 */
final class MapController extends Controller
{
    /**
     * Create a new MapController instance.
     */
    public function __construct(
        private readonly VenueService $venueService,
        private readonly VenueSearchService $venueSearchService
    ) {}

    /**
     * Display the map page with venues.
     *
     * Supports filtering by:
     * - bounds: Geographic bounds in format "swLat,swLng,neLat,neLng" (viewport-based loading)
     * - search: Text search on venue name and city
     * - city: Exact city match
     *
     * When no filters are provided, returns an empty collection to allow
     * frontend to fetch via API based on viewport.
     *
     * @param  Request  $request  HTTP request with optional filter parameters
     * @return Response Inertia response with venues
     */
    public function __invoke(Request $request): Response
    {
        $search = $request->query('search');
        $city = $request->query('city');
        $bounds = $request->query('bounds');

        $venues = $this->resolveVenues($search, $city, $bounds);

        $seo = Seo::default()
            ->withTitle('Interactive Venue Safety Map — Drink Safe')
            ->withDescription('Explore an interactive map of community-submitted drink-spiking reports across UK venues. Search by city or venue to see safety information near you.');

        return Inertia::render('Map', [
            'seo' => $seo->toArray(),
            'venues' => VenueMapResource::collection($venues)->resolve(),
            'filters' => [
                'search' => $search,
                'city' => $city,
                'bounds' => $bounds,
            ],
        ]);
    }

    /**
     * Resolve venues based on filter parameters.
     *
     * Priority order:
     * 1. Search query (if provided)
     * 2. City filter (if provided)
     * 3. Bounds/viewport (if provided)
     * 4. Empty collection (let frontend fetch via API)
     *
     * @return Collection<int, Venue>
     */
    private function resolveVenues(?string $search, ?string $city, ?string $bounds): Collection
    {
        if ($search !== null && $search !== '') {
            return $this->venueSearchService->withReportCounts()->search($search);
        }

        if ($city !== null && $city !== '') {
            return $this->venueSearchService->withReportCounts()->filterByCity($city);
        }

        if ($bounds !== null && $bounds !== '') {
            $parsedBounds = $this->parseBounds($bounds);
            if ($parsedBounds !== null) {
                return $this->venueService->getVenuesInBounds(
                    $parsedBounds['swLat'],
                    $parsedBounds['swLng'],
                    $parsedBounds['neLat'],
                    $parsedBounds['neLng']
                );
            }
        }

        return collect();
    }

    /**
     * Parse bounds string into coordinate array.
     *
     * @param  string  $bounds  Format: "swLat,swLng,neLat,neLng"
     * @return array{swLat: float, swLng: float, neLat: float, neLng: float}|null
     */
    private function parseBounds(string $bounds): ?array
    {
        $coordinates = explode(',', $bounds);

        if (count($coordinates) !== 4) {
            return null;
        }

        $floatCoordinates = array_map('floatval', $coordinates);

        if (! $this->areBoundsValid($floatCoordinates)) {
            return null;
        }

        return [
            'swLat' => $floatCoordinates[0],
            'swLng' => $floatCoordinates[1],
            'neLat' => $floatCoordinates[2],
            'neLng' => $floatCoordinates[3],
        ];
    }

    /**
     * Validate that bounds coordinates are within valid ranges.
     *
     * @param  array<int, float>  $coordinates  [swLat, swLng, neLat, neLng]
     */
    private function areBoundsValid(array $coordinates): bool
    {
        [$swLat, $swLng, $neLat, $neLng] = $coordinates;

        return $swLat >= -90 && $swLat <= 90
            && $neLat >= -90 && $neLat <= 90
            && $swLng >= -180 && $swLng <= 180
            && $neLng >= -180 && $neLng <= 180
            && $swLat <= $neLat;
    }
}
