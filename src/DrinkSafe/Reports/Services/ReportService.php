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
use DrinkSafe\Shared\Services\PublicCacheVersion;
use DrinkSafe\Venues\Models\Venue;
use DrinkSafe\Venues\Scopes\PublishedVenueScope;
use DrinkSafe\Venues\Services\SafetyScoreService;
use DrinkSafe\Venues\Services\VenueService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
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
        private readonly SafetyScoreService $safetyScoreService,
        private readonly PublicCacheVersion $publicCacheVersion,
    ) {}

    /**
     * Create a new report.
     *
     * Accepts either a venue UUID or venue details (name + city). If venue
     * details are provided, checks if venue exists or creates a new one.
     * Sanitises description and validates for PII before storage.
     *
     * The report is saved as pending and stays hidden until an admin
     * publishes it. The moderators get an email saying one is waiting.
     * A venue created here is unpublished until that happens too. The lookup
     * includes unpublished venues, so a second report naming the same new
     * venue joins the first rather than creating a copy.
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
            // Check if venue exists by name+city
            $venue = Venue::withoutGlobalScope(PublishedVenueScope::class)
                ->where('name', $data['venue_name'])
                ->where('city', $data['venue_city'])
                ->first();

            if ($venue === null) {
                // Create new venue
                $venue = $this->venueService->createVenue([
                    'name' => $data['venue_name'],
                    'city' => $data['venue_city'],
                    'address' => $data['venue_address'] ?? null,
                    'latitude' => $data['latitude'] ?? self::DEFAULT_LATITUDE,
                    'longitude' => $data['longitude'] ?? self::DEFAULT_LONGITUDE,
                ]);
            }
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
     * The report observer only clears the venue's score when a report's
     * status changes, so the venue clears its own caches once this commits.
     *
     * @param  Venue  $venue  The venue, locked for this transaction
     */
    private function hideVenueIfItHasNoPublishedReports(Venue $venue): void
    {
        if ($venue->published_at === null || $venue->reports()->exists()) {
            return;
        }

        $venue->forceFill([
            'published_at' => null,
            'unpublished_at' => now(),
        ])->save();

        DB::afterCommit(function () use ($venue): void {
            $this->safetyScoreService->invalidateScore($venue->uuid);
            $this->publicCacheVersion->bump();
        });
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
}
