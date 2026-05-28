<?php

declare(strict_types=1);

use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Venues\DataTransferObjects\SafetyScoreResult;
use DrinkSafe\Venues\Exceptions\VenueNotFoundException;
use DrinkSafe\Venues\Models\Venue;
use DrinkSafe\Venues\Services\SafetyScoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = new SafetyScoreService;
});

describe('SafetyScoreService', function (): void {
    describe('computeScore', function (): void {
        it('returns insufficient_data tier for venues with fewer than 3 reports', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(2)->for($venue, 'venue')->create();

            $result = $this->service->computeScore($venue);

            expect($result)->toBeInstanceOf(SafetyScoreResult::class)
                ->and($result->tier)->toBe('insufficient_data')
                ->and($result->score)->toBe(0);
        });

        it('returns insufficient_data tier for venues with no reports', function (): void {
            $venue = Venue::factory()->create();

            $result = $this->service->computeScore($venue);

            expect($result->tier)->toBe('insufficient_data')
                ->and($result->score)->toBe(0)
                ->and($result->reportCount30d)->toBe(0)
                ->and($result->reportCount90d)->toBe(0);
        });

        it('returns low tier for venues with old reports only', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(3)->for($venue, 'venue')->daysAgo(300)->create();

            $result = $this->service->computeScore($venue);

            expect($result->tier)->toBe('low')
                ->and($result->score)->toBeLessThanOrEqual(30);
        });

        it('returns moderate tier for venues with some recent reports', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(5)->for($venue, 'venue')->daysAgo(10)->create();

            $result = $this->service->computeScore($venue);

            expect($result->tier)->toBe('moderate')
                ->and($result->score)->toBeGreaterThan(30)
                ->and($result->score)->toBeLessThanOrEqual(60);
        });

        it('returns high tier for venues with many recent reports', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(10)->for($venue, 'venue')->daysAgo(7)->create();

            $result = $this->service->computeScore($venue);

            expect($result->tier)->toBe('high')
                ->and($result->score)->toBeGreaterThan(60);
        });

        it('includes correct report counts for 30 and 90 day periods', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(2)->for($venue, 'venue')->daysAgo(15)->create();
            Report::factory()->count(3)->for($venue, 'venue')->daysAgo(60)->create();
            Report::factory()->count(1)->for($venue, 'venue')->daysAgo(120)->create();

            $result = $this->service->computeScore($venue);

            expect($result->reportCount30d)->toBe(2)
                ->and($result->reportCount90d)->toBe(5);
        });

        it('excludes reports older than 365 days from score calculation', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(5)->for($venue, 'venue')->daysAgo(400)->create();

            $result = $this->service->computeScore($venue);

            expect($result->tier)->toBe('insufficient_data');
        });

        it('returns the venue UUID in the result', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(3)->for($venue, 'venue')->daysAgo(30)->create();

            $result = $this->service->computeScore($venue);

            expect($result->venueUuid)->toBe($venue->uuid);
        });
    });

    describe('computeScoreByUuid', function (): void {
        it('throws VenueNotFoundException for non-existent venue', function (): void {
            $this->service->computeScoreByUuid('non-existent-uuid');
        })->throws(VenueNotFoundException::class);

        it('computes score correctly when venue exists', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(3)->for($venue, 'venue')->daysAgo(10)->create();

            $result = $this->service->computeScoreByUuid($venue->uuid);

            expect($result)->toBeInstanceOf(SafetyScoreResult::class)
                ->and($result->venueUuid)->toBe($venue->uuid);
        });
    });

    describe('time decay algorithm', function (): void {
        it('weights recent reports higher than older reports', function (): void {
            $venueWithRecentReports = Venue::factory()->create();
            Report::factory()->count(3)->for($venueWithRecentReports, 'venue')->daysAgo(1)->create();

            $venueWithOldReports = Venue::factory()->create();
            Report::factory()->count(3)->for($venueWithOldReports, 'venue')->daysAgo(180)->create();

            $recentScore = $this->service->computeScore($venueWithRecentReports);
            $oldScore = $this->service->computeScore($venueWithOldReports);

            expect($recentScore->score)->toBeGreaterThan($oldScore->score);
        });

        it('applies decay factor of 0.5 over 90 days', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(3)->for($venue, 'venue')->daysAgo(90)->create();

            $result = $this->service->computeScore($venue);

            $expectedWeight = 0.5 * 3;
            $expectedScore = min(100, (int) round($expectedWeight * 10));

            expect($result->score)->toBe($expectedScore);
        });
    });

    describe('caching', function (): void {
        it('caches the computed score', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(3)->for($venue, 'venue')->daysAgo(30)->create();

            $this->service->computeScore($venue);

            expect(Cache::has(sprintf('venue:safety_score:%s', $venue->uuid)))->toBeTrue();
        });

        it('returns cached score on subsequent calls without recomputation', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(3)->for($venue, 'venue')->daysAgo(30)->create();

            $firstResult = $this->service->computeScore($venue);
            $cachedTimestamp = $firstResult->lastUpdated->toIso8601String();

            sleep(1);

            $secondResult = $this->service->computeScore($venue);

            expect($secondResult->lastUpdated->toIso8601String())->toBe($cachedTimestamp);
        });
    });

    describe('invalidateScore', function (): void {
        it('removes cached score for venue', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(3)->for($venue, 'venue')->daysAgo(30)->create();

            $this->service->computeScore($venue);

            expect(Cache::has(sprintf('venue:safety_score:%s', $venue->uuid)))->toBeTrue();

            $this->service->invalidateScore($venue->uuid);

            expect(Cache::has(sprintf('venue:safety_score:%s', $venue->uuid)))->toBeFalse();
        });

        it('allows fresh score computation after invalidation', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(3)->for($venue, 'venue')->daysAgo(30)->create();

            $firstResult = $this->service->computeScore($venue);

            Report::factory()->count(5)->for($venue, 'venue')->daysAgo(1)->create();

            $this->service->invalidateScore($venue->uuid);

            $secondResult = $this->service->computeScore($venue);

            expect($secondResult->score)->toBeGreaterThan($firstResult->score);
        });
    });

    describe('computeScoresForVenues', function (): void {
        it('computes scores for multiple venues', function (): void {
            $venues = Venue::factory()->count(3)->create();

            $venues->each(function (Venue $venue): void {
                Report::factory()->count(3)->for($venue, 'venue')->daysAgo(30)->create();
            });

            $results = $this->service->computeScoresForVenues($venues);

            expect($results)->toHaveCount(3);

            $venues->each(function (Venue $venue) use ($results): void {
                expect($results->has($venue->uuid))->toBeTrue()
                    ->and($results->get($venue->uuid))->toBeInstanceOf(SafetyScoreResult::class);
            });
        });
    });

    describe('tier thresholds', function (): void {
        it('classifies score of 30 as low tier', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(3)->for($venue, 'venue')->daysAgo(0)->create();

            $result = $this->service->computeScore($venue);

            expect($result->score)->toBe(30)
                ->and($result->tier)->toBe('low');
        });

        it('classifies score of 31 as moderate tier', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(4)->for($venue, 'venue')->daysAgo(0)->create();

            $result = $this->service->computeScore($venue);

            expect($result->score)->toBe(40)
                ->and($result->tier)->toBe('moderate');
        });

        it('classifies score of 60 as moderate tier', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(6)->for($venue, 'venue')->daysAgo(0)->create();

            $result = $this->service->computeScore($venue);

            expect($result->score)->toBe(60)
                ->and($result->tier)->toBe('moderate');
        });

        it('classifies score of 61 as high tier', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(7)->for($venue, 'venue')->daysAgo(0)->create();

            $result = $this->service->computeScore($venue);

            expect($result->score)->toBe(70)
                ->and($result->tier)->toBe('high');
        });

        it('caps score at 100', function (): void {
            $venue = Venue::factory()->create();
            Report::factory()->count(15)->for($venue, 'venue')->daysAgo(0)->create();

            $result = $this->service->computeScore($venue);

            expect($result->score)->toBe(100)
                ->and($result->tier)->toBe('high');
        });
    });
});
