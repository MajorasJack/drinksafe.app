<?php

declare(strict_types=1);

use DrinkSafe\Analytics\Exceptions\InsufficientDataException;
use DrinkSafe\Analytics\Services\InsightGeneratorService;
use DrinkSafe\Analytics\Services\TemporalAnalyticsService;
use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Venues\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->insightGenerator = new InsightGeneratorService;
    $this->service = new TemporalAnalyticsService($this->insightGenerator);
});

describe('TemporalAnalyticsService', function (): void {
    describe('getTemporalData', function (): void {
        it('throws InsufficientDataException when fewer than minimum reports exist', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(5)->for($venue, 'venue')->daysAgo(15)->create();

            $this->service->getTemporalData();
        })->throws(InsufficientDataException::class);

        it('returns analytics data when sufficient reports exist', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(15)->for($venue, 'venue')->daysAgo(15)->create();

            $result = $this->service->getTemporalData();

            expect($result)->toBeArray()
                ->and($result)->toHaveKeys(['grid', 'totals', 'insights', 'meta']);
        });

        it('returns 28 grid cells representing 7 days x 4 time periods', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(20)->for($venue, 'venue')->daysAgo(15)->create();

            $result = $this->service->getTemporalData();

            expect($result['grid'])->toHaveCount(28);
        });

        it('returns grid cells with correct structure', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(20)->for($venue, 'venue')->daysAgo(15)->create();

            $result = $this->service->getTemporalData();

            $firstCell = $result['grid'][0];

            expect($firstCell)->toHaveKeys(['day', 'time', 'count', 'percentage'])
                ->and($firstCell['day'])->toBeString()
                ->and($firstCell['time'])->toBeString()
                ->and($firstCell['count'])->toBeInt()
                ->and($firstCell['percentage'])->toBeFloat();
        });

        it('returns totals by day for all seven days', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(20)->for($venue, 'venue')->daysAgo(15)->create();

            $result = $this->service->getTemporalData();

            expect($result['totals']['by_day'])->toHaveCount(7)
                ->and($result['totals']['by_day'])->toHaveKeys([
                    'Sunday', 'Monday', 'Tuesday', 'Wednesday',
                    'Thursday', 'Friday', 'Saturday',
                ]);
        });

        it('returns totals by time for all four time periods', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(20)->for($venue, 'venue')->daysAgo(15)->create();

            $result = $this->service->getTemporalData();

            expect($result['totals']['by_time'])->toHaveCount(4)
                ->and($result['totals']['by_time'])->toHaveKeys([
                    'Morning', 'Afternoon', 'Evening', 'Night',
                ]);
        });

        it('returns metadata with query parameters', function (): void {
            $venue = Venue::factory()->create(['city' => 'London']);
            Report::factory()->count(20)->for($venue, 'venue')->daysAgo(15)->create();

            $result = $this->service->getTemporalData(city: 'London', periodDays: 30);

            expect($result['meta'])->toHaveKeys(['period_days', 'total_reports', 'city', 'venue_uuid'])
                ->and($result['meta']['period_days'])->toBe(30)
                ->and($result['meta']['city'])->toBe('London')
                ->and($result['meta']['total_reports'])->toBeGreaterThanOrEqual(10);
        });

        it('calculates non-zero percentages for cells with reports', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(20)->for($venue, 'venue')->daysAgo(15)->create();

            $result = $this->service->getTemporalData();

            $cellsWithReports = collect($result['grid'])->filter(
                fn (array $cell): bool => $cell['count'] > 0
            );

            $allHavePercentages = $cellsWithReports->every(
                fn (array $cell): bool => $cell['percentage'] > 0.0
            );

            expect($allHavePercentages)->toBeTrue();
        });
    });

    describe('city filtering', function (): void {
        it('filters results by city', function (): void {
            $londonVenue = Venue::factory()->create(['city' => 'London']);
            $manchesterVenue = Venue::factory()->create(['city' => 'Manchester']);

            Report::factory()->count(15)->for($londonVenue, 'venue')->daysAgo(15)->create();
            Report::factory()->count(10)->for($manchesterVenue, 'venue')->daysAgo(15)->create();

            $londonResult = $this->service->getTemporalData(city: 'London');

            expect($londonResult['meta']['city'])->toBe('London')
                ->and($londonResult['meta']['total_reports'])->toBe(15);
        });

        it('throws InsufficientDataException when filtered city has too few reports', function (): void {
            $londonVenue = Venue::factory()->create(['city' => 'London']);
            $manchesterVenue = Venue::factory()->create(['city' => 'Manchester']);

            Report::factory()->count(15)->for($londonVenue, 'venue')->daysAgo(15)->create();
            Report::factory()->count(5)->for($manchesterVenue, 'venue')->daysAgo(15)->create();

            $this->service->getTemporalData(city: 'Manchester');
        })->throws(InsufficientDataException::class);

        it('returns all cities data when city is null', function (): void {
            $londonVenue = Venue::factory()->create(['city' => 'London']);
            $manchesterVenue = Venue::factory()->create(['city' => 'Manchester']);

            Report::factory()->count(10)->for($londonVenue, 'venue')->daysAgo(15)->create();
            Report::factory()->count(10)->for($manchesterVenue, 'venue')->daysAgo(15)->create();

            $result = $this->service->getTemporalData(city: null);

            expect($result['meta']['city'])->toBeNull()
                ->and($result['meta']['total_reports'])->toBe(20);
        });
    });

    describe('period filtering', function (): void {
        it('filters by 30 day period', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(10)->for($venue, 'venue')->daysAgo(15)->create();
            Report::factory()->count(10)->for($venue, 'venue')->daysAgo(60)->create();

            $result = $this->service->getTemporalData(periodDays: 30);

            expect($result['meta']['period_days'])->toBe(30)
                ->and($result['meta']['total_reports'])->toBe(10);
        });

        it('filters by 90 day period', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(10)->for($venue, 'venue')->daysAgo(15)->create();
            Report::factory()->count(10)->for($venue, 'venue')->daysAgo(60)->create();
            Report::factory()->count(10)->for($venue, 'venue')->daysAgo(120)->create();

            $result = $this->service->getTemporalData(periodDays: 90);

            expect($result['meta']['period_days'])->toBe(90)
                ->and($result['meta']['total_reports'])->toBe(20);
        });

        it('filters by 365 day period', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(10)->for($venue, 'venue')->daysAgo(15)->create();
            Report::factory()->count(10)->for($venue, 'venue')->daysAgo(200)->create();
            Report::factory()->count(10)->for($venue, 'venue')->daysAgo(400)->create();

            $result = $this->service->getTemporalData(periodDays: 365);

            expect($result['meta']['period_days'])->toBe(365)
                ->and($result['meta']['total_reports'])->toBe(20);
        });
    });

    describe('venue filtering', function (): void {
        it('filters results by specific venue UUID', function (): void {
            $venue1 = Venue::factory()->create();
            $venue2 = Venue::factory()->create();

            Report::factory()->count(15)->for($venue1, 'venue')->daysAgo(15)->create();
            Report::factory()->count(10)->for($venue2, 'venue')->daysAgo(15)->create();

            $result = $this->service->getTemporalData(venueUuid: $venue1->uuid);

            expect($result['meta']['venue_uuid'])->toBe($venue1->uuid)
                ->and($result['meta']['total_reports'])->toBe(15);
        });
    });

    describe('caching', function (): void {
        it('caches the computed result', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(20)->for($venue, 'venue')->daysAgo(15)->create();

            $this->service->getTemporalData();

            expect(Cache::has('temporal_analytics:all:all:90'))->toBeTrue();
        });

        it('returns cached result on subsequent calls', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(20)->for($venue, 'venue')->daysAgo(15)->create();

            $firstResult = $this->service->getTemporalData();

            Report::factory()->count(10)->for($venue, 'venue')->daysAgo(1)->create();

            $secondResult = $this->service->getTemporalData();

            expect($secondResult['meta']['total_reports'])->toBe($firstResult['meta']['total_reports']);
        });

        it('uses different cache keys for different filter combinations', function (): void {
            $venue = Venue::factory()->create(['city' => 'London']);
            Report::factory()->count(20)->for($venue, 'venue')->daysAgo(15)->create();

            $this->service->getTemporalData(city: 'London', periodDays: 30);
            $this->service->getTemporalData(city: 'London', periodDays: 90);

            expect(Cache::has('temporal_analytics:London:all:30'))->toBeTrue()
                ->and(Cache::has('temporal_analytics:London:all:90'))->toBeTrue();
        });
    });

    describe('getTotalsByDay', function (): void {
        it('returns totals by day of week only', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(20)->for($venue, 'venue')->daysAgo(15)->create();

            $result = $this->service->getTotalsByDay();

            expect($result)->toHaveKeys([
                'Sunday', 'Monday', 'Tuesday', 'Wednesday',
                'Thursday', 'Friday', 'Saturday',
            ]);
        });
    });

    describe('getTotalsByTime', function (): void {
        it('returns totals by time of day only', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(20)->for($venue, 'venue')->daysAgo(15)->create();

            $result = $this->service->getTotalsByTime();

            expect($result)->toHaveKeys(['Morning', 'Afternoon', 'Evening', 'Night']);
        });
    });

    describe('invalidateCacheForFilters', function (): void {
        it('removes specific cache entry', function (): void {
            $venue = Venue::factory()->create(['city' => 'London']);
            Report::factory()->count(20)->for($venue, 'venue')->daysAgo(15)->create();

            $this->service->getTemporalData(city: 'London', periodDays: 30);

            expect(Cache::has('temporal_analytics:London:all:30'))->toBeTrue();

            $this->service->invalidateCacheForFilters('London', null, 30);

            expect(Cache::has('temporal_analytics:London:all:30'))->toBeFalse();
        });
    });
});
