<?php

declare(strict_types=1);

use Carbon\Carbon;
use DrinkSafe\Reports\Enums\TimeOfDay;
use DrinkSafe\Reports\Exceptions\ReportNotFoundException;
use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Reports\Services\ReportModerationService;
use DrinkSafe\Reports\Services\ReportService;
use DrinkSafe\Venues\Models\Venue;
use DrinkSafe\Venues\Services\VenueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->moderationService = new ReportModerationService;
    $this->venueService = new VenueService;
    $this->service = new ReportService($this->moderationService, $this->venueService);
});

describe('ReportService', function (): void {
    describe('createReport', function (): void {
        it('creates report with existing venue using venue_uuid', function (): void {
            // Arrange
            $venue = Venue::factory()->create();
            $data = [
                'venue_uuid' => $venue->uuid,
                'incident_date' => Carbon::yesterday()->toDateString(),
                'time_of_day' => TimeOfDay::Evening,
                'description' => 'Clean description without PII',
            ];

            // Act
            $report = $this->service->createReport($data);

            // Assert
            expect($report)->toBeInstanceOf(Report::class);
            expect($report->venue_uuid)->toBe($venue->uuid);
            expect($report->description)->toBe('Clean description without PII');
        });

        it('creates new venue if venue does not exist with name and city', function (): void {
            // Arrange
            $venueName = fake()->company();
            $venueCity = fake()->city();
            $data = [
                'venue_name' => $venueName,
                'venue_city' => $venueCity,
                'incident_date' => Carbon::yesterday()->toDateString(),
                'time_of_day' => TimeOfDay::Night,
                'description' => 'Incident description',
            ];

            // Act
            $report = $this->service->createReport($data);

            // Assert
            expect($report)->toBeInstanceOf(Report::class);
            expect($report->venue)->not->toBeNull();
            expect($report->venue->name)->toBe($venueName);
            expect($report->venue->city)->toBe($venueCity);
        });

        it('uses existing venue when name and city match', function (): void {
            // Arrange
            $venue = Venue::factory()->create([
                'name' => 'Test Club',
                'city' => 'London',
            ]);

            $data = [
                'venue_name' => 'Test Club',
                'venue_city' => 'London',
                'incident_date' => Carbon::yesterday()->toDateString(),
                'time_of_day' => TimeOfDay::Night,
                'description' => 'Test description',
            ];

            // Act
            $report = $this->service->createReport($data);

            // Assert
            expect($report->venue_uuid)->toBe($venue->uuid);
            expect(Venue::where('name', 'Test Club')->where('city', 'London')->count())->toBe(1);
        });

        it('sanitises description with email before storage', function (): void {
            // Arrange
            $venue = Venue::factory()->create();
            $email = fake()->email();
            $data = [
                'venue_uuid' => $venue->uuid,
                'incident_date' => Carbon::yesterday()->toDateString(),
                'time_of_day' => TimeOfDay::Evening,
                'description' => sprintf('Contact me at %s', $email),
            ];

            // Act
            $report = $this->service->createReport($data);

            // Assert
            expect($report->description)->not->toContain($email);
            expect($report->description)->toContain('[EMAIL REMOVED]');
        });

        it('sanitises description with phone number before storage', function (): void {
            // Arrange
            $venue = Venue::factory()->create();
            $data = [
                'venue_uuid' => $venue->uuid,
                'incident_date' => Carbon::yesterday()->toDateString(),
                'time_of_day' => TimeOfDay::Evening,
                'description' => 'Call me at 555-123-4567 for details',
            ];

            // Act
            $report = $this->service->createReport($data);

            // Assert
            expect($report->description)->not->toContain('555-123-4567');
            expect($report->description)->toContain('[PHONE REMOVED]');
        });
    });

    describe('getReportById', function (): void {
        it('returns report with venue loaded', function (): void {
            // Arrange
            $report = Report::factory()->create();

            // Act
            $result = $this->service->getReportById($report->uuid);

            // Assert
            expect($result)->toBeInstanceOf(Report::class);
            expect($result->uuid)->toBe($report->uuid);
            expect($result->relationLoaded('venue'))->toBeTrue();
        });

        it('throws ReportNotFoundException for invalid UUID', function (): void {
            // Arrange
            $invalidUuid = fake()->uuid();

            // Act & Assert
            expect(fn () => $this->service->getReportById($invalidUuid))
                ->toThrow(ReportNotFoundException::class);
        });
    });

    describe('getRecentReports', function (): void {
        it('returns recent reports ordered by created_at DESC', function (): void {
            // Arrange
            Report::factory()->count(5)->create();

            // Act
            $reports = $this->service->getRecentReports(10);

            // Assert
            expect($reports)->toHaveCount(5);
            expect($reports->first()->created_at)->toBeGreaterThanOrEqual($reports->last()->created_at);
        });

        it('respects limit parameter', function (): void {
            // Arrange
            Report::factory()->count(15)->create();

            // Act
            $reports = $this->service->getRecentReports(5);

            // Assert
            expect($reports)->toHaveCount(5);
        });

        it('eager loads venue relationship', function (): void {
            // Arrange
            Report::factory()->count(3)->create();

            // Act
            $reports = $this->service->getRecentReports(10);

            // Assert
            expect($reports->first()->relationLoaded('venue'))->toBeTrue();
        });
    });

    describe('filterByDateRange', function (): void {
        it('returns reports within date range', function (): void {
            // Arrange
            $start = Carbon::parse('2024-01-01');
            $end = Carbon::parse('2024-01-31');

            Report::factory()->create(['incident_date' => '2024-01-15']);
            Report::factory()->create(['incident_date' => '2024-01-20']);

            // Act
            $reports = $this->service->filterByDateRange($start, $end);

            // Assert
            expect($reports)->toHaveCount(2);
        });

        it('excludes reports outside date range', function (): void {
            // Arrange
            $start = Carbon::parse('2024-01-01');
            $end = Carbon::parse('2024-01-31');

            Report::factory()->create(['incident_date' => '2024-01-15']);
            Report::factory()->create(['incident_date' => '2023-12-15']);
            Report::factory()->create(['incident_date' => '2024-02-15']);

            // Act
            $reports = $this->service->filterByDateRange($start, $end);

            // Assert
            expect($reports)->toHaveCount(1);
        });

        it('eager loads venue relationship', function (): void {
            // Arrange
            $start = Carbon::parse('2024-01-01');
            $end = Carbon::parse('2024-01-31');

            Report::factory()->create(['incident_date' => '2024-01-15']);

            // Act
            $reports = $this->service->filterByDateRange($start, $end);

            // Assert
            expect($reports->first()->relationLoaded('venue'))->toBeTrue();
        });
    });

    describe('filterByTimeOfDay', function (): void {
        it('returns only reports with matching time_of_day', function (): void {
            // Arrange
            Report::factory()->count(2)->create(['time_of_day' => TimeOfDay::Evening]);
            Report::factory()->count(3)->create(['time_of_day' => TimeOfDay::Night]);

            // Act
            $reports = $this->service->filterByTimeOfDay(TimeOfDay::Evening);

            // Assert
            expect($reports)->toHaveCount(2);
            expect($reports->first()->time_of_day)->toBe(TimeOfDay::Evening);
        });

        it('eager loads venue relationship', function (): void {
            // Arrange
            Report::factory()->create(['time_of_day' => TimeOfDay::Morning]);

            // Act
            $reports = $this->service->filterByTimeOfDay(TimeOfDay::Morning);

            // Assert
            expect($reports->first()->relationLoaded('venue'))->toBeTrue();
        });
    });

    describe('deleteReport', function (): void {
        it('soft deletes report', function (): void {
            // Arrange
            $report = Report::factory()->create();

            // Act
            $result = $this->service->deleteReport($report->uuid);

            // Assert
            expect($result)->toBeTrue();
            expect($report->fresh()->trashed())->toBeTrue();
        });

        it('throws ReportNotFoundException for invalid UUID', function (): void {
            // Arrange
            $invalidUuid = fake()->uuid();

            // Act & Assert
            expect(fn () => $this->service->deleteReport($invalidUuid))
                ->toThrow(ReportNotFoundException::class);
        });
    });
});
