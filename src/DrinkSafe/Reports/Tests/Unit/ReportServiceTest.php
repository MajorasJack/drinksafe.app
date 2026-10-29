<?php

declare(strict_types=1);

use Carbon\Carbon;
use DrinkSafe\Reports\Enums\ReportStatus;
use DrinkSafe\Reports\Enums\TimeOfDay;
use DrinkSafe\Reports\Exceptions\ReportNotFoundException;
use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Reports\Notifications\ReportAwaitingReviewNotification;
use DrinkSafe\Reports\Services\ReportModerationService;
use DrinkSafe\Reports\Services\ReportService;
use DrinkSafe\Venues\Models\Venue;
use DrinkSafe\Venues\Services\VenueService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
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

    describe('createReport review queue', function (): void {
        it('saves new reports as pending', function (): void {
            $venue = Venue::factory()->create();

            $report = $this->service->createReport([
                'venue_uuid' => $venue->uuid,
                'incident_date' => Carbon::yesterday()->toDateString(),
                'time_of_day' => TimeOfDay::Evening,
                'description' => fake()->sentence(),
            ]);

            expect($report->status)->toBe(ReportStatus::Pending)
                ->and($report->reviewed_at)->toBeNull();
        });

        it('sends the review alert to the configured address', function (): void {
            Notification::fake();
            $alertEmail = fake()->safeEmail();
            config(['services.reports.alert_email' => $alertEmail]);
            $venue = Venue::factory()->create();

            $this->service->createReport([
                'venue_uuid' => $venue->uuid,
                'incident_date' => Carbon::yesterday()->toDateString(),
                'time_of_day' => TimeOfDay::Evening,
                'description' => fake()->sentence(),
            ]);

            Notification::assertSentOnDemand(
                ReportAwaitingReviewNotification::class,
                fn (ReportAwaitingReviewNotification $notification, array $channels, AnonymousNotifiable $notifiable): bool => $notifiable->routes['mail'] === $alertEmail,
            );
        });

        it('keeps the report when the review alert cannot be sent', function (): void {
            Notification::shouldReceive('route')->andThrow(new RuntimeException('Mail is down'));
            $venue = Venue::factory()->create();

            $report = $this->service->createReport([
                'venue_uuid' => $venue->uuid,
                'incident_date' => Carbon::yesterday()->toDateString(),
                'time_of_day' => TimeOfDay::Evening,
                'description' => fake()->sentence(),
            ]);

            $this->assertDatabaseHas('reports', ['uuid' => $report->uuid]);
        });
    });

    describe('getPendingReports', function (): void {
        it('returns only pending reports, oldest first', function (): void {
            $newerPending = Report::factory()->pending()->create(['created_at' => now()->subHour()]);
            $olderPending = Report::factory()->pending()->create(['created_at' => now()->subDay()]);
            Report::factory()->create();
            Report::factory()->rejected()->create();

            $reports = $this->service->getPendingReports();

            expect($reports->pluck('uuid')->all())->toBe([$olderPending->uuid, $newerPending->uuid]);
        });
    });

    describe('countPendingReports', function (): void {
        it('counts only pending reports', function (): void {
            Report::factory()->pending()->count(3)->create();
            Report::factory()->create();
            Report::factory()->rejected()->create();

            expect($this->service->countPendingReports())->toBe(3);
        });
    });

    describe('getRecentlyReviewedReports', function (): void {
        it('returns published and rejected reports, latest decision first', function (): void {
            $olderDecision = Report::factory()->create(['reviewed_at' => now()->subDay()]);
            $newerDecision = Report::factory()->rejected()->create(['reviewed_at' => now()->subHour()]);
            Report::factory()->pending()->create();

            $reports = $this->service->getRecentlyReviewedReports();

            expect($reports->pluck('uuid')->all())->toBe([$newerDecision->uuid, $olderDecision->uuid]);
        });
    });

    describe('reviewReport', function (): void {
        it('publishes a pending report', function (): void {
            $report = Report::factory()->pending()->create();

            $this->service->reviewReport(uuid: $report->uuid, status: ReportStatus::Published);

            expect($report->fresh()->status)->toBe(ReportStatus::Published);
        });

        it('rejects a pending report', function (): void {
            $report = Report::factory()->pending()->create();

            $this->service->reviewReport(uuid: $report->uuid, status: ReportStatus::Rejected);

            expect($report->fresh()->status)->toBe(ReportStatus::Rejected);
        });

        it('records when the report was reviewed', function (): void {
            $this->freezeSecond();
            $report = Report::factory()->pending()->create();

            $reviewedReport = $this->service->reviewReport(uuid: $report->uuid, status: ReportStatus::Published);

            expect($reviewedReport->reviewed_at->equalTo(now()))->toBeTrue();
        });

        it('throws ModelNotFoundException for an unknown UUID', function (): void {
            expect(fn () => $this->service->reviewReport(uuid: fake()->uuid(), status: ReportStatus::Published))
                ->toThrow(ModelNotFoundException::class);
        });
    });

    describe('getReportById', function (): void {
        it('throws ReportNotFoundException for a pending report', function (): void {
            $report = Report::factory()->pending()->create();

            expect(fn () => $this->service->getReportById($report->uuid))
                ->toThrow(ReportNotFoundException::class);
        });

        it('throws ReportNotFoundException for a rejected report', function (): void {
            $report = Report::factory()->rejected()->create();

            expect(fn () => $this->service->getReportById($report->uuid))
                ->toThrow(ReportNotFoundException::class);
        });

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
        it('returns only published reports', function (): void {
            $published = Report::factory()->create();
            Report::factory()->pending()->create();
            Report::factory()->rejected()->create();

            $reports = $this->service->getRecentReports(10);

            expect($reports->pluck('uuid')->all())->toBe([$published->uuid]);
        });

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
        it('returns only published reports', function (): void {
            $published = Report::factory()->create(['incident_date' => '2024-01-15']);
            Report::factory()->pending()->create(['incident_date' => '2024-01-15']);
            Report::factory()->rejected()->create(['incident_date' => '2024-01-15']);

            $reports = $this->service->filterByDateRange(Carbon::parse('2024-01-01'), Carbon::parse('2024-01-31'));

            expect($reports->pluck('uuid')->all())->toBe([$published->uuid]);
        });

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
        it('returns only published reports', function (): void {
            $published = Report::factory()->create(['time_of_day' => TimeOfDay::Night]);
            Report::factory()->pending()->create(['time_of_day' => TimeOfDay::Night]);
            Report::factory()->rejected()->create(['time_of_day' => TimeOfDay::Night]);

            $reports = $this->service->filterByTimeOfDay(TimeOfDay::Night);

            expect($reports->pluck('uuid')->all())->toBe([$published->uuid]);
        });

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

describe('ReportService unpublished venues', function (): void {
    it('leaves a venue named for the first time in a submission unpublished', function (): void {
        $venueName = fake()->company();

        $this->service->createReport([
            'venue_name' => $venueName,
            'venue_city' => fake()->city(),
            'incident_date' => Carbon::yesterday()->toDateString(),
            'time_of_day' => TimeOfDay::Night,
            'description' => fake()->sentence(),
        ]);

        $this->assertDatabaseHas('venues', ['name' => $venueName, 'published_at' => null]);
    });

    it('adds a second submission about the same new venue to that venue', function (): void {
        $venue = Venue::factory()->unpublished()->create();

        $report = $this->service->createReport([
            'venue_name' => $venue->name,
            'venue_city' => $venue->city,
            'incident_date' => Carbon::yesterday()->toDateString(),
            'time_of_day' => TimeOfDay::Night,
            'description' => fake()->sentence(),
        ]);

        expect($report->venue_uuid)->toBe($venue->uuid);
    });

    it('publishes the venue when it publishes the first report about it', function (): void {
        $this->freezeSecond();
        $venue = Venue::factory()->unpublished()->create();
        $report = Report::factory()->for($venue, 'venue')->pending()->create();

        $this->service->reviewReport(uuid: $report->uuid, status: ReportStatus::Published);

        expect(Venue::find($venue->uuid)?->published_at?->equalTo(now()))->toBeTrue();
    });

    it('keeps the date an already published venue was published', function (): void {
        $publishedAt = now()->subMonth()->startOfSecond();
        $venue = Venue::factory()->create(['published_at' => $publishedAt]);
        $report = Report::factory()->for($venue, 'venue')->pending()->create();

        $this->service->reviewReport(uuid: $report->uuid, status: ReportStatus::Published);

        expect(Venue::find($venue->uuid)->published_at->equalTo($publishedAt))->toBeTrue();
    });

    it('leaves the venue unpublished when it rejects the report', function (): void {
        $venue = Venue::factory()->unpublished()->create();
        $report = Report::factory()->for($venue, 'venue')->pending()->create();

        $this->service->reviewReport(uuid: $report->uuid, status: ReportStatus::Rejected);

        $this->assertDatabaseHas('venues', ['uuid' => $venue->uuid, 'published_at' => null]);
    });

    it('loads unpublished venues for the pending reports', function (): void {
        $venue = Venue::factory()->unpublished()->create();
        Report::factory()->for($venue, 'venue')->pending()->create();

        $reports = $this->service->getPendingReports();

        expect($reports->first()->venue?->uuid)->toBe($venue->uuid);
    });

    it('loads unpublished venues for the recently reviewed reports', function (): void {
        $venue = Venue::factory()->unpublished()->create();
        Report::factory()->for($venue, 'venue')->rejected()->create(['reviewed_at' => now()]);

        $reports = $this->service->getRecentlyReviewedReports();

        expect($reports->first()->venue?->uuid)->toBe($venue->uuid);
    });
});
