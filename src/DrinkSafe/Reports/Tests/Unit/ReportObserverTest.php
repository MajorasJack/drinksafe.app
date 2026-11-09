<?php

declare(strict_types=1);

use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Venues\Models\Venue;
use DrinkSafe\Venues\Services\SafetyScoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('clears the cached safety score of the venue a report moved away from', function (): void {
    $previousVenue = Venue::factory()->create();
    $report = Report::factory()->for($previousVenue, 'venue')->create();
    app(SafetyScoreService::class)->computeScore($previousVenue);

    DB::transaction(fn (): bool => $report->update(['venue_uuid' => Venue::factory()->create()->uuid]));

    expect(Cache::has(sprintf('venue:safety_score:%s', $previousVenue->uuid)))->toBeFalse();
});

it('leaves the public caches alone when a report changes without a status change', function (): void {
    $report = Report::factory()->create();

    $report->update(['incident_date' => now()->subDays(fake()->numberBetween(1, 30))]);

    expect(Cache::get('public-cache-version', 1))->toBe(1);
});

it('clears the public caches when a published report is deleted', function (): void {
    $report = Report::factory()->create();

    $report->delete();

    expect(Cache::get('public-cache-version'))->toBe(2);
});

it('clears the public caches when a published report is restored', function (): void {
    $report = Report::factory()->create();
    $report->delete();

    $report->restore();

    expect(Cache::get('public-cache-version'))->toBe(3);
});

it('leaves the public caches alone when a pending report is deleted', function (): void {
    $report = Report::factory()->pending()->create();

    $report->delete();

    expect(Cache::get('public-cache-version', 1))->toBe(1);
});
