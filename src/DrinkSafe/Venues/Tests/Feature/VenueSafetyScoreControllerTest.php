<?php

declare(strict_types=1);

use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Venues\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('VenueSafetyScoreController', function (): void {
    describe('__invoke', function (): void {
        it('returns safety score for venue with sufficient reports', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(5)->for($venue, 'venue')->daysAgo(15)->create();

            $response = $this->getJson(route('api.venues.safety-score', $venue->uuid));

            $response->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        'venue_uuid',
                        'score',
                        'tier',
                        'report_count_30d',
                        'report_count_90d',
                        'last_updated',
                    ],
                ])
                ->assertJsonPath('data.venue_uuid', $venue->uuid);
        });

        it('returns insufficient_data tier for venue with few reports', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(2)->for($venue, 'venue')->create();

            $response = $this->getJson(route('api.venues.safety-score', $venue->uuid));

            $response->assertOk()
                ->assertJsonPath('data.tier', 'insufficient_data')
                ->assertJsonPath('data.score', 0);
        });

        it('returns 404 for non-existent venue', function (): void {
            $response = $this->getJson(route('api.venues.safety-score', 'non-existent-uuid'));

            $response->assertNotFound()
                ->assertJsonStructure(['message']);
        });

        it('returns low tier for venue with old reports', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(3)->for($venue, 'venue')->daysAgo(300)->create();

            $response = $this->getJson(route('api.venues.safety-score', $venue->uuid));

            $response->assertOk()
                ->assertJsonPath('data.tier', 'low');
        });

        it('returns high tier for venue with many recent reports', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(10)->for($venue, 'venue')->daysAgo(5)->create();

            $response = $this->getJson(route('api.venues.safety-score', $venue->uuid));

            $response->assertOk()
                ->assertJsonPath('data.tier', 'high');
        });

        it('returns correct report counts', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(2)->for($venue, 'venue')->daysAgo(10)->create();
            Report::factory()->count(3)->for($venue, 'venue')->daysAgo(60)->create();

            $response = $this->getJson(route('api.venues.safety-score', $venue->uuid));

            $response->assertOk()
                ->assertJsonPath('data.report_count_30d', 2)
                ->assertJsonPath('data.report_count_90d', 5);
        });

        it('returns score as integer between 0 and 100', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(5)->for($venue, 'venue')->daysAgo(30)->create();

            $response = $this->getJson(route('api.venues.safety-score', $venue->uuid));

            $response->assertOk();

            $score = $response->json('data.score');

            expect($score)->toBeInt()
                ->and($score)->toBeGreaterThanOrEqual(0)
                ->and($score)->toBeLessThanOrEqual(100);
        });

        it('returns valid ISO 8601 timestamp for last_updated', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(3)->for($venue, 'venue')->create();

            $response = $this->getJson(route('api.venues.safety-score', $venue->uuid));

            $response->assertOk();

            $lastUpdated = $response->json('data.last_updated');

            expect(strtotime($lastUpdated))->not->toBeFalse();
        });

        it('caches the safety score', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(3)->for($venue, 'venue')->daysAgo(30)->create();

            $this->getJson(route('api.venues.safety-score', $venue->uuid));

            expect(Cache::has(sprintf('venue:safety_score:%s', $venue->uuid)))->toBeTrue();
        });

        it('returns tier as one of the valid values', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(5)->for($venue, 'venue')->daysAgo(30)->create();

            $response = $this->getJson(route('api.venues.safety-score', $venue->uuid));

            $response->assertOk();

            $tier = $response->json('data.tier');

            expect($tier)->toBeIn(['low', 'moderate', 'high', 'insufficient_data']);
        });
    });

    describe('cache invalidation via observer', function (): void {
        it('invalidates cache when new report is created', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(3)->for($venue, 'venue')->daysAgo(30)->create();

            $this->getJson(route('api.venues.safety-score', $venue->uuid));

            expect(Cache::has(sprintf('venue:safety_score:%s', $venue->uuid)))->toBeTrue();

            Report::factory()->for($venue, 'venue')->daysAgo(1)->create();

            expect(Cache::has(sprintf('venue:safety_score:%s', $venue->uuid)))->toBeFalse();
        });

        it('returns updated score after cache invalidation', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(3)->for($venue, 'venue')->daysAgo(180)->create();

            $firstResponse = $this->getJson(route('api.venues.safety-score', $venue->uuid));
            $firstScore = $firstResponse->json('data.score');

            Report::factory()->count(5)->for($venue, 'venue')->daysAgo(1)->create();

            $secondResponse = $this->getJson(route('api.venues.safety-score', $venue->uuid));
            $secondScore = $secondResponse->json('data.score');

            expect($secondScore)->toBeGreaterThan($firstScore);
        });
    });
});
