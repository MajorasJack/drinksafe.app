<?php

declare(strict_types=1);

namespace DrinkSafe\Venues\Controllers;

use App\Http\Controllers\Controller;
use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Reports\Resources\ReportResource;
use DrinkSafe\Reports\Services\ReportFlagService;
use DrinkSafe\Shared\Seo\Seo;
use DrinkSafe\Venues\Exceptions\VenueNotFoundException;
use DrinkSafe\Venues\Models\Venue;
use DrinkSafe\Venues\Requests\AdminUpdateVenueRequest;
use DrinkSafe\Venues\Requests\AdminVenueSearchRequest;
use DrinkSafe\Venues\Resources\VenueResource;
use DrinkSafe\Venues\Services\VenueService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * AdminVenueController
 *
 * The admin's venues: a list of every venue, published or not, and a page
 * for each one with its reports, an edit form, and publish and unpublish.
 * There is no delete. Access is limited to admins by the admin route group.
 */
final class AdminVenueController extends Controller
{
    /**
     * Create a new AdminVenueController instance.
     */
    public function __construct(
        private readonly VenueService $venueService,
        private readonly ReportFlagService $reportFlagService,
    ) {}

    /**
     * Show a page of venues matching the filters, by name.
     *
     * A page past the end, left behind when an unpublish empties the last
     * page of a filtered list, redirects to the last page that still has venues.
     *
     * @param  AdminVenueSearchRequest  $request  The validated filters
     * @return Response|RedirectResponse Inertia response for the venue list
     */
    public function index(AdminVenueSearchRequest $request): Response|RedirectResponse
    {
        $venues = $this->venueService->searchVenues($request->filters());

        if ($venues->currentPage() > $venues->lastPage()) {
            return redirect()->route('admin.venues.index', [...$request->filters(), 'page' => $venues->lastPage()]);
        }

        return Inertia::render('admin/Venues', [
            'seo' => Seo::default()->withTitle('Venues - Drink Safe')->noindex()->toArray(),
            'venues' => $venues->getCollection()->map(fn (Venue $venue): array => [
                ...(new VenueResource($venue))->resolve(),
                'published_reports_count' => $venue->published_reports_count,
                'pending_reports_count' => $venue->pending_reports_count,
            ])->all(),
            'total' => $venues->total(),
            'pagination' => [
                'current_page' => $venues->currentPage(),
                'last_page' => $venues->lastPage(),
            ],
            'filters' => $request->filters(),
        ]);
    }

    /**
     * Show a venue with its details, a map, and a page of its reports in every status.
     *
     * @param  string  $uuid  The venue UUID
     * @return Response Inertia response for the venue page
     */
    public function show(string $uuid): Response
    {
        $venue = $this->venueService->getVenueForAdmin($uuid);
        $reports = $this->venueService->getVenueReportsForAdmin($venue);

        $this->reportFlagService->loadVenueCounts($reports->getCollection());

        return Inertia::render('admin/VenueShow', [
            'seo' => Seo::default()->withTitle(sprintf('%s - Drink Safe', $venue->name))->noindex()->toArray(),
            'venue' => (new VenueResource($venue))->resolve(),
            'reports' => $reports->getCollection()->map(fn (Report $report): array => [
                ...(new ReportResource($report))->resolve(),
                'would_empty_venue' => $this->reportFlagService->wouldEmptyVenue($report),
            ])->all(),
            'total' => $reports->total(),
            'pagination' => [
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
            ],
        ]);
    }

    /**
     * Save the admin's edit of a venue. The slug stays as it is.
     *
     * @param  AdminUpdateVenueRequest  $request  The validated venue details
     * @param  string  $uuid  The venue UUID
     * @return RedirectResponse Back to the venue page
     */
    public function update(AdminUpdateVenueRequest $request, string $uuid): RedirectResponse
    {
        try {
            $this->venueService->updateVenue($uuid, $request->validated());
        } catch (VenueNotFoundException) {
            abort(HttpResponse::HTTP_NOT_FOUND);
        }

        return redirect()->route('admin.venues.show', $uuid)->with('success', 'Venue saved.');
    }

    /**
     * Put a venue back on the site. Its reports keep their status.
     *
     * @param  string  $uuid  The venue UUID
     * @return RedirectResponse Back to the page the admin came from
     */
    public function publish(string $uuid): RedirectResponse
    {
        $this->venueService->publishVenue($uuid);

        return redirect()->to(url()->previous(route('admin.venues.show', $uuid)))->with('success', 'Venue published.');
    }

    /**
     * Take a venue and all its reports off the site.
     *
     * @param  string  $uuid  The venue UUID
     * @return RedirectResponse Back to the page the admin came from
     */
    public function unpublish(string $uuid): RedirectResponse
    {
        $this->venueService->unpublishVenue($uuid);

        return redirect()->to(url()->previous(route('admin.venues.show', $uuid)))->with('success', 'Venue unpublished.');
    }
}
