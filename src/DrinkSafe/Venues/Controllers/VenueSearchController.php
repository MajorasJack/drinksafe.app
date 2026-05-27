<?php

declare(strict_types=1);

namespace DrinkSafe\Venues\Controllers;

use App\Http\Controllers\Controller;
use DrinkSafe\Venues\Resources\VenueCollection;
use DrinkSafe\Venues\Services\VenueSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * VenueSearchController
 *
 * Handles venue search operations including text search, city filtering,
 * and geographical proximity searches.
 */
final class VenueSearchController extends Controller
{
    public function __construct(
        private readonly VenueSearchService $venueSearchService
    ) {}

    /**
     * Search for venues using multiple criteria.
     *
     * Supported query parameters:
     * - q: Search term for name/city
     * - city: Filter by city name
     * - lat, lng, radius: Geospatial search (radius in km, default 10)
     *
     * Always includes report counts in results.
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['nullable', 'string', 'min:1'],
            'city' => ['nullable', 'string', 'min:1'],
            'lat' => ['nullable', 'numeric', 'between:-90,90', 'required_with:lng'],
            'lng' => ['nullable', 'numeric', 'between:-180,180', 'required_with:lat'],
            'radius' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);

        $service = $this->venueSearchService->withReportCounts();

        $lat = $request->query('lat');
        $lng = $request->query('lng');
        $radius = (int) $request->query('radius', 10);
        $city = $request->query('city');
        $query = $request->query('q');

        if ($lat !== null && $lng !== null) {
            $venues = $service->nearby((float) $lat, (float) $lng, $radius);
        } elseif ($city !== null) {
            $venues = $service->filterByCity($city);
        } elseif ($query !== null) {
            $venues = $service->search($query);
        } else {
            $venues = collect();
        }

        return response()->json(
            new VenueCollection($venues),
            Response::HTTP_OK
        );
    }
}
