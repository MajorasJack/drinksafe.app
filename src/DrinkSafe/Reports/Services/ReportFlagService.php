<?php

declare(strict_types=1);

namespace DrinkSafe\Reports\Services;

use DrinkSafe\Reports\Enums\ReportStatus;
use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Reports\Scopes\PublishedScope;
use DrinkSafe\Venues\Models\Venue;
use DrinkSafe\Venues\Scopes\PublishedVenueScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ReportFlagService
 *
 * Works out the abuse signals the review queue shows as badges, and whether
 * taking a report down would leave its venue with nothing published. Uses
 * only report and venue data. The flags are prompts to look closer, never
 * automatic actions.
 */
final class ReportFlagService
{
    /**
     * Load the venue counts the flags need onto every report's venue, in one query.
     *
     * Call this before flagsFor() or wouldEmptyVenue() on a page of reports,
     * so they don't query once per report.
     *
     * @param  Collection<int, Report>  $reports  Reports with their venue loaded
     */
    public function loadVenueCounts(Collection $reports): void
    {
        $venues = new Collection($reports->pluck('venue')->filter()->all());

        if ($venues->isEmpty()) {
            return;
        }

        $venues->loadCount([
            'reports as pending_reports_count' => fn (Builder $query): Builder => $query
                ->withoutGlobalScope(PublishedScope::class)
                ->where('status', ReportStatus::Pending),
            'reports as recent_reports_count' => fn (Builder $query): Builder => $query
                ->withoutGlobalScope(PublishedScope::class)
                ->where('created_at', '>=', now()->subDay()),
            'reports as published_reports_count',
        ]);
    }

    /**
     * Get the abuse signals for a report.
     *
     * - personal_details_removed: moderation replaced an email address or phone number in the description
     * - new_venue_several_pending: the venue has never been published and has more than one pending report
     * - venue_report_spike: the venue got at least the configured number of reports in the last 24 hours
     * - default_coordinates: the venue sits on the point used when a submission has no coordinates
     *
     * @param  Report  $report  A report with its venue loaded
     * @return array<int, string> The flags that apply, in the order above
     */
    public function flagsFor(Report $report): array
    {
        $venue = $this->venueWithCounts($report);

        return array_keys(array_filter([
            'personal_details_removed' => preg_match('/\[(EMAIL|PHONE) REMOVED\]/', $report->description) === 1,
            'new_venue_several_pending' => $venue->published_at === null
                && ! $venue->wasUnpublishedByAdmin()
                && $venue->pending_reports_count > 1,
            'venue_report_spike' => $venue->recent_reports_count >= (int) config('drinksafe.admin.report_spike_threshold'),
            'default_coordinates' => $venue->isAtDefaultCoordinates(),
        ]));
    }

    /**
     * Whether rejecting this report would leave its published venue with no published reports.
     *
     * @param  Report  $report  A report with its venue loaded
     */
    public function wouldEmptyVenue(Report $report): bool
    {
        $venue = $this->venueWithCounts($report);

        return $venue->published_at !== null
            && $venue->published_reports_count - ($report->status === ReportStatus::Published ? 1 : 0) === 0;
    }

    /**
     * Get the report's venue, loading its counts if loadVenueCounts() hasn't.
     *
     * @param  Report  $report  A report with its venue loaded
     */
    private function venueWithCounts(Report $report): Venue
    {
        $report->loadMissing(['venue' => fn (BelongsTo $query): BelongsTo => $query->withoutGlobalScope(PublishedVenueScope::class)]);

        if ($report->venue->getAttribute('pending_reports_count') === null) {
            $this->loadVenueCounts(new Collection([$report]));
        }

        return $report->venue;
    }
}
