<?php

declare(strict_types=1);

use DrinkSafe\Heatmap\Exceptions\InvalidBoundingBoxException;
use DrinkSafe\Heatmap\Services\HeatmapService;
use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Venues\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = new HeatmapService;
});

describe('HeatmapService', function (): void {
    describe('getHeatmapData', function (): void {
        it('returns empty points when no reports exist', function (): void {
            $result = $this->service->getHeatmapData(
                north: 51.6,
                south: 51.4,
                east: -0.0,
                west: -0.3,
            );

            expect($result)->toBeArray()
                ->and($result['points'])->toBeArray()
                ->and($result['points'])->toBeEmpty()
                ->and($result['total_reports'])->toBe(0);
        });

        it('returns heatmap data when sufficient reports exist in bounds', function (): void {
            $venue = Venue::factory()->create([
                'latitude' => 51.5,
                'longitude' => -0.1,
            ]);
            Report::factory()->count(5)->for($venue, 'venue')->daysAgo(15)->create();

            $result = $this->service->getHeatmapData(
                north: 51.6,
                south: 51.4,
                east: -0.0,
                west: -0.2,
            );

            expect($result)->toBeArray()
                ->and($result)->toHaveKeys(['points', 'period_days', 'total_reports'])
                ->and($result['total_reports'])->toBe(5);
        });

        it('returns points with correct structure', function (): void {
            $venue = Venue::factory()->create([
                'latitude' => 51.5,
                'longitude' => -0.1,
            ]);
            Report::factory()->count(5)->for($venue, 'venue')->daysAgo(15)->create();

            $result = $this->service->getHeatmapData(
                north: 51.6,
                south: 51.4,
                east: -0.0,
                west: -0.2,
            );

            $firstPoint = $result['points'][0];

            expect($firstPoint)->toHaveKeys(['lat', 'lng', 'intensity'])
                ->and($firstPoint['lat'])->toBeFloat()
                ->and($firstPoint['lng'])->toBeFloat()
                ->and($firstPoint['intensity'])->toBeFloat()
                ->and($firstPoint['intensity'])->toBeGreaterThanOrEqual(0.0)
                ->and($firstPoint['intensity'])->toBeLessThanOrEqual(1.0);
        });

        it('excludes venues outside bounding box', function (): void {
            $insideVenue = Venue::factory()->create([
                'latitude' => 51.5,
                'longitude' => -0.1,
            ]);
            $outsideVenue = Venue::factory()->create([
                'latitude' => 52.5,
                'longitude' => -1.5,
            ]);

            Report::factory()->count(5)->for($insideVenue, 'venue')->daysAgo(15)->create();
            Report::factory()->count(5)->for($outsideVenue, 'venue')->daysAgo(15)->create();

            $result = $this->service->getHeatmapData(
                north: 51.6,
                south: 51.4,
                east: -0.0,
                west: -0.2,
            );

            expect($result['total_reports'])->toBe(5);
        });
    });

    describe('privacy threshold', function (): void {
        it('excludes grid cells with fewer than 3 reports', function (): void {
            $venue = Venue::factory()->create([
                'latitude' => 51.5,
                'longitude' => -0.1,
            ]);
            Report::factory()->count(2)->for($venue, 'venue')->daysAgo(15)->create();

            $result = $this->service->getHeatmapData(
                north: 51.6,
                south: 51.4,
                east: -0.0,
                west: -0.2,
            );

            expect($result['points'])->toBeEmpty()
                ->and($result['total_reports'])->toBe(0);
        });

        it('includes grid cells with 3 or more reports', function (): void {
            $venue = Venue::factory()->create([
                'latitude' => 51.5,
                'longitude' => -0.1,
            ]);
            Report::factory()->count(3)->for($venue, 'venue')->daysAgo(15)->create();

            $result = $this->service->getHeatmapData(
                north: 51.6,
                south: 51.4,
                east: -0.0,
                west: -0.2,
            );

            expect($result['points'])->not->toBeEmpty()
                ->and($result['total_reports'])->toBe(3);
        });
    });

    describe('time period filtering', function (): void {
        it('filters by 7 day period', function (): void {
            $venue = Venue::factory()->create([
                'latitude' => 51.5,
                'longitude' => -0.1,
            ]);
            Report::factory()->count(3)->for($venue, 'venue')->daysAgo(3)->create();
            Report::factory()->count(3)->for($venue, 'venue')->daysAgo(15)->create();

            $result = $this->service->getHeatmapData(
                north: 51.6,
                south: 51.4,
                east: -0.0,
                west: -0.2,
                periodDays: 7,
            );

            expect($result['period_days'])->toBe(7)
                ->and($result['total_reports'])->toBe(3);
        });

        it('filters by 30 day period', function (): void {
            $venue = Venue::factory()->create([
                'latitude' => 51.5,
                'longitude' => -0.1,
            ]);
            Report::factory()->count(3)->for($venue, 'venue')->daysAgo(15)->create();
            Report::factory()->count(3)->for($venue, 'venue')->daysAgo(60)->create();

            $result = $this->service->getHeatmapData(
                north: 51.6,
                south: 51.4,
                east: -0.0,
                west: -0.2,
                periodDays: 30,
            );

            expect($result['period_days'])->toBe(30)
                ->and($result['total_reports'])->toBe(3);
        });

        it('filters by 90 day period', function (): void {
            $venue = Venue::factory()->create([
                'latitude' => 51.5,
                'longitude' => -0.1,
            ]);
            Report::factory()->count(3)->for($venue, 'venue')->daysAgo(60)->create();
            Report::factory()->count(3)->for($venue, 'venue')->daysAgo(120)->create();

            $result = $this->service->getHeatmapData(
                north: 51.6,
                south: 51.4,
                east: -0.0,
                west: -0.2,
                periodDays: 90,
            );

            expect($result['period_days'])->toBe(90)
                ->and($result['total_reports'])->toBe(3);
        });

        it('filters by 365 day period', function (): void {
            $venue = Venue::factory()->create([
                'latitude' => 51.5,
                'longitude' => -0.1,
            ]);
            Report::factory()->count(3)->for($venue, 'venue')->daysAgo(200)->create();
            Report::factory()->count(3)->for($venue, 'venue')->daysAgo(400)->create();

            $result = $this->service->getHeatmapData(
                north: 51.6,
                south: 51.4,
                east: -0.0,
                west: -0.2,
                periodDays: 365,
            );

            expect($result['period_days'])->toBe(365)
                ->and($result['total_reports'])->toBe(3);
        });

        it('defaults to 30 day period', function (): void {
            $venue = Venue::factory()->create([
                'latitude' => 51.5,
                'longitude' => -0.1,
            ]);
            Report::factory()->count(3)->for($venue, 'venue')->daysAgo(15)->create();

            $result = $this->service->getHeatmapData(
                north: 51.6,
                south: 51.4,
                east: -0.0,
                west: -0.2,
            );

            expect($result['period_days'])->toBe(30);
        });
    });

    describe('intensity calculation', function (): void {
        it('normalises intensity to maximum of 1.0', function (): void {
            $venue = Venue::factory()->create([
                'latitude' => 51.5,
                'longitude' => -0.1,
            ]);
            Report::factory()->count(50)->for($venue, 'venue')->daysAgo(15)->create();

            $result = $this->service->getHeatmapData(
                north: 51.6,
                south: 51.4,
                east: -0.0,
                west: -0.2,
            );

            $maxIntensity = collect($result['points'])->max('intensity');

            expect($maxIntensity)->toBeLessThanOrEqual(1.0);
        });

        it('returns proportional intensity for varying report counts', function (): void {
            $lowDensityVenue = Venue::factory()->create([
                'latitude' => 51.51,
                'longitude' => -0.11,
            ]);
            $highDensityVenue = Venue::factory()->create([
                'latitude' => 51.52,
                'longitude' => -0.12,
            ]);

            Report::factory()->count(5)->for($lowDensityVenue, 'venue')->daysAgo(15)->create();
            Report::factory()->count(15)->for($highDensityVenue, 'venue')->daysAgo(15)->create();

            $result = $this->service->getHeatmapData(
                north: 51.6,
                south: 51.4,
                east: -0.0,
                west: -0.2,
            );

            expect(count($result['points']))->toBeGreaterThanOrEqual(2);

            $intensities = collect($result['points'])->pluck('intensity')->sort()->values();

            expect($intensities->first())->toBeLessThan($intensities->last());
        });
    });

    describe('bounding box validation', function (): void {
        it('throws exception for invalid latitude', function (): void {
            $this->service->getHeatmapData(
                north: 100.0,
                south: 51.4,
                east: -0.0,
                west: -0.2,
            );
        })->throws(InvalidBoundingBoxException::class, 'Latitude must be between -90 and 90');

        it('throws exception for invalid longitude', function (): void {
            $this->service->getHeatmapData(
                north: 51.6,
                south: 51.4,
                east: 200.0,
                west: -0.2,
            );
        })->throws(InvalidBoundingBoxException::class, 'Longitude must be between -180 and 180');

        it('throws exception for inverted bounds', function (): void {
            $this->service->getHeatmapData(
                north: 51.4,
                south: 51.6,
                east: -0.0,
                west: -0.2,
            );
        })->throws(InvalidBoundingBoxException::class, 'South latitude must be less than north latitude');
    });

    describe('caching', function (): void {
        it('caches the computed result', function (): void {
            $venue = Venue::factory()->create([
                'latitude' => 51.5,
                'longitude' => -0.1,
            ]);
            Report::factory()->count(5)->for($venue, 'venue')->daysAgo(15)->create();

            $this->service->getHeatmapData(
                north: 51.6,
                south: 51.4,
                east: 0.0,
                west: -0.2,
            );

            expect(Cache::has('heatmap:51.6:51.4:0:-0.2:30'))->toBeTrue();
        });

        it('returns cached result on subsequent calls', function (): void {
            $venue = Venue::factory()->create([
                'latitude' => 51.5,
                'longitude' => -0.1,
            ]);
            Report::factory()->count(5)->for($venue, 'venue')->daysAgo(15)->create();

            $firstResult = $this->service->getHeatmapData(
                north: 51.6,
                south: 51.4,
                east: 0.0,
                west: -0.2,
            );

            Report::factory()->count(10)->for($venue, 'venue')->daysAgo(1)->create();

            $secondResult = $this->service->getHeatmapData(
                north: 51.6,
                south: 51.4,
                east: 0.0,
                west: -0.2,
            );

            expect($secondResult['total_reports'])->toBe($firstResult['total_reports']);
        });

        it('uses different cache keys for different bounds', function (): void {
            $venue = Venue::factory()->create([
                'latitude' => 51.5,
                'longitude' => -0.1,
            ]);
            Report::factory()->count(5)->for($venue, 'venue')->daysAgo(15)->create();

            $this->service->getHeatmapData(north: 51.6, south: 51.4, east: 0.0, west: -0.2, periodDays: 30);
            $this->service->getHeatmapData(north: 51.7, south: 51.5, east: 0.0, west: -0.2, periodDays: 30);

            expect(Cache::has('heatmap:51.6:51.4:0:-0.2:30'))->toBeTrue()
                ->and(Cache::has('heatmap:51.7:51.5:0:-0.2:30'))->toBeTrue();
        });

        it('uses different cache keys for different periods', function (): void {
            $venue = Venue::factory()->create([
                'latitude' => 51.5,
                'longitude' => -0.1,
            ]);
            Report::factory()->count(5)->for($venue, 'venue')->daysAgo(15)->create();

            $this->service->getHeatmapData(north: 51.6, south: 51.4, east: 0.0, west: -0.2, periodDays: 7);
            $this->service->getHeatmapData(north: 51.6, south: 51.4, east: 0.0, west: -0.2, periodDays: 30);

            expect(Cache::has('heatmap:51.6:51.4:0:-0.2:7'))->toBeTrue()
                ->and(Cache::has('heatmap:51.6:51.4:0:-0.2:30'))->toBeTrue();
        });
    });

    describe('invalidateCacheForBounds', function (): void {
        it('removes specific cache entry', function (): void {
            $venue = Venue::factory()->create([
                'latitude' => 51.5,
                'longitude' => -0.1,
            ]);
            Report::factory()->count(5)->for($venue, 'venue')->daysAgo(15)->create();

            $this->service->getHeatmapData(north: 51.6, south: 51.4, east: 0.0, west: -0.2, periodDays: 30);

            expect(Cache::has('heatmap:51.6:51.4:0:-0.2:30'))->toBeTrue();

            $this->service->invalidateCacheForBounds(51.6, 51.4, 0.0, -0.2, 30);

            expect(Cache::has('heatmap:51.6:51.4:0:-0.2:30'))->toBeFalse();
        });
    });

    describe('grid-based aggregation', function (): void {
        it('aggregates nearby venues into single grid cell', function (): void {
            $venue1 = Venue::factory()->create([
                'latitude' => 51.5001,
                'longitude' => -0.1001,
            ]);
            $venue2 = Venue::factory()->create([
                'latitude' => 51.5002,
                'longitude' => -0.1002,
            ]);

            Report::factory()->count(2)->for($venue1, 'venue')->daysAgo(15)->create();
            Report::factory()->count(2)->for($venue2, 'venue')->daysAgo(15)->create();

            $result = $this->service->getHeatmapData(
                north: 51.6,
                south: 51.4,
                east: 0.0,
                west: -0.2,
            );

            expect($result['total_reports'])->toBe(4)
                ->and(count($result['points']))->toBe(1);
        });
    });
});
