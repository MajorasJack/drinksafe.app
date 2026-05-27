<?php

declare(strict_types=1);

namespace DrinkSafe\Shared\Controllers;

use App\Http\Controllers\Controller;
use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Reports\Resources\ReportResource;
use DrinkSafe\Reports\Services\ReportService;
use DrinkSafe\Venues\Models\Venue;
use Inertia\Inertia;
use Inertia\Response;

/**
 * HomeController
 *
 * Displays the home page with recent incident reports and statistics.
 * Fetches the 5 most recent reports to give users a quick overview.
 */
final class HomeController extends Controller
{
    /**
     * Create a new HomeController instance.
     */
    public function __construct(
        private readonly ReportService $reportService
    ) {}

    /**
     * Display the home page with recent reports and stats.
     *
     * @return Response Inertia response with recent reports and statistics
     */
    public function __invoke(): Response
    {
        $recentReports = $this->reportService->getRecentReports(5);

        return Inertia::render('Home', [
            'recent_reports' => ReportResource::collection($recentReports)->resolve(),
            'stats' => [
                'total_venues' => Venue::count(),
                'total_reports' => Report::count(),
            ],
        ]);
    }
}
