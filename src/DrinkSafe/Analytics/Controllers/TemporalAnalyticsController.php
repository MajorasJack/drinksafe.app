<?php

declare(strict_types=1);

namespace DrinkSafe\Analytics\Controllers;

use App\Http\Controllers\Controller;
use DrinkSafe\Analytics\Exceptions\InsufficientDataException;
use DrinkSafe\Analytics\Requests\TemporalAnalyticsRequest;
use DrinkSafe\Analytics\Resources\TemporalAnalyticsResource;
use DrinkSafe\Analytics\Services\TemporalAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * TemporalAnalyticsController
 *
 * Handles requests for temporal analytics data.
 * Returns aggregated report data by day-of-week and time-of-day
 * with filtering support for city, period, and specific venues.
 */
final class TemporalAnalyticsController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param  TemporalAnalyticsService  $analyticsService  Service for computing temporal analytics
     */
    public function __construct(
        private readonly TemporalAnalyticsService $analyticsService,
    ) {}

    /**
     * Get temporal analytics data.
     *
     * Returns a 7x4 grid of day/time combinations with report counts,
     * percentages, totals by day and time, and auto-generated insights.
     *
     * Query parameters:
     * - city: Optional city name filter
     * - period: Number of days (30, 90, or 365; default: 90)
     * - venue_uuid: Optional specific venue filter
     *
     * @param  TemporalAnalyticsRequest  $request  Validated request
     */
    public function __invoke(TemporalAnalyticsRequest $request): JsonResponse
    {
        try {
            $data = $this->analyticsService->getTemporalData(
                city: $request->getCity(),
                venueUuid: $request->getVenueUuid(),
                periodDays: $request->getPeriodDays(),
            );

            return response()->json([
                'data' => (new TemporalAnalyticsResource($data))->toArray($request),
            ], Response::HTTP_OK);
        } catch (InsufficientDataException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'minimum_required' => $exception->getMinimumRequired(),
                'actual_count' => $exception->getActualCount(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
