<?php

declare(strict_types=1);

namespace DrinkSafe\Venues\Controllers;

use App\Http\Controllers\Controller;
use DrinkSafe\Venues\Exceptions\VenueNotFoundException;
use DrinkSafe\Venues\Services\SafetyScoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * VenueSafetyScoreController
 *
 * Handles requests for venue safety score data.
 * Returns computed safety scores with tier classification and report metadata.
 */
final class VenueSafetyScoreController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param  SafetyScoreService  $safetyScoreService  Service for computing safety scores
     */
    public function __construct(
        private readonly SafetyScoreService $safetyScoreService,
    ) {}

    /**
     * Get safety score for a specific venue.
     *
     * Returns the computed safety score including:
     * - Numeric score (0-100)
     * - Tier classification (low/moderate/high/insufficient_data)
     * - Report counts for 30 and 90 day periods
     * - Last updated timestamp
     *
     * @param  string  $uuid  Venue UUID
     */
    public function __invoke(string $uuid): JsonResponse
    {
        try {
            $score = $this->safetyScoreService->computeScoreByUuid($uuid);

            return response()->json([
                'data' => $score->toArray(),
            ], Response::HTTP_OK);
        } catch (VenueNotFoundException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        }
    }
}
