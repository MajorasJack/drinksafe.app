<?php

declare(strict_types=1);

namespace DrinkSafe\Reports\Services;

use Carbon\Carbon;
use DrinkSafe\Reports\Enums\ReportStatus;
use DrinkSafe\Reports\Enums\TimeOfDay;
use DrinkSafe\Reports\Exceptions\ReportNotFoundException;
use DrinkSafe\Reports\Exceptions\ReportValidationException;
use DrinkSafe\Reports\Exceptions\VenueUnpublishedException;
use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Reports\Notifications\ReportAwaitingReviewNotification;
use DrinkSafe\Reports\Scopes\PublishedScope;
use DrinkSafe\Shared\Controllers\SitemapController;
use DrinkSafe\Venues\Models\Venue;
use DrinkSafe\Venues\Scopes\PublishedVenueScope;
use DrinkSafe\Venues\Services\VenueService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * ReportService
 *
 * Handles business logic for report operations including creation,
 * retrieval, filtering, and deletion. Integrates with moderation
 * service to ensure PII is detected and sanitised.
 */
final class ReportService
{
    /**
     * Latitude given to a new venue when the submission has no coordinates (central London).
     */
    public const float DEFAULT_LATITUDE = 51.5074;

    /**
     * Longitude given to a new venue when the submission has no coordinates (central London).
     */
    public const float DEFAULT_LONGITUDE = -0.1278;

    /**
     * Create a new ReportService instance.
     */
    public function __construct(
        private readonly ReportModerationService $moderationService,
        private readonly VenueService $venueService,
    ) {}

    /**
     * Create a new report.
     *
     * Accepts either a venue UUID or venue details (name + city). If venue
     * details are provided, `VenueService::findSameVenue()` looks for the
     * venue they mean, and a new one is created when there isn't one.
     * Sanitises description and validates for PII before storage.
     *
     * The report is saved as pending and stays hidden until an admin
     * publishes it. The moderators get an email saying one is waiting.
     * A venue created here is unpublished until that happens too. The lookup
     * includes unpublished venues, so a second report naming the same new
     * venue joins the first rather than creating a copy. A submission with
     * no coordinates naming a chain can't say which branch it means, so it
     * gets a new venue at the default coordinates for the admin to place.
     *
     * @param  array<string, mixed>  $data  Report data
     *
     * @throws ReportValidationException If PII detected that cannot be sanitised
     */
    public function createReport(array $data): Report
    {
        // Handle venue lookup or creation
        if (isset($data['venue_uuid'])) {
            $venue = $this->venueService->getVenueById($data['venue_uuid']);
        } else {
            $hasCoordinates = isset($data['latitude'], $data['longitude']);

            $venue = $this->venueService->findSameVenue(
                name: $data['venue_name'],
                city: $data['venue_city'],
                latitude: $hasCoordinates ? (float) $data['latitude'] : null,
                longitude: $hasCoordinates ? (float) $data['longitude'] : null,
            ) ?? $this->venueService->createUnpublishedVenue([
                'name' => $data['venue_name'],
                'city' => $data['venue_city'],
                'address' => $data['venue_address'] ?? null,
                'latitude' => $hasCoordinates ? $data['latitude'] : self::DEFAULT_LATITUDE,
                'longitude' => $hasCoordinates ? $data['longitude'] : self::DEFAULT_LONGITUDE,
            ]);
        }

        // Validate and sanitise description
        $description = $data['description'];

        if (! $this->moderationService->validateDescription($description)) {
            $description = $this->moderationService->sanitizeDescription($description);

            // If still contains PII after sanitisation, throw exception
            if (! $this->moderationService->validateDescription($description)) {
                throw new ReportValidationException(
                    'Description contains personal information that cannot be automatically removed. Please remove names, emails, and phone numbers.'
                );
            }
        }

        $report = Report::create([
            'venue_uuid' => $venue->uuid,
            'incident_date' => $data['incident_date'],
            'incident_time' => $data['incident_time'] ?? null,
            'time_of_day' => $data['time_of_day'],
            'description' => $description,
        ])->setRelation('venue', $venue);

        // The report is already saved, so a mail outage must not fail the submission.
        rescue(fn (): mixed => Notification::route('mail', config('services.reports.alert_email'))
            ->notify(new ReportAwaitingReviewNotification));

        return $report;
    }

    /**
     * Get a page of the reports waiting for review, oldest first.
     *
     * @param  int  $perPage  Reports per page (default: 50)
     * @return LengthAwarePaginator<int, Report>
     */
    public function getPendingReports(int $perPage = 50): LengthAwarePaginator
    {
        return Report::withoutGlobalScope(PublishedScope::class)
            ->with(['venue' => fn (BelongsTo $query): BelongsTo => $query->withoutGlobalScope(PublishedVenueScope::class)])
            ->where('status', ReportStatus::Pending)
            ->oldest()
            ->oldest('uuid')
            ->paginate($perPage);
    }

    /**
     * Count the reports waiting for review.
     */
    public function countPendingReports(): int
    {
        return Report::withoutGlobalScope(PublishedScope::class)
            ->where('status', ReportStatus::Pending)
            ->count();
    }

    /**
     * Get the most recently published or rejected reports.
     *
     * @param  int  $limit  Maximum number of reports to return (default: 20)
     * @return Collection<int, Report>
     */
    public function getRecentlyReviewedReports(int $limit = 20): Collection
    {
        return Report::withoutGlobalScope(PublishedScope::class)
            ->with(['venue' => fn (BelongsTo $query): BelongsTo => $query->withoutGlobalScope(PublishedVenueScope::class)])
            ->whereIn('status', [ReportStatus::Published, ReportStatus::Rejected])
            ->whereNotNull('reviewed_at')
            ->latest('reviewed_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get a page of reports in any status, newest first, narrowed by the admin's filters.
     *
     * The description search matches reports containing every word typed, in
     * part or whole. It uses `LIKE` rather than the full-text index, which
     * skips words under three letters and common words such as "the".
     *
     * @param  array{venue?: string, city?: string, status?: string, from?: string, to?: string, q?: string}  $filters  Venue name, city, status, incident date range (inclusive) and description text
     * @param  int  $perPage  Reports per page (default: 25)
     * @return LengthAwarePaginator<int, Report>
     */
    public function searchReports(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        return Report::withoutGlobalScope(PublishedScope::class)
            ->with(['venue' => fn (BelongsTo $query): BelongsTo => $query->withoutGlobalScope(PublishedVenueScope::class)])
            ->when($filters['status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->when($filters['from'] ?? null, fn (Builder $query, string $from): Builder => $query->whereDate('incident_date', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $query, string $to): Builder => $query->whereDate('incident_date', '<=', $to))
            ->when(isset($filters['venue']) || isset($filters['city']), fn (Builder $query): Builder => $query->whereHas(
                'venue',
                fn (Builder $venueQuery): Builder => $venueQuery
                    ->withoutGlobalScope(PublishedVenueScope::class)
                    ->when($filters['venue'] ?? null, fn (Builder $venueQuery, string $name): Builder => $this->whereContains(query: $venueQuery, column: 'name', term: $name))
                    ->when($filters['city'] ?? null, fn (Builder $venueQuery, string $city): Builder => $venueQuery->where('city', $city)),
            ))
            ->when($filters['q'] ?? null, fn (Builder $query, string $text): Builder => collect(preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY))
                ->reduce(fn (Builder $query, string $word): Builder => $this->whereContains(query: $query, column: 'description', term: $word), $query))
            ->latest()
            ->latest('uuid')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Publish or reject a report, whatever its current status.
     *
     * Publishing a report also publishes its venue, if this is the first
     * report about it. Both happen in one transaction, so a venue never goes
     * public without the report that was approved alongside it. A venue an
     * admin unpublished stays unpublished: publishing a report about it is
     * refused until the venue itself is published again.
     *
     * Rejecting with $hideVenueIfEmpty unpublishes a published venue that is
     * left with no published reports, and marks it as unpublished by the admin.
     *
     * @param  string  $uuid  The report UUID
     * @param  ReportStatus  $status  The admin's decision
     * @param  bool  $hideVenueIfEmpty  Whether a rejection that empties the venue also hides it
     *
     * @throws ModelNotFoundException If no report has this UUID
     * @throws VenueUnpublishedException If publishing a report about a venue the admin unpublished
     */
    public function reviewReport(string $uuid, ReportStatus $status, bool $hideVenueIfEmpty = false): Report
    {
        $report = Report::withoutGlobalScope(PublishedScope::class)
            ->where('uuid', $uuid)
            ->firstOrFail();

        return DB::transaction(function () use ($report, $status, $hideVenueIfEmpty): Report {
            $venue = Venue::withoutGlobalScope(PublishedVenueScope::class)
                ->whereKey($report->venue_uuid)
                ->lockForUpdate()
                ->firstOrFail();

            if ($status === ReportStatus::Published) {
                throw_if($venue->wasUnpublishedByAdmin(), VenueUnpublishedException::class);

                if ($venue->published_at === null) {
                    $venue->forceFill(['published_at' => now()])->save();
                    DB::afterCommit(fn (): bool => Cache::forget(SitemapController::CACHE_KEY));
                }
            }

            $report->forceFill([
                'status' => $status,
                'reviewed_at' => now(),
            ])->save();

            if ($status === ReportStatus::Rejected && $hideVenueIfEmpty) {
                $this->hideVenueIfItHasNoPublishedReports($venue);
            }

            return $report;
        });
    }

    /**
     * Publish or reject several reports, skipping any that are no longer pending.
     *
     * Each report goes through reviewReport() in its own transaction, so one
     * that can't be published (its venue was unpublished by the admin) doesn't
     * undo the others. An unknown UUID counts as no longer pending.
     *
     * @param  array<int, string>  $uuids  The report UUIDs
     * @param  ReportStatus  $status  The admin's decision
     * @param  array<int, string>  $hideVenueUuids  Venues to hide if a rejection leaves them with no published reports
     * @return array{reviewed: int, not_pending: int, venue_unpublished: int} How many reports were reviewed, and how many were skipped and why
     */
    public function reviewMany(array $uuids, ReportStatus $status, array $hideVenueUuids = []): array
    {
        $outcomes = collect($uuids)
            ->unique()
            ->map(fn (string $uuid): string => $this->reviewIfPending(uuid: $uuid, status: $status, hideVenueUuids: $hideVenueUuids))
            ->countBy();

        return [
            'reviewed' => $outcomes->get('reviewed', 0),
            'not_pending' => $outcomes->get('not_pending', 0),
            'venue_unpublished' => $outcomes->get('venue_unpublished', 0),
        ];
    }

    /**
     * Review one report from a bulk review if it is still pending.
     *
     * @param  string  $uuid  The report UUID
     * @param  ReportStatus  $status  The admin's decision
     * @param  array<int, string>  $hideVenueUuids  Venues to hide if a rejection leaves them with no published reports
     * @return string reviewed, not_pending or venue_unpublished
     */
    private function reviewIfPending(string $uuid, ReportStatus $status, array $hideVenueUuids): string
    {
        try {
            return DB::transaction(function () use ($uuid, $status, $hideVenueUuids): string {
                $pendingReport = Report::withoutGlobalScope(PublishedScope::class)
                    ->whereKey($uuid)
                    ->where('status', ReportStatus::Pending)
                    ->lockForUpdate()
                    ->first();

                if ($pendingReport === null) {
                    return 'not_pending';
                }

                $this->reviewReport(
                    uuid: $uuid,
                    status: $status,
                    hideVenueIfEmpty: in_array($pendingReport->venue_uuid, $hideVenueUuids, true),
                );

                return 'reviewed';
            });
        } catch (VenueUnpublishedException) {
            return 'venue_unpublished';
        }
    }

    /**
     * Unpublish a published venue that has no published reports left.
     *
     * Goes through the venue page's own unpublish action, which also clears
     * the venue's caches once this commits.
     *
     * @param  Venue  $venue  The venue, locked for this transaction
     */
    private function hideVenueIfItHasNoPublishedReports(Venue $venue): void
    {
        if ($venue->published_at === null || $venue->reports()->exists()) {
            return;
        }

        $this->venueService->unpublishVenue($venue->uuid);
    }

    /**
     * Get a single report by UUID.
     *
     * @param  string  $uuid  The report UUID
     *
     * @throws ReportNotFoundException If report not found
     */
    public function getReportById(string $uuid): Report
    {
        $report = Report::with('venue')->where('uuid', $uuid)->first();

        if ($report === null) {
            throw new ReportNotFoundException($uuid);
        }

        return $report;
    }

    /**
     * Get recent reports.
     *
     * Retrieves reports from the last N days, ordered by creation date
     * descending. Eager loads venue relationship for efficiency.
     *
     * @param  int  $limit  Maximum number of reports to return (default: 10)
     * @return Collection<int, Report>
     */
    public function getRecentReports(int $limit = 10): Collection
    {
        return Report::with('venue')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Filter reports by date range.
     *
     * Returns all reports with incident dates between the start and end
     * dates (inclusive). Eager loads venue relationship.
     *
     * @param  Carbon  $start  Start date (inclusive)
     * @param  Carbon  $end  End date (inclusive)
     * @return Collection<int, Report>
     */
    public function filterByDateRange(Carbon $start, Carbon $end): Collection
    {
        return Report::with('venue')
            ->whereBetween('incident_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('incident_date', 'desc')
            ->get();
    }

    /**
     * Filter reports by time of day.
     *
     * Returns all reports matching the specified time of day.
     * Uses the Report model's scope for consistent filtering.
     *
     * @param  TimeOfDay  $timeOfDay  Time of day to filter by
     * @return Collection<int, Report>
     */
    public function filterByTimeOfDay(TimeOfDay $timeOfDay): Collection
    {
        return Report::with('venue')
            ->byTimeOfDay($timeOfDay)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Delete a report (soft delete).
     *
     * @param  string  $uuid  The report UUID
     * @return bool True on successful deletion
     *
     * @throws ReportNotFoundException If report not found
     */
    public function deleteReport(string $uuid): bool
    {
        $report = Report::where('uuid', $uuid)->first();

        if ($report === null) {
            throw new ReportNotFoundException($uuid);
        }

        return $report->delete();
    }

    /**
     * Match rows whose column contains the term, treating `%` and `_` in it as plain characters.
     *
     * @param  Builder<Model>  $query  The query to narrow
     * @param  string  $column  The column to search
     * @param  string  $term  The text the admin typed
     * @return Builder<Model>
     */
    private function whereContains(Builder $query, string $column, string $term): Builder
    {
        return $query->whereRaw(
            sprintf('%s LIKE ? ESCAPE ?', $query->getQuery()->getGrammar()->wrap($query->qualifyColumn($column))),
            [sprintf('%%%s%%', addcslashes($term, '\\%_')), '\\'],
        );
    }
}
