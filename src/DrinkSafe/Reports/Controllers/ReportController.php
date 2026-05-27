<?php

declare(strict_types=1);

namespace DrinkSafe\Reports\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use DrinkSafe\Reports\Enums\TimeOfDay;
use DrinkSafe\Reports\Exceptions\ReportNotFoundException;
use DrinkSafe\Reports\Exceptions\ReportValidationException;
use DrinkSafe\Reports\Requests\StoreReportRequest;
use DrinkSafe\Reports\Resources\ReportCollection;
use DrinkSafe\Reports\Resources\ReportResource;
use DrinkSafe\Reports\Services\ReportService;
use DrinkSafe\Venues\Exceptions\VenueNotFoundException;
use DrinkSafe\Venues\Services\VenueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * ReportController
 *
 * Handles HTTP requests for report-related operations.
 * Provides endpoints for listing, viewing, and creating reports.
 * Delegates business logic to ReportService and VenueService.
 */
final class ReportController extends Controller
{
    /**
     * Create a new ReportController instance.
     */
    public function __construct(
        private readonly ReportService $reportService,
        private readonly VenueService $venueService
    ) {}

    /**
     * List reports with optional filtering.
     *
     * Supports filtering by:
     * - venue_uuid: Show reports for a specific venue
     * - start_date + end_date: Filter by date range
     * - time_of_day: Filter by time period
     * - limit: Maximum number of results (default 50, max 100)
     *
     * Query parameters:
     * - venue_uuid (string, optional): Venue UUID to filter by
     * - start_date (string, optional): Start date (Y-m-d format)
     * - end_date (string, optional): End date (Y-m-d format)
     * - time_of_day (string, optional): Morning|Afternoon|Evening|Night|Unknown
     * - limit (int, optional): Results limit (default 50, max 100)
     *
     * @return JsonResponse Returns collection of reports with HTTP 200
     */
    public function index(Request $request): JsonResponse
    {
        // Validate query parameters
        $validated = $request->validate([
            'venue_uuid' => ['nullable', 'string', 'exists:venues,uuid'],
            'start_date' => ['nullable', 'date', 'required_with:end_date'],
            'end_date' => ['nullable', 'date', 'required_with:start_date', 'after_or_equal:start_date'],
            'time_of_day' => ['nullable', 'string', 'in:Morning,Afternoon,Evening,Night,Unknown'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $limit = (int) ($validated['limit'] ?? 50);

        // Filter by venue
        if (isset($validated['venue_uuid'])) {
            $reports = $this->venueService->getVenueReports($validated['venue_uuid'], $limit);

            return response()->json(
                new ReportCollection($reports),
                Response::HTTP_OK
            );
        }

        // Filter by date range
        if (isset($validated['start_date'], $validated['end_date'])) {
            $reports = $this->reportService->filterByDateRange(
                Carbon::parse($validated['start_date']),
                Carbon::parse($validated['end_date'])
            );

            return response()->json(
                new ReportCollection($reports->take($limit)),
                Response::HTTP_OK
            );
        }

        // Filter by time of day
        if (isset($validated['time_of_day'])) {
            $reports = $this->reportService->filterByTimeOfDay(
                TimeOfDay::from($validated['time_of_day'])
            );

            return response()->json(
                new ReportCollection($reports->take($limit)),
                Response::HTTP_OK
            );
        }

        // Default: Return recent reports
        $reports = $this->reportService->getRecentReports($limit);

        return response()->json(
            new ReportCollection($reports),
            Response::HTTP_OK
        );
    }

    /**
     * Show a single report by UUID.
     *
     * Returns report with venue relationship eager loaded.
     *
     * @param  string  $uuid  The report UUID
     * @return JsonResponse Returns report resource with HTTP 200, or error with HTTP 404
     */
    public function show(string $uuid): JsonResponse
    {
        try {
            $report = $this->reportService->getReportById($uuid);

            return response()->json(
                new ReportResource($report),
                Response::HTTP_OK
            );
        } catch (ReportNotFoundException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Create a new report.
     *
     * Accepts either:
     * - venue_uuid (existing venue), OR
     * - venue_name + venue_city (creates or finds venue)
     *
     * Validates description for PII (personal information).
     * If venue_name + venue_city match an existing venue, uses that venue.
     * If not, creates a new venue with provided details.
     *
     * @param  StoreReportRequest  $request  Validated request data
     * @return JsonResponse Returns created report with HTTP 201, or error with HTTP 422/404
     */
    public function store(StoreReportRequest $request): JsonResponse
    {
        try {
            $report = $this->reportService->createReport($request->validated());

            Log::info('Report submitted successfully', [
                'report_uuid' => $report->uuid,
                'venue_uuid' => $report->venue_uuid,
                'incident_date' => $report->incident_date->toDateString(),
                'time_of_day' => $report->time_of_day->value,
                'ip_hash' => hash('sha256', $request->ip()),
                'user_agent_hash' => hash('sha256', $request->userAgent() ?? 'unknown'),
                'timestamp' => now()->toIso8601String(),
            ]);

            return response()->json([
                'message' => 'Report created successfully',
                'data' => new ReportResource($report->load('venue')),
            ], Response::HTTP_CREATED);
        } catch (ReportValidationException $e) {
            Log::warning('Report validation failed', [
                'error' => $e->getMessage(),
                'ip_hash' => hash('sha256', $request->ip()),
                'timestamp' => now()->toIso8601String(),
            ]);

            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (VenueNotFoundException $e) {
            Log::warning('Venue not found during report creation', [
                'error' => $e->getMessage(),
                'ip_hash' => hash('sha256', $request->ip()),
                'timestamp' => now()->toIso8601String(),
            ]);

            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        }
    }
}
