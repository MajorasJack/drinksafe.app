<?php

declare(strict_types=1);

namespace DrinkSafe\Shared\Controllers;

use App\Http\Controllers\Controller;
use DrinkSafe\Venues\Resources\VenueResource;
use DrinkSafe\Venues\Services\VenueSearchService;
use DrinkSafe\Venues\Services\VenueService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * MapController
 *
 * Displays the map page with all venues marked.
 * Supports optional filtering by city and search query.
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
     * - search: Text search on venue name and city
     * - city: Exact city match
     *
     * @param  Request  $request  HTTP request with optional filter parameters
     * @return Response Inertia response with venues
     */
    public function __invoke(Request $request): Response
    {
        $search = $request->query('search');
        $city = $request->query('city');

        if ($search !== null && $search !== '') {
            $venues = $this->venueSearchService->withReportCounts()->search($search);
        } elseif ($city !== null && $city !== '') {
            $venues = $this->venueSearchService->withReportCounts()->filterByCity($city);
        } else {
            $venues = $this->venueService->getAllVenues();
        }

        return Inertia::render('Map', [
            'venues' => VenueResource::collection($venues)->resolve(),
            'filters' => [
                'search' => $search,
                'city' => $city,
            ],
        ]);
    }
}
