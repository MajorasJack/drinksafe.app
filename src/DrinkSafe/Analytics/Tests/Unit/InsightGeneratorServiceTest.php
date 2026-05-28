<?php

declare(strict_types=1);

use DrinkSafe\Analytics\Services\InsightGeneratorService;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    $this->service = new InsightGeneratorService;
});

describe('InsightGeneratorService', function (): void {
    describe('generateInsights', function (): void {
        it('returns empty array when total reports below minimum threshold', function (): void {
            $gridData = buildGridWithPeaks(['Friday' => ['Night' => 5]]);
            $totals = buildTotals($gridData);

            $insights = $this->service->generateInsights($gridData, $totals, 5);

            expect($insights)->toBeArray()->toBeEmpty();
        });

        it('returns array of strings when sufficient data exists', function (): void {
            $gridData = buildGridWithPeaks([
                'Friday' => ['Night' => 30],
                'Saturday' => ['Night' => 25],
                'Monday' => ['Morning' => 5],
            ]);
            $totals = buildTotals($gridData);
            $total = 60;

            $insights = $this->service->generateInsights($gridData, $totals, $total);

            expect($insights)->toBeArray();
            foreach ($insights as $insight) {
                expect($insight)->toBeString();
            }
        });
    });

    describe('peak day/time insight', function (): void {
        it('identifies peak day and time combination', function (): void {
            $gridData = buildGridWithPeaks([
                'Friday' => ['Night' => 40],
                'Saturday' => ['Night' => 30],
                'Monday' => ['Morning' => 5],
                'Tuesday' => ['Afternoon' => 5],
            ]);
            $totals = buildTotals($gridData);
            $total = 80;

            $insights = $this->service->generateInsights($gridData, $totals, $total);

            $hasPeakInsight = collect($insights)->contains(
                fn (string $insight): bool => str_contains($insight, 'Peak reporting')
            );

            expect($hasPeakInsight)->toBeTrue();
        });

        it('combines similar peak times into single insight', function (): void {
            $gridData = buildGridWithPeaks([
                'Friday' => ['Night' => 25],
                'Saturday' => ['Night' => 20],
                'Sunday' => ['Morning' => 5],
            ]);
            $totals = buildTotals($gridData);
            $total = 50;

            $insights = $this->service->generateInsights($gridData, $totals, $total);

            $combinedInsight = collect($insights)->first(
                fn (string $insight): bool => str_contains($insight, 'Friday') && str_contains($insight, 'Saturday')
            );

            expect($combinedInsight)->not->toBeNull();
        });
    });

    describe('weekend vs weekday insight', function (): void {
        it('generates insight when weekends have significantly more reports', function (): void {
            $gridData = buildGridWithPeaks([
                'Saturday' => ['Night' => 40],
                'Sunday' => ['Night' => 30],
                'Monday' => ['Evening' => 5],
                'Tuesday' => ['Evening' => 5],
                'Wednesday' => ['Evening' => 5],
                'Thursday' => ['Evening' => 5],
                'Friday' => ['Evening' => 10],
            ]);
            $totals = buildTotals($gridData);
            $total = 100;

            $insights = $this->service->generateInsights($gridData, $totals, $total);

            $hasWeekendInsight = collect($insights)->contains(
                fn (string $insight): bool => str_contains(strtolower($insight), 'weekend')
            );

            expect($hasWeekendInsight)->toBeTrue();
        });

        it('includes ratio in weekend insight', function (): void {
            $gridData = buildGridWithPeaks([
                'Saturday' => ['Night' => 40],
                'Sunday' => ['Night' => 40],
                'Monday' => ['Evening' => 4],
                'Tuesday' => ['Evening' => 4],
                'Wednesday' => ['Evening' => 4],
                'Thursday' => ['Evening' => 4],
                'Friday' => ['Evening' => 4],
            ]);
            $totals = buildTotals($gridData);
            $total = 100;

            $insights = $this->service->generateInsights($gridData, $totals, $total);

            $weekendInsight = collect($insights)->first(
                fn (string $insight): bool => str_contains(strtolower($insight), 'weekend')
            );

            expect($weekendInsight)->toContain('x more');
        });
    });

    describe('time of day insight', function (): void {
        it('generates insight when one time period dominates', function (): void {
            $gridData = buildGridWithPeaks([
                'Friday' => ['Night' => 30],
                'Saturday' => ['Night' => 30],
                'Sunday' => ['Night' => 10],
                'Monday' => ['Morning' => 5],
            ]);
            $totals = buildTotals($gridData);
            $total = 75;

            $insights = $this->service->generateInsights($gridData, $totals, $total);

            $hasTimeInsight = collect($insights)->contains(
                fn (string $insight): bool => str_contains($insight, 'most common reporting time')
            );

            expect($hasTimeInsight)->toBeTrue();
        });
    });

    describe('lowest time insight', function (): void {
        it('generates insight when one time period has very few reports', function (): void {
            $gridData = buildGridWithPeaks([
                'Friday' => ['Night' => 40],
                'Saturday' => ['Night' => 40],
                'Sunday' => ['Evening' => 15],
                'Monday' => ['Morning' => 2],
            ]);
            $totals = buildTotals($gridData);
            $total = 100;

            $insights = $this->service->generateInsights($gridData, $totals, $total);

            $hasRareInsight = collect($insights)->contains(
                fn (string $insight): bool => str_contains(strtolower($insight), 'rare')
            );

            expect($hasRareInsight)->toBeTrue();
        });
    });
});

/**
 * Helper function to build grid data with specific peak values.
 *
 * @param  array<string, array<string, int>>  $peaks  Day => Time => Count mapping
 * @return array<int, array{day: string, time: string, count: int, percentage: float}>
 */
function buildGridWithPeaks(array $peaks): array
{
    $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    $times = ['Morning', 'Afternoon', 'Evening', 'Night'];

    $grid = [];
    $totalCount = 0;

    foreach ($days as $day) {
        foreach ($times as $time) {
            $count = $peaks[$day][$time] ?? 0;
            $totalCount += $count;
            $grid[] = [
                'day' => $day,
                'time' => $time,
                'count' => $count,
                'percentage' => 0.0,
            ];
        }
    }

    foreach ($grid as $index => $cell) {
        $grid[$index]['percentage'] = $totalCount > 0
            ? round(($cell['count'] / $totalCount) * 100, 1)
            : 0.0;
    }

    return $grid;
}

/**
 * Helper function to build totals from grid data.
 *
 * @param  array<int, array{day: string, time: string, count: int, percentage: float}>  $gridData
 * @return array{by_day: array<string, int>, by_time: array<string, int>}
 */
function buildTotals(array $gridData): array
{
    $byDay = [];
    $byTime = [];

    foreach ($gridData as $cell) {
        $byDay[$cell['day']] = ($byDay[$cell['day']] ?? 0) + $cell['count'];
        $byTime[$cell['time']] = ($byTime[$cell['time']] ?? 0) + $cell['count'];
    }

    return [
        'by_day' => $byDay,
        'by_time' => $byTime,
    ];
}
