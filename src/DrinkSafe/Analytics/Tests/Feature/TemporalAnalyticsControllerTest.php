<?php

declare(strict_types=1);

use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Venues\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('TemporalAnalyticsController', function (): void {
    describe('__invoke', function (): void {
        it('returns analytics data when sufficient reports exist', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(15)->for($venue, 'venue')->daysAgo(15)->create();

            $response = $this->getJson(route('api.analytics.temporal'));

            $response->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        'grid',
                        'totals' => [
                            'by_day',
                            'by_time',
                        ],
                        'insights',
                        'meta' => [
                            'period_days',
                            'total_reports',
                            'city',
                            'venue_uuid',
                        ],
                    ],
                ]);
        });

        it('returns 28 grid cells for 7 days x 4 time periods', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(20)->for($venue, 'venue')->daysAgo(15)->create();

            $response = $this->getJson(route('api.analytics.temporal'));

            $response->assertOk();

            expect($response->json('data.grid'))->toHaveCount(28);
        });

        it('returns unprocessable when insufficient data', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(5)->for($venue, 'venue')->daysAgo(15)->create();

            $response = $this->getJson(route('api.analytics.temporal'));

            $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->assertJsonStructure([
                    'message',
                    'minimum_required',
                    'actual_count',
                ]);
        });

        it('returns minimum required and actual count in error response', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(5)->for($venue, 'venue')->daysAgo(15)->create();

            $response = $this->getJson(route('api.analytics.temporal'));

            $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->assertJsonPath('minimum_required', 10)
                ->assertJsonPath('actual_count', 5);
        });

        it('returns totals by all seven days', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(20)->for($venue, 'venue')->daysAgo(15)->create();

            $response = $this->getJson(route('api.analytics.temporal'));

            $response->assertOk();

            $byDay = $response->json('data.totals.by_day');

            expect($byDay)->toHaveKeys([
                'Sunday', 'Monday', 'Tuesday', 'Wednesday',
                'Thursday', 'Friday', 'Saturday',
            ]);
        });

        it('returns totals by all four time periods', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(20)->for($venue, 'venue')->daysAgo(15)->create();

            $response = $this->getJson(route('api.analytics.temporal'));

            $response->assertOk();

            $byTime = $response->json('data.totals.by_time');

            expect($byTime)->toHaveKeys(['Morning', 'Afternoon', 'Evening', 'Night']);
        });

        it('returns insights as array of strings', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(50)->for($venue, 'venue')->daysAgo(15)->create();

            $response = $this->getJson(route('api.analytics.temporal'));

            $response->assertOk();

            $insights = $response->json('data.insights');

            expect($insights)->toBeArray();
        });
    });

    describe('city filtering', function (): void {
        it('filters by city query parameter', function (): void {
            $londonVenue = Venue::factory()->create(['city' => 'London']);
            $manchesterVenue = Venue::factory()->create(['city' => 'Manchester']);

            Report::factory()->count(15)->for($londonVenue, 'venue')->daysAgo(15)->create();
            Report::factory()->count(15)->for($manchesterVenue, 'venue')->daysAgo(15)->create();

            $response = $this->getJson(route('api.analytics.temporal', ['city' => 'London']));

            $response->assertOk()
                ->assertJsonPath('data.meta.city', 'London')
                ->assertJsonPath('data.meta.total_reports', 15);
        });

        it('returns all data when city not specified', function (): void {
            $londonVenue = Venue::factory()->create(['city' => 'London']);
            $manchesterVenue = Venue::factory()->create(['city' => 'Manchester']);

            Report::factory()->count(10)->for($londonVenue, 'venue')->daysAgo(15)->create();
            Report::factory()->count(10)->for($manchesterVenue, 'venue')->daysAgo(15)->create();

            $response = $this->getJson(route('api.analytics.temporal'));

            $response->assertOk()
                ->assertJsonPath('data.meta.city', null)
                ->assertJsonPath('data.meta.total_reports', 20);
        });
    });

    describe('period filtering', function (): void {
        it('accepts 30 day period', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(15)->for($venue, 'venue')->daysAgo(15)->create();

            $response = $this->getJson(route('api.analytics.temporal', ['period' => 30]));

            $response->assertOk()
                ->assertJsonPath('data.meta.period_days', 30);
        });

        it('accepts 90 day period', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(15)->for($venue, 'venue')->daysAgo(60)->create();

            $response = $this->getJson(route('api.analytics.temporal', ['period' => 90]));

            $response->assertOk()
                ->assertJsonPath('data.meta.period_days', 90);
        });

        it('accepts 365 day period', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(15)->for($venue, 'venue')->daysAgo(200)->create();

            $response = $this->getJson(route('api.analytics.temporal', ['period' => 365]));

            $response->assertOk()
                ->assertJsonPath('data.meta.period_days', 365);
        });

        it('returns validation error for invalid period', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(15)->for($venue, 'venue')->daysAgo(15)->create();

            $response = $this->getJson(route('api.analytics.temporal', ['period' => 60]));

            $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->assertJsonValidationErrors(['period']);
        });

        it('defaults to 90 days when period not specified', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(15)->for($venue, 'venue')->daysAgo(15)->create();

            $response = $this->getJson(route('api.analytics.temporal'));

            $response->assertOk()
                ->assertJsonPath('data.meta.period_days', 90);
        });
    });

    describe('venue filtering', function (): void {
        it('filters by specific venue UUID', function (): void {
            $venue1 = Venue::factory()->create();
            $venue2 = Venue::factory()->create();

            Report::factory()->count(15)->for($venue1, 'venue')->daysAgo(15)->create();
            Report::factory()->count(10)->for($venue2, 'venue')->daysAgo(15)->create();

            $response = $this->getJson(route('api.analytics.temporal', ['venue_uuid' => $venue1->uuid]));

            $response->assertOk()
                ->assertJsonPath('data.meta.venue_uuid', $venue1->uuid)
                ->assertJsonPath('data.meta.total_reports', 15);
        });

        it('returns validation error for non-existent venue UUID', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(15)->for($venue, 'venue')->daysAgo(15)->create();

            $response = $this->getJson(route('api.analytics.temporal', ['venue_uuid' => fake()->uuid()]));

            $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->assertJsonValidationErrors(['venue_uuid']);
        });

        it('returns validation error for invalid UUID format', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(15)->for($venue, 'venue')->daysAgo(15)->create();

            $response = $this->getJson(route('api.analytics.temporal', ['venue_uuid' => 'not-a-uuid']));

            $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->assertJsonValidationErrors(['venue_uuid']);
        });
    });

    describe('grid structure', function (): void {
        it('returns grid cells with day, time, count, and percentage', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(20)->for($venue, 'venue')->daysAgo(15)->create();

            $response = $this->getJson(route('api.analytics.temporal'));

            $response->assertOk();

            $firstCell = $response->json('data.grid.0');

            expect($firstCell)->toHaveKeys(['day', 'time', 'count', 'percentage']);
        });

        it('returns non-zero percentages for cells with reports', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(20)->for($venue, 'venue')->daysAgo(15)->create();

            $response = $this->getJson(route('api.analytics.temporal'));

            $response->assertOk();

            $cellsWithReports = collect($response->json('data.grid'))->filter(
                fn (array $cell): bool => $cell['count'] > 0
            );

            $allHavePercentages = $cellsWithReports->every(
                fn (array $cell): bool => $cell['percentage'] > 0.0
            );

            expect($allHavePercentages)->toBeTrue();
        });
    });

    describe('caching behaviour', function (): void {
        it('caches results for same parameters', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(20)->for($venue, 'venue')->daysAgo(15)->create();

            $this->getJson(route('api.analytics.temporal'));

            expect(Cache::has('temporal_analytics:all:all:90'))->toBeTrue();
        });

        it('uses separate cache keys for different cities', function (): void {
            $londonVenue = Venue::factory()->create(['city' => 'London']);
            $manchesterVenue = Venue::factory()->create(['city' => 'Manchester']);

            Report::factory()->count(20)->for($londonVenue, 'venue')->daysAgo(15)->create();
            Report::factory()->count(20)->for($manchesterVenue, 'venue')->daysAgo(15)->create();

            $this->getJson(route('api.analytics.temporal', ['city' => 'London']));
            $this->getJson(route('api.analytics.temporal', ['city' => 'Manchester']));

            expect(Cache::has('temporal_analytics:London:all:90'))->toBeTrue()
                ->and(Cache::has('temporal_analytics:Manchester:all:90'))->toBeTrue();
        });
    });

    describe('combined filters', function (): void {
        it('accepts city and period together', function (): void {
            $venue = Venue::factory()->create(['city' => 'London']);
            Report::factory()->count(15)->for($venue, 'venue')->daysAgo(15)->create();

            $response = $this->getJson(route('api.analytics.temporal', [
                'city' => 'London',
                'period' => 30,
            ]));

            $response->assertOk()
                ->assertJsonPath('data.meta.city', 'London')
                ->assertJsonPath('data.meta.period_days', 30);
        });

        it('accepts all filter parameters together', function (): void {
            $venue = Venue::factory()->create(['city' => 'London']);
            Report::factory()->count(15)->for($venue, 'venue')->daysAgo(15)->create();

            $response = $this->getJson(route('api.analytics.temporal', [
                'city' => 'London',
                'period' => 30,
                'venue_uuid' => $venue->uuid,
            ]));

            $response->assertOk()
                ->assertJsonPath('data.meta.city', 'London')
                ->assertJsonPath('data.meta.period_days', 30)
                ->assertJsonPath('data.meta.venue_uuid', $venue->uuid);
        });
    });
});
