<?php

declare(strict_types=1);

namespace DrinkSafe\Reports\Observers;

use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Venues\Services\SafetyScoreService;

/**
 * ReportObserver
 *
 * Observes Report model events to maintain data integrity and cache consistency.
 * Automatically invalidates venue safety score cache when reports are created,
 * updated, or deleted.
 */
final class ReportObserver
{
    /**
     * Create a new observer instance.
     *
     * @param  SafetyScoreService  $safetyScoreService  Service for managing safety score cache
     */
    public function __construct(
        private readonly SafetyScoreService $safetyScoreService,
    ) {}

    /**
     * Handle the Report "created" event.
     *
     * Invalidates the venue's safety score cache so the next request
     * will compute a fresh score including the new report.
     *
     * @param  Report  $report  The created report
     */
    public function created(Report $report): void
    {
        $this->invalidateVenueScore($report);
    }

    /**
     * Handle the Report "updated" event.
     *
     * Invalidates the venue's safety score cache if the report's
     * venue or incident date has changed.
     *
     * @param  Report  $report  The updated report
     */
    public function updated(Report $report): void
    {
        if ($report->wasChanged(['venue_uuid', 'incident_date'])) {
            $this->invalidateVenueScore($report);

            $originalVenueUuid = $report->getOriginal('venue_uuid');
            if ($originalVenueUuid !== null && $originalVenueUuid !== $report->venue_uuid) {
                $this->safetyScoreService->invalidateScore($originalVenueUuid);
            }
        }
    }

    /**
     * Handle the Report "deleted" event.
     *
     * Invalidates the venue's safety score cache so the next request
     * will compute a fresh score without the deleted report.
     *
     * @param  Report  $report  The deleted report
     */
    public function deleted(Report $report): void
    {
        $this->invalidateVenueScore($report);
    }

    /**
     * Handle the Report "restored" event.
     *
     * Invalidates the venue's safety score cache when a soft-deleted
     * report is restored.
     *
     * @param  Report  $report  The restored report
     */
    public function restored(Report $report): void
    {
        $this->invalidateVenueScore($report);
    }

    /**
     * Invalidate the safety score cache for a report's venue.
     *
     * @param  Report  $report  The report whose venue cache should be invalidated
     */
    private function invalidateVenueScore(Report $report): void
    {
        $this->safetyScoreService->invalidateScore($report->venue_uuid);
    }
}
