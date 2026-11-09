<?php

declare(strict_types=1);

namespace DrinkSafe\Reports\Controllers;

use App\Http\Controllers\Controller;
use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Reports\Requests\AdminReportSearchRequest;
use DrinkSafe\Reports\Resources\ReportResource;
use DrinkSafe\Reports\Services\ReportFlagService;
use DrinkSafe\Reports\Services\ReportService;
use DrinkSafe\Shared\Seo\Seo;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * AdminReportSearchController
 *
 * The admin's list of every report in any status, with filters. Rows are
 * published or taken down through the review queue's update action.
 */
final class AdminReportSearchController extends Controller
{
    /**
     * Create a new AdminReportSearchController instance.
     */
    public function __construct(
        private readonly ReportService $reportService,
        private readonly ReportFlagService $reportFlagService,
    ) {}

    /**
     * Show a page of reports matching the filters, newest first.
     *
     * A page past the end, left behind when a takedown empties the last page
     * of a filtered list, redirects to the last page that still has reports.
     *
     * @param  AdminReportSearchRequest  $request  The validated filters
     * @return Response|RedirectResponse Inertia response for the report list
     */
    public function __invoke(AdminReportSearchRequest $request): Response|RedirectResponse
    {
        $reports = $this->reportService->searchReports($request->filters());

        if ($reports->currentPage() > $reports->lastPage()) {
            return redirect()->route('admin.reports.search', [...$request->filters(), 'page' => $reports->lastPage()]);
        }

        $this->reportFlagService->loadVenueCounts($reports->getCollection());

        return Inertia::render('admin/ReportSearch', [
            'seo' => Seo::default()->withTitle('Reports - Drink Safe')->noindex()->toArray(),
            'reports' => $reports->getCollection()->map(fn (Report $report): array => [
                ...(new ReportResource($report))->resolve(),
                'would_empty_venue' => $this->reportFlagService->wouldEmptyVenue($report),
            ])->all(),
            'total' => $reports->total(),
            'pagination' => [
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
            ],
            'filters' => $request->filters(),
        ]);
    }
}
