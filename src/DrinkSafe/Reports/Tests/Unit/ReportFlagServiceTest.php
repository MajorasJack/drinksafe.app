<?php

declare(strict_types=1);

use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Reports\Services\ReportFlagService;
use DrinkSafe\Venues\Models\Venue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = app(ReportFlagService::class);
});

describe('ReportFlagService', function (): void {
    describe('flagsFor', function (): void {
        it('flags a report with no warning signs with nothing', function (): void {
            $report = Report::factory()->pending()->create(['description' => fake()->sentence()]);

            expect($this->service->flagsFor($report))->toBe([]);
        });

        it('flags a description that had an email address removed', function (): void {
            $report = Report::factory()->pending()->create(['description' => sprintf('%s [EMAIL REMOVED]', fake()->sentence())]);

            expect($this->service->flagsFor($report))->toBe(['personal_details_removed']);
        });

        it('flags a description that had a phone number removed', function (): void {
            $report = Report::factory()->pending()->create(['description' => sprintf('[PHONE REMOVED] %s', fake()->sentence())]);

            expect($this->service->flagsFor($report))->toBe(['personal_details_removed']);
        });

        it('flags a new venue with more than one pending report', function (): void {
            $venue = Venue::factory()->unpublished()->create();
            [$report] = Report::factory()->for($venue, 'venue')->pending()->count(2)->create()->all();

            expect($this->service->flagsFor($report))->toBe(['new_venue_several_pending']);
        });

        it('does not flag a new venue with a single pending report', function (): void {
            $report = Report::factory()->for(Venue::factory()->unpublished(), 'venue')->pending()->create();

            expect($this->service->flagsFor($report))->toBe([]);
        });

        it('does not treat a published venue with several pending reports as new', function (): void {
            $venue = Venue::factory()->create();
            [$report] = Report::factory()->for($venue, 'venue')->pending()->count(2)->create()->all();

            expect($this->service->flagsFor($report))->toBe([]);
        });

        it('flags a venue that reached the spike threshold in the last 24 hours', function (): void {
            config(['drinksafe.admin.report_spike_threshold' => 3]);
            $venue = Venue::factory()->create();
            Report::factory()->for($venue, 'venue')->count(2)->create(['created_at' => now()->subHours(fake()->numberBetween(1, 23))]);
            $report = Report::factory()->for($venue, 'venue')->pending()->create();

            expect($this->service->flagsFor($report))->toBe(['venue_report_spike']);
        });

        it('does not count reports older than 24 hours towards a spike', function (): void {
            config(['drinksafe.admin.report_spike_threshold' => 3]);
            $venue = Venue::factory()->create();
            Report::factory()->for($venue, 'venue')->count(2)->create(['created_at' => now()->subDays(2)]);
            $report = Report::factory()->for($venue, 'venue')->pending()->create();

            expect($this->service->flagsFor($report))->toBe([]);
        });

        it('defaults the spike threshold to five reports', function (): void {
            expect(config('drinksafe.admin.report_spike_threshold'))->toBe(5);
        });

        it('flags a venue at the default coordinates', function (): void {
            $report = Report::factory()->for(Venue::factory()->atDefaultCoordinates(), 'venue')->pending()->create();

            expect($this->service->flagsFor($report))->toBe(['default_coordinates']);
        });

        it('uses counts loaded for a page of reports instead of querying each one', function (): void {
            $reports = Report::factory()->pending()->count(3)->create();
            $page = Report::withoutGlobalScopes()->with('venue')->whereKey($reports->modelKeys())->get();
            $this->service->loadVenueCounts($page);
            DB::enableQueryLog();

            $page->each(fn (Report $report): array => $this->service->flagsFor($report));

            expect(DB::getQueryLog())->toBe([]);
        });
    });

    describe('wouldEmptyVenue', function (): void {
        it('is true for the only published report of a published venue', function (): void {
            $report = Report::factory()->create();

            expect($this->service->wouldEmptyVenue($report))->toBeTrue();
        });

        it('is false when the venue has another published report', function (): void {
            $venue = Venue::factory()->create();
            [$report] = Report::factory()->for($venue, 'venue')->count(2)->create()->all();

            expect($this->service->wouldEmptyVenue($report))->toBeFalse();
        });

        it('is true for a pending report on a published venue with no published reports', function (): void {
            $report = Report::factory()->pending()->create();

            expect($this->service->wouldEmptyVenue($report))->toBeTrue();
        });

        it('is false for a venue that is not published', function (): void {
            $report = Report::factory()->for(Venue::factory()->unpublished(), 'venue')->pending()->create();

            expect($this->service->wouldEmptyVenue($report))->toBeFalse();
        });
    });

    describe('loadVenueCounts', function (): void {
        it('does nothing for an empty page', function (): void {
            DB::enableQueryLog();

            $this->service->loadVenueCounts(new Collection);

            expect(DB::getQueryLog())->toBe([]);
        });
    });
});
