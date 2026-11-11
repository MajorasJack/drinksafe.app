<?php

declare(strict_types=1);

namespace DrinkSafe\Reports\Controllers;

use App\Http\Controllers\Controller;
use DrinkSafe\Reports\Enums\ReportStatus;
use DrinkSafe\Reports\Exceptions\ReportNotPendingException;
use DrinkSafe\Reports\Exceptions\VenueUnpublishedException;
use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Reports\Requests\BulkReviewReportsRequest;
use DrinkSafe\Reports\Requests\MoveReportRequest;
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
     * A page past the end, left behind when a review empties the last page,
     * redirects to the last page that still has reports.
     *
     * @return Response|RedirectResponse Inertia response for the review queue
     */
    public function index(): Response|RedirectResponse
    {
        $pendingReports = $this->reportService->getPendingReports();

        if ($pendingReports->currentPage() > $pendingReports->lastPage()) {
            return redirect()->route('admin.reports.index', ['page' => $pendingReports->lastPage()]);
        }

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
            ->to(url()->previous(route('admin.reports.index')))
            ->with('success', $report->status === ReportStatus::Published ? 'Report published.' : 'Report rejected.');
    }

    /**
     * Move a pending report onto another venue.
     *
     * @param  MoveReportRequest  $request  The validated venue
     * @param  string  $uuid  The report UUID
     * @return RedirectResponse Back to the page the admin came from
     *
     * @throws ValidationException If the report is no longer pending
     */
    public function move(MoveReportRequest $request, string $uuid): RedirectResponse
    {
        try {
            $report = $this->reportService->moveReportToVenue(uuid: $uuid, venueUuid: $request->validated('venue_uuid'));
        } catch (ReportNotPendingException $exception) {
            throw ValidationException::withMessages(['venue_uuid' => $exception->getMessage()]);
        }

        return redirect()
            ->to(url()->previous(route('admin.reports.index')))
            ->with('success', sprintf('Report moved to %s, %s.', $report->venue->name, $report->venue->city));
    }

    /**
     * Publish or reject several pending reports at once.
     *
     * Reports that are no longer pending are skipped, as are reports that
     * can't be published because the admin unpublished their venue. The
     * message says how many were skipped and why.
     *
     * @param  BulkReviewReportsRequest  $request  The validated decision and reports
     * @return RedirectResponse Back to the review queue
     */
    public function bulk(BulkReviewReportsRequest $request): RedirectResponse
    {
        $outcome = $this->reportService->reviewMany(
            uuids: $request->uuids(),
            status: $request->status(),
            hideVenueUuids: $request->hideVenueUuids(),
        );

        $skippedReasons = collect([
            $outcome['venue_unpublished'] > 0 ? sprintf(
                '%d skipped because %s venue is unpublished. Publish the venue first.',
                $outcome['venue_unpublished'],
                $outcome['venue_unpublished'] === 1 ? 'its' : 'their',
            ) : null,
            $outcome['not_pending'] > 0 ? sprintf(
                '%d skipped because %s no longer pending.',
                $outcome['not_pending'],
                $outcome['not_pending'] === 1 ? 'it is' : 'they are',
            ) : null,
        ])->filter();

        $message = collect([
            sprintf('%d %s %s', $outcome['reviewed'], Str::plural('report', $outcome['reviewed']), $request->status()->value),
            $skippedReasons->implode(' '),
        ])->filter()->implode('. ');

        Inertia::flash('toast', [
            'type' => $skippedReasons->isEmpty() ? 'success' : 'warning',
            'message' => $message,
        ]);

        return redirect()->to(url()->previous(route('admin.reports.index')))->with('status', $message);
    }
}
