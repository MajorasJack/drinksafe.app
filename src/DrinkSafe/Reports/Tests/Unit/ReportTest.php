<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use DrinkSafe\Reports\Enums\TimeOfDay;
use DrinkSafe\Reports\Models\Report;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Report Model', function (): void {
    it('has UUID primary key not integer', function (): void {
        // Arrange
        $report = Report::factory()->create();

        // Act
        $keyType = $report->getKeyType();
        $isIncrementing = $report->getIncrementing();

        // Assert
        expect($keyType)->toBe('string')
            ->and($isIncrementing)->toBeFalse()
            ->and($report->uuid)->toBeString()
            ->and($report->uuid)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/');
    });

    it('has soft deletes trait', function (): void {
        // Arrange
        $report = Report::factory()->create();

        // Act
        $usesSoftDeletes = in_array(SoftDeletes::class, class_uses_recursive($report), true);

        // Assert
        expect($usesSoftDeletes)->toBeTrue()
            ->and($report->deleted_at)->toBeNull();
    });

    it('venue relationship returns BelongsTo', function (): void {
        // Arrange
        $report = Report::factory()->create();

        // Act
        $relationship = $report->venue();

        // Assert
        expect($relationship)->toBeInstanceOf(BelongsTo::class);
    });

    it('scopeRecent returns reports from last N days', function (): void {
        // Arrange
        Report::factory()->count(3)->create([
            'incident_date' => now()->subDays(5),
        ]);
        Report::factory()->count(2)->create([
            'incident_date' => now()->subDays(40),
        ]);

        // Act
        $recentReports = Report::recent(30)->get();

        // Assert
        expect($recentReports)->toHaveCount(3);
    });

    it('scopeByTimeOfDay filters reports correctly', function (): void {
        // Arrange
        Report::factory()->count(4)->night()->create();
        Report::factory()->count(2)->morning()->create();
        Report::factory()->count(1)->afternoon()->create();

        // Act
        $nightReports = Report::byTimeOfDay(TimeOfDay::Night)->get();
        $morningReports = Report::byTimeOfDay(TimeOfDay::Morning)->get();

        // Assert
        expect($nightReports)->toHaveCount(4)
            ->and($morningReports)->toHaveCount(2);
    });

    it('time_of_day casts to TimeOfDay enum', function (): void {
        // Arrange & Act
        $report = Report::factory()->create([
            'time_of_day' => TimeOfDay::Evening,
        ]);

        // Assert
        expect($report->time_of_day)->toBeInstanceOf(TimeOfDay::class)
            ->and($report->time_of_day)->toBe(TimeOfDay::Evening);
    });

    it('incident_date casts to Carbon date', function (): void {
        // Arrange & Act
        $report = Report::factory()->create([
            'incident_date' => '2024-03-15',
        ]);

        // Assert
        expect($report->incident_date)->toBeInstanceOf(CarbonImmutable::class)
            ->and($report->incident_date->format('Y-m-d'))->toBe('2024-03-15');
    });

    it('factory creates valid report with all required fields', function (): void {
        // Act
        $report = Report::factory()->create();

        // Assert
        expect($report->uuid)->not->toBeNull()
            ->and($report->venue_uuid)->not->toBeNull()
            ->and($report->incident_date)->toBeInstanceOf(CarbonImmutable::class)
            ->and($report->time_of_day)->toBeInstanceOf(TimeOfDay::class)
            ->and($report->description)->toBeString()
            ->and($report->description)->not->toBeEmpty();
    });

    it('factory recent state sets date within last 7 days', function (): void {
        // Act
        $report = Report::factory()->recent()->create();

        // Assert
        $sevenDaysAgo = now()->subDays(7)->startOfDay();
        $today = now()->endOfDay();

        expect($report->incident_date->gte($sevenDaysAgo))->toBeTrue()
            ->and($report->incident_date->lte($today))->toBeTrue();
    });

    it('factory night state sets time_of_day to Night', function (): void {
        // Act
        $report = Report::factory()->night()->create();

        // Assert
        expect($report->time_of_day)->toBe(TimeOfDay::Night);
    });

    it('soft delete works correctly', function (): void {
        // Arrange
        $report = Report::factory()->create();
        $uuid = $report->uuid;

        // Act
        $report->delete();

        // Assert
        expect($report->deleted_at)->not->toBeNull()
            ->and(Report::withTrashed()->find($uuid))->not->toBeNull()
            ->and(Report::find($uuid))->toBeNull();
    });

    it('formatted_date accessor returns human readable format', function (): void {
        // Arrange
        $report = Report::factory()->create([
            'incident_date' => now()->subDays(2),
        ]);

        // Act
        $formattedDate = $report->formatted_date;

        // Assert
        expect($formattedDate)->toBeString()
            ->and($formattedDate)->toContain('ago');
    });
});
