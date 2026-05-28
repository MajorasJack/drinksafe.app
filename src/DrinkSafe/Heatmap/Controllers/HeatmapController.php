<?php

declare(strict_types=1);

namespace DrinkSafe\Heatmap\Controllers;

use App\Http\Controllers\Controller;
use DrinkSafe\Heatmap\Exceptions\InvalidBoundingBoxException;
use DrinkSafe\Heatmap\Requests\HeatmapRequest;
use DrinkSafe\Heatmap\Resources\HeatmapResource;
use DrinkSafe\Heatmap\Services\HeatmapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * HeatmapController
 *
 * Handles requests for heatmap data.
 * Returns aggregated incident density data for Leaflet.heat visualisation.
 */
final class HeatmapController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param  HeatmapService  $heatmapService  Service for computing heatmap data
     */
    public function __construct(
        private readonly HeatmapService $heatmapService,
    ) {}

    /**
     * Get heatmap data for the specified bounding box and time period.
     *
     * Query parameters:
     * - north: Northern boundary latitude (required)
     * - south: Southern boundary latitude (required)
     * - east: Eastern boundary longitude (required)
     * - west: Western boundary longitude (required)
     * - period: Number of days (7, 30, 90, or 365; default: 30)
     *
     * @param  HeatmapRequest  $request  Validated request
     */
    public function __invoke(HeatmapRequest $request): JsonResponse
    {
        try {
            $data = $this->heatmapService->getHeatmapData(
                north: $request->getNorth(),
                south: $request->getSouth(),
                east: $request->getEast(),
                west: $request->getWest(),
                periodDays: $request->getPeriodDays(),
            );

            return response()->json([
                'data' => (new HeatmapResource($data))->toArray($request),
            ], Response::HTTP_OK);
        } catch (InvalidBoundingBoxException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}
