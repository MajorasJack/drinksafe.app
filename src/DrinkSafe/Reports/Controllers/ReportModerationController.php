<?php

declare(strict_types=1);

namespace DrinkSafe\Reports\Controllers;

use App\Http\Controllers\Controller;
use DrinkSafe\Reports\Enums\ReportStatus;
use DrinkSafe\Reports\Exceptions\VenueUnpublishedException;
use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Reports\Requests\BulkReviewReportsRequest;
use DrinkSafe\Reports\Requests\UpdateReportStatusRequest;
use DrinkSafe\Reports\Resources\ReportResource;
use DrinkSafe\Reports\Services\ReportFlagService;
use DrinkSafe\Reports\Services\ReportService;
use DrinkSafe\Shared\Seo\Seo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ReportModerationController
 *
 * The admin review queue. Lists pending reports and lets an admin publish
 * or reject them one at a time or several at once. Access is limited to
 * admins by ReportPolicy.
 */
final class ReportModerationController extends Controller
{
    /**
     * Create a new ReportModerationController instance.
     */
    public function __construct(
        private readonly ReportService $reportService,
        private readonly ReportFlagService $reportFlagService,
    ) {}

    /**
     * Show a page of pending reports, oldest first, then the latest decisions.
     *
     * Each report carries whether rejecting it would leave its venue with no
     * published reports, and each pending report carries its abuse flags.
     *
     * @return Response Inertia response for the review queue
     */
    public function index(): Response
    {
        $pendingReports = $this->reportService->getPendingReports();
        $reviewedReports = $this->reportService->getRecentlyReviewedReports();

        $this->reportFlagService->loadVenueCounts($reviewedReports->concat($pendingReports->items()));

        return Inertia::render('admin/Reports', [
            'seo' => Seo::default()->withTitle('Review reports - Drink Safe')->noindex()->toArray(),
            'pending_reports' => $pendingReports->getCollection()->map(fn (Report $report): array => [
                ...(new ReportResource($report))->resolve(),
                'flags' => $this->reportFlagService->flagsFor($report),
                'would_empty_venue' => $this->reportFlagService->wouldEmptyVenue($report),
            ])->all(),
            'pending_count' => $pendingReports->total(),
            'pending_pagination' => [
                'current_page' => $pendingReports->currentPage(),
                'last_page' => $pendingReports->lastPage(),
            ],
            'reviewed_reports' => $reviewedReports->map(fn (Report $report): array => [
                ...(new ReportResource($report))->resolve(),
                'would_empty_venue' => $this->reportFlagService->wouldEmptyVenue($report),
            ])->all(),
        ]);
    }

    /**
     * Publish or reject a report.
     *
     * @param  UpdateReportStatusRequest  $request  The validated decision
     * @param  string  $uuid  The report UUID
     * @return RedirectResponse Back to the review queue
     *
     * @throws ValidationException If publishing a report about a venue the admin unpublished
     */
    public function update(UpdateReportStatusRequest $request, string $uuid): RedirectResponse
    {
        try {
            $report = $this->reportService->reviewReport(
                uuid: $uuid,
                status: $request->status(),
                hideVenueIfEmpty: $request->hidesVenue(),
            );
        } catch (VenueUnpublishedException $exception) {
            throw ValidationException::withMessages(['status' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.reports.index')
            ->with('success', $report->status === ReportStatus::Published ? 'Report published.' : 'Report rejected.');
    }

    /**
     * Publish or reject several pending reports at once.
     *
     * Reports that are no longer pending are skipped, as are reports that
     * can't be published because the admin unpublished their venue.
     *
     * @param  BulkReviewReportsRequest  $request  The validated decision and reports
     * @return RedirectResponse Back to the review queue
     */
    public function bulk(BulkReviewReportsRequest $request): RedirectResponse
    {
        $reviewedCount = $this->reportService->reviewMany(
            uuids: $request->uuids(),
            status: $request->status(),
            hideVenueUuids: $request->hideVenueUuids(),
        );

        $message = sprintf('%d %s %s', $reviewedCount, Str::plural('report', $reviewedCount), $request->status()->value);

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back()->with('status', $message);
    }
}
