<?php

declare(strict_types=1);

namespace DrinkSafe\Reports\Observers;

use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Shared\Services\PublicCacheVersion;
use DrinkSafe\Venues\Services\SafetyScoreService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

/**
 * ReportObserver
 *
 * Observes Report model events to maintain data integrity and cache consistency.
 * Automatically invalidates venue safety score cache when reports are created,
 * updated, or deleted, and clears the public heatmap and analytics caches
 * when a report's status changes.
 *
 * Runs after the surrounding transaction commits, so a rolled-back review
 * never clears a cache, and a cache can't be rebuilt from data that hasn't
 * committed yet.
 */
final class ReportObserver implements ShouldHandleEventsAfterCommit
{
    /**
     * Create a new observer instance.
     *
     * @param  SafetyScoreService  $safetyScoreService  Service for managing safety score cache
     * @param  PublicCacheVersion  $publicCacheVersion  Version of the heatmap and analytics caches
     */
    public function __construct(
        private readonly SafetyScoreService $safetyScoreService,
        private readonly PublicCacheVersion $publicCacheVersion,
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
     * venue, incident date or review status has changed. A status change
     * also clears the public heatmap and analytics caches, so a takedown
     * disappears from them straight away.
     *
     * @param  Report  $report  The updated report
     */
    public function updated(Report $report): void
    {
        if ($report->wasChanged(['venue_uuid', 'incident_date', 'status'])) {
            $this->invalidateVenueScore($report);

            // after a commit, getOriginal() already holds the new venue
            $previousVenueUuid = $report->getPrevious()['venue_uuid'] ?? null;
            if ($previousVenueUuid !== null) {
                $this->safetyScoreService->invalidateScore($previousVenueUuid);
            }
        }

        if ($report->wasChanged('status')) {
            $this->publicCacheVersion->bump();
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
