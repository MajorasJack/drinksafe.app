<?php

declare(strict_types=1);

use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Reports\Resources\ReportResource;
use Illuminate\Support\Facades\Schema;

it('has an optional incident_time column', function (): void {
    expect(Schema::hasColumn('reports', 'incident_time'))->toBeTrue();
});

it('persists the optional incident time', function (): void {
    $report = Report::factory()->withTime('23:15')->create();

    expect($report->fresh()->incident_time)->toBe('23:15');
});

it('exposes the incident time formatted as H:i', function (): void {
    $report = Report::factory()->withTime('09:05')->create();

    expect((new ReportResource($report))->resolve()['incident_time'])->toBe('09:05');
});

it('exposes a null incident time when none was recorded', function (): void {
    $report = Report::factory()->withoutTime()->create();

    expect((new ReportResource($report))->resolve()['incident_time'])->toBeNull();
});
