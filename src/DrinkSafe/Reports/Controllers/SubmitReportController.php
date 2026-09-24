<?php

declare(strict_types=1);

namespace DrinkSafe\Reports\Controllers;

use App\Http\Controllers\Controller;
use DrinkSafe\Reports\Exceptions\ReportValidationException;
use DrinkSafe\Reports\Requests\StoreReportRequest;
use DrinkSafe\Reports\Services\ReportService;
use DrinkSafe\Shared\Seo\Seo;
use DrinkSafe\Venues\Exceptions\VenueNotFoundException;
use DrinkSafe\Venues\Models\Venue;
use DrinkSafe\Venues\Resources\VenueResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * SubmitReportController
 *
 * Handles the report submission workflow.
 * Provides the form page and processes submitted reports.
 */
final class SubmitReportController extends Controller
{
    /**
     * Create a new SubmitReportController instance.
     */
    public function __construct(
        private readonly ReportService $reportService
    ) {}

    /**
     * Display the report submission form.
     *
     * Venues are not preloaded here: the form searches them on demand via
     * the venues API, and shipping the whole table as a page prop made this
     * response large enough to time out at the edge. A `venue` query
     * parameter preselects one, which is how the venue pages link here.
     *
     * @param  Request  $request  HTTP request with an optional venue uuid
     * @return Response Inertia response for the submission form
     */
    public function create(Request $request): Response
    {
        $venue = Venue::where('uuid', $request->query('venue'))->first();

        $seo = Seo::default()
            ->withTitle('Submit a Report - Drink Safe')
            ->withDescription('Anonymously report a drink-spiking incident at a venue to help keep the community informed and safe.')
            ->noindex();

        return Inertia::render('SubmitReport', [
            'seo' => $seo->toArray(),
            'venue' => $venue instanceof Venue
                ? (new VenueResource($venue))->resolve()
                : null,
        ]);
    }

    /**
     * Store a newly submitted report.
     *
     * Creates the report and redirects to the venue detail page.
     * Displays success message on successful submission.
     *
     * @param  StoreReportRequest  $request  Validated report data
     * @return RedirectResponse Redirects to venue detail page
     */
    public function store(StoreReportRequest $request): RedirectResponse
    {
        try {
            $report = $this->reportService->createReport($request->validated());

            return redirect()
                ->route('venues.show', $report->venue_uuid)
                ->with('success', 'Report submitted successfully');
        } catch (ReportValidationException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['description' => $e->getMessage()]);
        } catch (VenueNotFoundException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['venue_uuid' => $e->getMessage()]);
        }
    }
}
