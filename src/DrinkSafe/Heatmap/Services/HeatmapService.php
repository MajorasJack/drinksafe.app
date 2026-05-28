<?php

declare(strict_types=1);

namespace DrinkSafe\Heatmap\Services;

use DrinkSafe\Heatmap\DataTransferObjects\HeatmapPointDTO;
use DrinkSafe\Heatmap\Exceptions\InvalidBoundingBoxException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * HeatmapService
 *
 * Generates spatial aggregation data for heatmap visualisation.
 * Uses grid-based clustering with privacy-preserving thresholds.
 *
 * The service aggregates incident reports into geographic grid cells,
 * calculating intensity values based on report density. Privacy is
 * preserved by only showing cells with a minimum number of reports.
 */
final class HeatmapService
{
    /**
     * Grid cell size in degrees (approximately 100m at mid-latitudes).
     */
    private const float GRID_CELL_SIZE = 0.001;

    /**
     * Minimum reports per cell to display (privacy threshold).
     */
    private const int MINIMUM_REPORTS_PER_CELL = 3;

    /**
     * Cache TTL in seconds (15 minutes).
     */
    private const int CACHE_TTL_SECONDS = 900;

    /**
     * Maximum expected reports per cell for normalisation.
     */
    private const int MAX_REPORTS_FOR_NORMALISATION = 20;

    /**
     * Cache key prefix for heatmap data.
     */
    private const string CACHE_KEY_PREFIX = 'heatmap:';

    /**
     * Get heatmap data for a geographic bounding box.
     *
     * Returns aggregated points with intensity values for Leaflet.heat rendering.
     *
     * @param  float  $north  Northern boundary latitude
     * @param  float  $south  Southern boundary latitude
     * @param  float  $east  Eastern boundary longitude
     * @param  float  $west  Western boundary longitude
     * @param  int  $periodDays  Number of days to include (default: 30)
     * @return array{
     *     points: array<int, array{lat: float, lng: float, intensity: float}>,
     *     period_days: int,
     *     total_reports: int
     * }
     *
     * @throws InvalidBoundingBoxException If bounds are invalid
     */
    public function getHeatmapData(
        float $north,
        float $south,
        float $east,
        float $west,
        int $periodDays = 30,
    ): array {
        $this->validateBounds($north, $south, $east, $west);

        $cacheKey = $this->buildCacheKey($north, $south, $east, $west, $periodDays);

        $cachedResult = Cache::get($cacheKey);

        if ($cachedResult !== null) {
            return $cachedResult;
        }

        $result = $this->computeHeatmapData($north, $south, $east, $west, $periodDays);

        Cache::put($cacheKey, $result, self::CACHE_TTL_SECONDS);

        return $result;
    }

    /**
     * Invalidate all heatmap caches.
     *
     * Should be called when new reports are submitted.
     */
    public function invalidateCache(): void
    {
        Cache::flush();
    }

    /**
     * Invalidate cache for a specific bounding box.
     *
     * @param  float  $north  Northern boundary latitude
     * @param  float  $south  Southern boundary latitude
     * @param  float  $east  Eastern boundary longitude
     * @param  float  $west  Western boundary longitude
     * @param  int  $periodDays  Period in days
     */
    public function invalidateCacheForBounds(
        float $north,
        float $south,
        float $east,
        float $west,
        int $periodDays,
    ): void {
        $cacheKey = $this->buildCacheKey($north, $south, $east, $west, $periodDays);
        Cache::forget($cacheKey);
    }

    /**
     * Validate bounding box coordinates.
     *
     * @throws InvalidBoundingBoxException
     */
    private function validateBounds(float $north, float $south, float $east, float $west): void
    {
        if ($south < -90 || $south > 90 || $north < -90 || $north > 90) {
            throw InvalidBoundingBoxException::invalidLatitude();
        }

        if ($west < -180 || $west > 180 || $east < -180 || $east > 180) {
            throw InvalidBoundingBoxException::invalidLongitude();
        }

        if ($south > $north) {
            throw InvalidBoundingBoxException::invertedBounds();
        }
    }

    /**
     * Build cache key from bounds and period.
     */
    private function buildCacheKey(
        float $north,
        float $south,
        float $east,
        float $west,
        int $periodDays,
    ): string {
        $roundedNorth = round($north, 3);
        $roundedSouth = round($south, 3);
        $roundedEast = round($east, 3);
        $roundedWest = round($west, 3);

        return sprintf(
            '%s%s:%s:%s:%s:%d',
            self::CACHE_KEY_PREFIX,
            $roundedNorth,
            $roundedSouth,
            $roundedEast,
            $roundedWest,
            $periodDays,
        );
    }

    /**
     * Compute heatmap data from database.
     *
     * Uses grid-based spatial aggregation with venue coordinates.
     *
     * @return array{
     *     points: array<int, array{lat: float, lng: float, intensity: float}>,
     *     period_days: int,
     *     total_reports: int
     * }
     */
    private function computeHeatmapData(
        float $north,
        float $south,
        float $east,
        float $west,
        int $periodDays,
    ): array {
        $gridCellSize = self::GRID_CELL_SIZE;
        $minReports = self::MINIMUM_REPORTS_PER_CELL;

        $aggregatedData = $this->getAggregatedData(
            $north,
            $south,
            $east,
            $west,
            $periodDays,
            $gridCellSize,
            $minReports,
        );

        $totalReports = (int) $aggregatedData->sum('report_count');

        $points = $aggregatedData
            ->map(fn (object $row): array => HeatmapPointDTO::fromDatabaseRow(
                $row,
                self::MAX_REPORTS_FOR_NORMALISATION,
            )->toArray())
            ->values()
            ->toArray();

        return [
            'points' => $points,
            'period_days' => $periodDays,
            'total_reports' => $totalReports,
        ];
    }

    /**
     * Get aggregated report data from database.
     *
     * @return Collection<int, object>
     */
    private function getAggregatedData(
        float $north,
        float $south,
        float $east,
        float $west,
        int $periodDays,
        float $gridCellSize,
        int $minReports,
    ): Collection {
        $driver = DB::connection()->getDriverName();

        $gridLatExpression = $this->getGridExpression('venues.latitude', $gridCellSize, $driver);
        $gridLngExpression = $this->getGridExpression('venues.longitude', $gridCellSize, $driver);

        return DB::table('reports')
            ->join('venues', 'reports.venue_uuid', '=', 'venues.uuid')
            ->where('reports.incident_date', '>=', now()->subDays($periodDays))
            ->whereNull('reports.deleted_at')
            ->whereBetween('venues.latitude', [$south, $north])
            ->whereBetween('venues.longitude', [$west, $east])
            ->selectRaw(
                sprintf(
                    '%s AS grid_lat, %s AS grid_lng, COUNT(*) AS report_count',
                    $gridLatExpression,
                    $gridLngExpression,
                ),
            )
            ->groupBy('grid_lat', 'grid_lng')
            ->havingRaw(sprintf('COUNT(*) >= %d', $minReports))
            ->get();
    }

    /**
     * Get database-specific expression for grid cell calculation.
     */
    private function getGridExpression(string $column, float $gridCellSize, string $driver): string
    {
        return match ($driver) {
            'sqlite' => sprintf('ROUND(%s / %f) * %f', $column, $gridCellSize, $gridCellSize),
            default => sprintf('ROUND(%s / %f) * %f', $column, $gridCellSize, $gridCellSize),
        };
    }
}
