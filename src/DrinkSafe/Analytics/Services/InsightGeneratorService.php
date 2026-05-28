<?php

declare(strict_types=1);

namespace DrinkSafe\Analytics\Services;

/**
 * InsightGeneratorService
 *
 * Generates human-readable insights from temporal analytics data.
 * Analyses patterns in the day/time grid to produce actionable observations
 * such as peak reporting times, weekend vs weekday comparisons, and notable trends.
 */
final class InsightGeneratorService
{
    /**
     * Minimum threshold for peak percentage to be considered significant.
     */
    private const float PEAK_THRESHOLD_PERCENTAGE = 10.0;

    /**
     * Minimum total reports required to generate insights.
     */
    private const int MINIMUM_REPORTS_FOR_INSIGHTS = 10;

    /**
     * Generate insights from temporal analytics data.
     *
     * Analyses the grid data and totals to produce human-readable insights
     * about reporting patterns. Returns an array of insight strings.
     *
     * @param  array<int, array{day: string, time: string, count: int, percentage: float}>  $gridData  The 7x4 grid data
     * @param  array{by_day: array<string, int>, by_time: array<string, int>}  $totals  Aggregated totals
     * @param  int  $totalReports  Total number of reports in the dataset
     * @return array<int, string> Array of insight strings
     */
    public function generateInsights(array $gridData, array $totals, int $totalReports): array
    {
        if ($totalReports < self::MINIMUM_REPORTS_FOR_INSIGHTS) {
            return [];
        }

        $insights = [];

        $peakDayTimeInsight = $this->generatePeakDayTimeInsight($gridData, $totalReports);
        if ($peakDayTimeInsight !== null) {
            $insights[] = $peakDayTimeInsight;
        }

        $weekendInsight = $this->generateWeekendVsWeekdayInsight($totals['by_day'], $totalReports);
        if ($weekendInsight !== null) {
            $insights[] = $weekendInsight;
        }

        $timeOfDayInsight = $this->generateTimeOfDayInsight($totals['by_time'], $totalReports);
        if ($timeOfDayInsight !== null) {
            $insights[] = $timeOfDayInsight;
        }

        $lowestTimeInsight = $this->generateLowestTimeInsight($totals['by_time'], $totalReports);
        if ($lowestTimeInsight !== null) {
            $insights[] = $lowestTimeInsight;
        }

        return $insights;
    }

    /**
     * Generate insight about peak day/time combination.
     *
     * Identifies the single highest day+time cell and reports its percentage.
     *
     * @param  array<int, array{day: string, time: string, count: int, percentage: float}>  $gridData
     */
    private function generatePeakDayTimeInsight(array $gridData, int $totalReports): ?string
    {
        if ($totalReports === 0) {
            return null;
        }

        $peakCells = collect($gridData)
            ->sortByDesc('count')
            ->take(2);

        $topCell = $peakCells->first();

        if ($topCell === null || $topCell['percentage'] < self::PEAK_THRESHOLD_PERCENTAGE) {
            return null;
        }

        $secondCell = $peakCells->skip(1)->first();

        if ($secondCell !== null && $this->areSimilarDayTime($topCell, $secondCell)) {
            $combinedPercentage = round($topCell['percentage'] + $secondCell['percentage'], 0);

            return sprintf(
                'Peak reporting occurs %s and %s nights (%d%% of all reports)',
                $topCell['day'],
                $secondCell['day'],
                (int) $combinedPercentage,
            );
        }

        return sprintf(
            'Peak reporting occurs on %s %s (%s%% of all reports)',
            $topCell['day'],
            strtolower($topCell['time']),
            number_format($topCell['percentage'], 1),
        );
    }

    /**
     * Check if two cells represent similar patterns (same time of day).
     *
     * @param  array{day: string, time: string, count: int, percentage: float}  $cell1
     * @param  array{day: string, time: string, count: int, percentage: float}  $cell2
     */
    private function areSimilarDayTime(array $cell1, array $cell2): bool
    {
        return $cell1['time'] === $cell2['time'];
    }

    /**
     * Generate insight comparing weekend vs weekday reporting.
     *
     * Calculates the ratio of weekend to weekday reports and generates
     * an insight if there is a significant difference.
     *
     * @param  array<string, int>  $byDay  Report counts by day
     */
    private function generateWeekendVsWeekdayInsight(array $byDay, int $totalReports): ?string
    {
        if ($totalReports === 0) {
            return null;
        }

        $weekendCount = ($byDay['Saturday'] ?? 0) + ($byDay['Sunday'] ?? 0);
        $weekdayCount = $totalReports - $weekendCount;

        if ($weekdayCount === 0 || $weekendCount === 0) {
            return null;
        }

        $weekendDays = 2;
        $weekdayDays = 5;

        $avgWeekend = $weekendCount / $weekendDays;
        $avgWeekday = $weekdayCount / $weekdayDays;

        if ($avgWeekday === 0.0) {
            return null;
        }

        $ratio = $avgWeekend / $avgWeekday;

        if ($ratio >= 2.0) {
            return sprintf(
                'Weekend days see %.1fx more reports on average than weekdays',
                $ratio,
            );
        }

        if ($ratio <= 0.5) {
            $inverseRatio = 1 / $ratio;

            return sprintf(
                'Weekdays see %.1fx more reports on average than weekend days',
                $inverseRatio,
            );
        }

        return null;
    }

    /**
     * Generate insight about dominant time of day.
     *
     * Identifies if one time period accounts for a significant portion of reports.
     *
     * @param  array<string, int>  $byTime  Report counts by time of day
     */
    private function generateTimeOfDayInsight(array $byTime, int $totalReports): ?string
    {
        if ($totalReports === 0) {
            return null;
        }

        $sortedTimes = collect($byTime)->sortDesc();
        $topTime = $sortedTimes->keys()->first();
        $topCount = $sortedTimes->first();

        if ($topTime === null || $topCount === null) {
            return null;
        }

        $percentage = ($topCount / $totalReports) * 100;

        if ($percentage >= 40) {
            return sprintf(
                '%s is the most common reporting time (%d%% of all reports)',
                $topTime,
                (int) round($percentage),
            );
        }

        return null;
    }

    /**
     * Generate insight about lowest reporting time period.
     *
     * Highlights times with very few reports relative to the total.
     *
     * @param  array<string, int>  $byTime  Report counts by time of day
     */
    private function generateLowestTimeInsight(array $byTime, int $totalReports): ?string
    {
        if ($totalReports === 0) {
            return null;
        }

        $sortedTimes = collect($byTime)->sort();
        $lowestTime = $sortedTimes->keys()->first();
        $lowestCount = $sortedTimes->first();

        if ($lowestTime === null || $lowestCount === null) {
            return null;
        }

        $percentage = ($lowestCount / $totalReports) * 100;

        if ($percentage <= 5) {
            return sprintf(
                '%s incidents are rare (only %d%% of reports)',
                $lowestTime,
                (int) round($percentage),
            );
        }

        return null;
    }
}
