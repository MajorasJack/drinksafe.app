<?php

declare(strict_types=1);

namespace DrinkSafe\Analytics\Services;

use DrinkSafe\Analytics\Enums\DayOfWeek;
use DrinkSafe\Analytics\Exceptions\InsufficientDataException;
use DrinkSafe\Reports\Enums\TimeOfDay;
use DrinkSafe\Reports\Models\Report;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * TemporalAnalyticsService
 *
 * Aggregates report data by day-of-week and time-of-day for temporal pattern analysis.
 * Supports filtering by city, venue, and date range. Results are cached for performance.
 *
 * The service produces a 7x4 grid (7 days x 4 time periods) with report counts
 * and percentages, along with totals and auto-generated insights.
 */
final class TemporalAnalyticsService
{
    /**
     * Minimum reports required for meaningful analysis.
     */
    private const int MINIMUM_REPORTS_FOR_ANALYSIS = 10;

    /**
     * Cache TTL in seconds (6 hours).
     */
    private const int CACHE_TTL_SECONDS = 21600;

    /**
     * Cache key prefix for temporal analytics.
     */
    private const string CACHE_KEY_PREFIX = 'temporal_analytics:';

    /**
     * Create a new TemporalAnalyticsService instance.
     *
     * @param  InsightGeneratorService  $insightGenerator  Service for generating insights
     */
    public function __construct(
        private readonly InsightGeneratorService $insightGenerator,
    ) {}

    /**
     * Get temporal analytics data.
     *
     * Returns a comprehensive analytics result including:
     * - 7x4 grid (day of week x time of day) with counts and percentages
     * - Totals aggregated by day and by time
     * - Auto-generated human-readable insights
     * - Metadata about the query parameters
     *
     * @param  string|null  $city  Optional city filter
     * @param  string|null  $venueUuid  Optional venue UUID filter
     * @param  int  $periodDays  Number of days to analyse (default: 90)
     * @return array{
     *     grid: array<int, array{day: string, time: string, count: int, percentage: float}>,
     *     totals: array{by_day: array<string, int>, by_time: array<string, int>},
     *     insights: array<int, string>,
     *     meta: array{period_days: int, total_reports: int, city: string|null, venue_uuid: string|null}
     * }
     *
     * @throws InsufficientDataException If too few reports for meaningful analysis
     */
    public function getTemporalData(
        ?string $city = null,
        ?string $venueUuid = null,
        int $periodDays = 90,
    ): array {
        $cacheKey = $this->buildCacheKey($city, $venueUuid, $periodDays);

        $cachedResult = Cache::get($cacheKey);

        if ($cachedResult !== null) {
            return $cachedResult;
        }

        $result = $this->computeAnalytics($city, $venueUuid, $periodDays);

        Cache::put($cacheKey, $result, self::CACHE_TTL_SECONDS);

        return $result;
    }

    /**
     * Get aggregated totals by day of week only.
     *
     * @param  string|null  $city  Optional city filter
     * @param  int  $periodDays  Number of days to analyse
     * @return array<string, int> Counts keyed by day name
     */
    public function getTotalsByDay(?string $city = null, int $periodDays = 90): array
    {
        $analytics = $this->getTemporalData($city, null, $periodDays);

        return $analytics['totals']['by_day'];
    }

    /**
     * Get aggregated totals by time of day only.
     *
     * @param  string|null  $city  Optional city filter
     * @param  int  $periodDays  Number of days to analyse
     * @return array<string, int> Counts keyed by time period name
     */
    public function getTotalsByTime(?string $city = null, int $periodDays = 90): array
    {
        $analytics = $this->getTemporalData($city, null, $periodDays);

        return $analytics['totals']['by_time'];
    }

    /**
     * Invalidate all analytics cache entries.
     *
     * Should be called when new reports are submitted to ensure
     * fresh data on next request.
     */
    public function invalidateCache(): void
    {
        Cache::flush();
    }

    /**
     * Invalidate cache for a specific filter combination.
     *
     * @param  string|null  $city  City filter
     * @param  string|null  $venueUuid  Venue UUID filter
     * @param  int  $periodDays  Period in days
     */
    public function invalidateCacheForFilters(
        ?string $city,
        ?string $venueUuid,
        int $periodDays,
    ): void {
        $cacheKey = $this->buildCacheKey($city, $venueUuid, $periodDays);
        Cache::forget($cacheKey);
    }

    /**
     * Build cache key from filter parameters.
     *
     * @param  string|null  $city  City filter
     * @param  string|null  $venueUuid  Venue UUID filter
     * @param  int  $periodDays  Period in days
     */
    private function buildCacheKey(?string $city, ?string $venueUuid, int $periodDays): string
    {
        return sprintf(
            '%s%s:%s:%d',
            self::CACHE_KEY_PREFIX,
            $city ?? 'all',
            $venueUuid ?? 'all',
            $periodDays,
        );
    }

    /**
     * Compute analytics data from database.
     *
     * @param  string|null  $city  City filter
     * @param  string|null  $venueUuid  Venue UUID filter
     * @param  int  $periodDays  Period in days
     * @return array{
     *     grid: array<int, array{day: string, time: string, count: int, percentage: float}>,
     *     totals: array{by_day: array<string, int>, by_time: array<string, int>},
     *     insights: array<int, string>,
     *     meta: array{period_days: int, total_reports: int, city: string|null, venue_uuid: string|null}
     * }
     *
     * @throws InsufficientDataException
     */
    private function computeAnalytics(?string $city, ?string $venueUuid, int $periodDays): array
    {
        $query = Report::query()
            ->join('venues', 'reports.venue_uuid', '=', 'venues.uuid')
            ->where('reports.incident_date', '>=', now()->subDays($periodDays))
            ->whereNull('reports.deleted_at');

        if ($city !== null) {
            $query->where('venues.city', $city);
        }

        if ($venueUuid !== null) {
            $query->where('reports.venue_uuid', $venueUuid);
        }

        $dayOfWeekExpression = $this->getDayOfWeekExpression();

        $rawData = $query
            ->selectRaw(sprintf('%s AS day_of_week, reports.time_of_day, COUNT(*) AS report_count', $dayOfWeekExpression))
            ->groupBy('day_of_week', 'time_of_day')
            ->get();

        $totalReports = (int) $rawData->sum('report_count');

        if ($totalReports < self::MINIMUM_REPORTS_FOR_ANALYSIS) {
            throw InsufficientDataException::create(
                self::MINIMUM_REPORTS_FOR_ANALYSIS,
                $totalReports,
            );
        }

        $grid = $this->buildGrid($rawData, $totalReports);
        $totals = $this->calculateTotals($rawData);
        $insights = $this->insightGenerator->generateInsights($grid, $totals, $totalReports);

        return [
            'grid' => $grid,
            'totals' => $totals,
            'insights' => $insights,
            'meta' => [
                'period_days' => $periodDays,
                'total_reports' => $totalReports,
                'city' => $city,
                'venue_uuid' => $venueUuid,
            ],
        ];
    }

    /**
     * Build the 7x4 grid from raw aggregated data.
     *
     * Creates a cell for each day/time combination with count and percentage.
     *
     * @param  Collection<int, object>  $rawData  Raw query results
     * @param  int  $totalReports  Total reports for percentage calculation
     * @return array<int, array{day: string, time: string, count: int, percentage: float}>
     */
    private function buildGrid(Collection $rawData, int $totalReports): array
    {
        $grid = [];
        $daysOfWeek = DayOfWeek::cases();
        $timesOfDay = [TimeOfDay::Morning, TimeOfDay::Afternoon, TimeOfDay::Evening, TimeOfDay::Night];

        foreach ($daysOfWeek as $day) {
            foreach ($timesOfDay as $time) {
                $matchingRow = $rawData
                    ->where('day_of_week', $day->mysqlDayNumber())
                    ->where('time_of_day', $time->value)
                    ->first();

                $count = $matchingRow !== null ? (int) $matchingRow->report_count : 0;

                $percentage = $totalReports > 0
                    ? round(($count / $totalReports) * 100, 1)
                    : 0.0;

                $grid[] = [
                    'day' => $day->toLabel(),
                    'time' => $time->label(),
                    'count' => $count,
                    'percentage' => $percentage,
                ];
            }
        }

        return $grid;
    }

    /**
     * Get the database-specific expression for day of week.
     *
     * Returns a SQL expression that produces consistent day numbers:
     * 1=Sunday, 2=Monday, 3=Tuesday, 4=Wednesday, 5=Thursday, 6=Friday, 7=Saturday
     *
     * MySQL uses DAYOFWEEK() which returns 1-7 (1=Sunday).
     * SQLite uses strftime('%w') which returns 0-6 (0=Sunday), so we add 1.
     */
    private function getDayOfWeekExpression(): string
    {
        $driver = DB::connection()->getDriverName();

        return match ($driver) {
            'sqlite' => "(CAST(strftime('%w', reports.incident_date) AS INTEGER) + 1)",
            default => 'DAYOFWEEK(reports.incident_date)',
        };
    }

    /**
     * Calculate totals by day and by time.
     *
     * @param  Collection<int, object>  $rawData  Raw query results
     * @return array{by_day: array<string, int>, by_time: array<string, int>}
     */
    private function calculateTotals(Collection $rawData): array
    {
        $byDay = [];
        foreach (DayOfWeek::cases() as $day) {
            $byDay[$day->toLabel()] = (int) $rawData
                ->where('day_of_week', $day->mysqlDayNumber())
                ->sum('report_count');
        }

        $byTime = [];
        $timesOfDay = [TimeOfDay::Morning, TimeOfDay::Afternoon, TimeOfDay::Evening, TimeOfDay::Night];
        foreach ($timesOfDay as $time) {
            $byTime[$time->label()] = (int) $rawData
                ->where('time_of_day', $time->value)
                ->sum('report_count');
        }

        return [
            'by_day' => $byDay,
            'by_time' => $byTime,
        ];
    }
}
