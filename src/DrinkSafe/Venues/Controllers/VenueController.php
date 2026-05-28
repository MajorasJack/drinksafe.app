<?php

declare(strict_types=1);

namespace DrinkSafe\Venues\Controllers;

use App\Http\Controllers\Controller;
use DrinkSafe\Venues\Exceptions\VenueDuplicateException;
use DrinkSafe\Venues\Exceptions\VenueNotFoundException;
use DrinkSafe\Venues\Models\Venue;
use DrinkSafe\Venues\Requests\IndexVenueRequest;
use DrinkSafe\Venues\Requests\StoreVenueRequest;
use DrinkSafe\Venues\Resources\VenueCollection;
use DrinkSafe\Venues\Resources\VenueResource;
use DrinkSafe\Venues\Services\VenueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * VenueController
 *
 * Handles HTTP requests for venue CRUD operations.
 * Returns JSON responses with appropriate status codes.
 */
final class VenueController extends Controller
{
    public function __construct(
        private readonly VenueService $venueService
    ) {}

    /**
     * Display a listing of venues.
     *
     * Supports filtering by:
     * - bounds: Geographic bounds in format "swLat,swLng,neLat,neLng"
     * - city: Exact city match
     * - limit: Maximum number of venues to return (default 300, max 500)
     */
    public function index(IndexVenueRequest $request): JsonResponse
    {
        $bounds = $request->getBounds();
        $city = $request->validated()['city'] ?? null;
        $limit = (int) ($request->validated()['limit'] ?? 300);

        if ($bounds !== null) {
            $venues = $this->venueService->getVenuesInBounds(
                $bounds['swLat'],
                $bounds['swLng'],
                $bounds['neLat'],
                $bounds['neLng'],
                $limit
            );
        } elseif ($city !== null && $city !== '') {
            $venues = Venue::inCity($city)
                ->withCount('reports')
                ->limit($limit)
                ->get();
        } else {
            $venues = $this->venueService->getAllVenues($limit);
        }

        return response()->json(
            new VenueCollection($venues),
            Response::HTTP_OK
        );
    }

    /**
     * Display a specific venue.
     *
     * Returns 404 if venue not found.
     */
    public function show(string $uuid): JsonResponse
    {
        try {
            $venue = $this->venueService->getVenueById($uuid);

            return response()->json(
                new VenueResource($venue),
                Response::HTTP_OK
            );
        } catch (VenueNotFoundException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Store a newly created venue.
     *
     * Returns 422 if duplicate venue exists (same name + city).
     */
    public function store(StoreVenueRequest $request): JsonResponse
    {
        try {
            $venue = $this->venueService->createVenue($request->validated());

            Log::info('Venue created successfully', [
                'venue_uuid' => $venue->uuid,
                'venue_name' => $venue->name,
                'venue_city' => $venue->city,
                'ip_hash' => hash('sha256', $request->ip()),
                'user_agent_hash' => hash('sha256', $request->userAgent() ?? 'unknown'),
                'timestamp' => now()->toIso8601String(),
            ]);

            return response()->json([
                'message' => sprintf('Venue %s created successfully', $venue->name),
                'data' => new VenueResource($venue),
            ], Response::HTTP_CREATED);
        } catch (VenueDuplicateException $e) {
            Log::warning('Duplicate venue creation attempt', [
                'venue_name' => $request->input('name'),
                'venue_city' => $request->input('city'),
                'error' => $e->getMessage(),
                'ip_hash' => hash('sha256', $request->ip()),
                'timestamp' => now()->toIso8601String(),
            ]);

            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
