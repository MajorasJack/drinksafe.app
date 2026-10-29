<?php

declare(strict_types=1);

namespace DrinkSafe\Reports\Controllers;

use App\Http\Controllers\Controller;
use DrinkSafe\Reports\Enums\ReportStatus;
use DrinkSafe\Reports\Requests\UpdateReportStatusRequest;
use DrinkSafe\Reports\Resources\ReportResource;
use DrinkSafe\Reports\Services\ReportService;
use DrinkSafe\Shared\Seo\Seo;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ReportModerationController
 *
 * The admin review queue. Lists pending reports and lets an admin publish
 * or reject each one. Access is limited to admins by ReportPolicy.
 */
final class ReportModerationController extends Controller
{
    /**
     * Create a new ReportModerationController instance.
     */
    public function __construct(
        private readonly ReportService $reportService
    ) {}

    /**
     * Show pending reports, oldest first, then the latest decisions.
     *
     * @return Response Inertia response for the review queue
     */
    public function index(): Response
    {
        return Inertia::render('admin/Reports', [
            'seo' => Seo::default()->withTitle('Review reports - Drink Safe')->noindex()->toArray(),
            'pending_reports' => ReportResource::collection($this->reportService->getPendingReports())->resolve(),
            'pending_count' => $this->reportService->countPendingReports(),
            'reviewed_reports' => ReportResource::collection($this->reportService->getRecentlyReviewedReports())->resolve(),
        ]);
    }

    /**
     * Publish or reject a report.
     *
     * @param  UpdateReportStatusRequest  $request  The validated decision
     * @param  string  $uuid  The report UUID
     * @return RedirectResponse Back to the review queue
     */
    public function update(UpdateReportStatusRequest $request, string $uuid): RedirectResponse
    {
        $report = $this->reportService->reviewReport(uuid: $uuid, status: $request->status());

        return redirect()
            ->route('admin.reports.index')
            ->with('success', $report->status === ReportStatus::Published ? 'Report published.' : 'Report rejected.');
    }
}
