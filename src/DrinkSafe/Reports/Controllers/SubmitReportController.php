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
     * Loads all venues for the venue selection dropdown.
     *
     * @return Response Inertia response with venues
     */
    public function create(): Response
    {
        $venues = Venue::orderBy('name')->get();

        $seo = Seo::default()
            ->withTitle('Submit a Report - Drink Safe')
            ->withDescription('Anonymously report a drink-spiking incident at a venue to help keep the community informed and safe.')
            ->noindex();

        return Inertia::render('SubmitReport', [
            'seo' => $seo->toArray(),
            'venues' => VenueResource::collection($venues)->resolve(),
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
