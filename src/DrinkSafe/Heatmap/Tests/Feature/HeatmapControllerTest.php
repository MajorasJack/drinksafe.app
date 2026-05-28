<?php

declare(strict_types=1);

use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Venues\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('HeatmapController', function (): void {
    describe('__invoke', function (): void {
        it('returns heatmap data for valid bounding box', function (): void {
            $venue = Venue::factory()->create([
                'latitude' => 51.5,
                'longitude' => -0.1,
            ]);
            Report::factory()->count(5)->for($venue, 'venue')->daysAgo(15)->create();

            $response = $this->getJson(route('api.heatmap', [
                'north' => 51.6,
                'south' => 51.4,
                'east' => 0.0,
                'west' => -0.2,
            ]));

            $response->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        'points',
                        'period_days',
                        'total_reports',
                    ],
                ]);
        });

        it('returns points with correct structure', function (): void {
            $venue = Venue::factory()->create([
                'latitude' => 51.5,
                'longitude' => -0.1,
            ]);
            Report::factory()->count(5)->for($venue, 'venue')->daysAgo(15)->create();

            $response = $this->getJson(route('api.heatmap', [
                'north' => 51.6,
                'south' => 51.4,
                'east' => 0.0,
                'west' => -0.2,
            ]));

            $response->assertOk();

            $points = $response->json('data.points');

            expect($points)->toBeArray();

            if (count($points) > 0) {
                $firstPoint = $points[0];

                expect($firstPoint)->toHaveKeys(['lat', 'lng', 'intensity']);
            }
        });

        it('returns empty points when no reports in bounds', function (): void {
            $venue = Venue::factory()->create([
                'latitude' => 52.5,
                'longitude' => -1.5,
            ]);
            Report::factory()->count(5)->for($venue, 'venue')->daysAgo(15)->create();

            $response = $this->getJson(route('api.heatmap', [
                'north' => 51.6,
                'south' => 51.4,
                'east' => 0.0,
                'west' => -0.2,
            ]));

            $response->assertOk()
                ->assertJsonPath('data.points', [])
                ->assertJsonPath('data.total_reports', 0);
        });

        it('returns correct total_reports count', function (): void {
            $venue = Venue::factory()->create([
                'latitude' => 51.5,
                'longitude' => -0.1,
            ]);
            Report::factory()->count(10)->for($venue, 'venue')->daysAgo(15)->create();

            $response = $this->getJson(route('api.heatmap', [
                'north' => 51.6,
                'south' => 51.4,
                'east' => 0.0,
                'west' => -0.2,
            ]));

            $response->assertOk()
                ->assertJsonPath('data.total_reports', 10);
        });
    });

    describe('validation', function (): void {
        it('requires north parameter', function (): void {
            $response = $this->getJson(route('api.heatmap', [
                'south' => 51.4,
                'east' => 0.0,
                'west' => -0.2,
            ]));

            $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->assertJsonValidationErrors(['north']);
        });

        it('requires south parameter', function (): void {
            $response = $this->getJson(route('api.heatmap', [
                'north' => 51.6,
                'east' => 0.0,
                'west' => -0.2,
            ]));

            $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->assertJsonValidationErrors(['south']);
        });

        it('requires east parameter', function (): void {
            $response = $this->getJson(route('api.heatmap', [
                'north' => 51.6,
                'south' => 51.4,
                'west' => -0.2,
            ]));

            $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->assertJsonValidationErrors(['east']);
        });

        it('requires west parameter', function (): void {
            $response = $this->getJson(route('api.heatmap', [
                'north' => 51.6,
                'south' => 51.4,
                'east' => 0.0,
            ]));

            $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->assertJsonValidationErrors(['west']);
        });

        it('validates latitude range for north', function (): void {
            $response = $this->getJson(route('api.heatmap', [
                'north' => 100.0,
                'south' => 51.4,
                'east' => 0.0,
                'west' => -0.2,
            ]));

            $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->assertJsonValidationErrors(['north']);
        });

        it('validates latitude range for south', function (): void {
            $response = $this->getJson(route('api.heatmap', [
                'north' => 51.6,
                'south' => -100.0,
                'east' => 0.0,
                'west' => -0.2,
            ]));

            $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->assertJsonValidationErrors(['south']);
        });

        it('validates longitude range for east', function (): void {
            $response = $this->getJson(route('api.heatmap', [
                'north' => 51.6,
                'south' => 51.4,
                'east' => 200.0,
                'west' => -0.2,
            ]));

            $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->assertJsonValidationErrors(['east']);
        });

        it('validates longitude range for west', function (): void {
            $response = $this->getJson(route('api.heatmap', [
                'north' => 51.6,
                'south' => 51.4,
                'east' => 0.0,
                'west' => -200.0,
            ]));

            $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->assertJsonValidationErrors(['west']);
        });

        it('validates period parameter values', function (): void {
            $response = $this->getJson(route('api.heatmap', [
                'north' => 51.6,
                'south' => 51.4,
                'east' => 0.0,
                'west' => -0.2,
                'period' => 60,
            ]));

            $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->assertJsonValidationErrors(['period']);
        });
    });

    describe('period filtering', function (): void {
        it('accepts 7 day period', function (): void {
            $venue = Venue::factory()->create([
                'latitude' => 51.5,
                'longitude' => -0.1,
            ]);
            Report::factory()->count(5)->for($venue, 'venue')->daysAgo(3)->create();

            $response = $this->getJson(route('api.heatmap', [
                'north' => 51.6,
                'south' => 51.4,
                'east' => 0.0,
                'west' => -0.2,
                'period' => 7,
            ]));

            $response->assertOk()
                ->assertJsonPath('data.period_days', 7);
        });

        it('accepts 30 day period', function (): void {
            $venue = Venue::factory()->create([
                'latitude' => 51.5,
                'longitude' => -0.1,
            ]);
            Report::factory()->count(5)->for($venue, 'venue')->daysAgo(15)->create();

            $response = $this->getJson(route('api.heatmap', [
                'north' => 51.6,
                'south' => 51.4,
                'east' => 0.0,
                'west' => -0.2,
                'period' => 30,
            ]));

            $response->assertOk()
                ->assertJsonPath('data.period_days', 30);
        });

        it('accepts 90 day period', function (): void {
            $venue = Venue::factory()->create([
                'latitude' => 51.5,
                'longitude' => -0.1,
            ]);
            Report::factory()->count(5)->for($venue, 'venue')->daysAgo(60)->create();

            $response = $this->getJson(route('api.heatmap', [
                'north' => 51.6,
                'south' => 51.4,
                'east' => 0.0,
                'west' => -0.2,
                'period' => 90,
            ]));

            $response->assertOk()
                ->assertJsonPath('data.period_days', 90);
        });

        it('accepts 365 day period', function (): void {
            $venue = Venue::factory()->create([
                'latitude' => 51.5,
                'longitude' => -0.1,
            ]);
            Report::factory()->count(5)->for($venue, 'venue')->daysAgo(200)->create();

            $response = $this->getJson(route('api.heatmap', [
                'north' => 51.6,
                'south' => 51.4,
                'east' => 0.0,
                'west' => -0.2,
                'period' => 365,
            ]));

            $response->assertOk()
                ->assertJsonPath('data.period_days', 365);
        });

        it('defaults to 30 day period when not specified', function (): void {
            $venue = Venue::factory()->create([
                'latitude' => 51.5,
                'longitude' => -0.1,
            ]);
            Report::factory()->count(5)->for($venue, 'venue')->daysAgo(15)->create();

            $response = $this->getJson(route('api.heatmap', [
                'north' => 51.6,
                'south' => 51.4,
                'east' => 0.0,
                'west' => -0.2,
            ]));

            $response->assertOk()
                ->assertJsonPath('data.period_days', 30);
        });

        it('filters reports by time period', function (): void {
            $venue = Venue::factory()->create([
                'latitude' => 51.5,
                'longitude' => -0.1,
            ]);
            Report::factory()->count(5)->for($venue, 'venue')->daysAgo(3)->create();
            Report::factory()->count(5)->for($venue, 'venue')->daysAgo(15)->create();

            $response = $this->getJson(route('api.heatmap', [
                'north' => 51.6,
                'south' => 51.4,
                'east' => 0.0,
                'west' => -0.2,
                'period' => 7,
            ]));

            $response->assertOk()
                ->assertJsonPath('data.total_reports', 5);
        });
    });

    describe('privacy threshold', function (): void {
        it('excludes grid cells with fewer than 3 reports', function (): void {
            $venue = Venue::factory()->create([
                'latitude' => 51.5,
                'longitude' => -0.1,
            ]);
            Report::factory()->count(2)->for($venue, 'venue')->daysAgo(15)->create();

            $response = $this->getJson(route('api.heatmap', [
                'north' => 51.6,
                'south' => 51.4,
                'east' => 0.0,
                'west' => -0.2,
            ]));

            $response->assertOk()
                ->assertJsonPath('data.points', [])
                ->assertJsonPath('data.total_reports', 0);
        });

        it('includes grid cells with 3 or more reports', function (): void {
            $venue = Venue::factory()->create([
                'latitude' => 51.5,
                'longitude' => -0.1,
            ]);
            Report::factory()->count(3)->for($venue, 'venue')->daysAgo(15)->create();

            $response = $this->getJson(route('api.heatmap', [
                'north' => 51.6,
                'south' => 51.4,
                'east' => 0.0,
                'west' => -0.2,
            ]));

            $response->assertOk();

            $points = $response->json('data.points');

            expect($points)->not->toBeEmpty();
        });
    });

    describe('bounding box filtering', function (): void {
        it('only includes venues within bounding box', function (): void {
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

            $response = $this->getJson(route('api.heatmap', [
                'north' => 51.6,
                'south' => 51.4,
                'east' => 0.0,
                'west' => -0.2,
            ]));

            $response->assertOk()
                ->assertJsonPath('data.total_reports', 5);
        });

        it('handles multiple venues within bounds', function (): void {
            $venue1 = Venue::factory()->create([
                'latitude' => 51.51,
                'longitude' => -0.11,
            ]);
            $venue2 = Venue::factory()->create([
                'latitude' => 51.52,
                'longitude' => -0.12,
            ]);

            Report::factory()->count(5)->for($venue1, 'venue')->daysAgo(15)->create();
            Report::factory()->count(5)->for($venue2, 'venue')->daysAgo(15)->create();

            $response = $this->getJson(route('api.heatmap', [
                'north' => 51.6,
                'south' => 51.4,
                'east' => 0.0,
                'west' => -0.2,
            ]));

            $response->assertOk()
                ->assertJsonPath('data.total_reports', 10);
        });
    });

    describe('caching behaviour', function (): void {
        it('caches results for same parameters', function (): void {
            $venue = Venue::factory()->create([
                'latitude' => 51.5,
                'longitude' => -0.1,
            ]);
            Report::factory()->count(5)->for($venue, 'venue')->daysAgo(15)->create();

            $this->getJson(route('api.heatmap', [
                'north' => 51.6,
                'south' => 51.4,
                'east' => 0.0,
                'west' => -0.2,
            ]));

            expect(Cache::has('heatmap:51.6:51.4:0:-0.2:30'))->toBeTrue();
        });
    });

    describe('inverted bounds handling', function (): void {
        it('returns bad request for inverted bounds', function (): void {
            $response = $this->getJson(route('api.heatmap', [
                'north' => 51.4,
                'south' => 51.6,
                'east' => 0.0,
                'west' => -0.2,
            ]));

            $response->assertStatus(Response::HTTP_BAD_REQUEST)
                ->assertJsonPath('message', 'South latitude must be less than north latitude');
        });
    });
});
