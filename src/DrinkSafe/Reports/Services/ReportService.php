<?php

declare(strict_types=1);

namespace DrinkSafe\Reports\Services;

use Carbon\Carbon;
use DrinkSafe\Reports\Enums\TimeOfDay;
use DrinkSafe\Reports\Exceptions\ReportNotFoundException;
use DrinkSafe\Reports\Exceptions\ReportValidationException;
use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Venues\Models\Venue;
use DrinkSafe\Venues\Services\VenueService;
use Illuminate\Support\Collection;

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
     * Create a new ReportService instance.
     */
    public function __construct(
        private readonly ReportModerationService $moderationService,
        private readonly VenueService $venueService
    ) {}

    /**
     * Create a new report.
     *
     * Accepts either a venue UUID or venue details (name + city). If venue
     * details are provided, checks if venue exists or creates a new one.
     * Sanitises description and validates for PII before storage.
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
            $venue = Venue::where('name', $data['venue_name'])
                ->where('city', $data['venue_city'])
                ->first();

            if ($venue === null) {
                // Create new venue
                $venue = $this->venueService->createVenue([
                    'name' => $data['venue_name'],
                    'city' => $data['venue_city'],
                    'address' => $data['venue_address'] ?? null,
                    'latitude' => $data['latitude'] ?? 51.5074, // Default to London
                    'longitude' => $data['longitude'] ?? -0.1278,
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

        // Create report
        return Report::create([
            'venue_uuid' => $venue->uuid,
            'incident_date' => $data['incident_date'],
            'incident_time' => $data['incident_time'] ?? null,
            'time_of_day' => $data['time_of_day'],
            'description' => $description,
        ]);
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
